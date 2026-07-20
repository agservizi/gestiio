<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SeafileClient
{
    private ?string $token = null;

    private string $tokenUser = '';

    public function baseUrl(): string
    {
        return rtrim((string) config('services.seafile.url', 'http://seafile'), '/');
    }

    public function publicUrl(): string
    {
        return rtrim((string) config('services.seafile.public_url', ''), '/');
    }

    public function repoId(): string
    {
        $id = (string) config('services.seafile.repo_id', '');
        if ($id === '') {
            throw new RuntimeException('SEAFILE_REPO_ID non configurato.');
        }

        return $id;
    }

    public function authToken(?string $email = null, ?string $password = null): string
    {
        $email = $email ?: (string) config('services.seafile.admin_email');
        $password = $password ?: (string) config('services.seafile.admin_password');
        $cacheKey = $email;

        if ($this->token !== null && $this->tokenUser === $cacheKey) {
            return $this->token;
        }

        $response = Http::asForm()
            ->timeout((int) config('services.seafile.timeout', 120))
            ->post($this->baseUrl().'/api2/auth-token/', [
                'username' => $email,
                'password' => $password,
            ]);

        if (! $response->successful() || empty($response->json('token'))) {
            throw new RuntimeException('Autenticazione Seafile fallita: HTTP '.$response->status());
        }

        $this->token = (string) $response->json('token');
        $this->tokenUser = $cacheKey;

        return $this->token;
    }

    public function withAdmin(): self
    {
        $this->authToken(
            (string) config('services.seafile.admin_email'),
            (string) config('services.seafile.admin_password')
        );

        return $this;
    }

    public function withAgente(): self
    {
        $this->authToken(
            (string) config('services.seafile.agente_email'),
            (string) config('services.seafile.agente_password')
        );

        return $this;
    }

    public function ping(): bool
    {
        try {
            $this->withAdmin();
            $r = $this->request('get', '/api2/auth/ping/');

            return $r->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function ensureDir(string $repoId, string $path): void
    {
        $path = $this->normalizeDirPath($path);
        if ($path === '/') {
            return;
        }

        $parts = array_values(array_filter(explode('/', trim($path, '/'))));
        $current = '';
        foreach ($parts as $part) {
            $current .= '/'.$part;
            $exists = $this->request('get', '/api2/repos/'.$repoId.'/dir/', [
                'p' => $current,
            ]);
            if ($exists->successful()) {
                continue;
            }

            $created = $this->request('post', '/api2/repos/'.$repoId.'/dir/', [
                'p' => $current,
            ], [
                'operation' => 'mkdir',
            ]);

            if ($created->successful()) {
                continue;
            }

            // Race / already exists
            $check = $this->request('get', '/api2/repos/'.$repoId.'/dir/', ['p' => $current]);
            if (! $check->successful()) {
                throw new RuntimeException(
                    'Impossibile creare cartella Seafile '.$current.': HTTP '.$created->status().' '.$created->body()
                );
            }
        }
    }

    public function uploadFile(string $repoId, string $parentDir, string $absoluteLocalPath, string $filename): string
    {
        $parentDir = $this->normalizeDirPath($parentDir);
        $this->ensureDir($repoId, $parentDir);

        if (! is_readable($absoluteLocalPath)) {
            throw new RuntimeException('File locale non leggibile: '.$absoluteLocalPath);
        }

        $linkResponse = $this->request('get', '/api2/repos/'.$repoId.'/upload-link/', [
            'p' => $parentDir,
        ]);

        if (! $linkResponse->successful()) {
            throw new RuntimeException('Upload-link Seafile fallito: HTTP '.$linkResponse->status());
        }

        $uploadUrl = trim((string) $linkResponse->body(), "\" \n\r\t");
        // URL interno può puntare a hostname pubblico: riscrivi verso base interno se serve
        $uploadUrl = $this->rewriteToInternal($uploadUrl);

        $response = Http::timeout((int) config('services.seafile.timeout', 300))
            ->attach('file', fopen($absoluteLocalPath, 'r'), $filename)
            ->post($uploadUrl, [
                'parent_dir' => $parentDir,
                'replace' => '1',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Upload Seafile fallito per '.$filename.': HTTP '.$response->status().' '.$response->body());
        }

        $seaPath = rtrim($parentDir, '/').'/'.$filename;
        if ($parentDir === '/') {
            $seaPath = '/'.$filename;
        }

        return $seaPath;
    }

    public function fileExists(string $repoId, string $dirPath, string $filename): bool
    {
        $dirPath = $this->normalizeDirPath($dirPath);
        $response = $this->request('get', '/api2/repos/'.$repoId.'/dir/', [
            'p' => $dirPath,
        ]);
        if (! $response->successful()) {
            return false;
        }
        foreach ((array) $response->json() as $entry) {
            if (($entry['type'] ?? '') === 'file' && ($entry['name'] ?? '') === $filename) {
                return true;
            }
        }

        return false;
    }

    public function libraryUrl(?string $repoId = null): string
    {
        $repoId = $repoId ?: $this->repoId();
        $public = $this->publicUrl();
        if ($public === '') {
            $public = $this->baseUrl();
        }

        return $public.'/library/'.$repoId.'/';
    }

    /**
     * Login Seahub (session cookies) per iframe/SSO.
     *
     * @return array<int, array{name:string,value:string,path:?string}>
     */
    public function webLogin(string $email, string $password): array
    {
        $base = $this->baseUrl();
        $public = $this->publicUrl();

        // Login sull'URL pubblico se raggiungibile (cookie allineati al browser)
        $loginBase = $base;
        $verify = true;
        if ($public !== '') {
            try {
                $probe = Http::withOptions(['verify' => false, 'allow_redirects' => false])
                    ->timeout(10)
                    ->get($public.'/accounts/login/');
                if ($probe->successful() || $probe->status() === 302) {
                    $loginBase = $public;
                    $verify = false;
                }
            } catch (\Throwable) {
                // resta su URL interno
            }
        }

        $cookieJar = [];

        $loginPage = Http::withOptions([
            'allow_redirects' => false,
            'verify' => $verify,
        ])
            ->timeout(30)
            ->withHeaders(['Accept-Language' => 'it'])
            ->get($loginBase.'/accounts/login/');

        foreach ($loginPage->cookies() as $cookie) {
            $cookieJar[$cookie->getName()] = $cookie->getValue();
        }

        // Header Set-Cookie (Laravel a volte non popola cookies())
        foreach ($loginPage->headers() as $headerName => $values) {
            if (strtolower((string) $headerName) !== 'set-cookie') {
                continue;
            }
            foreach ((array) $values as $line) {
                if (preg_match('/^([^=]+)=([^;]*)/', (string) $line, $m)) {
                    $cookieJar[$m[1]] = urldecode($m[2]);
                }
            }
        }

        $csrf = $this->extractCsrfFromHtml((string) $loginPage->body())
            ?: ($cookieJar['sfcsrftoken'] ?? $cookieJar['csrftoken'] ?? null);

        if (! $csrf) {
            throw new RuntimeException('CSRF Seafile non disponibile.');
        }

        $headers = [
            'Referer' => $loginBase.'/accounts/login/',
            'Origin' => $loginBase,
            'Accept-Language' => 'it',
            'X-CSRFToken' => $csrf,
            'Cookie' => $this->cookieHeader($cookieJar),
        ];

        $post = Http::withHeaders($headers)
            ->asForm()
            ->withOptions([
                'allow_redirects' => false,
                'verify' => $verify,
            ])
            ->timeout(30)
            ->post($loginBase.'/accounts/login/', [
                'login' => $email,
                'password' => $password,
                'csrfmiddlewaretoken' => $csrf,
                'next' => '/',
                'remember_me' => 'on',
            ]);

        foreach ($post->cookies() as $cookie) {
            $cookieJar[$cookie->getName()] = $cookie->getValue();
        }
        foreach ($post->headers() as $headerName => $values) {
            if (strtolower((string) $headerName) !== 'set-cookie') {
                continue;
            }
            foreach ((array) $values as $line) {
                if (preg_match('/^([^=]+)=([^;]*)/', (string) $line, $m)) {
                    $cookieJar[$m[1]] = urldecode($m[2]);
                }
            }
        }

        // Segui un eventuale 302 di login riuscito
        if (in_array($post->status(), [301, 302, 303, 307, 308], true)) {
            $location = $post->header('Location');
            if (is_string($location) && $location !== '') {
                if (str_starts_with($location, '/')) {
                    $location = $loginBase.$location;
                }
                $follow = Http::withHeaders([
                    'Cookie' => $this->cookieHeader($cookieJar),
                    'Accept-Language' => 'it',
                ])->withOptions([
                    'allow_redirects' => false,
                    'verify' => $verify,
                ])->timeout(30)->get($location);

                foreach ($follow->cookies() as $cookie) {
                    $cookieJar[$cookie->getName()] = $cookie->getValue();
                }
            }
        }

        if (empty($cookieJar['sessionid'])) {
            throw new RuntimeException(
                'Login web Seafile fallito: sessionid assente (HTTP '.$post->status().').'
            );
        }

        $cookieJar['django_language'] = 'it';

        $out = [];
        foreach ($cookieJar as $name => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $out[] = [
                'name' => (string) $name,
                'value' => (string) $value,
                'path' => '/',
                'httpOnly' => in_array($name, ['sessionid'], true),
            ];
        }

        return $out;
    }

    public function normalizeDirPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = '/'.trim($path, '/');
        if ($path === '/') {
            return '/';
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            $part = trim($part);
            if ($part === '' || $part === '.') {
                continue;
            }
            // Seafile: niente slash nei nomi
            $part = str_replace(['/', '\\', "\0"], '-', $part);
            $part = preg_replace('/\s+/', ' ', $part) ?: $part;
            $parts[] = $part;
        }

        return '/'.implode('/', $parts);
    }

    private function request(string $method, string $path, array $query = [], array $form = [])
    {
        $token = $this->token ?? $this->authToken();
        $url = $this->baseUrl().$path;
        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        $pending = Http::withHeaders([
            'Authorization' => 'Token '.$token,
            'Accept' => 'application/json',
        ])->timeout((int) config('services.seafile.timeout', 120));

        $method = strtoupper($method);
        if ($form !== []) {
            $pending = $pending->asForm();
            return match ($method) {
                'POST' => $pending->post($url, $form),
                'PUT' => $pending->put($url, $form),
                'PATCH' => $pending->patch($url, $form),
                default => $pending->send($method, $url, ['form_params' => $form]),
            };
        }

        return match ($method) {
            'GET' => $pending->get($url),
            'DELETE' => $pending->delete($url),
            'POST' => $pending->post($url),
            'PUT' => $pending->put($url),
            default => $pending->send($method, $url),
        };
    }

    private function rewriteToInternal(string $url): string
    {
        $public = $this->publicUrl();
        $base = $this->baseUrl();
        if ($public !== '' && str_starts_with($url, $public)) {
            return $base.substr($url, strlen($public));
        }

        // hostname documenti → seafile interno
        $host = parse_url($public, PHP_URL_HOST);
        if ($host && str_contains($url, $host)) {
            return preg_replace('#https?://'.preg_quote((string) $host, '#').'#', $base, $url) ?: $url;
        }

        return $url;
    }

    private function extractCookie(array $cookies, string $name): ?string
    {
        foreach ($cookies as $cookie) {
            if (($cookie['Name'] ?? $cookie['name'] ?? null) === $name) {
                return (string) ($cookie['Value'] ?? $cookie['value'] ?? '');
            }
        }

        return null;
    }

    private function extractCsrfFromHtml(string $html): ?string
    {
        if (preg_match('/name=["\']csrfmiddlewaretoken["\']\s+value=["\']([^"\']+)/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/value=["\']([^"\']+)["\']\s+name=["\']csrfmiddlewaretoken["\']/i', $html, $m)) {
            return $m[1];
        }

        return null;
    }

    private function cookieHeader(array $jar): string
    {
        $parts = [];
        foreach ($jar as $k => $v) {
            $parts[] = $k.'='.$v;
        }

        return implode('; ', $parts);
    }
}
