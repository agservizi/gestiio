<?php

namespace Tests\Feature\Controllers\Backend;

use App\Models\Agente;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/backend');

        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_user_redirected_from_lavoro()
    {
        $response = $this->get('/backend/lavoro');

        $response->assertRedirect('/login');
    }

    public function test_non_staff_user_cannot_access_dashboard()
    {
        $this->authenticatedUser();

        $response = $this->get('/backend');

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE-BASED ACCESS
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_access_dashboard()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $response->assertStatus(200);
    }

    public function test_agente_can_access_dashboard()
    {
        $user = $this->staffUser('agente');

        // Agente dashboard requires an Agente model relationship
        if (Schema::hasTable('agenti')) {
            Agente::firstOrCreate(
                ['user_id' => $user->id],
                ['portafoglio_servizi' => 100, 'portafoglio_spedizioni' => 100, 'portafoglio_visure' => 100]
            );
        }

        $response = $this->get('/backend/lavoro');

        $response->assertStatus(200);
    }

    public function test_supervisore_can_access_dashboard()
    {
        $this->staffUser('supervisore');

        $response = $this->get('/backend/lavoro');

        $response->assertStatus(200);
    }

    public function test_operatore_can_access_dashboard()
    {
        $this->staffUser('operatore');

        // operatore doesn't have admin/supervisore/agente permission
        // so show() should route to showAgente (default branch)
        $response = $this->get('/backend/lavoro');

        $response->assertStatus(200);
    }

    public function test_all_staff_roles_can_access_dashboard()
    {
        $roles = ['admin', 'agente', 'supervisore', 'operatore'];

        foreach ($roles as $role) {
            $user = $this->staffUser($role);

            // Agente dashboard requires Agente relationship
            if ($role === 'agente' && Schema::hasTable('agenti')) {
                Agente::firstOrCreate(
                    ['user_id' => $user->id],
                    ['portafoglio_servizi' => 100, 'portafoglio_spedizioni' => 100, 'portafoglio_visure' => 100]
                );
            }

            $response = $this->get('/backend/lavoro');

            $response->assertStatus(200, "Role '$role' should have access to dashboard");

            Auth()->logout();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function test_admin_dashboard_renders_admin_view()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $response->assertStatus(200);
        $response->assertViewIs('Backend.Dashboard.showAdmin');
    }

    public function test_admin_dashboard_has_required_view_data()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $response->assertViewHasAll([
            'titoloPagina',
            'mainMenu',
            'contratti',
            'servizi',
            'tikets',
            'conteggioTikets',
            'datiTortaEsiti',
            'elencoMesi',
            'mese',
            'filtroAnno',
            'filtroMese',
            'kpiDashboard',
            'alertDashboard',
            'controlRoomAdmin',
            'azioniRapide',
            'chatDashboard',
        ]);
    }

    public function test_admin_dashboard_kpi_has_required_keys()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $kpiDashboard = $response->viewData('kpiDashboard');

        $this->assertArrayHasKey('richieste_assistenza_totali', $kpiDashboard);
        $this->assertArrayHasKey('richieste_assistenza_oggi', $kpiDashboard);
        $this->assertArrayHasKey('clienti_assistenza_totali', $kpiDashboard);
        $this->assertArrayHasKey('ticket_aperti', $kpiDashboard);
    }

    public function test_admin_dashboard_alert_has_required_keys()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $alertDashboard = $response->viewData('alertDashboard');

        $this->assertArrayHasKey('richieste_senza_credenziali', $alertDashboard);
        $this->assertArrayHasKey('clienti_senza_contatti', $alertDashboard);
    }

    public function test_admin_dashboard_control_room_has_sections()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $controlRoom = $response->viewData('controlRoomAdmin');

        $this->assertArrayHasKey('code', $controlRoom);
        $this->assertArrayHasKey('alert', $controlRoom);
        $this->assertArrayHasKey('economico', $controlRoom);
        $this->assertArrayHasKey('audit', $controlRoom);
        $this->assertArrayHasKey('azioni', $controlRoom);
        $this->assertArrayHasKey('salute', $controlRoom);
    }

    public function test_admin_dashboard_chat_dashboard_has_required_keys()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $chatDashboard = $response->viewData('chatDashboard');

        $this->assertArrayHasKey('messaggi_non_letti', $chatDashboard);
        $this->assertArrayHasKey('thread_attive', $chatDashboard);
        $this->assertArrayHasKey('nuovi_messaggi_oggi', $chatDashboard);
    }

    public function test_admin_dashboard_filters_by_month()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro?mese=2026_01');

        $response->assertStatus(200);
        $response->assertViewHas('filtroAnno', '2026');
        $response->assertViewHas('filtroMese', '01');
    }

    public function test_admin_dashboard_defaults_to_current_month()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $response->assertViewHas('mese', now()->format('Y_m'));
    }

    /*
    |--------------------------------------------------------------------------
    | SUPERVISORE DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function test_supervisore_dashboard_renders_supervisore_view()
    {
        $this->staffUser('supervisore');

        $response = $this->get('/backend/lavoro');

        $response->assertStatus(200);
        $response->assertViewIs('Backend.Dashboard.showSupervisore');
    }

    public function test_supervisore_dashboard_has_kpi_data()
    {
        $this->staffUser('supervisore');

        $response = $this->get('/backend/lavoro');

        $response->assertViewHasAll([
            'kpiSupervisore',
            'alertSupervisore',
            'serviziAbilitati',
            'chatDashboard',
        ]);
    }

    public function test_supervisore_kpi_has_required_keys()
    {
        $this->staffUser('supervisore');

        $response = $this->get('/backend/lavoro');

        $kpi = $response->viewData('kpiSupervisore');

        $this->assertArrayHasKey('contratti_telefonia_mese', $kpi);
        $this->assertArrayHasKey('contratti_energia_mese', $kpi);
        $this->assertArrayHasKey('pratiche_caf_mese', $kpi);
        $this->assertArrayHasKey('ticket_aperti', $kpi);
        $this->assertArrayHasKey('pratiche_ferme', $kpi);
    }

    /*
    |--------------------------------------------------------------------------
    | AGENTE DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function test_agente_dashboard_renders_agente_view()
    {
        $user = $this->staffUser('agente');

        if (Schema::hasTable('agenti')) {
            Agente::firstOrCreate(
                ['user_id' => $user->id],
                ['portafoglio_servizi' => 100, 'portafoglio_spedizioni' => 100, 'portafoglio_visure' => 100]
            );
        }

        $response = $this->get('/backend/lavoro');

        $response->assertStatus(200);
        $response->assertViewIs('Backend.Dashboard.showAgente');
    }

    public function test_agente_dashboard_has_operational_data()
    {
        $user = $this->staffUser('agente');

        if (Schema::hasTable('agenti')) {
            Agente::firstOrCreate(
                ['user_id' => $user->id],
                ['portafoglio_servizi' => 100, 'portafoglio_spedizioni' => 100, 'portafoglio_visure' => 100]
            );
        }

        $response = $this->get('/backend/lavoro');

        $response->assertViewHasAll([
            'heroOperativo',
            'chatOperativa',
            'filtriGlobali',
            'ticketDaPrendereInCarico',
            'visureInAttesaDocumenti',
            'cafInAttesaDocumenti',
            'scadenzeOggi',
            'monitorOperativo',
            'timelineAttivita',
        ]);
    }

    public function test_agente_dashboard_accepts_period_filter()
    {
        $user = $this->staffUser('agente');

        if (Schema::hasTable('agenti')) {
            Agente::firstOrCreate(
                ['user_id' => $user->id],
                ['portafoglio_servizi' => 100, 'portafoglio_spedizioni' => 100, 'portafoglio_visure' => 100]
            );
        }

        $response = $this->get('/backend/lavoro?periodo=30d');

        $response->assertStatus(200);
    }

    public function test_agente_dashboard_accepts_priority_filter()
    {
        $user = $this->staffUser('agente');

        if (Schema::hasTable('agenti')) {
            Agente::firstOrCreate(
                ['user_id' => $user->id],
                ['portafoglio_servizi' => 100, 'portafoglio_spedizioni' => 100, 'portafoglio_visure' => 100]
            );
        }

        $response = $this->get('/backend/lavoro?priorita=alta');

        $response->assertStatus(200);
    }

    public function test_agente_dashboard_accepts_status_filter()
    {
        $user = $this->staffUser('agente');

        if (Schema::hasTable('agenti')) {
            Agente::firstOrCreate(
                ['user_id' => $user->id],
                ['portafoglio_servizi' => 100, 'portafoglio_spedizioni' => 100, 'portafoglio_visure' => 100]
            );
        }

        $response = $this->get('/backend/lavoro?stato=chiuso');

        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ACTION
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_bulk_action()
    {
        $response = $this->postJson('/backend/dashboard/bulk-action', [
            'azione' => 'open',
            'items' => [['type' => 'ticket', 'id' => 1]],
        ]);

        $response->assertStatus(401);
    }

    public function test_bulk_action_validates_required_fields()
    {
        $this->staffUser('admin');

        $response = $this->postJson('/backend/dashboard/bulk-action', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['azione', 'items']);
    }

    public function test_bulk_action_validates_azione_enum()
    {
        $this->staffUser('admin');

        $response = $this->postJson('/backend/dashboard/bulk-action', [
            'azione' => 'invalid',
            'items' => [['type' => 'ticket', 'id' => 1]],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('azione');
    }

    public function test_bulk_action_validates_item_type_enum()
    {
        $this->staffUser('admin');

        $response = $this->postJson('/backend/dashboard/bulk-action', [
            'azione' => 'open',
            'items' => [['type' => 'invalid', 'id' => 1]],
        ]);

        $response->assertStatus(422);
    }

    public function test_bulk_action_open_returns_redirect_url()
    {
        $this->staffUser('admin');

        $response = $this->postJson('/backend/dashboard/bulk-action', [
            'azione' => 'open',
            'items' => [['type' => 'ticket', 'id' => 1]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'redirect', 'message']);
    }

    public function test_bulk_action_complete_returns_processed_count()
    {
        $this->staffUser('admin');

        $response = $this->postJson('/backend/dashboard/bulk-action', [
            'azione' => 'complete',
            'items' => [['type' => 'ticket', 'id' => 99999]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'processed', 'message']);
    }

    /*
    |--------------------------------------------------------------------------
    | SALUTO DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function test_dashboard_title_contains_greeting()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $titolo = $response->viewData('titoloPagina');

        // The salutoDashboard() always includes one of these
        $hasGreeting = str_contains($titolo, 'Buongiorno')
            || str_contains($titolo, 'Buon pomeriggio')
            || str_contains($titolo, 'Buonasera');

        $this->assertTrue($hasGreeting, 'Dashboard title should contain a greeting based on time of day');
    }

    public function test_dashboard_main_menu_is_set()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/lavoro');

        $response->assertViewHas('mainMenu', 'dashboard');
    }
}
