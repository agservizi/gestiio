# Piano di Implementazione — Gestiio

Basato su analisi del codebase (2026-06-23). Identifica gap critici e propone timeline per colmarli.

## 📊 Stato Attuale vs Ideale

| Aspetto | Stato | Target | Gap |
|---------|-------|--------|-----|
| Test Coverage | 0% (4 boilerplate) | 70%+ | Critico |
| Static Analysis | ✗ | ✓ (Pint + Larastan) | Critico |
| CI/CD Pipeline | ✗ | ✓ (GitHub Actions) | Critico |
| Error Tracking | ✗ | ✓ (Sentry) | Alto |
| API Documentation | ✗ | ✓ (OpenAPI/Swagger) | Alto |
| Type Safety | ⚠ Parziale | ✓ Strict | Medio |
| Logging Strategy | ⚠ Minimo | ✓ Structured | Medio |
| Cache Strategy | ⚠ Ad-hoc | ✓ Organized | Basso |

---

## 🚀 Piano in 4 Fasi (Progressione Logica)

### FASE 1: Foundation (Settimane 1-2) — Setup Automation
**Goal**: Rendere il codice "lintable" e "testable"  
**Effort**: ~20h

#### 1.1 Installare Laravel Pint (PHP Linter)
```bash
composer require --dev laravel/pint
php artisan pint:install
php artisan pint --test          # Dry-run, non modifica
php artisan pint                 # Applica fix
```
- **Deliverable**: `.pint.json` con regole coerenti
- **Nota**: Escludi `vendor/`, `storage/`, `bootstrap/cache/`
- **Tempo**: 2-3h

#### 1.2 Installare PHPStan + Larastan
```bash
composer require --dev phpstan/phpstan phpstan/extension-installer nunomaduro/larastan
php artisan code:analyse         # Scan codebase
```
- **Deliverable**: `phpstan.neon` configurato (level 5)
- **Baseline**: Genera baseline per ignorare warning pre-esistenti
- **Tempo**: 3-4h

#### 1.3 Setup GitHub Actions (CI Pipeline)
Crea `.github/workflows/tests.yml`:
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: php artisan pint --test
      - run: php artisan code:analyse
      - run: php artisan test
```
- **Deliverable**: CI che corre su ogni push
- **Tempo**: 2-3h

#### 1.4 Setup Pre-commit Hooks (Locale)
```bash
composer require --dev captainhook/captainhook
php artisan vendor:publish --provider="CaptainHook\\CaptainHookProvider"
```
- **Deliverable**: Hooks che eseguono pint + phpstan prima di commit
- **Tempo**: 1-2h

**Checkpoint**: Tutti i PR futuri hanno lint + static analysis automatici

---

### FASE 2: Testing Foundation (Settimane 3-4) — Build Test Suite
**Goal**: Coprire 70% del codice critico  
**Effort**: ~35h

#### 2.1 Setup Test Infrastructure
- Creare `tests/TestCase.php` base con factory + seeder
- Setup `RefreshDatabase` trait per isolation
- Creare `tests/Factories/` per model factories
- **Deliverable**: Base test infrastructure  
- **Tempo**: 3-4h

#### 2.2 Test Models (Priorità Alta)
Coprire i 20 modelli critici:
- `User`, `Contratto`, `ContrattoEnergia`, `Ticket`, `ChatMessage`
- `Agente`, `Servizio`, `VisuraCamerale`, `CafPatronato`
- Test: relazioni, scopes, mutators, custom methods
```bash
php artisan make:test Models/UserTest
php artisan make:test Models/ContrattoTest
# ... etc per 20 modelli
```
- **Deliverable**: 20 test model files con 3-5 test cases cada  
- **Tempo**: 12-15h

#### 2.3 Test Actions (Fortify + TwoFactor)
Coprire i workflow critici di auth:
- `Actions/Fortify/CreateNewUser`
- `Actions/Fortify/ResetUserPassword`
- `Actions/TwoFactor/EnableTwoFactorAuthentication`
- `Actions/TwoFactor/ConfirmTwoFactorAuthentication`
- Test: validazione, edge cases, session handling
- **Deliverable**: 6 test action files  
- **Tempo**: 8-10h

#### 2.4 Test Controllers (High-Value)
Coprire i 10 controller critici:
- `Frontend/AreaUtenteController`
- `Frontend/TicketController`
- `Backend/TicketController` (staff view)
- `Backend/ChatController`
- `Frontend/ContrattoEnergiaDocumentiController`
- Test: CRUD, authorization, edge cases
- **Deliverable**: 10 controller test files  
- **Tempo**: 12-15h

**Checkpoint**: `php artisan test` runs 60+ tests, coverage ~50% per core modules

---

### FASE 3: Observability & Reliability (Settimane 5-6) — Add Monitoring
**Goal**: Tracciare errori, perf, e business events  
**Effort**: ~25h

#### 3.1 Setup Sentry (Error Tracking)
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish-config

# In .env:
# SENTRY_LARAVEL_DSN=https://xxx@sentry.io/xxx
# SENTRY_ENVIRONMENT=production
```
- Configurare `config/sentry.php`: environment, trace_sample_rate (10%), ignored_exceptions
- **Deliverable**: Errori tracciati in Sentry  
- **Tempo**: 3-4h

