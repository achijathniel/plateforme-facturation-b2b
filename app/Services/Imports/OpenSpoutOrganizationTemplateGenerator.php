<?php

declare(strict_types=1);

namespace App\Services\Imports;

use App\Services\Imports\Contracts\OrganizationTemplateGeneratorInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

/**
 * Génère un fichier modèle Excel (.xlsx) contenant les en-têtes officiels et une ligne d'exemple.
 */
final class OpenSpoutOrganizationTemplateGenerator implements OrganizationTemplateGeneratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function generate(): string
    {
        $filePath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'dealtoo_org_tpl_'
            .bin2hex(random_bytes(16))
            .'.xlsx';

        $writer = new Writer;

        try {
            $writer->openToFile($filePath);

            $writer->addRow(Row::fromValues([
                'raison_sociale',
                'nif',
                'email',
                'telephone',
                'adresse',
                'actif',
            ]));

            $writer->addRow(Row::fromValues([
                'Société Exemple Côte d\'Ivoire SA',
                'CI-ABJ-2026-B-00001',
                'facturation@exemple-ci.com',
                '+225 27 20 00 00 00',
                'Plateau, Rue du Commerce, Abidjan',
                'oui',
            ]));

            $writer->close();

            return $filePath;
        } catch (Throwable $exception) {
            try {
                $writer->close();
            } catch (Throwable) {
                // Ignore les erreurs secondaires de fermeture
            }

            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            throw $exception;
        }
    }
}
