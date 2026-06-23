# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Gestiio — Sistema di Gestione Servizi Aziendale

Applicazione Laravel complessa per gestire servizi aziendali in multiple vertenti: contratti energia, SIM, visure camerali, CAF/Patronato, ticketing, e gestione interna (chat, dashboard, export dati).

**111 Models | Frontend + Backend | Livewire | 2FA | Web Push | Spatie Permission**

## Architettura

### Layers Principali

| Layer | Descrizione | Locations |
|-------|-------------|-----------|
| **Models** | 111+ modelli Eloquent mappano la logica aziendale (Contratto, Servizio, Ticket, etc.) | `app/Models/` |
| **Actions** | Business logic per operazioni complesse (Fortify auth, TwoFactor, custom workflows) | `app/Actions/` |
| **Services** | Coordinamento di logica trasversale (notifiche, export, integrazioni) | `app/Http/Services/` |
| **Controllers** | Handler HTTP divisi in Frontend (utenti) e Backend (staff) | `app/Http/Controllers/{Frontend,Backend}/` |
| **Livewire** | Componenti dinamici per UI interattiva (form, list, datatables) | `app/Http/Livewire/` + `resources/views/livewire/` |
| **Rules** | Custom validation rules (Spatie Permission, CodiceFiscale, etc.) | `app/Rules/` |
| **Policies** | Gate-based authorization per azioni su Model | `app/Policies/` |
| **Jobs** | Async tasks (Web Push notifications) | `app/Jobs/` |
| **Listeners** | Event subscribers (audit log, notifications) | `app/Listeners/` |
| **Notifications** | 53+ notification channels (Mail, SMS, Push, Chat) | `app/Notifications/` |

### Frontend vs Backend

```
Route '/' → redirects to /backend (staff) or /area-personale (users)
├── Frontend (user-facing)
│   ├── /area-personale               - user dashboard
│   ├── /area-personale/contratti     - user contracts
│   ├── /ticket                       - ticket management (CRUD)
│   └── /contratto-energia/documenti  - energy contract documents
└── Backend (staff-only, role-gated)
    ├── /backend                      - staff dashboard
    ├── Middleware: auth + role_or_permission + 2fa
    └── Routes aliased without /backend prefix (legacy compat)
```

Ruoli staff: `admin`, `agente`, `supervisore`, `operatore` — controllati da Spatie Permission.

### Views Structure

```
resources/views/
├── Frontend/           # User-facing pages
│   ├── Home, AreaUtente, Contratto, ContrattoEnergia, Ticket
│   └── _layout/        # Shared Frontend layout
├── Backend/            # Staff dashboards (20+ controllers worth)
│   ├── Tickets, Profilo, Gestore, ProduzioneOperatore, etc.
│   └── [one dir per resource]
├── auth/               # Login/Register (Fortify)
├── Mail/               # Email templates
└── livewire/           # Livewire component templates
```

## Comandi Essenziali

### Sviluppo & Build
```bash
npm run dev             # One-off asset build (dev)
npm run watch           # Watch assets, rebuild on file change
npm run hot             # Hot reload (if configured)
npm run prod            # Production-optimized build

php artisan serve       # Start dev server (http://localhost:8000)
```

### Database
```bash
php artisan migrate              # Apply pending migrations
php artisan migrate:rollback     # Undo last batch
php artisan migrate:refresh      # Rollback + migrate (resets DB!)
php artisan db:seed              # Run seeders
php artisan tinker               # Interactive PHP shell
```

### Testing
```bash
php artisan test                  # Run all Unit + Feature tests
php artisan test tests/Unit       # Only Unit tests
php artisan test tests/Feature    # Only Feature tests
php artisan test --filter=UserTest    # Single test class
```

### Queue & Notifications
```bash
php artisan queue:work            # Start queue worker (listens for jobs)
php artisan queue:failed          # List failed jobs
php artisan queue:retry [id]      # Retry a failed job
```

### Cache & Config
```bash
php artisan cache:clear                   # Clear app cache
php artisan config:clear                  # Clear config cache
php artisan route:cache                   # Cache routes (prod optimization)
php artisan permission:cache-reset        # Reset Spatie Permission cache (after permission changes)
```

