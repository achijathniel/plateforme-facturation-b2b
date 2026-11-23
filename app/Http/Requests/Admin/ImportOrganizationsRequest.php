<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

final class ImportOrganizationsRequest extends FormRequest
{
    /**
     * Correspondance stricte entre extension déclarée et types MIME réellement acceptés.
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_MIME_BY_EXTENSION = [
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ],
        'csv' => [
            'text/csv',
            'text/plain',
            'application/csv',
            'text/x-csv',
            'application/vnd.ms-excel',
        ],
    ];

    /**
     * Détermine si l'administrateur est autorisé à effectuer cet import.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Règles de validation strictes pour le fichier Excel / CSV et la stratégie de doublons.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'extensions:xlsx,csv', 'max:5120'],
            'mode' => ['sometimes', 'string', 'in:skip_errors,strict'],
        ];
    }

    /**
     * Vérifie la cohérence réelle entre l'extension et le contenu MIME détecté.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('file')) {
                return;
            }

            $file = $this->file('file');
            if (! $file instanceof UploadedFile) {
                return;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $detectedMime = strtolower((string) $file->getMimeType());
            $allowedMimes = self::ALLOWED_MIME_BY_EXTENSION[$extension] ?? [];

            if (! in_array($detectedMime, $allowedMimes, true)) {
                $validator->errors()->add(
                    'file',
                    'Le contenu du fichier ne correspond pas à son extension (.xlsx ou .csv valide requis).'
                );
            }
        });
    }

    /**
     * Messages d'erreur traduits en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Veuillez sélectionner un fichier Excel (.xlsx) ou CSV à importer.',
            'file.file' => 'Le document envoyé n\'est pas un fichier valide.',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx) ou CSV (.csv).',
            'file.extensions' => 'Seules les extensions .xlsx et .csv sont autorisées.',
            'file.max' => 'La taille maximale du fichier est de 5 Mo.',
            'mode.in' => 'Le mode d\'importation sélectionné est invalide.',
        ];
    }

    /**
     * Retourne le fichier validé de manière strictement typée.
     */
    public function uploadedFile(): UploadedFile
    {
        $file = $this->file('file');
        assert($file instanceof UploadedFile);

        return $file;
    }

    /**
     * Retourne la stratégie de gestion des erreurs demandée.
     */
    public function importMode(): string
    {
        return (string) $this->validated('mode', 'skip_errors');
    }
}
