# Authentication & Authorization Workflow

Documentazione completa dei flussi di autenticazione e autorizzazione in Gestiio.

## 1. Login Flow

### Sequence

```
User (Browser)
    ↓ POST /login (email, password)
LoginController (Fortify)
    ↓ Validate credentials
CreateNewUser (Action)
    ↓ Encrypt password + create session
Session stored (Redis/file)
    ↓ Check user role
Role check: admin|agente|supervisore|operatore?
    ├─ YES → Redirect to /backend (staff dashboard)
    └─ NO → Redirect to /area-personale (user dashboard)
```

### Code Path

**File**: `routes/web.php:15-26`

```php
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->hasAnyPermission(['admin', 'agente', 'supervisore', 'operatore'])) {
            return redirect('/backend');
        }
        return redirect('/area-personale');
    }
    return redirect('/login');
});
```

**File**: `app/Actions/Fortify/CreateNewUser.php`

- Validates email, password (min 8 chars)
- Hashes password with bcrypt
- Creates User record
- Sets session

## 2. Two-Factor Authentication (2FA)

### Requirement

Staff users (**only**) require 2FA:
- Middleware: `role_or_permission:admin|agente|supervisore|operatore` + `2fa`
- File: `app/Http/Kernel.php`

```php
'role_or_permission:admin|agente|supervisore|operatore',
'2fa',
```

### Sequence

```
POST /login (email, password)
    ↓
CreateNewUser validates credentials
    ↓
Session created (user NOT yet authenticated fully)
    ↓
Check if user has 2FA enabled
    ├─ NO → Allow access (rare)
    └─ YES → Redirect to /2fa-verify
    
POST /confirm-otp (otp_code)
    ↓
ConfirmTwoFactorAuthentication (Action)
    ↓ Validate OTP code against stored secret
    ├─ VALID → Set session('2fa_verified' => true)
    └─ INVALID → Redirect back with error
    
session('2fa_verified') set → User can access /backend
```

### Code Path

**File**: `app/Actions/TwoFactor/GenerateOTP.php`

```php
public function __invoke(User $user)
{
    // Generate 6-digit OTP
    $otp = rand(100000, 999999);
    
    // Store in cache (5 min expiry)
    Cache::put("otp.{$user->id}", $otp, now()->addMinutes(5));
    
    // Send via email
    Mail::to($user->email)->send(new OtpCodeMailable($otp));
    
    return $otp;
}
```

**File**: `app/Actions/TwoFactor/ConfirmTwoFactorAuthentication.php`

```php
public function __invoke(User $user, string $otp): bool
{
    $cachedOtp = Cache::get("otp.{$user->id}");
    
    if (!$cachedOtp || $cachedOtp !== $otp) {
        throw new \Exception('Invalid OTP');
    }
    
    Cache::forget("otp.{$user->id}");
    session(['2fa_verified' => true]);
    
    return true;
}
```

**Sending OTP Email**:

**File**: `routes/web.php:32`

```php
Route::post('/send-otp-email/{id}', [LogOut::class, 'sendOtpEmail']);
```

## 3. Impersonation (Support)

### Purpose
Support staff can login as another user to troubleshoot issues.

### How It Works

**Command**: `/login-id/{id}` (dev/staging only)

```php
Route::get('login-id/{id}', [\App\Http\Controllers\LogOut::class, 'loginId']);
```

**Process**:

```
Support staff visits: /login-id/123
    ↓
Store original user ID in session('impersona' => auth()->id())
    ↓
Login as user ID 123
    ↓
Original user ID 123 can browse app as user 123
    ↓
Visit /stop-impersona
    ↓
Restore original session
    ↓
Relogin as original user
```

**Code**:

**File**: `routes/web.php:84-92`

```php
Route::get('/stop-impersona', function () {
    if (!session()->has('impersona')) {
        return redirect('/');
    }
    $orig = session('impersona');
    session()->forget('impersona');
    Auth::loginUsingId($orig, false);
    return redirect('/backend');
})->middleware('auth');
```

### Security Note
- Only available in `env('APP_ENV') == 'local'`
- Not available in production
- Logs all impersonation attempts (recommended: add to StructuredLogger)

## 4. Role & Permission Gating

### Roles

| Role | Level | Access | Created By |
|------|-------|--------|------------|
| admin | 5 | Full backend access | Super admin |
| supervisore | 4 | Manage agents, view reports | Admin |
| agente | 3 | Create/manage contracts, tickets | Supervisor |
| operatore | 2 | View/update existing resources | Agent |

### Permissions

Permissions are Spatie Permission managed:

```php
// In seeder or action:
$user->assignRole('agente');
$user->givePermissionTo('view_contracts', 'create_contracts');
```

**Caching**: Spatie Permission caches roles/permissions. After changes:

```bash
php artisan permission:cache-reset
```

### Authorization in Controllers

**File**: `app/Policies/TicketPolicy.php`

```php
public function view(User $user, Ticket $ticket)
{
    // User can view own tickets
    return $user->id === $ticket->user_id;
}

public function update(User $user, Ticket $ticket)
{
    // Only staff can update
    return $user->hasAnyPermission(['agente', 'supervisore', 'admin']);
}
```

**In Controllers**:

```php
// Check authorization
$this->authorize('view', $ticket);  // Throws 403 if denied

// Check permission
if (auth()->user()->can('delete_contracts')) {
    // Allow deletion
}

// Check role
if (auth()->user()->hasRole('admin')) {
    // Admin only
}
```

## 5. API Token Authentication (if applicable)

Currently **not implemented**. For future API:

```php
// Route
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Usage
curl -H "Authorization: Bearer {token}" https://api.example.com/user
```

## 6. Troubleshooting

### User stuck at 2FA screen
1. Check `cache` table for OTP records
2. Clear OTP: `Cache::forget("otp.{$user_id}")`
3. Resend OTP

### Permission cache not updating
```bash
php artisan permission:cache-reset
php artisan cache:clear
```

### User can't access backend
1. Check if user has staff role:
   ```php
   $user->hasAnyPermission(['admin', 'agente', 'supervisore', 'operatore']);
   ```
2. Check middleware in `Kernel.php`
3. Check 2FA is not blocking access

### Impersonation not working
1. Only works in `APP_ENV=local`
2. Must be authenticated first
3. Check `session('impersona')` exists

## 7. Security Best Practices

- ✅ Always use bcrypt for passwords (Laravel default)
- ✅ Validate all inputs before creating users
- ✅ Use 2FA for staff accounts
- ✅ Log authentication events (use StructuredLogger)
- ✅ Clear session on logout
- ✅ Use HTTPS in production (Sentry + logging need HTTPS for Web Push)
- ⚠️ Monitor failed login attempts (add rate limiting)
- ⚠️ Monitor permission cache hits (performance)

## 8. Related Files

- `routes/web.php` — Auth routes
- `app/Actions/Fortify/` — Login/register logic
- `app/Actions/TwoFactor/` — 2FA logic
- `app/Http/Controllers/LogOut.php` — Logout + impersonation
- `app/Policies/` — Authorization policies
- `config/auth.php` — Auth configuration
