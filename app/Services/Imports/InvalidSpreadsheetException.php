<?php

declare(strict_types=1);

namespace App\Services\Imports;

use RuntimeException;

/**
 * Exception métier levée lorsqu'un fichier tableur est corrompu, illisible ou structurellement invalide.
 */
final class InvalidSpreadsheetException extends RuntimeException {}
