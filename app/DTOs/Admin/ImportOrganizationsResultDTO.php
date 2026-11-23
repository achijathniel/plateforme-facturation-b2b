<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

/**
 * Data Transfer Object immutable encapsulant le rapport complet d'un import Excel/CSV d'entreprises.
 */
final readonly class ImportOrganizationsResultDTO
{
    /**
     * @param  array<int, array{row: int, name: ?string, messages: array<int, string>}>  $errors
     * @param  array<int, string>  $createdNames
     */
    public function __construct(
        public int $totalRows,
        public int $createdCount,
        public int $skippedCount,
        public int $errorCount,
        public string $mode,
        public bool $aborted,
        public array $errors = [],
        public array $createdNames = [],
    ) {}

    /**
     * Convertit le rapport en tableau sérialisable pour la session / Inertia.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'created_count' => $this->createdCount,
            'skipped_count' => $this->skippedCount,
            'error_count' => $this->errorCount,
            'mode' => $this->mode,
            'aborted' => $this->aborted,
            'errors' => $this->errors,
            'created_names' => $this->createdNames,
        ];
    }
}
