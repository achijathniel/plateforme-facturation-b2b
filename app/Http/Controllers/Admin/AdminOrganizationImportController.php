<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ImportOrganizationsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportOrganizationsRequest;
use App\Services\Imports\Contracts\OrganizationTemplateGeneratorInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AdminOrganizationImportController extends Controller
{
    /**
     * Affiche l'interface d'importation Excel / CSV d'entreprises.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Organizations/Import', [
            'importReport' => session('import_report'),
        ]);
    }

    /**
     * Télécharge un modèle Excel (.xlsx) pré-formaté pour l'importation.
     */
    public function downloadTemplate(OrganizationTemplateGeneratorInterface $generator): BinaryFileResponse
    {
        $filePath = $generator->generate();

        return response()
            ->download(
                $filePath,
                'modele-import-entreprises.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * Traite le fichier envoyé et retourne le rapport d'importation détaillé.
     */
    public function store(ImportOrganizationsRequest $request, ImportOrganizationsAction $action): RedirectResponse
    {
        $result = $action->execute($request->uploadedFile(), $request->importMode());

        $redirect = redirect()
            ->route('admin.organizations.import.create')
            ->with('import_report', $result->toArray());

        if ($result->aborted) {
            return $redirect->with(
                'error',
                'Importation annulée : veuillez corriger les erreurs signalées dans le rapport ci-dessous.'
            );
        }

        if ($result->createdCount > 0 && $result->errorCount === 0) {
            return $redirect->with(
                'success',
                "{$result->createdCount} entreprise(s) importée(s) avec succès."
            );
        }

        if ($result->createdCount > 0) {
            return $redirect->with(
                'warning',
                "{$result->createdCount} entreprise(s) importée(s), mais {$result->errorCount} ligne(s) ont été ignorées."
            );
        }

        return $redirect->with(
            'error',
            'Aucune entreprise n\'a pu être importée. Vérifiez les motifs ci-dessous.'
        );
    }
}
