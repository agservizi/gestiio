<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\SeafileClient;
use Illuminate\Http\Request;

class SeafileDocumentiController extends Controller
{
    public function __construct(private SeafileClient $seafile)
    {
    }

    public function index()
    {
        $this->authorizeDocumenti();

        return view('Backend.Documenti.seafile', [
            'titoloPagina' => 'Documenti',
            'nascondiToolbar' => true,
            'nascondiFooter' => true,
            'container' => 'container-fluid p-0',
            'ssoUrl' => url('/backend/documenti/sso').'?t='.time(),
            'isAgenteOnly' => $this->isAgenteOnly(),
        ]);
    }

    /**
     * Login server-side Seafile → set cookie sessione su .agenziaplinio.it → apri library.
     * Niente form POST cross-domain (evita 403 CSRF).
     */
    public function sso(Request $request)
    {
        $this->authorizeDocumenti();

        $isAdmin = $this->currentUserHasPermission('admin');
        $email = $isAdmin
            ? (string) config('services.seafile.admin_email')
            : (string) config('services.seafile.agente_email');
        $password = $isAdmin
            ? (string) config('services.seafile.admin_password')
            : (string) config('services.seafile.agente_password');

        if ($email === '' || $password === '') {
            abort(503, 'Seafile non configurato (credenziali mancanti).');
        }

        try {
            $cookies = $this->seafile->webLogin($email, $password);
            $target = $this->seafile->libraryUrl();
        } catch (\Throwable $e) {
            report($e);
            abort(502, 'Documenti (Seafile) non disponibile. Riprova tra poco.');
        }

        $cookieDomain = $this->cookieDomain();

        // HTML + JS: applica cookie poi naviga (più affidabile del solo 302 in iframe)
        $html = view('Backend.Documenti.seafile-sso', [
            'target' => $target,
        ])->render();

        $response = response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');

        foreach ($cookies as $cookie) {
            $name = $cookie['name'];
            // Non propagare cookie tecnici inutili / troppo lunghi
            if (in_array($name, ['sessionid', 'sfcsrftoken', 'csrftoken', 'django_language'], true) === false) {
                continue;
            }
            $httpOnly = (bool) ($cookie['httpOnly'] ?? ($name === 'sessionid'));
            $response->headers->setCookie(cookie(
                $name,
                $cookie['value'],
                $name === 'sessionid' ? 60 * 24 * 7 : 60 * 24 * 365,
                '/',
                $cookieDomain,
                true,
                $httpOnly,
                false,
                'None'
            ));
        }

        return $response;
    }

    private function authorizeDocumenti(): void
    {
        abort_unless(
            $this->currentUserHasPermission('admin') || $this->currentUserHasPermission('agente'),
            403
        );
    }

    private function isAgenteOnly(): bool
    {
        return ! $this->currentUserHasPermission('admin') && $this->currentUserHasPermission('agente');
    }

    private function currentUserHasPermission(string $permission): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasPermissionTo')) {
            try {
                return (bool) $user->hasPermissionTo($permission);
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    private function cookieDomain(): ?string
    {
        $public = (string) config('services.seafile.public_url', '');
        $host = parse_url($public, PHP_URL_HOST) ?: '';
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';

        if ($host !== '' && $appHost !== '' && str_ends_with($host, '.agenziaplinio.it') && str_ends_with($appHost, '.agenziaplinio.it')) {
            return '.agenziaplinio.it';
        }

        return $host !== '' ? $host : null;
    }
}
