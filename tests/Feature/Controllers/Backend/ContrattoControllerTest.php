<?php

namespace Tests\Feature\Controllers\Backend;

use App\Models\Contratto;
use Tests\TestCase;

class ContrattoControllerTest extends TestCase
{
    public function test_staff_can_view_contratti_list()
    {
        $staff = $this->staffUser('agente');
        $response = $this->get('/backend/contratto');
        $response->assertStatus(200);
    }

    public function test_staff_can_create_contratto()
    {
        $staff = $this->staffUser('agente');
        $response = $this->post('/backend/contratto', [
            'numero_contratto' => 'CTR-2024-001',
            'importo' => 5000,
        ]);
        $this->assertDatabaseHas('contratti', [
            'numero_contratto' => 'CTR-2024-001',
        ]);
    }

    public function test_staff_can_view_single_contratto()
    {
        $staff = $this->staffUser('agente');
        $contratto = Contratto::factory()->create();
        $response = $this->get("/backend/contratto/{$contratto->id}");
        $response->assertStatus(200);
    }

    public function test_non_staff_cannot_access()
    {
        $user = $this->authenticatedUser();
        $response = $this->get('/backend/contratto');
        $response->assertStatus(403);
    }
}
