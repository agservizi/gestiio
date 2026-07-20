<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StirlingSsoService
{
    private const ADMIN_CACHE_KEY = 'stirling_admin_jwt_v2';

    public function usernameFor(User $user): string
    {
        return 'gestiio-'.(int) $user->id;
    }

    public function passwordFor(User $user): string
    {
        $secret = (string) config('services.stirling.user_secret', '');
        if ($secret === '') {
            // Fallback sicuro: non usare la password admin in chiaro.
            $secret = (string) config('services.stirling.admin_password', '').'|'.(string) config('app.key', 'gestiio');
        }

        // Password URL/form-safe, lunghezza adeguata per Stirling.
        return substr(hash_hmac('sha256', 'stirling-user:'.(int) $user->id, $secret), 0, 32);
    }

    public function roleFor(User $user): string
    {
        return $user->hasPermissionTo('admin') ? 'ROLE_ADMIN' : 'ROLE_USER';
    }

    public function desktopCredentials(User $user): array
    {
        if ($this->usesSharedSession()) {
            return [
                'username' => (string) config('services.stirling.admin_user', 'admin'),
                'password' => (string) config('services.stirling.admin_password', ''),
                'role' => 'ROLE_ADMIN',
                'server_url' => (string) config('services.stirling.desktop_url', 'http://192.168.1.50:8092'),
                'shared' => true,
                'storage_enabled' => false,
            ];
        }

        $this->ensureUser($user);

        return [
            'username' => $this->usernameFor($user),
            'password' => $this->passwordFor($user),
            'role' => $this->roleFor($user),
            'server_url' => (string) config('services.stirling.desktop_url', 'http://192.168.1.50:8092'),
            'shared' => false,
            'storage_enabled' => (bool) config('services.stirling.storage_enabled', false),
        ];
    }

    public function getJwtForUser(User $user, bool $forceRefresh = false): string
    {
        if ($this->usesSharedSession()) {
            return $this->adminJwt($forceRefresh);
        }

        $cacheKey = 'stirling_jwt_user_'.(int) $user->id;
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return (string) Cache::remember($cacheKey, now()->addMinutes(45), function () use ($user) {
            $this->ensureUser($user);

            return $this->login($this->usernameFor($user), $this->passwordFor($user));
        });
    }

    /** @deprecated Usare getJwtForUser(); mantenuto per compatibilità. */
    public function getServiceJwt(bool $forceRefresh = false): string
    {
        $user = auth()->user();
        if ($user instanceof User) {
            return $this->getJwtForUser($user, $forceRefresh);
        }

        return $this->adminJwt($forceRefresh);
    }

    public function clearCachedJwt(?User $user = null): void
    {
        if ($user) {
            Cache::forget('stirling_jwt_user_'.(int) $user->id);
        }
        Cache::forget(self::ADMIN_CACHE_KEY);
    }

    public function usesSharedSession(): bool
    {
        return (bool) config('services.stirling.shared_session', true);
    }

    public function ensureUser(User $user): void
    {
        if ($this->usesSharedSession()) {
            return;
        }

        $username = $this->usernameFor($user);
        $password = $this->passwordFor($user);
        $role = $this->roleFor($user);

        // Se il login funziona già, l'utente esiste con la password attesa.
        try {
            $this->login($username, $password);

            return;
        } catch (\Throwable $e) {
            // Sblocca eventuali lock da tentativi falliti (form Gestiio digitato a mano).
            $this->tryUnlock($username);
            try {
                $this->login($username, $password);

                return;
            } catch (\Throwable $e2) {
                // continua con provisioning
            }
        }

        $adminToken = $this->adminJwt(true);

        $response = Http::timeout((int) config('services.stirling.timeout', 300))
            ->withToken($adminToken)
            ->acceptJson()
            ->asMultipart()
            ->post($this->baseUrl().'/pdf-tools/api/v1/user/admin/saveUser', [
                'username' => $username,
                'password' => $password,
                'role' => $role,
                'authType' => 'WEB',
                'forceChange' => 'false',
            ]);

        if ($response->successful() || $response->status() === 302) {
            Log::info('Stirling user provisioned', [
                'gestiio_user_id' => (int) $user->id,
                'stirling_username' => $username,
                'role' => $role,
            ]);
            // Verifica login con password derivata
            $this->login($username, $password);

            return;
        }

        $body = mb_substr($response->body(), 0, 500);
        Log::warning('Stirling ensureUser failed', [
            'username' => $username,
            'status' => $response->status(),
            'body' => $body,
        ]);

        // Ultimo tentativo: forse creato comunque
        try {
            $this->login($username, $password);

            return;
        } catch (\Throwable $e) {
            throw new RuntimeException('Provisioning utente Stirling non riuscito (HTTP '.$response->status().')');
        }
    }

    private function tryUnlock(string $username): void
    {
        try {
            $adminToken = $this->adminJwt(true);
            Http::timeout((int) config('services.stirling.timeout', 300))
                ->withToken($adminToken)
                ->acceptJson()
                ->post($this->baseUrl().'/pdf-tools/api/v1/user/admin/unlockUser/'.$username);
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    private function adminJwt(bool $forceRefresh = false): string
    {
        $user = (string) config('services.stirling.admin_user', '');
        $password = (string) config('services.stirling.admin_password', '');
        if ($user === '' || $password === '') {
            throw new RuntimeException('STIRLING_ADMIN_USER / STIRLING_ADMIN_PASSWORD non configurati');
        }

        if ($forceRefresh) {
            Cache::forget(self::ADMIN_CACHE_KEY);
        }

        return (string) Cache::remember(self::ADMIN_CACHE_KEY, now()->addMinutes(45), function () use ($user, $password) {
            return $this->login($user, $password, true);
        });
    }

    private function login(string $username, string $password, bool $clearAdminFirstLogin = false): string
    {
        $response = Http::timeout((int) config('services.stirling.timeout', 300))
            ->acceptJson()
            ->asJson()
            ->post($this->baseUrl().'/pdf-tools/api/v1/auth/login', [
                'username' => $username,
                'password' => $password,
            ]);

        if (! $response->successful()) {
            Log::warning('Stirling SSO login failed', [
                'username' => $username,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('Login Stirling non riuscito (HTTP '.$response->status().')');
        }

        $json = $response->json();
        $token = data_get($json, 'session.access_token')
            ?: data_get($json, 'access_token')
            ?: data_get($json, 'token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Login Stirling: token JWT assente nella risposta');
        }

        // Shared admin: evita il modal "Cambia password al primo accesso"
        if ($clearAdminFirstLogin && data_get($json, 'user.user_metadata.firstLogin') === true) {
            $this->clearFirstLoginPasswordGate($token, $password);
        }

        return $token;
    }

    /**
     * Se admin ha firstLogin=true, Stirling mostra "Cambia password".
     * Completa il gate, aggiorna .env e config runtime.
     */
    private function clearFirstLoginPasswordGate(string $token, string $currentPassword): void
    {
        $newPassword = substr(hash_hmac('sha256', 'stirling-admin-rotated', (string) config('app.key', 'gestiio')), 0, 24);

        $response = Http::timeout((int) config('services.stirling.timeout', 300))
            ->withToken($token)
            ->asForm()
            ->post($this->baseUrl().'/pdf-tools/api/v1/user/change-password-on-login', [
                'currentPassword' => $currentPassword,
                'newPassword' => $newPassword,
                'confirmPassword' => $newPassword,
            ]);

        if (! $response->successful()) {
            Log::warning('Stirling clear firstLogin failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return;
        }

        $envPath = base_path('.env');
        if (is_writable($envPath)) {
            $env = (string) file_get_contents($envPath);
            if (preg_match('/^STIRLING_ADMIN_PASSWORD=.*$/m', $env)) {
                $env = preg_replace('/^STIRLING_ADMIN_PASSWORD=.*$/m', 'STIRLING_ADMIN_PASSWORD='.$newPassword, $env, 1);
            } else {
                $env .= "\nSTIRLING_ADMIN_PASSWORD=".$newPassword."\n";
            }
            file_put_contents($envPath, $env);
        }

        config(['services.stirling.admin_password' => $newPassword]);
        Cache::forget(self::ADMIN_CACHE_KEY);

        Log::info('Stirling admin firstLogin cleared; STIRLING_ADMIN_PASSWORD rotated');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.stirling.url', 'http://stirling-pdf:8080'), '/');
    }
}
