<?php

namespace Tests\Feature\Controllers\Backend;

use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    /**
     * Test that staff user can access dashboard.
     *
     * @return void
     */
    public function test_staff_user_can_access_dashboard()
    {
        $user = $this->staffUser('agente');

        $response = $this->get('/backend');

        $response->assertStatus(200);
    }

    /**
     * Test that non-staff user cannot access dashboard.
     *
     * @return void
     */
    public function test_non_staff_user_cannot_access_dashboard()
    {
        $user = $this->authenticatedUser();

        $response = $this->get('/backend');

        $response->assertStatus(403);
    }

    /**
     * Test that unauthenticated user is redirected to login.
     *
     * @return void
     */
    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/backend');

        $response->assertRedirect('/login');
    }

    /**
     * Test that different roles can access dashboard.
     *
     * @return void
     */
    public function test_all_staff_roles_can_access_dashboard()
    {
        $roles = ['admin', 'agente', 'supervisore', 'operatore'];

        foreach ($roles as $role) {
            $user = $this->staffUser($role);

            $response = $this->get('/backend');

            $response->assertStatus(200);

            $this->actingAs(null);
        }
    }
}
