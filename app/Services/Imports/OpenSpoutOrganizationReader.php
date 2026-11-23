<?php

declare(strict_types=1);

namespace App\Services\Imports;

use App\Services\Imports\Contracts\OrganizationSpreadsheetReaderInterface;
use Illuminate\Support\Str;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Adaptateur OpenSpout pour lire les fichiers Excel (.xlsx) et CSV en streaming.
 */
final class OpenSpoutOrganizationReader implements OrganizationSpreadsheetReaderInterface
{
    /**
     * Table d'alias FR / EN pour mapper les en-têtes utilisateur vers les champs canoniques.
     *
     * @var array<string, string>
     */
    private const HEADER_ALIASES = [
        'name' => 'name',
        'nom' => 'name',
        'raison_sociale' => 'name',
        'entreprise' => 'name',
        'societe' => 'name',
        'tax_number' => 'tax_number',
        'nif' => 'tax_number',
        'rccm' => 'tax_number',
        'numero_fiscal' => 'tax_number',
        'compte_contribuable' => 'tax_number',
        'ncc' => 'tax_number',
        'email' => 'email',
        'e_mail' => 'email',
        'mail' => 'email',
        'adresse_email' => 'email',
        'courriel' => 'email',
        'phone' => 'phone',
        'telephone' => 'phone',
        'tel' => 'phone',
        'contact' => 'phone',
        'address' => 'address',
        'adresse' => 'address',
        'siege' => 'address',
        'localisation' => 'address',
        'is_active' => 'is_active',
        'actif' => 'is_active',
        'active' => 'is_active',
        'statut' => 'is_active',
        'status' => 'is_active',
    ];

    /**
     * {@inheritDoc}
     *
     * @throws InvalidSpreadsheetException
     */
    public function read(string $filePath, string $extension, int $maxRows = 1000): array
    {
        $ext = strtolower($extension);
        $tempUtf8Path = null;
        $targetPath = $filePath;

        if ($ext === 'csv') {
            [$targetPath, $tempUtf8Path] = $this->ensureUtf8CsvFile($filePath);
        }

        $reader = $this->createReader($targetPath, $ext);
        $headers = [];
        $rows = [];
        $exceededLimit = false;
        $headerParsed = false;

        try {
            $reader->open($targetPath);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                    if ($row->isEmpty()) {
                        continue;
                    }

                    $values = $row->toArray();

                    if (! $headerParsed) {
                        $headers = $this->mapHeaders($values);
                        $headerParsed = true;

                        continue;
                    }

                    if (count($rows) >= $maxRows) {
                        $exceededLimit = true;

                        break 2;
                    }

                    $rows[] = [
                        'row_number' => (int) $rowNumber,
                        'data' => $this->combineRow($headers, $values),
                    ];
                }

                // Seule la première feuille du classeur est exploitée pour l'import
                break;
            }
        } catch (OpenSpoutException $exception) {
            throw new InvalidSpreadsheetException(
                'Le fichier est illisible, corrompu ou chiffré. Veuillez utiliser un fichier .xlsx ou .csv valide.',
                0,
                $exception
            );
        } finally {
            try {
                $reader->close();
            } catch (\Throwable) {
                // Ignore les erreurs de fermeture sur flux non ouvert
            }

            if ($tempUtf8Path !== null && file_exists($tempUtf8Path)) {
                @unlink($tempUtf8Path);
            }
        }

        return [
            'headers' => array_values(array_filter(array_unique($headers))),
            'rows' => $rows,
            'exceeded_limit' => $exceededLimit,
        ];
    }

    /**
     * Convertit un fichier CSV Windows-1252 / ISO-8859-1 (export Excel FR) en UTF-8 si nécessaire.
     *
     * @return array{0: string, 1: ?string}
     */
    private function ensureUtf8CsvFile(string $filePath): array
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            throw new InvalidSpreadsheetException('Impossible de lire le fichier CSV fourni.');
        }

        if (mb_check_encoding($content, 'UTF-8')) {
            return [$filePath, null];
        }

        $converted = mb_convert_encoding($content, 'UTF-8', 'Windows-1252,ISO-8859-1');
        if (! is_string($converted) || ! mb_check_encoding($converted, 'UTF-8')) {
            throw new InvalidSpreadsheetException(
                'L\'encodage du fichier CSV n\'est pas supporté. Enregistrez-le au format CSV UTF-8.'
            );
        }

        $tempPath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'dealtoo_csv_utf8_'
            .bin2hex(random_bytes(16))
            .'.csv';

        if (@file_put_contents($tempPath, $converted) === false) {
            throw new InvalidSpreadsheetException('Impossible de préparer le fichier CSV pour la lecture.');
        }

        return [$tempPath, $tempPath];
    }

    /**
     * Instancie le lecteur OpenSpout adapté à l'extension et au séparateur CSV.
     */
    private function createReader(string $filePath, string $extension): ReaderInterface
    {
        if ($extension === 'csv') {
            $delimiter = $this->detectCsvDelimiter($filePath);

            return new CsvReader(new CsvOptions(FIELD_DELIMITER: $delimiter));
        }

        return new XlsxReader;
    }

    /**
     * Détecte automatiquement le séparateur ',' ou ';' sur la première ligne non vide.
     */
    private function detectCsvDelimiter(string $filePath): string
    {
        $handle = @fopen($filePath, 'r');
        if ($handle === false) {
            return ',';
        }

        try {
            while (($line = fgets($handle)) !== false) {
                if (trim($line) === '') {
                    continue;
                }

                // Retire les segments entre guillemets avant de compter les séparateurs
                $unquoted = preg_replace('/"[^"]*"/', '', $line) ?? $line;

                return substr_count($unquoted, ';') > substr_count($unquoted, ',') ? ';' : ',';
            }

            return ',';
        } finally {
            fclose($handle);
        }
    }

    /**
     * Normalise, mappe et vérifie l'absence de colonnes canoniques dupliquées.
     *
     * @param  array<int, mixed>  $rawHeaders
     * @return array<int, string>
     */
    private function mapHeaders(array $rawHeaders): array
    {
        $mapped = [];
        $seenCanonical = [];

        foreach ($rawHeaders as $index => $rawHeader) {
            $normalized = $this->normalizeHeader($rawHeader);
            if ($normalized === '') {
                $mapped[$index] = '';

                continue;
            }

            $canonical = self::HEADER_ALIASES[$normalized] ?? $normalized;
            if (isset(self::HEADER_ALIASES[$normalized]) && isset($seenCanonical[$canonical])) {
                throw new InvalidSpreadsheetException(
                    "En-tête en doublon détecté pour la colonne « {$canonical} ». Chaque colonne ne doit apparaître qu'une seule fois."
                );
            }

            $seenCanonical[$canonical] = true;
            $mapped[$index] = $canonical;
        }

        return $mapped;
    }

    /**
     * Nettoie un intitulé de colonne (BOM UTF-8, accents, casse, séparateurs).
     */
    private function normalizeHeader(mixed $header): string
    {
        $text = is_scalar($header) ? (string) $header : '';
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
        $ascii = Str::ascii(trim($text));
        $lower = mb_strtolower($ascii);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $lower) ?? '';

        return trim($slug, '_');
    }

    /**
     * Associe les cellules d'une ligne aux noms de colonnes normalisés.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, mixed>  $values
     * @return array<string, mixed>
     */
    private function combineRow(array $headers, array $values): array
    {
        $combined = [];

        foreach ($headers as $index => $columnName) {
            if ($columnName === '') {
                continue;
            }
            $combined[$columnName] = $values[$index] ?? null;
        }

        return $combined;
    }
}