### Maintenance
```bash
php artisan backup:run                    # Trigger database backup (Spatie)
php artisan db-snapshots:create {name}    # Create named DB snapshot (Spatie)
php artisan db-snapshots:load {name}      # Load snapshot (dev restore)
```

## Key Model Groups

**111 models organized by business domain:**
- **Contracts**: `Contratto`, `ContrattoEnergia`, `AllegatoContratto`, `ContratoStato`, etc.
- **Services**: `Servizio`, `Agente`, `Operatore`, `Supervisore`
- **Customers**: `User`, `Azienda`, `Persona`, `RagioneSociale`
- **Products**: `ProdottoWindtre`, `ProdottoEnergiaEgea`, `Licenza`, `AttivazioneSim`
- **Tickets**: `Ticket`, `ChatMessage`, `ChatThread`, `CausaleTicket`, `AllegatoMessaggioTicket`
- **Compliance**: `VisuraCamerale`, `CafPatronato`, `EsitoSegnalazione`
- **Files**: `CartellaFiles`, `FileAuditLog`, `Allegato*`

Relationships heavily use foreign keys + many-to-many. Check Model methods for `belongsTo()`, `hasMany()`, etc. before writing queries.

## Special Behaviors

### Authentication & Authorization
- **Fortify Actions** (app/Actions/Fortify/) → handle login/registration logic
- **2FA**: TwoFactor Actions verify OTP → stored in session via `sendOtpEmail()`
- **Impersonation**: `/stop-impersona` restores original user from `session('impersona')`
- **Role Gating**: Middleware `role_or_permission:admin|agente|supervisore|operatore` + `2fa` required for `/backend`

### Web Push Notifications
- **Queue Job**: `SendChatWebPushNotification` in `app/Jobs/`
- **Setup**: VAPID keys in `.env`, Service Worker in `public/sw-chat-push.js`
- **Trigger**: Events dispatch notification jobs; queue worker processes them
- **Scope**: Push only works HTTPS or localhost

### Validation
- Custom rules in `app/Rules/` (not generic Laravel rules)
- Example: `Rules/CodiceFiscale.php` for Italian tax IDs
- Form Requests in `app/Http/Requests/` → define `rules()` method

### Exports & GDPR
- `Exports/` directory handles Excel/PDF generation
- `AreaPersonaleController::exportDatiPersonali()` exports user data (GDPR right to data)

### Helpers
- `app/helper.php` → loaded autoload-dev (PHPUnit + Tinker access)
- `app/Http/HelperForBlade.php`, `HelperForMetronic.php`, `HelperProgetto.php` → utilities for views

## PHPUnit Configuration

- **Test Suites**: Unit (`tests/Unit/`) and Feature (`tests/Feature/`) separate directories
- **DB**: Uses MySQL (DB_CONNECTION=mysql in .env); tests run against configured DB by default
  - Optionally set `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` in phpunit.xml for in-memory tests
- **Mail**: `MAIL_MAILER=array` during tests (no external sending)
- **Queue**: `QUEUE_CONNECTION=sync` (jobs execute immediately, no worker needed)
- **Session**: `SESSION_DRIVER=array` (stateless during tests)

## Git & Deployment Notes

- Uses Laravel Backup (Spatie) → backups stored in `storage/app/backups/`
- Routes lazy-load Livewire components, so check `app/Http/Livewire/` when features don't load
- Middleware stacked in `app/Http/Kernel.php` → order matters (auth before 2fa)

## Common Pitfalls

1. **Forgetting `permission:cache-reset`** after editing permissions → changes don't apply until cache clears
2. **Livewire Components**: Must match kebab-case directory names to `@livewire('component-name')` syntax
3. **Model Relationships**: With 111 models, eager-loading is critical — use `with()` to prevent N+1 queries
4. **Fortify Auth**: Custom logic is in Actions, not the standard Laravel login flow
5. **Queue Worker**: Must be running for Web Push; dev can test with `QUEUE_CONNECTION=sync`

---

