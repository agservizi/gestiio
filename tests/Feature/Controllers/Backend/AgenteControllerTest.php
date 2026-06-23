<?php

namespace Tests\Feature\Controllers\Backend;

use App\Models\Agente;
use Tests\TestCase;

class AgenteControllerTest extends TestCase
{
    public function test_admin_can_view_agenti()
    {
        $admin = $this->staffUser('admin');
        $response = $this->get('/backend/agente');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_manage_agenti()
    {
        $agente = $this->staffUser('agente');
        $response = $this->get('/backend/agente');
        $response->assertStatus(403);
    }
}