#### 3.2 Structured Logging
Implementare logging strategy per business events:
```php
// In Services/NotificationService.php, etc:
Log::info('web_push_sent', [
    'user_id' => $userId,
    'thread_id' => $threadId,
    'result' => 'success',
    'duration_ms' => $duration,
]);
```
- Creare `app/Logging/StructuredLogger.php` helper
- Loggare: auth events, payment events (Stripe), ticket creation, export GDPR
- **Deliverable**: Structured logs in storage/logs/  
- **Tempo**: 5-6h

#### 3.3 Cache Strategy
Identificare e cachare:
- User permissions (already done by Spatie, validate it works)
- Frequently accessed configs
- Popular search results (azienda names, visure results)
```php
// In Model scopes:
Cache::remember("azienda:{$id}", 3600, fn() => $this->with(...)->get());
```
- **Deliverable**: Cache invalidation + usage documented  
- **Tempo**: 4-5h

#### 3.4 Slow Query Detection
Abilitare Laravel Debugbar in dev:
```php
// In config/debugbar.php:
'enabled' => env('DEBUGBAR_ENABLED', env('APP_DEBUG')),
```
- Integrare query logging nel Sentry (breadcrumb)
- **Deliverable**: Query > 500ms visible in Sentry + logs  
- **Tempo**: 2-3h

#### 3.5 Performance Baseline
Creare benchmark per pagine critiche:
```bash
php artisan tinker
>>> time_ms { AreaUtenteController->show(); }
>>> time_ms { TicketController->index(); }
```
- Documentare query count per pagina (N+1 audit)
- **Deliverable**: `docs/PERFORMANCE_BASELINE.md`  
- **Tempo**: 3-4h

**Checkpoint**: Errori in Sentry, structured logs in S3/CloudWatch, performance baseline

---

### FASE 4: Documentation & Knowledge (Settimane 7-8) — Complete Docs
**Goal**: Documentare architectural decisions + critical workflows  
**Effort**: ~20h

#### 4.1 Auth/2FA/Impersonation Workflow
Creare `docs/AUTH_WORKFLOW.md`:
```markdown
# Authentication Workflow

## Login Flow
1. User POST /login → RegistratiController
2. Fortify validates → CreateNewUser Action
3. Session set → check role → redirect /backend or /area-personale

## 2FA Flow
1. After login → TwoFactorController
2. sendOtpEmail Action → email with OTP code
3. User POST /confirm-otp → ConfirmTwoFactor Action
4. Session '2fa_verified' set

## Impersonation (Support)
- `/login-id/{id}` (dev only) → loginUsingId
- `session('impersona')` stores original user
- `/stop-impersona` → restore session('impersona'), loginUsingId(original)
```
- Includi sequence diagrams (ASCII o .drawio)
- **Tempo**: 4-5h

#### 4.2 Web Push & Queue Workflow
Creare `docs/WEB_PUSH_WORKFLOW.md`:
```markdown
# Web Push Notification Workflow

## Setup
1. Generate VAPID keys: php artisan chat:generate-vapid-keys
2. Set WEBPUSH_VAPID_PUBLIC_KEY, WEBPUSH_VAPID_PRIVATE_KEY
3. Start queue worker: php artisan queue:work

## Trigger → Delivery
1. ChatMessage created → ChatMessageCreated event
2. Event listener → SendChatWebPushNotification job enqueued
3. Queue worker → send via minishlink/web-push
4. Service Worker (public/sw-chat-push.js) receives → displays notification
```
- Documentare VAPID key rotation procedure
- Tempo: 3h

#### 4.3 Database Schema Documentation
Creare `docs/DATABASE_SCHEMA.md`:
```markdown
# Core Tables & Relationships

## users
- id, email, name, phone, created_at
- Relations: hasMany(Contratto), hasMany(Ticket), hasMany(ChatMessage)
- Indexes: email (unique), phone

## contratti (111+ models → pick top 15)
- Detailed schema per table
- Foreign keys diagrammed
- Business rules (e.g., "ContrattoEnergia only if servizio_id = 3")
```
- Use Laravel Migrations as source of truth
- **Tempo**: 4h