*Configurato per: Claude Code, Cursor, ChatGPT v2.0*  
*Ultima modifica: 2026-06-23*

## Comandi Comuni

### Sviluppo
```bash
npm run dev           # Build assets in dev mode
npm run watch         # Rebuild assets on file change
npm run hot           # Hot reload (se configurato)
php artisan serve     # Start dev server (localhost:8000)
```

### Database
```bash
php artisan migrate                     # Run migrations
php artisan migrate:rollback            # Rollback last migration
php artisan db:seed                     # Seed database
php artisan tinker                      # Interactive shell
```

### Queue (per Web Push e background jobs)
```bash
php artisan queue:work                  # Start queue worker
php artisan queue:work --tries=3        # Con retry limit
```

### Testing
```bash
php artisan test                        # Run all tests
php artisan test --filter=ModelTest     # Run specific test
```

### Cache & Config
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:cache
php artisan permission:cache-reset      # Spatie Permission cache
```

## Convenzioni Importanti

### Naming
- **Models**: PascalCase (es. `User`, `BlogPost`)
- **Controllers**: PascalCase + `Controller` (es. `UserController`)
- **Migrations**: snake_case con timestamp (auto-generato)
- **Livewire Components**: kebab-case in directory (es. `user-table`)
- **Routes**: kebab-case (es. `/user-profile`)

### Permessi (Spatie)
- I permessi sono cachati: ricordati di fare `php artisan permission:cache-reset` dopo modifiche
- Usa `@can('permission-name')` in Blade e `$user->can('permission-name')` in PHP

### Web Push Setup
Se modifichi le notifiche push:
1. Generare chiavi VAPID: `php artisan chat:generate-vapid-keys`
2. Aggiungere al `.env`: `WEBPUSH_VAPID_PUBLIC_KEY` e `WEBPUSH_VAPID_PRIVATE_KEY`
3. Avviare queue worker: `php artisan queue:work`
4. Push funzionano solo in HTTPS o localhost

### Migrazioni
- **Naming convention**: descrive cosa cambia nel schema (es. `2024_01_15_create_users_table`)
- Usa `nullable()` con cautela (NULL in database != assente)
- Sempre add `timestamps()` a meno che non sia una junction table
- Usa `$table->foreignId('user_id')->constrained()` per FK pulite

## FAQ Sviluppo

### Come aggiungere una nuova pagina?
1. Creare una Migration (se serve nuova tabella): `php artisan make:migration create_posts_table`
2. Creare un Model: `php artisan make:model Post --migration` (include migration)
3. Creare un Controller: `php artisan make:controller PostController --model=Post`
4. Aggiungere rotta in `routes/web.php`
5. Creare view in `resources/views/posts/` con Blade template
6. Se è form dinamica: usare Livewire `php artisan livewire:make post-form`

### Come aggiungere validazione?
```bash
php artisan make:request StorePostRequest
# In app/Http/Requests/StorePostRequest.php metti le regole
# Usa in Controller: public function store(StorePostRequest $request)
```

### Come aggiungere Test?
```bash
php artisan make:test PostTest          # Unit test
php artisan make:test PostTest --feature # Feature test (include HTTP)
```

### Database ha molti dati e vedo problemi?
```bash
php artisan tinker
# Dentro tinker:
>>> Post::chunk(1000, function ($posts) { /* processa */ });
>>> Post::where('old', true)->delete();
```

## File Critici

| File | Scopo |
|------|-------|
| `.env` | Variabili ambiente (DB, STRIPE_KEY, etc.) |
| `composer.json` | Dipendenze PHP |
| `package.json` | Dipendenze JavaScript |
| `config/app.php` | Configurazione application |
| `config/database.php` | Configurazione database |
| `routes/web.php` | Definizione rotte web |
| `app/Models/User.php` | Modello User base |

## Link Utili

- [Laravel Docs](https://laravel.com/docs/9.x)
- [Livewire Docs](https://laravel-livewire.com/docs)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Stripe PHP](https://stripe.com/docs/api)

---

**Ultima modifica**: 2026-06-23  
**Configurato per**: Claude Code, Cursor, ChatGPT
