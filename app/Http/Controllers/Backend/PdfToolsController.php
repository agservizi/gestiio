<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\StirlingSsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfToolsController extends Controller
{
    public const JWT_COOKIE = 'gestiio_stirling_jwt';

    private function authorizePdfTools(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->hasPermissionTo('admin') || $user->hasPermissionTo('agente')),
            403
        );
    }

    private function attachStirlingJwtCookie(\Symfony\Component\HttpFoundation\Response $response, string $token): \Symfony\Component\HttpFoundation\Response
    {
        // Cookie letto dal proxy: Apache spesso rimuove Authorization prima di PHP.
        return $response->withCookie(cookie(
            self::JWT_COOKIE,
            $token,
            45,
            '/',
            null,
            (bool) config('session.secure', true),
            true,
            false,
            'Lax'
        ));
    }

    /**
     * Percorsi usabili dal telefono (QR mobile-scanner) senza sessione Gestiio.
     */
    public static function isPublicProxyPath(?string $path, string $method): bool
    {
        $path = ltrim((string) $path, '/');
        $method = strtoupper($method);

        if (preg_match('#^(mobile-scanner|api/v1/mobile-scanner)(/|$)#i', $path)) {
            return true;
        }

        if (in_array($method, ['GET', 'HEAD'], true)) {
            if (preg_match('#^api/v1/config/app-config/?$#i', $path)) {
                return true;
            }
            // Stub SaaS-only: evita 302/404 rumorosi dall'UI Stirling V2
            if (preg_match('#^api/v1/policies(/|$)#i', $path)) {
                return true;
            }
            if (preg_match('#^(assets|static|css|js|fonts|pdfjs|i18n|locales|modern-logo|og_images)(/|$)#i', $path)) {
                return true;
            }
            if (preg_match('#^(manifest\.json|robots\.txt|sw\.js|favicon\.ico|site\.webmanifest)$#i', $path)) {
                return true;
            }
        }

        return false;
    }

    public function index()
    {
        $this->authorizePdfTools();

        return response()
            ->view('Backend.PdfTools.index', [
                'titoloPagina' => 'PDF Tools',
                'nascondiToolbar' => true,
                'nascondiFooter' => true,
                'container' => 'container-fluid p-0',
                'ssoTokenUrl' => action([self::class, 'ssoToken']),
                'enterUrl' => action([self::class, 'enter']),
                'noStorageNotice' => ! (bool) config('services.stirling.storage_enabled', false),
                'displayName' => $this->gestiioDisplayName(auth()->user()),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function ssoToken(StirlingSsoService $sso): JsonResponse
    {
        $this->authorizePdfTools();
        /** @var \App\Models\User $user */
        $user = auth()->user();

        try {
            $token = $sso->getJwtForUser($user, true);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Impossibile ottenere la sessione Stirling.',
            ], 502);
        }

        return $this->attachStirlingJwtCookie(response()->json([
            'ok' => true,
            'token' => $token,
            'storageKey' => 'stirling_jwt',
            'username' => $sso->usesSharedSession()
                ? (string) config('services.stirling.admin_user', 'admin')
                : $sso->usernameFor($user),
            'shared_session' => $sso->usesSharedSession(),
            'storage_enabled' => (bool) config('services.stirling.storage_enabled', false),
        ]), $token);
    }

    /**
     * Bootstrap iframe: login Stirling via API browser + JWT, poi apre /pdf-tools/.
     */
    public function enter(StirlingSsoService $sso)
    {
        $this->authorizePdfTools();
        /** @var \App\Models\User $user */
        $user = auth()->user();

        try {
            $creds = $sso->desktopCredentials($user);
            $token = $sso->getJwtForUser($user, true);
        } catch (\Throwable $e) {
            report($e);

            return response(
                '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem">'
                .'<h1>PDF Tools</h1><p>Impossibile aprire la sessione Stirling. Ricarica la pagina.</p>'
                .'<pre style="color:#64748b;font-size:12px">'.e($e->getMessage()).'</pre>'
                .'</body></html>',
                502
            )->header('Content-Type', 'text/html; charset=utf-8');
        }

        $payload = [
            'token' => $token,
            'username' => $creds['username'],
            'password' => $creds['password'],
            'displayName' => $this->gestiioDisplayName($user),
            'loginUrl' => url('/pdf-tools/api/v1/auth/login'),
            'meUrl' => url('/pdf-tools/api/v1/auth/me'),
            'targetUrl' => url('/pdf-tools/'),
        ];
        $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Accesso PDF Tools</title>
<meta name="robots" content="noindex">
</head>
<body style="font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#64748b;background:#fff">
<div id="msg">Accesso automatico a PDF Tools…</div>
<script>
(function () {
  var cfg = {$json};
  var msg = document.getElementById('msg');
  function setMsg(t) { if (msg) msg.textContent = t; }
  function forceItalian() {
    try {
      // Priorita' User (3): altrimenti un en salvato in i18nextLng-source vince sul default server
      localStorage.setItem('i18nextLng', 'it-IT');
      localStorage.setItem('i18nextLng-source', '3');
      if (cfg.displayName) localStorage.setItem('gestiio_display_name', cfg.displayName);
      ['language', 'languageCode', 'lng', 'locale'].forEach(function (k) {
        localStorage.setItem(k, 'it-IT');
      });
      try {
        var prefs = JSON.parse(localStorage.getItem('stirlingpdf_preferences') || '{}') || {};
        prefs.language = 'it-IT';
        prefs.locale = 'it-IT';
        prefs.languageCode = 'it-IT';
        localStorage.setItem('stirlingpdf_preferences', JSON.stringify(prefs));
      } catch (_e) {}
    } catch (e) {}
  }
  function storeToken(token) {
    try {
      sessionStorage.removeItem('stirling_sso_auto_login_logged_out');
      localStorage.removeItem('stirling_sso_auto_login_logged_out');
      localStorage.setItem('stirling_jwt', token);
      forceItalian();
      window.dispatchEvent(new CustomEvent('jwt-available'));
    } catch (e) {}
  }
  function go() {
    window.location.replace(cfg.targetUrl);
  }
  async function verify(token) {
    var r = await fetch(cfg.meUrl, {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + token,
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    return r.ok;
  }
  async function loginBrowser() {
    var r = await fetch(cfg.loginUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ username: cfg.username, password: cfg.password })
    });
    if (!r.ok) throw new Error('login HTTP ' + r.status);
    var data = await r.json();
    var token = (data.session && data.session.access_token) || data.access_token || data.token;
    if (!token) throw new Error('token assente');
    return token;
  }
  (async function () {
    try {
      storeToken(cfg.token);
      if (await verify(cfg.token)) { go(); return; }
      setMsg('Rinnovo sessione Stirling…');
      var token = await loginBrowser();
      storeToken(token);
      if (!(await verify(token))) throw new Error('verifica fallita');
      go();
    } catch (e) {
      setMsg('Accesso automatico fallito. Ricarica la pagina. (' + (e && e.message ? e.message : e) + ')');
    }
  })();
})();
</script>
</body>
</html>
HTML;

        return $this->attachStirlingJwtCookie(
            response($html, 200)->header('Content-Type', 'text/html; charset=utf-8'),
            $token
        );
    }

    public function desktopCredentials(StirlingSsoService $sso): JsonResponse
    {
        $this->authorizePdfTools();
        /** @var \App\Models\User $user */
        $user = auth()->user();

        try {
            $creds = $sso->desktopCredentials($user);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Impossibile preparare le credenziali desktop Stirling.',
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'credentials' => $creds,
        ]);
    }

    public function proxyPublic(Request $request, ?string $path = null)
    {
        abort_unless(self::isPublicProxyPath($path, $request->method()), 403);

        return $this->forward($request, $path);
    }

    public function proxy(Request $request, ?string $path = null)
    {
        if (! self::isPublicProxyPath($path, $request->method())) {
            if (! auth()->check()) {
                return redirect()->guest('/login');
            }
            $this->authorizePdfTools();
        }

        return $this->forward($request, $path);
    }

    private function forward(Request $request, ?string $path = null)
    {
        // Feature SaaS-only di Stirling V2: sul self-hosted l'API non esiste (404).
        // Stub neutrale per non riempire la console e non far fallire il client.
        if ($stub = $this->saasOnlyPoliciesStub($request, $path)) {
            return $stub;
        }

        $base = rtrim((string) config('services.stirling.url', 'http://stirling-pdf:8080'), '/');
        $publicBase = rtrim((string) config('services.stirling.public_url', ''), '/');
        if ($publicBase === '') {
            $publicBase = rtrim((string) $request->getSchemeAndHttpHost(), '/').'/pdf-tools';
        }
        $publicOrigin = (string) preg_replace('#/pdf-tools/?$#', '', $publicBase);
        $publicHost = parse_url($publicBase, PHP_URL_HOST) ?: $request->getHost();
        $publicScheme = parse_url($publicBase, PHP_URL_SCHEME) ?: 'https';

        $prefix = '/pdf-tools';
        $suffix = $path ? '/'.ltrim($path, '/') : '';
        $target = $base.$prefix.$suffix;
        if ($suffix === '') {
            $target .= '/';
        }

        if ($query = $request->getQueryString()) {
            $target .= '?'.$query;
        }

        $headers = [];
        foreach ([
            'Accept',
            'Accept-Language',
            'Content-Type',
            'X-Requested-With',
            'Authorization',
        ] as $name) {
            if ($request->headers->has($name)) {
                $headers[$name] = $request->headers->get($name);
            }
        }

        // Forza UI italiana verso Stirling (indipendente dal browser)
        $headers['Accept-Language'] = 'it-IT,it;q=0.9';

        $bearer = $this->resolveStirlingBearer($request);

            // Se l'utente Gestiio è in sessione, assicuriamo sempre un JWT Stirling
            // (anche sulla prima richiesta HTML dell'iframe, senza dipendere da localStorage).
            if (auth()->check()) {
                $user = auth()->user();
                $allowed = false;
                try {
                    $allowed = $user && ($user->hasPermissionTo('admin') || $user->hasPermissionTo('agente'));
                } catch (\Throwable $e) {
                    $allowed = (bool) $user;
                }
                if ($allowed) {
                    try {
                        // Se manca bearer, forza refresh; altrimenti riusa cache
                        $fresh = app(StirlingSsoService::class)->getJwtForUser($user, $bearer === null);
                        if (is_string($fresh) && $fresh !== '') {
                            $bearer = $fresh;
                        }
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }

            if ($bearer !== null) {
                $headers['Authorization'] = 'Bearer '.$bearer;
            }

        $headers['X-Forwarded-Host'] = $publicHost;
        $headers['X-Forwarded-Proto'] = $publicScheme;
        $headers['X-Forwarded-Port'] = $publicScheme === 'https' ? '443' : '80';
        $headers['X-Forwarded-Prefix'] = $prefix;
        $headers['Forwarded'] = 'proto='.$publicScheme.';host='.$publicHost;
        $headers['Host'] = 'stirling-pdf:8080';

        $method = strtoupper($request->method());
        $contentType = (string) $request->header('Content-Type', '');
        $isSpaShell = $method === 'GET' && $this->isSpaShellPath($path);

        try {
            $response = $this->sendUpstream($method, $target, $request, $headers, $contentType, ! $isSpaShell);

            // /pdf-tools/ senza JWT → 401 JSON: ritenta con token fresco
            if ($isSpaShell && $response->status() === 401 && auth()->check()) {
                try {
                    $bearer = app(StirlingSsoService::class)->getJwtForUser(auth()->user(), true);
                    $headers['Authorization'] = 'Bearer '.$bearer;
                    $response = $this->sendUpstream($method, $target, $request, $headers, $contentType, false);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $hops = 0;
            while ($hops < 3 && in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                $location = $response->header('Location');
                if (! is_string($location) || $location === '') {
                    break;
                }
                $next = $this->resolveUpstreamLocation($location, $base, $prefix, $publicBase, $publicOrigin);
                if ($next === null || $next === $target) {
                    break;
                }
                $target = $next;
                $response = $this->sendUpstream('GET', $target, $request, $headers, $contentType, ! $isSpaShell);
                $hops++;
            }
        } catch (\Throwable $e) {
            report($e);

            return response('PDF Tools non disponibile. Riprova tra poco.', 502);
        }

        // In Docker Stirling non può auto-riavviarsi (503). Un watcher sul NAS ricrea il container.
        if ($this->isAdminSettingsRestartPath($path, $method) && in_array($response->status(), [500, 502, 503], true)) {
            return response()->json([
                'status' => 'accepted',
                'message' => 'Riavvio richiesto. Il servizio si riavvia automaticamente tra pochi secondi.',
            ], 202);
        }

        // Forza defaultLocale italiano nell'app-config (evita override UI/settings corrotti)
        if ($method === 'GET' && preg_match('#^api/v1/config/app-config/?$#i', ltrim((string) $path, '/'))) {
            $payload = json_decode((string) $response->body(), true);
            if (is_array($payload)) {
                $payload['defaultLocale'] = 'it-IT';
                $payload['languages'] = [];
                if (! (bool) config('services.stirling.storage_enabled', false)) {
                    $payload['storageEnabled'] = false;
                }

                return response()->json($payload, $response->status())
                    ->header('Cache-Control', 'no-store');
            }
        }

        if ($isSpaShell) {
            $html = (string) $response->body();
            $displayName = $this->gestiioDisplayName(auth()->user());
            if (is_string($bearer) && $bearer !== '') {
                $html = $this->injectJwtIntoHtml($html, $bearer, $displayName);
            } else {
                $html = $this->injectItalianForce($html, $displayName);
            }
            $out = response($html, $response->status());
            foreach ($response->headers() as $name => $values) {
                $lower = strtolower($name);
                if (in_array($lower, ['transfer-encoding', 'connection', 'content-encoding', 'x-frame-options', 'content-length'], true)) {
                    continue;
                }
                foreach ((array) $values as $value) {
                    if (in_array($lower, ['location', 'content-location'], true) && is_string($value)) {
                        $value = $this->rewritePublicLocation($value, $base, $prefix, $publicBase, $publicOrigin, $publicScheme, $publicHost);
                    }
                    $out->headers->set($name, $value, false);
                }
            }
            $out->headers->set('Content-Type', 'text/html; charset=utf-8');
            $out->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $out->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            if (is_string($bearer) && $bearer !== '') {
                $out->cookie(
                    self::JWT_COOKIE,
                    $bearer,
                    45,
                    '/',
                    null,
                    (bool) config('session.secure', true),
                    true,
                    false,
                    'Lax'
                );
            }

            return $out;
        }

        $stream = $response->toPsrResponse()->getBody();
        $out = new StreamedResponse(function () use ($stream) {
            while (! $stream->eof()) {
                echo $stream->read(1024 * 64);
                if (function_exists('fastcgi_finish_request') === false) {
                    flush();
                }
            }
        }, $response->status());

        foreach ($response->headers() as $name => $values) {
            $lower = strtolower($name);
            if (in_array($lower, ['transfer-encoding', 'connection', 'content-encoding', 'x-frame-options'], true)) {
                continue;
            }
            foreach ((array) $values as $value) {
                if (in_array($lower, ['location', 'content-location'], true) && is_string($value)) {
                    $value = $this->rewritePublicLocation($value, $base, $prefix, $publicBase, $publicOrigin, $publicScheme, $publicHost);
                }
                $out->headers->set($name, $value, false);
            }
        }

        $out->headers->set('X-Frame-Options', 'SAMEORIGIN');

        return $out;
    }

    /**
     * Stirling V2 chiama /api/v1/policies anche in self-hosted, ma l'endpoint
     * esiste solo nel build SaaS → 404 "Endpoint Not Found".
     */
    private function saasOnlyPoliciesStub(Request $request, ?string $path)
    {
        $path = ltrim((string) $path, '/');
        if (! preg_match('#^api/v1/policies(/|$)#i', $path)) {
            return null;
        }

        $method = strtoupper($request->method());

        // Lista policies / runs → array vuoto
        if ($method === 'GET' && preg_match('#^api/v1/policies/?$#i', $path)) {
            return response()->json([], 200);
        }
        if ($method === 'GET' && preg_match('#^api/v1/policies/runs/?$#i', $path)) {
            return response()->json([], 200);
        }

        // Altri metodi SaaS: rispondi "non disponibile" senza 404 rumoroso
        if (in_array($method, ['GET', 'HEAD'], true)) {
            return response()->json([], 200);
        }

        return response()->json([
            'error' => 'Policies are not available on this self-hosted instance.',
        ], 501);
    }

    private function isAdminSettingsRestartPath(?string $path, string $method): bool
    {
        return strtoupper($method) === 'POST'
            && preg_match('#^api/v1/admin/settings/restart/?$#i', ltrim((string) $path, '/'));
    }

    private function isSpaShellPath(?string $path): bool
    {
        $path = ltrim((string) $path, '/');
        if ($path === '' || $path === 'index.html') {
            return true;
        }
        if (str_starts_with($path, 'api/')) {
            return false;
        }
        // Asset statici / i18n: mai iniettare HTML/script (rompe .toml → UI resta in inglese)
        if (preg_match('/\.(js|css|map|png|jpe?g|gif|svg|ico|woff2?|ttf|json|txt|webp|toml|xml|wasm|mjs)$/i', $path)) {
            return false;
        }
        if (preg_match('#^(locales|assets|static|css|js|fonts|pdfjs|i18n)(/|$)#i', $path)) {
            return false;
        }

        // Route SPA (es. /login): inietta JWT anche lì per uscire dal form.
        return true;
    }

    private function injectJwtIntoHtml(string $html, string $token, string $displayName = ''): string
    {
        $json = json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $script = '<script>(function(){try{'
            .'sessionStorage.removeItem("stirling_sso_auto_login_logged_out");'
            .'localStorage.removeItem("stirling_sso_auto_login_logged_out");'
            .'localStorage.setItem("stirling_jwt",'.$json.');'
            .'}catch(e){}})();</script>'
            .$this->italianForceScript($displayName)
            .'<script>(function(){try{'
            .'window.dispatchEvent(new CustomEvent("jwt-available"));'
            .'var pth=(location.pathname||"");'
            .'if(/\\/login\\/?$/.test(pth)||/\\/sign-in\\/?$/.test(pth)){'
            .'var b=document.querySelector("base");'
            .'location.replace((b&&b.href)?b.href:(pth.replace(/\\/login\\/?$/, "/")||"/pdf-tools/"));'
            .'}'
            .'}catch(e){}})();</script>';

        return $this->prependHeadScript($html, $script);
    }

    private function injectItalianForce(string $html, string $displayName = ''): string
    {
        return $this->prependHeadScript($html, $this->italianForceScript($displayName));
    }

    /**
     * Stirling V2: forza italiano + chiude onboarding/upsell + mostra nome Gestiio al posto di "admin".
     */
    private function italianForceScript(string $displayName = ''): string
    {
        if ($displayName === '' && auth()->check()) {
            $displayName = $this->gestiioDisplayName(auth()->user());
        }
        $nameJson = json_encode($displayName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return '<script>(function(){try{'
            .'var _displayName='.$nameJson.';'
            .'if(_displayName)localStorage.setItem("gestiio_display_name",_displayName);'
            .'localStorage.setItem("i18nextLng","it-IT");'
            .'localStorage.setItem("i18nextLng-source","3");'
            .'["language","languageCode","lng","locale"].forEach(function(k){localStorage.setItem(k,"it-IT");});'
            .'try{var pr=JSON.parse(localStorage.getItem("stirlingpdf_preferences")||"{}")||{};'
            .'pr.language="it-IT";pr.locale="it-IT";pr.languageCode="it-IT";'
            .'localStorage.setItem("stirlingpdf_preferences",JSON.stringify(pr));}catch(_e){}'
            .'try{["stirling_onboarding_complete","onboardingComplete","welcomeV2Seen","hasSeenWelcome","hasCompletedOnboarding","licenseModalDismissed","stirling_license_skipped","skipLicensePrompt","desktopInstallSkipped"].forEach(function(k){localStorage.setItem(k,"true");sessionStorage.setItem(k,"true");});}catch(_e){}'
            .'var _uxCss=function(){try{'
            .'var s=document.getElementById("gestiio-stirling-ux");'
            .'if(!s){s=document.createElement("style");s.id="gestiio-stirling-ux";(document.head||document.documentElement).appendChild(s);}'
            .'s.textContent='.json_encode(
                'a[href*="stirlingpdf.com/pricing"],a[href*="server-plan"],'
                .'a[href*="/pricing"],[data-testid*="upgrade"],[data-testid*="pricing"],'
                .'[class*="UpgradeBanner"],[class*="upgrade-banner"],[class*="PricingBanner"],'
                .'a[href*="/files"],a[href*="my-files"]'
                .'{display:none!important;visibility:hidden!important;}'
            ).';'
            .'}catch(_e){}};'
            .'var _dismissUpsell=function(){try{'
            .'document.querySelectorAll("button,[role=button],a").forEach(function(b){'
            .'var t=(b.textContent||"").replace(/\\s+/g," ").trim();'
            .'if(/^(Salta per ora|Skip for now|Skip|Not now|Non ora)$/i.test(t)){b.click();}'
            .'});'
            .'document.querySelectorAll("[role=dialog],.modal,[class*=Modal],[class*=modal],[class*=Dialog]").forEach(function(m){'
            .'var t=m.textContent||"";'
            .'if(/Welcome to Stirling|Stirling V2|Licenza server|Open-Core|Upgrade to Server|Server Plan|posti illimitati|Vedi piani|See plans|\\$99/i.test(t)){'
            .'m.style.setProperty("display","none","important");'
            .'var back=m.parentElement;'
            .'if(back&&/overlay|backdrop|portal/i.test(back.className||""))back.style.setProperty("display","none","important");'
            .'}});'
            .'}catch(_e){}};'
            .'var _renameUser=function(){try{'
            .'var name=_displayName||localStorage.getItem("gestiio_display_name")||"";'
            .'if(!name)return;'
            .'var initial=(name.replace(/^\\s+/,"")||"U").charAt(0).toUpperCase();'
            .'document.querySelectorAll("span,div,p,button,a,li").forEach(function(el){'
            .'if(el.children&&el.children.length)return;'
            .'var t=(el.textContent||"").replace(/\\s+/g," ").trim();'
            .'if(t==="admin"||t==="Admin"||t==="gestiio"||/^gestiio-\\d+$/i.test(t)){'
            .'el.textContent=name;'
            .'el.setAttribute("title",name);'
            .'}});'
            .'document.querySelectorAll("[class*=avatar],[class*=Avatar],button").forEach(function(el){'
            .'var t=(el.textContent||"").replace(/\\s+/g," ").trim();'
            .'if(t==="A"||t==="a"||t==="G"){el.textContent=initial;}'
            .'});'
            .'}catch(_e){}};'
            .'var _forceIt=function(){try{'
            .'localStorage.setItem("i18nextLng","it-IT");'
            .'localStorage.setItem("i18nextLng-source","3");'
            .'if(window.i18next&&window.i18next.changeLanguage)window.i18next.changeLanguage("it-IT");'
            .'if(window.i18n&&window.i18n.changeLanguage)window.i18n.changeLanguage("it-IT");'
            .'_uxCss();_dismissUpsell();_renameUser();'
            .'}catch(_e){}};'
            .'_forceIt();setTimeout(_forceIt,200);setTimeout(_forceIt,800);setTimeout(_forceIt,2000);setTimeout(_forceIt,5000);'
            .'try{var mo=new MutationObserver(function(){_renameUser();});'
            .'if(document.body)mo.observe(document.body,{childList:true,subtree:true,characterData:true});'
            .'else document.addEventListener("DOMContentLoaded",function(){mo.observe(document.body,{childList:true,subtree:true,characterData:true});});'
            .'}catch(_e){}'
            .'}catch(e){}})();</script>';
    }

    private function gestiioDisplayName($user): string
    {
        if (! $user) {
            return 'Utente';
        }

        try {
            $label = trim((string) $user->denominazione());
            if ($label === '' || $label === ' ') {
                $label = trim((string) $user->nominativo());
            }
            if ($label === '' || $label === ' ') {
                $label = trim((string) ($user->name ?? ''));
            }
            if ($label === '') {
                $label = trim((string) ($user->email ?? ''));
            }
        } catch (\Throwable $e) {
            $label = trim((string) ($user->name ?? $user->email ?? 'Utente'));
        }

        return $label !== '' ? $label : 'Utente';
    }

    private function prependHeadScript(string $html, string $script): string
    {
        if (stripos($html, '<head>') !== false) {
            return preg_replace('/<head>/i', '<head>'.$script, $html, 1) ?: ($script.$html);
        }
        if (preg_match('/<html[^>]*>/i', $html)) {
            return preg_replace('/<html[^>]*>/i', '$0'.$script, $html, 1) ?: ($script.$html);
        }

        return $script.$html;
    }

    private function resolveStirlingBearer(Request $request): ?string
    {
        $auth = (string) $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(\S+)/i', $auth, $m)) {
            return $m[1];
        }

        $redirect = (string) ($request->server->get('REDIRECT_HTTP_AUTHORIZATION')
            ?: $request->server->get('HTTP_AUTHORIZATION')
            ?: '');
        if (preg_match('/^Bearer\s+(\S+)/i', $redirect, $m)) {
            return $m[1];
        }

        $cookie = (string) $request->cookie(self::JWT_COOKIE, '');
        if ($cookie !== '') {
            return $cookie;
        }

        return null;
    }

    private function sendUpstream(
        string $method,
        string $target,
        Request $request,
        array $headers,
        string $contentType,
        bool $stream = true
    ) {
        $pending = Http::withHeaders($headers)
            ->withOptions([
                'stream' => $stream,
                'allow_redirects' => false,
                'timeout' => (int) config('services.stirling.timeout', 300),
                'connect_timeout' => 10,
            ]);

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && $request->allFiles()) {
            $multipart = [];
            foreach ($request->except(array_keys($request->allFiles())) as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        if (! is_array($item)) {
                            $multipart[] = ['name' => $key.'[]', 'contents' => (string) $item];
                        }
                    }
                } else {
                    $multipart[] = ['name' => $key, 'contents' => (string) $value];
                }
            }
            foreach ($request->allFiles() as $key => $file) {
                $files = is_array($file) ? $file : [$file];
                foreach ($files as $uploaded) {
                    if (! $uploaded) {
                        continue;
                    }
                    $multipart[] = [
                        'name' => $key,
                        'contents' => fopen($uploaded->getRealPath(), 'r'),
                        'filename' => $uploaded->getClientOriginalName(),
                        'headers' => [
                            'Content-Type' => $uploaded->getMimeType() ?: 'application/octet-stream',
                        ],
                    ];
                }
            }
            $hdr = $headers;
            unset($hdr['Content-Type']);

            return Http::withHeaders($hdr)
                ->withOptions([
                    'stream' => $stream,
                    'allow_redirects' => false,
                    'timeout' => (int) config('services.stirling.timeout', 300),
                    'connect_timeout' => 10,
                ])
                ->asMultipart()
                ->send($method, $target, ['multipart' => $multipart]);
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $request->getContent() !== '') {
            return $pending->withBody(
                $request->getContent(),
                $contentType !== '' ? $contentType : 'application/octet-stream'
            )->send($method, $target);
        }

        return $pending->send($method, $target);
    }

    private function resolveUpstreamLocation(
        string $location,
        string $base,
        string $prefix,
        string $publicBase,
        string $publicOrigin
    ): ?string {
        $location = $this->rewritePublicLocation(
            $location,
            $base,
            $prefix,
            $publicBase,
            $publicOrigin,
            'https',
            parse_url($publicBase, PHP_URL_HOST) ?: ''
        );

        if (str_starts_with($location, $publicBase)) {
            return $base.$prefix.substr($location, strlen($publicBase)) ?: $base.$prefix.'/';
        }
        if (str_starts_with($location, $prefix)) {
            return $base.$location;
        }
        if (str_starts_with($location, $base)) {
            return $location;
        }

        return null;
    }

    private function rewritePublicLocation(
        string $value,
        string $base,
        string $prefix,
        string $publicBase,
        string $publicOrigin,
        string $publicScheme,
        string $publicHost
    ): string {
        $value = str_replace(
            [
                $base.$prefix,
                'http://stirling-pdf:8080'.$prefix,
                'https://stirling-pdf:8080'.$prefix,
                $base,
                'http://stirling-pdf:8080',
                'https://stirling-pdf:8080',
            ],
            [
                $publicBase,
                $publicBase,
                $publicBase,
                $publicOrigin,
                $publicOrigin,
                $publicOrigin,
            ],
            $value
        );

        if ($publicHost !== '') {
            $value = preg_replace(
                '#^http://'.preg_quote($publicHost, '#').'#i',
                $publicScheme.'://'.$publicHost,
                $value
            ) ?: $value;
        }

        if (str_starts_with($value, '/pdf-tools')) {
            $value = $publicOrigin.$value;
        }

        return $value;
    }
}