#### 4.4 API Endpoints Documentation
Se `/api/` sarà esposto, creare `docs/API.md`:
```markdown
# API Endpoints

## GET /api/contratti
Returns paginated contracts for authenticated user
```
- Or generate OpenAPI spec from routes (use openapi3 generator)
- **Tempo**: 3-4h (if applicable)

#### 4.5 Update CLAUDE.md with Production Notes
Aggiungere sezioni:
- **Production Checklist**: env vars, backups, queue monitoring
- **Troubleshooting**: common issues (permission cache, queue hung, etc.)
- **Scaling Notes**: database indexes, caching strategy
- **Tempo**: 2h

**Checkpoint**: Documentazione completa, wikis, runbooks

---

## 📈 Priority Ranking & Timeline

### Quick Wins (1-2h each)
1. ✅ Laravel Pint setup + run
2. ✅ Sentry integration
3. ✅ GitHub Actions basic workflow

### High-Value (3-5h each)
4. ⭐ PHPStan setup
5. ⭐ Test infrastructure + first 3 model tests
6. ⭐ Pre-commit hooks
7. ⭐ Structured logging for 5 key workflows

### Medium-Term (1-2 weeks)
8. Test Models (20 files)
9. Test Actions (auth workflows)
10. Cache strategy
11. Documentation (workflows, schema)

### Nice-to-Have (Later)
12. Test Controllers (10 files)
13. API documentation
14. Performance dashboards
15. Custom exception classes

---

## 🎯 Recommended Execution Order

**Week 1 (Parallel)**:
- Monday: Pint + PHPStan install & fix violations (4h)
- Tuesday-Wednesday: GitHub Actions + pre-commit hooks (4h)
- Thursday-Friday: Sentry setup + basic structured logging (3h)

**Week 2**:
- Test infrastructure + 5 model tests (8h)
- Actions tests (Fortify, TwoFactor) (6h)

**Week 3-4**:
- 15 more model tests (12h)
- Slow query audit (3h)

**Week 5**:
- Cache strategy (4h)
- Workflow documentation (Auth, Web Push) (5h)
- Performance baseline (3h)

**Week 6**:
- Database schema doc (4h)
- Update CLAUDE.md (2h)
- Buffer (2h)

**Total Estimate**: 60-70 engineering hours (~1.5-2 weeks full-time, or 4-6 weeks part-time)

---

## 🛠 Tools to Install

```bash
# Code Quality
composer require --dev laravel/pint
composer require --dev phpstan/phpstan phpstan/extension-installer nunomaduro/larastan

# Error Tracking
composer require sentry/sentry-laravel

# Pre-commit Hooks
composer require --dev captainhook/captainhook

# Already Present:
# - phpunit (testing)
# - laravel/debugbar (dev profiling)
# - spatie/laravel-backup (backups)
```

---

## 📝 Files to Create

```
.github/
└── workflows/
    └── tests.yml              # CI pipeline

.pint.json                      # Pint linter config
phpstan.neon                    # Static analysis config
.git/hooks/pre-commit           # Pre-commit script (auto-generated)

docs/
├── AUTH_WORKFLOW.md            # Login, 2FA, impersonation flow
├── WEB_PUSH_WORKFLOW.md        # Queue → notification delivery
├── DATABASE_SCHEMA.md          # Table relationships
├── PERFORMANCE_BASELINE.md     # Query benchmarks
└── PRODUCTION_CHECKLIST.md     # Deploy & ops

config/
├── sentry.php                  # (auto-published)
└── debugbar.php                # (already present)

app/Logging/
└── StructuredLogger.php        # Helper for consistent logging
```

---

## ✅ Success Criteria

- [ ] `php artisan pint --test` passes 100%
- [ ] `php artisan code:analyse` has 0 errors (level 5)
- [ ] GitHub Actions workflow runs on every push
- [ ] `php artisan test` runs 60+ tests, zero failures
- [ ] Sentry captures errors in production
- [ ] All critical workflows documented (Auth, 2FA, Web Push)
- [ ] Performance baseline established (N+1 queries identified)
- [ ] Cache strategy implemented for top 5 queries
- [ ] Code review checklist includes lint + test checks

---

**Status**: Plan draft, ready for refinement  
**Next Step**: Prioritize tasks with stakeholders, assign owners, start Week 1
