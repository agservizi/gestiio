<?php

namespace Tests\Feature\Controllers\Backend;

use App\Models\VisuraCamerale;
use Tests\TestCase;

class VisuraControllerTest extends TestCase
{
    public function test_staff_can_view_visure()
    {
        $staff = $this->staffUser('agente');
        $response = $this->get('/backend/visura');
        $response->assertStatus(200);
    }

    public function test_staff_can_search_azienda()
    {
        $staff = $this->staffUser('agente');
        $response = $this->post('/visura-cerca-azienda', [
            'partita_iva' => '12345678901234',
        ]);
        $this->assertNotEquals(500, $response->status());
    }
}
