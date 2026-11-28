<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class FrontendArchitectureTest extends TestCase
{
    /**
     * Vérifie que le fichier orphelin app.js n'est jamais réintroduit.
     */
    public function test_legacy_app_js_does_not_exist(): void
    {
        $this->assertFileDoesNotExist(
            resource_path('js/app.js'),
            "Le fichier obsolète 'resources/js/app.js' ne doit pas être recréé. Seul 'app.jsx' fait foi."
        );
    }

    /**
     * Vérifie que les formulaires de factures réutilisent le composant InvoiceForm.
     */
    public function test_invoice_create_and_edit_reuse_invoice_form_component(): void
    {
        $createPath = resource_path('js/Pages/Portal/Invoices/Create.jsx');
        $editPath = resource_path('js/Pages/Portal/Invoices/Edit.jsx');

        $this->assertFileExists($createPath);
        $this->assertFileExists($editPath);

        $createContent = file_get_contents($createPath);
        $editContent = file_get_contents($editPath);

        $this->assertMatchesRegularExpression(
            '/<InvoiceForm\b/',
            $createContent,
            'Create.jsx doit instancier le composant JSX <InvoiceForm /> au lieu de recréer le formulaire.'
        );

        $this->assertMatchesRegularExpression(
            '/<InvoiceForm\b/',
            $editContent,
            'Edit.jsx doit instancier le composant JSX <InvoiceForm /> au lieu de recréer le formulaire.'
        );

        // Garde-fou sur la taille des conteneurs pour empêcher la réintroduction de 300 lignes dupliquées
        $createLines = count(explode("\n", $createContent));
        $editLines = count(explode("\n", $editContent));

        $this->assertLessThan(
            60,
            $createLines,
            "Create.jsx est devenu trop verbeux ({$createLines} lignes). Le code doit être factorisé dans InvoiceForm."
        );

        $this->assertLessThan(
            60,
            $editLines,
            "Edit.jsx est devenu trop verbeux ({$editLines} lignes). Le code doit être factorisé dans InvoiceForm."
        );
    }

    /**
     * Vérifie que les écrans d'authentification réutilisent le composant LoginForm.
     */
    public function test_auth_login_pages_reuse_login_form_component(): void
    {
        $adminLoginPath = resource_path('js/Pages/Admin/Auth/Login.jsx');
        $portalLoginPath = resource_path('js/Pages/Portal/Auth/Login.jsx');

        $this->assertFileExists($adminLoginPath);
        $this->assertFileExists($portalLoginPath);

        $adminLogin = file_get_contents($adminLoginPath);
        $portalLogin = file_get_contents($portalLoginPath);

        $this->assertMatchesRegularExpression(
            '/<LoginForm\b/',
            $adminLogin,
            'Admin Login.jsx doit instancier le composant JSX factorisé <LoginForm />.'
        );

        $this->assertMatchesRegularExpression(
            '/<LoginForm\b/',
            $portalLogin,
            'Portal Login.jsx doit instancier le composant JSX factorisé <LoginForm />.'
        );
    }

    /**
     * Vérifie que les Layouts réutilisent le composant UserControls.
     */
    public function test_layouts_reuse_user_controls_component(): void
    {
        $adminLayoutPath = resource_path('js/Layouts/AdminLayout.jsx');
        $portalLayoutPath = resource_path('js/Layouts/PortalLayout.jsx');

        $this->assertFileExists($adminLayoutPath);
        $this->assertFileExists($portalLayoutPath);

        $adminLayout = file_get_contents($adminLayoutPath);
        $portalLayout = file_get_contents($portalLayoutPath);

        $this->assertMatchesRegularExpression(
            '/<UserControls\b/',
            $adminLayout,
            'AdminLayout doit instancier le composant JSX <UserControls />.'
        );

        $this->assertMatchesRegularExpression(
            '/<UserControls\b/',
            $portalLayout,
            'PortalLayout doit instancier le composant JSX <UserControls />.'
        );
    }

    /**
     * Vérifie que AdminLayout et PortalLayout réutilisent le composant partagé AppSidebarLayout.
     */
    public function test_layouts_reuse_app_sidebar_layout_component(): void
    {
        $sidebarPath = resource_path('js/Components/Layout/AppSidebarLayout.jsx');
        $adminLayoutPath = resource_path('js/Layouts/AdminLayout.jsx');
        $portalLayoutPath = resource_path('js/Layouts/PortalLayout.jsx');

        $this->assertFileExists($sidebarPath);
        $this->assertFileExists($adminLayoutPath);
        $this->assertFileExists($portalLayoutPath);

        $adminLayout = (string) file_get_contents($adminLayoutPath);
        $portalLayout = (string) file_get_contents($portalLayoutPath);

        $this->assertMatchesRegularExpression(
            '/<AppSidebarLayout\b/',
            $adminLayout,
            'AdminLayout doit réutiliser le composant partagé <AppSidebarLayout />.'
        );

        $this->assertMatchesRegularExpression(
            '/<AppSidebarLayout\b/',
            $portalLayout,
            'PortalLayout doit réutiliser le composant partagé <AppSidebarLayout />.'
        );
    }

    /**
     * Vérifie que les tableaux de bord Admin et Portail sont des Skinny Pages
     * réutilisant DashboardPageView et les composants analytiques partagés.
     */
    public function test_dashboards_reuse_shared_analytics_components(): void
    {
        $adminDashboardPath = resource_path('js/Pages/Admin/Dashboard.jsx');
        $portalDashboardPath = resource_path('js/Pages/Portal/Dashboard.jsx');
        $sharedViewPath = resource_path('js/Components/Dashboard/DashboardPageView.jsx');

        $this->assertFileExists($sharedViewPath);

        $adminDashboard = (string) file_get_contents($adminDashboardPath);
        $portalDashboard = (string) file_get_contents($portalDashboardPath);
        $sharedView = (string) file_get_contents($sharedViewPath);

        $this->assertMatchesRegularExpression('/<DashboardPageView\b/', $adminDashboard);
        $this->assertMatchesRegularExpression('/<DashboardPageView\b/', $portalDashboard);
        $this->assertLessThan(75, count(explode("\n", $adminDashboard)));
        $this->assertLessThan(75, count(explode("\n", $portalDashboard)));

        $sharedComponents = [
            'DashboardFilterBar',
            'RevenueTrendChart',
            'StatusDonutChart',
            'TopEntitiesChart',
        ];

        foreach ($sharedComponents as $component) {
            $this->assertFileExists(resource_path("js/Components/Dashboard/{$component}.jsx"));
            $this->assertMatchesRegularExpression(
                "/<{$component}\\b/",
                $sharedView,
                "DashboardPageView.jsx doit réutiliser <{$component} />."
            );
        }
    }

    /**
     * Vérifie que le CSS reste modulaire et n'est pas réécrit sous forme de monolithe.
     */
    public function test_css_remains_modular_via_imports(): void
    {
        $appCssPath = resource_path('css/app.css');
        $this->assertFileExists($appCssPath);

        $appCss = file_get_contents($appCssPath);

        $this->assertStringContainsString(
            "@import './modules/variables.css';",
            $appCss,
            'app.css doit importer ses modules et ne pas contenir de styles en vrac.'
        );

        $this->assertLessThan(
            50,
            count(explode("\n", $appCss)),
            "app.css ne doit être qu'un point d'entrée d'imports modulaires."
        );
    }

    /**
     * Vérifie que les pages CRUD/Import d'entreprises restent des Skinny Pages (< 60 lignes)
     * et réutilisent leurs composants dédiés.
     */
    public function test_admin_organization_pages_are_skinny_and_reuse_shared_components(): void
    {
        $checks = [
            ['path' => resource_path('js/Pages/Admin/Organizations/Create.jsx'), 'tag' => '/<OrganizationForm\b/'],
            ['path' => resource_path('js/Pages/Admin/Organizations/Edit.jsx'), 'tag' => '/<OrganizationForm\b/'],
            ['path' => resource_path('js/Pages/Admin/Organizations/Import.jsx'), 'tag' => '/<OrganizationImportForm\b/'],
        ];

        foreach ($checks as $check) {
            $this->assertFileExists($check['path']);
            $content = (string) file_get_contents($check['path']);
            $this->assertMatchesRegularExpression($check['tag'], $content);

            $lines = count(explode("\n", $content));
            $this->assertLessThan(
                60,
                $lines,
                "{$check['path']} est trop verbeux ({$lines} lignes). La logique doit rester dans le composant partagé."
            );
        }
    }

    /**
     * Vérifie qu'aucun fichier JS/JSX n'importe sweetalert2 statiquement afin de préserver le code-splitting.
     */
    public function test_sweetalert2_is_never_statically_imported(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path('js'))
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array($file->getExtension(), ['js', 'jsx'], true)) {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*import\s+.*from\s+[\'"]sweetalert2[\'"]/m',
                $content,
                "Le fichier {$file->getPathname()} doit charger sweetalert2 dynamiquement via confirmAction() ou import()."
            );
        }
    }

    /**
     * Vérifie que les modules CSS ne référencent pas de custom properties inexistantes dans variables.css.
     */
    public function test_css_modules_only_reference_defined_custom_properties(): void
    {
        $variablesCss = (string) file_get_contents(resource_path('css/modules/variables.css'));
        preg_match_all('/(--[a-zA-Z0-9-]+)\s*:/', $variablesCss, $definedMatches);
        $definedVars = array_flip($definedMatches[1]);

        $cssFiles = glob(resource_path('css/modules/*.css')) ?: [];
        $this->assertNotEmpty($cssFiles);

        foreach ($cssFiles as $cssFile) {
            $content = (string) file_get_contents($cssFile);
            preg_match_all('/var\(\s*(--[a-zA-Z0-9-]+)\s*\)/', $content, $usedMatches);

            foreach ($usedMatches[1] as $varName) {
                $this->assertArrayHasKey(
                    $varName,
                    $definedVars,
                    "La variable CSS {$varName} utilisée dans ".basename($cssFile).' n\'est pas définie dans variables.css.'
                );
            }
        }
    }
}
