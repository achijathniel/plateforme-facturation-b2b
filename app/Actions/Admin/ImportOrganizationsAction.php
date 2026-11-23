<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\CreateOrganizationDTO;
use App\DTOs\Admin\ImportOrganizationRowDTO;
use App\DTOs\Admin\ImportOrganizationsResultDTO;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Services\Imports\Contracts\OrganizationSpreadsheetReaderInterface;
use App\Services\Imports\InvalidSpreadsheetException;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class ImportOrganizationsAction
{
    public const MAX_ROWS = 1000;

    private const REQUIRED_COLUMNS = ['name', 'tax_number', 'email'];

    private const COLUMN_LABELS = [
        'name' => 'Raison sociale (raison_sociale / name)',
        'tax_number' => 'Numéro fiscal (nif / tax_number)',
        'email' => 'Adresse email (email)',
    ];

    public function __construct(
        private readonly OrganizationSpreadsheetReaderInterface $reader,
        private readonly OrganizationRepositoryInterface $repository,
    ) {}

    /**
     * Lit, valide et importe un fichier Excel/CSV d'entreprises avec détection des doublons.
     */
    public function execute(UploadedFile $file, string $mode = 'skip_errors'): ImportOrganizationsResultDTO
    {
        try {
            $parsed = $this->readAndValidateStructure($file);
        } catch (InvalidSpreadsheetException $exception) {
            return $this->abortedResult($mode, 0, [[
                'row' => 1,
                'name' => null,
                'messages' => [$exception->getMessage()],
            ]]);
        }

        if ($parsed instanceof ImportOrganizationsResultDTO) {
            return $parsed;
        }

        return DB::transaction(function () use ($parsed, $mode): ImportOrganizationsResultDTO {
            $analysis = $this->analyzeRows($parsed['rows']);
            $totalRows = count($parsed['rows']);
            $errorCount = count($analysis['errors']);

            if ($mode === 'strict' && $errorCount > 0) {
                return $this->abortedResult($mode, $totalRows, $analysis['errors']);
            }

            return $this->persistValidRows($analysis['valid_rows'], $analysis['errors'], $totalRows, $mode);
        });
    }

    /**
     * Lit le fichier et contrôle les limites de lignes, colonnes requises et présence de données.
     *
     * @return array{headers: array<int, string>, rows: array<int, array{row_number: int, data: array<string, mixed>}>}|ImportOrganizationsResultDTO
     */
    private function readAndValidateStructure(UploadedFile $file): array|ImportOrganizationsResultDTO
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'xlsx');
        $parsed = $this->reader->read($file->getPathname(), $extension, self::MAX_ROWS);

        if ($parsed['exceeded_limit']) {
            return $this->abortedResult('strict', 0, [[
                'row' => self::MAX_ROWS + 2,
                'name' => null,
                'messages' => ['Le fichier dépasse la limite maximale de '.self::MAX_ROWS.' entreprises par import.'],
            ]]);
        }

        $missingColumns = array_diff(self::REQUIRED_COLUMNS, $parsed['headers']);
        if ($missingColumns !== []) {
            $labels = array_map(
                static fn (string $col): string => self::COLUMN_LABELS[$col] ?? $col,
                $missingColumns
            );

            return $this->abortedResult('strict', 0, [[
                'row' => 1,
                'name' => null,
                'messages' => ['Colonnes obligatoires manquantes dans l\'en-tête : '.implode(', ', $labels).'.'],
            ]]);
        }

        if ($parsed['rows'] === []) {
            return $this->abortedResult('strict', 0, [[
                'row' => 2,
                'name' => null,
                'messages' => ['Aucune ligne d\'entreprise à importer n\'a été trouvée dans ce fichier.'],
            ]]);
        }

        return $parsed;
    }

    /**
     * Normalise, valide et déduplique chaque ligne face à la base de données et au fichier.
     *
     * @param  array<int, array{row_number: int, data: array<string, mixed>}>  $rawRows
     * @return array{
     *     valid_rows: array<int, ImportOrganizationRowDTO>,
     *     errors: array<int, array{row: int, name: ?string, messages: array<int, string>}>
     * }
     */
    private function analyzeRows(array $rawRows): array
    {
        $prepared = array_map(
            fn (array $entry): array => [
                'row_number' => $entry['row_number'],
                'normalized' => $this->normalizeRow($entry['data']),
            ],
            $rawRows
        );

        $candidateEmails = [];
        $candidateTaxes = [];

        foreach ($prepared as $item) {
            if (is_string($item['normalized']['email'])) {
                $candidateEmails[] = $item['normalized']['email'];
            }
            if (is_string($item['normalized']['tax_number'])) {
                $candidateTaxes[] = $item['normalized']['tax_number'];
            }
        }

        $existing = $this->repository->findExistingIdentifiers($candidateEmails, $candidateTaxes);
        $seenEmails = [];
        $seenTaxNumbers = [];
        $validRows = [];
        $errors = [];

        foreach ($prepared as $item) {
            $rowNumber = $item['row_number'];
            $normalized = $item['normalized'];
            $rowErrors = $this->validateNormalizedRow($normalized);
            $rowErrors = [
                ...$rowErrors,
                ...$this->detectDuplicates($normalized, $rowNumber, $existing, $seenEmails, $seenTaxNumbers),
            ];

            if ($rowErrors !== []) {
                $errors[] = [
                    'row' => $rowNumber,
                    'name' => $normalized['name'],
                    'messages' => $rowErrors,
                ];

                continue;
            }

            $seenEmails[mb_strtolower((string) $normalized['email'])] = $rowNumber;
            $seenTaxNumbers[mb_strtolower((string) $normalized['tax_number'])] = $rowNumber;

            $validRows[] = new ImportOrganizationRowDTO(
                rowNumber: $rowNumber,
                name: (string) $normalized['name'],
                taxNumber: (string) $normalized['tax_number'],
                email: (string) $normalized['email'],
                phone: $normalized['phone'],
                address: $normalized['address'],
                isActive: (bool) $normalized['is_active_bool'],
            );
        }

        return [
            'valid_rows' => $validRows,
            'errors' => $errors,
        ];
    }

    /**
     * Vérifie l'unicité de l'email et du NIF contre la base existante et les lignes déjà acceptées.
     *
     * @param  array<string, mixed>  $normalized
     * @param  array{emails: array<string, true>, tax_numbers: array<string, true>}  $existing
     * @param  array<string, int>  $seenEmails
     * @param  array<string, int>  $seenTaxNumbers
     * @return array<int, string>
     */
    private function detectDuplicates(
        array $normalized,
        int $rowNumber,
        array $existing,
        array $seenEmails,
        array $seenTaxNumbers
    ): array {
        $messages = [];

        if (is_string($normalized['email']) && $normalized['email'] !== '') {
            $emailKey = mb_strtolower($normalized['email']);
            if (isset($existing['emails'][$emailKey])) {
                $messages[] = "L'email « {$normalized['email']} » est déjà enregistré en base.";
            } elseif (isset($seenEmails[$emailKey])) {
                $messages[] = "L'email « {$normalized['email']} » est en doublon dans le fichier (ligne {$seenEmails[$emailKey]}).";
            }
        }

        if (is_string($normalized['tax_number']) && $normalized['tax_number'] !== '') {
            $taxKey = mb_strtolower($normalized['tax_number']);
            if (isset($existing['tax_numbers'][$taxKey])) {
                $messages[] = "Le numéro fiscal « {$normalized['tax_number']} » est déjà enregistré en base.";
            } elseif (isset($seenTaxNumbers[$taxKey])) {
                $messages[] = "Le numéro fiscal « {$normalized['tax_number']} » est en doublon dans le fichier (ligne {$seenTaxNumbers[$taxKey]}).";
            }
        }

        return $messages;
    }

    /**
     * Persiste les lignes valides en base, invalide le cache et gère d'éventuels conflits concurrents.
     *
     * @param  array<int, ImportOrganizationRowDTO>  $validRows
     * @param  array<int, array{row: int, name: ?string, messages: array<int, string>}>  $errors
     */
    private function persistValidRows(array $validRows, array $errors, int $totalRows, string $mode): ImportOrganizationsResultDTO
    {
        $createdCount = 0;
        $createdNames = [];

        if ($validRows !== []) {
            $createDTOs = array_map(
                static fn (ImportOrganizationRowDTO $row): CreateOrganizationDTO => $row->toCreateDTO(),
                $validRows
            );

            try {
                $createdCount = $this->repository->createMany($createDTOs);
            } catch (UniqueConstraintViolationException) {
                return $this->abortedResult($mode, $totalRows, [[
                    'row' => 1,
                    'name' => null,
                    'messages' => ['Un conflit d\'unicité concurrent est survenu pendant l\'import. Veuillez relancer l\'opération.'],
                ]]);
            }

            $createdNames = array_map(
                static fn (ImportOrganizationRowDTO $row): string => $row->name,
                $validRows
            );

            Cache::forget(GetAdminDashboardStatsAction::CACHE_KEY);
        }

        $errorCount = count($errors);

        return new ImportOrganizationsResultDTO(
            totalRows: $totalRows,
            createdCount: $createdCount,
            skippedCount: $errorCount,
            errorCount: $errorCount,
            mode: $mode,
            aborted: false,
            errors: $errors,
            createdNames: $createdNames,
        );
    }

    /**
     * @param  array<int, array{row: int, name: ?string, messages: array<int, string>}>  $errors
     */
    private function abortedResult(string $mode, int $totalRows, array $errors): ImportOrganizationsResultDTO
    {
        return new ImportOrganizationsResultDTO(
            totalRows: $totalRows,
            createdCount: 0,
            skippedCount: $totalRows,
            errorCount: count($errors),
            mode: $mode,
            aborted: true,
            errors: $errors,
            createdNames: [],
        );
    }

    /**
     * Nettoie et prépare les champs d'une ligne brute issue du tableur.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeRow(array $data): array
    {
        [$isActiveValid, $isActiveBool] = $this->parseBoolean($data['is_active'] ?? null);

        return [
            'name' => $this->cleanString($data['name'] ?? null),
            'tax_number' => $this->cleanString($data['tax_number'] ?? null),
            'email' => $this->cleanString($data['email'] ?? null),
            'phone' => $this->cleanString($data['phone'] ?? null),
            'address' => $this->cleanString($data['address'] ?? null),
            'is_active_valid' => $isActiveValid,
            'is_active_bool' => $isActiveBool,
        ];
    }

    /**
     * Valide les contraintes métier et de sécurité (CWE-1236) d'une ligne normalisée.
     *
     * @param  array<string, mixed>  $normalized
     * @return array<int, string>
     */
    private function validateNormalizedRow(array $normalized): array
    {
        $validator = Validator::make($normalized, [
            'name' => ['required', 'string', 'max:255'],
            'tax_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s().-]{6,30}$/'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'La raison sociale est obligatoire.',
            'name.max' => 'La raison sociale ne doit pas dépasser 255 caractères.',
            'tax_number.required' => 'Le numéro fiscal (NIF) est obligatoire.',
            'tax_number.max' => 'Le numéro fiscal ne doit pas dépasser 50 caractères.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email est invalide.',
            'email.max' => 'L\'adresse email ne doit pas dépasser 255 caractères.',
            'phone.max' => 'Le téléphone ne doit pas dépasser 30 caractères.',
            'phone.regex' => 'Le format du numéro de téléphone est invalide.',
            'address.max' => 'L\'adresse ne doit pas dépasser 500 caractères.',
        ]);

        $messages = $validator->fails() ? $validator->errors()->all() : [];

        // Protection anti-injection de formule tableur (CWE-1236)
        foreach (['name', 'tax_number', 'email', 'address'] as $field) {
            if (is_string($normalized[$field]) && preg_match('/^[=+\-@\t\r]/', $normalized[$field]) === 1) {
                $messages[] = "Le champ « {$field} » ne peut pas commencer par un caractère de formule (=, +, -, @).";
            }
        }

        if (! $normalized['is_active_valid']) {
            $messages[] = 'La colonne actif/statut doit contenir oui/non, 1/0 ou true/false.';
        }

        return $messages;
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (! is_scalar($value)) {
            return null;
        }

        $str = (string) $value;
        if (! mb_check_encoding($str, 'UTF-8')) {
            $converted = mb_convert_encoding($str, 'UTF-8', 'Windows-1252,ISO-8859-1');
            $str = is_string($converted) ? $converted : mb_scrub($str, 'UTF-8');
        }

        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
        if ($cleaned === null) {
            $cleaned = preg_replace('/[\x00-\x1F\x7F]/', '', $str) ?? '';
        }

        $trimmed = trim($cleaned);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array{0: bool, 1: bool}
     */
    private function parseBoolean(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [true, true];
        }

        if (is_bool($value)) {
            return [true, $value];
        }

        if (is_int($value) || is_float($value)) {
            if ((int) $value === 1) {
                return [true, true];
            }
            if ((int) $value === 0) {
                return [true, false];
            }

            return [false, true];
        }

        if (! is_string($value)) {
            return [false, true];
        }

        $normalized = mb_strtolower(trim($value));
        if ($normalized === '') {
            return [true, true];
        }

        if (in_array($normalized, ['1', 'true', 'oui', 'yes', 'actif', 'active'], true)) {
            return [true, true];
        }

        if (in_array($normalized, ['0', 'false', 'non', 'no', 'inactif', 'inactive', 'suspendu', 'suspendue'], true)) {
            return [true, false];
        }

        return [false, true];
    }
}
