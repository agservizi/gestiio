# ANALISI_MODULO_SEND

Report tecnico pre-implementazione del modulo SEND (Servizio Notifiche Digitali) in Gestiio.

## Stack rilevato

| Area | Tecnologia |
|------|------------|
| PHP | `^8.1.0` |
| Laravel | `^9.0` |
| Auth | Laravel Fortify + 2FA |
| Autorizzazione | Spatie Permission (`servizio_*` + permessi dotted admin) |
| Frontend | Blade + Metronic (KeenThemes) + Bootstrap 5 |
| JS | Mix/webpack, jQuery, Dropzone UX esistente |
| Livewire | Presente ma minimo; **non** usato per nuovi moduli documentali |
| Vue/React/Inertia/Tailwind | Non usati nell’UI gestionale |
| DB | MySQL |
| Queue | `database` (default) |
| File sensibili | `SensitiveFileService` + disk `sensitive` (privato) |
| Notifiche | Laravel Notification (mail) + `App\Models\Notifica` in-app |
| Test | PHPUnit (`tests/Unit`, `tests/Feature`) |

## Architettura rilevata

- Controllers Backend/Frontend; business logic nei moduli recenti in `app/Http/Services/`
- Actions quasi solo Fortify
- Form Request sparsi (Luggage/Locker); CAF valida inline
- Policy su moduli moderni (Luggage, Ticket, SEND)
- Enum PHP backed string nei moduli recenti
- Isolamento dati: **agente-scoped** (`created_by` / `agente_id`), non multi-tenant classico (nessun `tenant_id` / `branch_id`)

## Sistema ruoli e permessi

- Ruoli/permessi base: `admin`, `agente`, `supervisore`, `operatore` (usati anche come permission)
- Gate servizi: `servizio_*` (es. `servizio_caf_patronato`, `servizio_deposito_bagagli`)
- Attivazione agenti: `AttivaServizioController` + `ETICHETTE_SERVIZI`; alcuni solo admin (`PERMESSI_SOLO_ADMIN`)
- Middleware backend: `auth` + `role_or_permission:admin|agente|supervisore|operatore` + `2fa`

**Strategia SEND (ibrida):**

1. Gate modulo: `servizio_send` (sidebar / AttivaServizio), in `PERMESSI_SOLO_ADMIN`
2. Matrice fine: `send.access`, `send.requests.*`, `send.documents.*`, `send.notes.*`, `send.audit.view`, `send.reports.view`, `send.settings.manage`
3. Policy `SendRequestPolicy` verifica entrambe le dimensioni

## Componenti da riutilizzare

| Componente | Percorso |
|------------|----------|
| CAF pratica + allegati | `CafPatronatoController`, `CafPatronato`, `AllegatoCafPatronato` |
| Luggage Policy/Service/Enum | `LuggageDepositPolicy`, `LuggageDepositService`, `LuggageDepositStatus` |
| Ticket status history | `TicketStatusLog` |
| File sensibili | `SensitiveFileService`, `config/security_files.php` |
| Dropzone UX | `resources/views/Backend/_components/dropzoneUx.blade.php` |
| CF validation | `App\Rules\CodiceFiscaleRule` |
| Sidebar / CTA agente | `app-sidebar-menu.blade.php`, `Dashboard/showAgente.blade.php` |
| Seed permesso | pattern `2026_07_12_200000_seed_servizio_deposito_bagagli_permission.php` |

## Tabelle esistenti coinvolte

- `users` (creatore, supervisore assegnato)
- `permissions` / `roles` / `role_has_permissions` / `model_has_permissions`
- Nessuna tabella anagrafica obbligatoria riusata: soggetti SEND in `send_request_subjects`

## Strategia di implementazione

1. Documentazione analisi (questo file)
2. Config + migrations + seed permessi
3. Domain: models, enums, policy, services, ManualSendProvider
4. HTTP: controller, form requests, routes, download protetto
5. UI Metronic: dashboard, lista, wizard, dettaglio, coda supervisore
6. Notifiche + audit
7. Docs operative/security
8. Test feature + review

Provider esterno: solo stub `ManualSendProvider`; nessuna API SEND ufficiale.

## Rischi tecnici

- Matrice `send.*` più granulare del resto del prodotto → seed chiaro + policy centralizzata
- Allegati identità: obbligatorio storage privato e download gated
- Assenza multi-sede: scope solo owner / view-all / coda supervisore
- SLA interni ≠ scadenze legali SEND (etichettare chiaramente in UI/docs)
- Retention: solo config, nessuna cancellazione automatica senza OK titolare
- Concorrenza: numerazione e assegnazione con lock/transazioni

## Dipendenze da installare

Nessuna. Riuso `robertogallea/laravel-codicefiscale` già in `composer.json`.

## File creati / modificati (previsti)

### Creati

- `ANALISI_MODULO_SEND.md`
- `config/send.php`
- `database/migrations/2026_07_18_100000_create_send_module_tables.php`
- `database/migrations/2026_07_18_100001_seed_servizio_send_permissions.php`
- `app/Enums/SendRequestStatus.php`, `SendApplicantType.php`, `SendPriority.php`, `SendDocumentCategory.php`, `SendNoteVisibility.php`
- `app/Models/SendRequest.php` + related models
- `app/Policies/SendRequestPolicy.php`
- `app/Contracts/SendProviderInterface.php`
- `app/Http/Services/Send/*.php` e services SEND
- `app/Http/Controllers/Backend/SendRequestController.php`
- `app/Http/Requests/Send/*.php`
- `app/Notifications/NotificaSend*.php`
- `resources/views/Backend/Send/*.blade.php`
- `docs/SEND_MODULE.md`, `docs/SEND_USER_GUIDE.md`, `docs/SEND_SECURITY.md`
- `tests/Feature/Send/*`

### Modificati

- `app/Providers/AuthServiceProvider.php`
- `app/Http/Controllers/Backend/AttivaServizioController.php`
- `routes/web-backend.php`
- `resources/views/Backend/_layout/app-sidebar-menu.blade.php`
- `resources/views/Backend/Dashboard/showAgente.blade.php`
- `resources/views/Backend/Dashboard/showSupervisore.blade.php` (se presente)
- `.env.example`

## Conclusione

Il modulo SEND si allinea allo stack esistente senza introdurre tecnologie nuove. Pattern primario: Luggage (authz moderna) + CAF (pratica documentale) + Ticket (storico stati).
