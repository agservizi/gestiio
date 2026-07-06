<?php

namespace Tests\Feature\Controllers\Backend;

use App\Models\AllegatoCafPatronato;
use App\Models\CafPatronato;
use App\Models\EsitoCafPatronato;
use App\Models\TipoCafPatronato;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CafPatronatoControllerTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION & AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/backend/caf-patronato');

        $response->assertRedirect('/login');
    }

    public function test_non_staff_user_cannot_access_index()
    {
        $this->authenticatedUser();

        $response = $this->get('/backend/caf-patronato');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_index()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato');

        $response->assertStatus(200);
    }

    public function test_agente_can_access_index()
    {
        $this->staffUser('agente');

        $response = $this->get('/backend/caf-patronato');

        $response->assertStatus(200);
    }

    public function test_supervisore_can_access_index()
    {
        $this->staffUser('supervisore');

        $response = $this->get('/backend/caf-patronato');

        $response->assertStatus(200);
    }

    public function test_operatore_can_access_index()
    {
        $this->staffUser('operatore');

        $response = $this->get('/backend/caf-patronato');

        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function test_index_renders_correct_view()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato');

        $response->assertStatus(200);
        $response->assertViewIs('Backend.CafPatronato.index');
    }

    public function test_index_passes_required_view_data()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato');

        $response->assertViewHasAll([
            'records',
            'titoloPagina',
            'orderBy',
            'ordinamenti',
            'puoModificare',
            'puoModificareEsito',
            'kpiInLavorazione',
            'kpiBloccate',
            'kpiInScadenza',
            'kpiConcluse',
            'praticheFermiCount',
        ]);
    }

    public function test_index_title_contains_nome_plurale()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato');

        $response->assertViewHas('titoloPagina', 'Elenco '.CafPatronato::NOME_PLURALE);
    }

    public function test_index_ajax_request_returns_json_with_html()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato', ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['html']);
    }

    public function test_index_accepts_order_by_parameter()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato?orderBy=nominativo');

        $response->assertStatus(200);
        $response->assertViewHas('orderBy', 'nominativo');
    }

    public function test_index_defaults_to_recente_ordering()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato');

        $response->assertViewHas('orderBy', 'recente');
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX FILTERS
    |--------------------------------------------------------------------------
    */

    public function test_index_filters_by_search_term()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato?cerca=RSSMRA');

        $response->assertStatus(200);
        $response->assertViewHas('conFiltro', true);
    }

    public function test_index_filters_by_month_and_year()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato?mese=6&anno=2026');

        $response->assertStatus(200);
        $response->assertViewHas('conFiltro', true);
    }

    public function test_index_filters_by_agente_id()
    {
        $user = $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato?agente_id='.$user->id);

        $response->assertStatus(200);
        $response->assertViewHas('conFiltro', true);
    }

    public function test_index_filters_solo_fermi()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato?solo_fermi=1&giorni_fermo=7');

        $response->assertStatus(200);
        $response->assertViewHas('conFiltro', true);
    }

    public function test_index_giorni_fermo_defaults_to_7()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato');

        $response->assertViewHas('giorniFermo', 7);
    }

    public function test_index_giorni_fermo_is_at_least_1()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato?giorni_fermo=0');

        $response->assertViewHas('giorniFermo', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function test_show_displays_existing_record()
    {
        $user = $this->staffUser('admin');

        $record = new CafPatronato;
        $record->nome = 'Mario';
        $record->cognome = 'Rossi';
        $record->codice_fiscale = 'RSSMRA80A01H501U';
        $record->data = now();
        $record->agente_id = $user->id;
        $record->esito_id = 'da-gestire';
        $record->save();

        $response = $this->get("/backend/caf-patronato/{$record->id}");

        $response->assertStatus(200);
        $response->assertViewIs('Backend.CafPatronato.show');
        $response->assertViewHas('record');
    }

    public function test_show_returns_404_for_nonexistent_record()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato/99999');

        $response->assertStatus(404);
    }

    public function test_show_passes_breadcrumbs()
    {
        $user = $this->staffUser('admin');

        $record = new CafPatronato;
        $record->nome = 'Test';
        $record->cognome = 'Show';
        $record->data = now();
        $record->agente_id = $user->id;
        $record->esito_id = 'da-gestire';
        $record->save();

        $response = $this->get("/backend/caf-patronato/{$record->id}");

        $response->assertViewHas('breadcrumbs');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE (validation)
    |--------------------------------------------------------------------------
    */

    public function test_store_requires_mandatory_fields()
    {
        $this->staffUser('agente');

        $response = $this->post('/backend/caf-patronato', []);

        $response->assertSessionHasErrors([
            'data',
            'agente_id',
            'nome',
            'cognome',
            'cellulare',
            'codice_fiscale',
        ]);
    }

    public function test_store_validates_email_format()
    {
        $this->staffUser('agente');

        $response = $this->post('/backend/caf-patronato', [
            'data' => now()->format('Y-m-d'),
            'agente_id' => Auth::id(),
            'nome' => 'Mario',
            'cognome' => 'Rossi',
            'cellulare' => '3331234567',
            'codice_fiscale' => 'RSSMRA80A01H501U',
            'email' => 'not-an-email',
            'tipo_servizio' => 1,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_max_length_fields()
    {
        $this->staffUser('agente');

        $response = $this->post('/backend/caf-patronato', [
            'data' => now()->format('Y-m-d'),
            'agente_id' => Auth::id(),
            'nome' => str_repeat('A', 256),
            'cognome' => str_repeat('B', 256),
            'cellulare' => '3331234567',
            'codice_fiscale' => 'RSSMRA80A01H501U',
            'tipo_servizio' => 1,
        ]);

        $response->assertSessionHasErrors(['nome', 'cognome']);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function test_edit_displays_existing_record()
    {
        $user = $this->staffUser('admin');

        $tipo = new TipoCafPatronato;
        $tipo->nome = 'ISEE';
        $tipo->save();

        $record = new CafPatronato;
        $record->nome = 'Mario';
        $record->cognome = 'Rossi';
        $record->data = now();
        $record->agente_id = $user->id;
        $record->esito_id = 'da-gestire';
        $record->tipo_caf_patronato_id = $tipo->id;
        $record->save();

        $response = $this->get("/backend/caf-patronato/{$record->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('Backend.CafPatronato.edit');
    }

    public function test_edit_returns_404_for_nonexistent_record()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato/99999/edit');

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function test_update_returns_404_for_nonexistent_record()
    {
        $this->staffUser('admin');

        $response = $this->put('/backend/caf-patronato/99999', [
            'data' => now()->format('Y-m-d'),
            'agente_id' => Auth::id(),
            'nome' => 'Updated',
            'cognome' => 'Name',
            'cellulare' => '3331234567',
            'codice_fiscale' => 'RSSMRA80A01H501U',
        ]);

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function test_destroy_deletes_existing_record()
    {
        $user = $this->staffUser('admin');

        $record = new CafPatronato;
        $record->nome = 'Delete';
        $record->cognome = 'Me';
        $record->data = now();
        $record->agente_id = $user->id;
        $record->esito_id = 'da-gestire';
        $record->save();

        $id = $record->id;

        $response = $this->delete("/backend/caf-patronato/{$id}");

        $response->assertJsonStructure(['success', 'redirect']);
        $this->assertDatabaseMissing('caf_patronato', ['id' => $id]);
    }

    public function test_destroy_returns_404_for_nonexistent_record()
    {
        $this->staffUser('admin');

        $response = $this->delete('/backend/caf-patronato/99999');

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | AGGIORNA STATO
    |--------------------------------------------------------------------------
    */

    public function test_aggiorna_stato_returns_404_for_nonexistent_record()
    {
        $this->staffUser('admin');

        $response = $this->post('/backend/caf-patronato-stato/99999', [
            'esito_id' => 'da-gestire',
        ]);

        $response->assertStatus(404);
    }

    public function test_aggiorna_stato_updates_esito()
    {
        $user = $this->staffUser('admin');

        $esito = new EsitoCafPatronato;
        $esito->id = 'completato';
        $esito->nome = 'Completato';
        $esito->esito_finale = 'ok';
        $esito->save();

        $record = new CafPatronato;
        $record->nome = 'Status';
        $record->cognome = 'Update';
        $record->data = now();
        $record->agente_id = $user->id;
        $record->esito_id = 'da-gestire';
        $record->save();

        $response = $this->post("/backend/caf-patronato-stato/{$record->id}", [
            'esito_id' => 'completato',
        ]);

        $response->assertJsonStructure(['success', 'id', 'html']);

        $record->refresh();
        $this->assertEquals('completato', $record->esito_id);
        $this->assertEquals('ok', $record->esito_finale);
    }

    /*
    |--------------------------------------------------------------------------
    | ALLEGATI ORFANI
    |--------------------------------------------------------------------------
    */

    public function test_allegati_orfani_requires_admin_permission()
    {
        $this->staffUser('agente');

        $response = $this->get('/backend/caf-patronato-allegati-orfani');

        $response->assertStatus(403);
    }

    public function test_allegati_orfani_is_accessible_by_admin()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato-allegati-orfani');

        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD ALLEGATO
    |--------------------------------------------------------------------------
    */

    public function test_download_allegato_returns_404_for_nonexistent_allegato()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato/1/allegato/99999');

        $response->assertStatus(404);
    }

    public function test_download_allegato_cliente_returns_404_for_nonexistent_record()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato-download/99999');

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD / DELETE ALLEGATO
    |--------------------------------------------------------------------------
    */

    public function test_upload_allegato_without_file_returns_404()
    {
        $this->staffUser('admin');

        $response = $this->post('/backend/allegato-caf', [
            'caf_patronato_id' => 1,
        ]);

        $response->assertStatus(404);
    }

    public function test_delete_allegato_returns_404_for_nonexistent()
    {
        $this->staffUser('admin');

        $response = $this->delete('/backend/allegato-caf', [
            'id' => 99999,
        ]);

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSION-BASED VIEW DATA
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_modify_esito_in_index()
    {
        $this->staffUser('admin');

        $response = $this->get('/backend/caf-patronato');

        $response->assertViewHas('puoModificareEsito', true);
    }

    public function test_agente_cannot_modify_esito_in_index()
    {
        $this->staffUser('agente');

        $response = $this->get('/backend/caf-patronato');

        $response->assertViewHas('puoModificareEsito', false);
    }

    public function test_supervisore_cannot_modify_records_in_index()
    {
        $this->staffUser('supervisore');

        $response = $this->get('/backend/caf-patronato');

        $response->assertViewHas('puoModificare', false);
    }

    public function test_agente_can_modify_records_in_index()
    {
        $this->staffUser('agente');

        $response = $this->get('/backend/caf-patronato');

        $response->assertViewHas('puoModificare', true);
    }
}
