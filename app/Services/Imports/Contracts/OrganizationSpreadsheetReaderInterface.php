<?php

declare(strict_types=1);

namespace App\Services\Imports\Contracts;

interface OrganizationSpreadsheetReaderInterface
{
    /**
     * Lit un fichier tableur (.xlsx ou .csv) et extrait les en-têtes normalisés ainsi que les lignes indexées.
     *
     * @return array{
     *     headers: array<int, string>,
     *     rows: array<int, array{row_number: int, data: array<string, mixed>}>,
     *     exceeded_limit: bool
     * }
     */
    public function read(string $filePath, string $extension, int $maxRows = 1000): array;
}
