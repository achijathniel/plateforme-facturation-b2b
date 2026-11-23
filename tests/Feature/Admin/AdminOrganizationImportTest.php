<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\GetAdminDashboardStatsAction;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

final class AdminOrganizationImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crée un fichier .xlsx temporaire à partir de lignes brutes et le retourne sous forme d'UploadedFile.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeXlsxUpload(array $rows, string $filename = 'entreprises.xlsx'): UploadedFile
    {
        $tmpBase = tempnam(sys_get_temp_dir(), 'test_org_xlsx_');
        if ($tmpBase !== false) {
            @unlink($tmpBase);
            $path = $tmpBase.'.xlsx';
        } else {
            $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('test_org_', true).'.xlsx';
        }

        $writer = new XlsxWriter;
        $writer->openToFile($path);
        foreach ($rows as $rowValues) {
            $writer->addRow(Row::fromValues($rowValues));
        }
        $writer->close();

        return new UploadedFile(
            $path,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_guest_cannot_access_import_endpoints(): void
    {
        $this->get(route('admin.organizations.import.create'))
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.organizations.import.template'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.organizations.import.store'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_accountant_is_forbidden_from_import_endpoints(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $this->actingAs($accountant)
            ->get(route('admin.organizations.import.create'))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->get(route('admin.organizations.import.template'))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('admin.organizations.import.store'))
            ->assertForbidden();
    }

    public function test_admin_can_view_import_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.organizations.import.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Organizations/Import')
        );
    }

    public function test_admin_can_download_valid_xlsx_template(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.organizations.import.template'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString(
            'modele-import-entreprises.xlsx',
            (string) $response->headers->get('content-disposition')
        );

        $file = $response->getFile();
        $reader = new XlsxReader;
        $reader->open($file->getPathname());

        $readRows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $readRows[] = $row->toArray();
            }
            break;
        }
        $reader->close();

        $this->assertCount(2, $readRows);
        $this->assertSame(['raison_sociale', 'nif', 'email', 'telephone', 'adresse', 'actif'], $readRows[0]);
    }

    public function test_admin_can_import_valid_xlsx_file_and_invalidate_dashboard_cache(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Cache::put(GetAdminDashboardStatsAction::CACHE_KEY, ['cached' => true], 300);

        $file = $this->makeXlsxUpload([
            ['raison_sociale', 'nif', 'email', 'telephone', 'adresse', 'actif'],
            ['Ivoiroise Telecom SA', 'CI-ABJ-2026-B-101', 'contact@ivoiroise.ci', '+225 07 01 02 03', 'Plateau, Abidjan', 'oui'],
            ['Agro Négoce SARL', 'CI-ABJ-2026-B-102', 'compta@agronegoce.ci', '+225 05 04 05 06', 'Marcory, Abidjan', 'non'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.organizations.import.store'), [
            'file' => $file,
            'mode' => 'skip_errors',
        ]);

        $response->assertRedirect(route('admin.organizations.import.create'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_report', function (array $report): bool {
            return $report['total_rows'] === 2
                && $report['created_count'] === 2
                && $report['error_count'] === 0
                && $report['aborted'] === false;
        });

        $this->assertFalse(Cache::has(GetAdminDashboardStatsAction::CACHE_KEY));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Ivoiroise Telecom SA',
            'tax_number' => 'CI-ABJ-2026-B-101',
            'email' => 'contact@ivoiroise.ci',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Agro Négoce SARL',
            'tax_number' => 'CI-ABJ-2026-B-102',
            'email' => 'compta@agronegoce.ci',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_import_french_semicolon_csv_file(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $csvContent = "\xEF\xBB\xBF"
            ."Raison sociale;NIF;Email;Téléphone;Adresse;Actif\n"
            ."Bâtiment Express CI;CI-ABJ-2026-B-201;info@batexpress.ci;+225 27 21 00 00;Cocody;1\n"
            ."Transports Lagunes;CI-ABJ-2026-B-202;factures@lagunes.ci;;Treichville;0\n";

        $file = UploadedFile::fake()->createWithContent('import_fr.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.organizations.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.organizations.import.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('organizations', [
            'name' => 'Bâtiment Express CI',
            'tax_number' => 'CI-ABJ-2026-B-201',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Transports Lagunes',
            'tax_number' => 'CI-ABJ-2026-B-202',
            'is_active' => false,
        ]);
    }

    public function test_skip_errors_mode_imports_valid_rows_and_reports_duplicates_and_invalid_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Organization::factory()->create([
            'name' => 'Déjà En Base SA',
            'tax_number' => 'CI-EXISTING-900',
            'email' => 'existant@entreprise.ci',
        ]);

        $file = $this->makeXlsxUpload([
            ['raison_sociale', 'nif', 'email', 'telephone', 'adresse', 'actif'],
            ['Valide Alpha SA', 'CI-NEW-001', 'alpha@new.ci', '', '', 'oui'],
            ['Doublon Base', 'CI-EXISTING-900', 'existant@entreprise.ci', '', '', 'oui'],
            ['Doublon Fichier', 'CI-NEW-001', 'ALPHA@new.ci', '', '', 'oui'],
            ['Email Invalide SA', 'CI-NEW-004', 'pas-un-email', '', '', 'oui'],
            ['=CMD|calc', 'CI-NEW-005', 'inj@new.ci', '', '', 'oui'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.organizations.import.store'), [
            'file' => $file,
            'mode' => 'skip_errors',
        ]);

        $response->assertRedirect(route('admin.organizations.import.create'));
        $response->assertSessionHas('warning');

        $report = session('import_report');
        $this->assertIsArray($report);
        $this->assertSame(5, $report['total_rows']);
        $this->assertSame(1, $report['created_count']);
        $this->assertSame(4, $report['error_count']);
        $this->assertFalse($report['aborted']);
        $this->assertSame(3, $report['errors'][0]['row']);
        $this->assertSame(4, $report['errors'][1]['row']);
        $this->assertSame(5, $report['errors'][2]['row']);
        $this->assertSame(6, $report['errors'][3]['row']);

        $this->assertDatabaseHas('organizations', ['tax_number' => 'CI-NEW-001']);
        $this->assertDatabaseMissing('organizations', ['tax_number' => 'CI-NEW-004']);
        $this->assertDatabaseMissing('organizations', ['tax_number' => 'CI-NEW-005']);
    }

    public function test_strict_mode_aborts_all_inserts_when_one_row_is_invalid(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $file = $this->makeXlsxUpload([
            ['raison_sociale', 'nif', 'email'],
            ['Entreprise Saine', 'CI-STRICT-001', 'saine@strict.ci'],
            ['Entreprise Cassée', 'CI-STRICT-002', 'email-invalide'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.organizations.import.store'), [
            'file' => $file,
            'mode' => 'strict',
        ]);

        $response->assertRedirect(route('admin.organizations.import.create'));
        $response->assertSessionHas('error');

        $report = session('import_report');
        $this->assertIsArray($report);
        $this->assertTrue($report['aborted']);
        $this->assertSame(0, $report['created_count']);
        $this->assertSame(1, $report['error_count']);

        $this->assertDatabaseMissing('organizations', ['tax_number' => 'CI-STRICT-001']);
    }

    public function test_upload_rejects_unsupported_file_extension(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('admin.organizations.import.store'), [
            'file' => $pdfFile,
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_upload_rejects_spoofed_text_file_renamed_as_xlsx(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tmpPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('spoof_', true).'.xlsx';
        file_put_contents($tmpPath, 'ceci est juste du texte brut');

        try {
            $fakeXlsx = new UploadedFile($tmpPath, 'faux.xlsx', null, null, true);

            $response = $this->actingAs($admin)->post(route('admin.organizations.import.store'), [
                'file' => $fakeXlsx,
            ]);

            $response->assertSessionHasErrors(['file']);
        } finally {
            @unlink($tmpPath);
        }
    }

    public function test_corrupted_zip_renamed_as_xlsx_aborts_gracefully_without_500(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $xlsxPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('corrupt_', true).'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($xlsxPath);
        $writer->addRow(Row::fromValues(['raison_sociale', 'nif', 'email']));
        $writer->close();

        // Corrompt le XML interne de la feuille tout en conservant une archive XLSX détectée comme telle
        $zip = new \ZipArchive;
        $zip->open($xlsxPath);
        $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet><unclosed');
        $zip->close();

        try {
            $corruptFile = new UploadedFile(
                $xlsxPath,
                'corrompu.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );

            $response = $this->actingAs($admin)->post(route('admin.organizations.import.store'), [
                'file' => $corruptFile,
            ]);

            $response->assertRedirect(route('admin.organizations.import.create'));
            $response->assertSessionHas('error');
            $report = session('import_report');
            $this->assertTrue($report['aborted']);
        } finally {
            @unlink($xlsxPath);
        }
    }

    public function test_admin_can_import_windows_1252_encoded_csv_and_block_dde_injections(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $utf8Csv = "Raison sociale;NIF;Email;Téléphone\n"
            ."Société Générale Côte d'Ivoire;CI-WIN-001;sgci@test.ci;+225 27 20 20 20\n"
            ."+cmd|' /C calc'!A0;CI-WIN-002;dde@test.ci;+225 01 02 03 04\n"
            ."Entreprise Tel Malveillant;CI-WIN-003;telbad@test.ci;=HYPERLINK(\"x\")\n";

        $cp1252Csv = mb_convert_encoding($utf8Csv, 'Windows-1252', 'UTF-8');
        $file = UploadedFile::fake()->createWithContent('excel_fr_cp1252.csv', $cp1252Csv);

        $response = $this->actingAs($admin)->post(route('admin.organizations.import.store'), [
            'file' => $file,
            'mode' => 'skip_errors',
        ]);

        $response->assertRedirect(route('admin.organizations.import.create'));
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('organizations', [
            'name' => "Société Générale Côte d'Ivoire",
            'tax_number' => 'CI-WIN-001',
        ]);
        $this->assertDatabaseMissing('organizations', ['tax_number' => 'CI-WIN-002']);
        $this->assertDatabaseMissing('organizations', ['tax_number' => 'CI-WIN-003']);
    }

    public function test_import_endpoint_is_rate_limited_to_five_requests_per_minute(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        for ($i = 1; $i <= 5; $i++) {
            $file = $this->makeXlsxUpload([
                ['raison_sociale', 'nif', 'email'],
                ["Société Rate {$i}", "CI-RL-00{$i}", "rate{$i}@test.ci"],
            ]);

            $this->actingAs($admin)
                ->post(route('admin.organizations.import.store'), ['file' => $file])
                ->assertRedirect();
        }

        $sixthFile = $this->makeXlsxUpload([
            ['raison_sociale', 'nif', 'email'],
            ['Société Rate 6', 'CI-RL-006', 'rate6@test.ci'],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.organizations.import.store'), ['file' => $sixthFile])
            ->assertStatus(429);
    }
}
