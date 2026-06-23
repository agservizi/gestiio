<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Tests\TestCase;

class AreaPersonaleControllerTest extends TestCase
{
    /**
     * Test that authenticated user can access area personale.
     *
     * @return void
     */
    public function test_authenticated_user_can_access_area_personale()
    {
        $user = $this->authenticatedUser();

        $response = $this->get('/area-personale');

        $response->assertStatus(200);
    }

    /**
     * Test that unauthenticated user cannot access area personale.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_access_area_personale()
    {
        $response = $this->get('/area-personale');

        $response->assertRedirect('/login');
    }

    /**
     * Test that user can access area-utente (profile).
     *
     * @return void
     */
    public function test_user_can_access_profile()
    {
        $user = $this->authenticatedUser();

        $response = $this->get('/area-utente');

        $response->assertStatus(200);
    }

    /**
     * Test that user can view their own data.
     *
     * @return void
     */
    public function test_user_can_view_own_data()
    {
        $user = $this->authenticatedUser();

        $response = $this->get('/dati-utente');

        $response->assertStatus(200);
    }

    /**
     * Test that user can update their profile information.
     *
     * @return void
     */
    public function test_user_can_update_profile()
    {
        $user = $this->authenticatedUser();

        $response = $this->patch('/dati-utente/nome', [
            'nome' => 'Updated Name',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test that user can export personal data (GDPR).
     *
     * @return void
     */
    public function test_user_can_export_personal_data()
    {
        $user = $this->authenticatedUser();

        $response = $this->get('/dati-utente/export');

        // Should return a downloadable file
        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('Content-Disposition'), 'attachment') ||
            str_contains($response->headers->get('Content-Type'), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        );
    }
}
