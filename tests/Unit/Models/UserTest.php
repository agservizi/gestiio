<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * Test that a user can be created with factory.
     *
     * @return void
     */
    public function test_user_can_be_created()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->email);
    }

    /**
     * Test user attributes are set correctly.
     *
     * @return void
     */
    public function test_user_attributes_are_set_correctly()
    {
        $attributes = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = User::factory()->create($attributes);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    /**
     * Test that user can be unverified.
     *
     * @return void
     */
    public function test_user_can_be_unverified()
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    /**
     * Test that password is hashed.
     *
     * @return void
     */
    public function test_password_is_hashed()
    {
        $user = User::factory()->create();

        $this->assertNotEquals('password', $user->password);
    }
}
