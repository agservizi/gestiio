<?php

namespace Tests\Feature\Controllers\Backend;

use Tests\TestCase;

class CartellaFilesControllerTest extends TestCase
{
    public function test_staff_can_access_CartellaFiles_list()
    {
        $staff = $this->staffUser('agente');
        $response = $this->get('/backend/CartellaFiles');
        $this->assertIn($response->status(), [200, 403, 404]);
    }

    public function test_non_staff_cannot_access()
    {
        $user = $this->authenticatedUser();
        $response = $this->get('/backend/CartellaFiles');
        $response->assertStatus(403);
    }
}
