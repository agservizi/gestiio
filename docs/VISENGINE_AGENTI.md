# Visengine per-agente (modulo Visure)

Integrazione [Visengine](https://console.openapi.com/it/apis/visengine/info) sul modulo Visure di Gestiio, usando l’ecosistema [Openapi su GitHub](https://github.com/openapi/).

## Componenti

| Pezzo | Ruolo |
|-------|--------|
| `openapi/openapi-sdk` | Client HTTP + `OauthClient` |
| `agenti.openapi_email` + `openapi_api_key` | Credenziali **account Openapi dell’agente** (wallet proprio, solo admin) |
| `agenti.openapi_visure_token` / `openapi_catasto_token` | Bearer scoped **per agente** (solo admin) |
| `OPENAPI_EMAIL` / `OPENAPI_USERNAME` + `OPENAPI_API_KEY` / `OPENAPI_KEY` | Credenziali **account piattaforma** (catalogo/hash + banner admin) |
| `App\Http\Services\OpenApi\OpenApiPlatformClient` | Sync credito wallet piattaforma **o** per-agente |
| `App\Http\Services\OpenApiVisureService` | Create/poll/documento Visengine via SDK |
| Skill locali `npx skills add openapi/openapi-skills` | Solo tooling agente (non su NAS) |

## Flusso create (ibrido)

1. Verifica `portafoglio_visure >= prezzo_agente` (listino Gestiio).
2. Se mancano email + API key Openapi sul profilo → `openapi_stato_richiesta = backoffice`.
3. Se manca Bearer Visengine/Catasto per il tipo → backoffice.
4. Altrimenti: gate credito sul **wallet Openapi dell’agente** (non sul wallet piattaforma) + chiamata Visengine col Bearer agente.
5. Addebito Gestiio **sempre** su `portafoglio_visure` (anche backoffice).

Gli agenti **non condividono** il wallet Openapi: ogni create automatica addebita l’account Openapi configurato sul profilo.

## Admin

- Profilo agente: email + API key Openapi + Bearer Visengine/Catasto.
- Elenco Visure: filtro “Solo coda backoffice”, KPI, badge; banner credito piattaforma solo monitoraggio.
- Su pratica backoffice: pulsante **Riprova Openapi** (richiede credenziali account + Bearer + credito agente).

## Env NAS / locale

```env
OPENAPI_EMAIL=
OPENAPI_USERNAME=
OPENAPI_API_KEY=
OPENAPI_KEY=
OPENAPI_SANDBOX=false
# opzionale override OAuth host (default https://oauth.openapi.com)
# OPENAPI_OAUTH_URL=https://oauth.openapi.com
```

Bearer legacy `OPENAPI_BEARER_VISURE` resta per sync hash tipi; le create agente usano token + wallet sul profilo.

## Comandi

```bash
php artisan visure:poll-openapi --limit=100
php artisan visure:sync-openapi-credit
php artisan visure:sync-openapi-credit --agente=123
```

Poll e sync piattaforma schedulati ogni 5 minuti. Il poll **salta** le pratiche `backoffice`.

## Prezzi

`TipoVisura.prezzo_agente` / `prezzo_cliente` = listino **Gestiio**, indipendente dal listino Openapi.

## Riferimenti

- [openapi-php-sdk](https://github.com/openapi/openapi-php-sdk)
- [openapi-cli](https://github.com/openapi/openapi-cli) (generazione Bearer scoped)
- [openapi-skills](https://github.com/openapi/openapi-skills)
- Skill `openapi-auth` (OAuth/wallet) e `openapi-documents` (Visengine)
