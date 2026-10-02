<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

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
        $editPath   = resource_path('js/Pages/Portal/Invoices/Edit.jsx');

        $this->assertFileExists($createPath);
        $this->assertFileExists($editPath);

        $createContent = file_get_contents($createPath);
        $editContent   = file_get_contents($editPath);

        $this->assertMatchesRegularExpression(
            '/<InvoiceForm\b/',
            $createContent,
            "Create.jsx doit instancier le composant JSX <InvoiceForm /> au lieu de recréer le formulaire."
        );

        $this->assertMatchesRegularExpression(
            '/<InvoiceForm\b/',
            $editContent,
            "Edit.jsx doit instancier le composant JSX <InvoiceForm /> au lieu de recréer le formulaire."
        );

        // Garde-fou sur la taille des conteneurs pour empêcher la réintroduction de 300 lignes dupliquées
        $createLines = count(explode("\n", $createContent));
        $editLines   = count(explode("\n", $editContent));

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
        $adminLoginPath  = resource_path('js/Pages/Admin/Auth/Login.jsx');
        $portalLoginPath = resource_path('js/Pages/Portal/Auth/Login.jsx');

        $this->assertFileExists($adminLoginPath);
        $this->assertFileExists($portalLoginPath);

        $adminLogin  = file_get_contents($adminLoginPath);
        $portalLogin = file_get_contents($portalLoginPath);

        $this->assertMatchesRegularExpression(
            '/<LoginForm\b/',
            $adminLogin,
            "Admin Login.jsx doit instancier le composant JSX factorisé <LoginForm />."
        );

        $this->assertMatchesRegularExpression(
            '/<LoginForm\b/',
            $portalLogin,
            "Portal Login.jsx doit instancier le composant JSX factorisé <LoginForm />."
        );
    }

    /**
     * Vérifie que les Layouts réutilisent le composant UserControls.
     */
    public function test_layouts_reuse_user_controls_component(): void
    {
        $adminLayoutPath  = resource_path('js/Layouts/AdminLayout.jsx');
        $portalLayoutPath = resource_path('js/Layouts/PortalLayout.jsx');

        $this->assertFileExists($adminLayoutPath);
        $this->assertFileExists($portalLayoutPath);

        $adminLayout  = file_get_contents($adminLayoutPath);
        $portalLayout = file_get_contents($portalLayoutPath);

        $this->assertMatchesRegularExpression(
            '/<UserControls\b/',
            $adminLayout,
            "AdminLayout doit instancier le composant JSX <UserControls />."
        );

        $this->assertMatchesRegularExpression(
            '/<UserControls\b/',
            $portalLayout,
            "PortalLayout doit instancier le composant JSX <UserControls />."
        );
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
            "app.css doit importer ses modules et ne pas contenir de styles en vrac."
        );

        $this->assertLessThan(
            50,
            count(explode("\n", $appCss)),
            "app.css ne doit être qu'un point d'entrée d'imports modulaires."
        );
    }
}
