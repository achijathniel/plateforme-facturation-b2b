<?php

declare(strict_types=1);

namespace App\Services\Imports\Contracts;

interface OrganizationTemplateGeneratorInterface
{
    /**
     * Génère un fichier modèle Excel (.xlsx) temporaire prêt à être téléchargé et retourne son chemin absolu.
     */
    public function generate(): string;
}
