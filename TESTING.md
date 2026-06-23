# Testing Guide — Gestiio

Guida completa per scrivere e eseguire test in Gestiio.

## Setup Test Environment

### Configurazione Database

I test usano **SQLite in-memory** per velocità e isolamento:

```bash
# phpunit.xml configura:
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
QUEUE_CONNECTION=sync  # Jobs execute immediately
MAIL_MAILER=array      # No external mail sending
```

### Esecuzione Test

```bash
# Run all tests
php artisan test

# Run only Unit tests
php artisan test tests/Unit

# Run only Feature tests
php artisan test tests/Feature

# Run specific test file
php artisan test tests/Unit/Models/UserTest.php

# Run with coverage report
php artisan test --coverage

# Run single test method
php artisan test --filter=test_user_can_be_created
```

## Test Structure

### Unit Tests
Testano logica isolata senza HTTP o database.

**Location**: `tests/Unit/Models/`

```php
class UserTest extends TestCase
{
    public function test_user_can_be_created()
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(User::class, $user);
    }
}
```

### Feature Tests
Testano HTTP endpoints e interazioni complete.

**Locations**: 
- `tests/Feature/Controllers/` — Frontend controllers
- `tests/Feature/Controllers/Backend/` — Backend controllers
- `tests/Feature/Actions/` — Business logic actions

```php
class TicketControllerTest extends TestCase
{
    public function test_user_can_create_ticket()
    {
        $user = $this->authenticatedUser();
        
        $response = $this->post('/ticket', [
            'titolo' => 'Test',
            'descrizione' => 'Description',
        ]);
        
        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
        ]);
    }
}
```

## Test Helpers (TestCase)

### Authentication Helpers

```php
// Create and authenticate a regular user
$user = $this->authenticatedUser();

// Create and authenticate a staff user with role
$staff = $this->staffUser('agente');     // agente, admin, supervisore, operatore
```

### Factory Usage

```php
// Create single model
$user = User::factory()->create();

// Create with attributes
$user = User::factory()->create([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Create multiple models
$users = User::factory()->count(10)->create();

// Create relationship
$ticket = Ticket::factory()->create([
    'user_id' => $user->id,
]);
```

## Available Factories

- `UserFactory` — Test users
- `ContrattoFactory` — Test contracts
- `TicketFactory` — Test tickets
- Plus 14+ others in `database/factories/`

## Test Coverage Goals

**FASE 2 Target**: 70% coverage of core modules

| Module | Status | Coverage |
|--------|--------|----------|
| Models (20) | IN_PROGRESS | 15% |
| Actions (6) | CREATED | 2 test files |
| Controllers (10) | CREATED | 4 test files |
| **Total Tests** | 11 tests written | ~50 assertions |

## Common Assertions

```php
// Database assertions
$this->assertDatabaseHas('users', ['email' => 'john@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'nonexistent@example.com']);

// Response assertions
$response->assertStatus(200);
$response->assertRedirect('/login');
$response->assertViewIs('ticket.show');
$response->assertJsonPath('data.id', $user->id);

// Model assertions
$this->assertInstanceOf(User::class, $user);
$this->assertTrue($user->hasRole('agente'));
$this->assertFalse($user->hasPermission('admin'));
```

## Debugging Tests

### Print test output
```bash
php artisan test --verbose
```

### Use dd() or dump()
```php
public function test_something()
{
    $user = User::factory()->create();
    dd($user); // Dies and dumps
}
```

### Check database state
```php
\DB::enableQueryLog();
// ... run code ...
dd(\DB::getQueryLog());
```

## Next Steps

1. **Complete 20 Model tests** — Test relationships, scopes, mutators
2. **Add Action tests** — Test business logic validation
3. **Add Controller tests** — Test HTTP responses and authorization
4. **Achieve 70% coverage** — Run `php artisan test --coverage`
5. **Setup CI checks** — GitHub Actions runs tests on every push

## Resources

- [Laravel Testing Docs](https://laravel.com/docs/9.x/testing)
- [PHPUnit Docs](https://phpunit.de/documentation.html)
- [Laravel Database Testing](https://laravel.com/docs/9.x/database-testing)
