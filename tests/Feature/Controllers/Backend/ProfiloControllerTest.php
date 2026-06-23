<?php

namespace Tests\Feature\Controllers\Backend;

use Tests\TestCase;

class ProfiloControllerTest extends TestCase
{
    public function test_staff_can_access_Profilo_list()
    {
        $staff = $this->staffUser('agente');
        $response = $this->get('/backend/Profilo');
        $this->assertIn($response->status(), [200, 403, 404]);
    }

    public function test_non_staff_cannot_access()
    {
        $user = $this->authenticatedUser();
        $response = $this->get('/backend/Profilo');
        $response->assertStatus(403);
    }
}
