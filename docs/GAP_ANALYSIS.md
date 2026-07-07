# Gap Analysis — Gestiio

Analisi globale del progetto (2026-07-07) per individuare cosa manca prima di considerarlo "finito". Gap concreti, non consigli generici — ognuno ha file/percorso di riferimento.

## Deploy / Infrastruttura

- [ ] `.github/workflows/deploy.yml` punta a `/Volume1/homes/Carmine/gestiio-latest-deploy`, una directory scollegata dal container reale in produzione (`/home/Carmine/apps/gestiio-20260624-2128`). La pipeline non può funzionare così com'è.
- [ ] Nessun secret configurato su GitHub (`NAS_HOST`, `NAS_USER`, `NAS_SSH_KEY`) — il deploy automatico via Actions non può partire.
- [ ] Password DB in chiaro in `docker-compose.nas.yml` (root/app), non gestite da secrets/`.env` — da rimuovere e sostituire con variabili d'ambiente iniettate a runtime.
- [ ] Manca un `.env.example` valido: esiste solo `.env_example` (nome non standard, non riconosciuto da Laravel/tooling CI), e copre solo ~50 variabili contro le 200+ referenziate in `config/*.php` (`BRT_*`, `INPOST_*`, `N8N_*`, `OPENAPI_VISURE*`/`OPENAPI_CATASTO*`, `SENTRY_*`, `WEBPUSH_VAPID_*`, `RESEND_*`, ecc.). Un nuovo dev non può bootstrappare l'app da zero.

## Test

- [ ] Copertura ~17%: 35 file di test (20 Unit + 15 Feature) contro 118 model e 82 controller.
- [ ] 0 controller Frontend testati (7 controller, nessun test Feature).
- [ ] Zero test per le feature più recenti: soglia minima portafoglio (`MovimentoPortafoglio`, `NotificaSogliaMinimaPortafoglio`), `RichiestaSpostamentoPortafoglio`, `RegistroAttivita`, `SettingsAuditLog`, `PerformanceTracker`.
- [ ] CI (`.github/workflows/ci.yml`) rischia di fallire nella pratica:
  - lo step `cp .env.example .env || true` fallisce silenziosamente perché il file non esiste (vedi sopra) — `.env` potrebbe non esistere per i test.
  - `key:generate`/`route:list`/`php artisan test` possono fallire a cascata di conseguenza.
  - nessun `pdo_sqlite` richiesto nel setup PHP, mentre `phpunit.xml` forza `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.
  - nessuno step di migration prima di `php artisan test`.
- [ ] `phpunit.xml` (solo root, manca `.dist`) contraddice `CLAUDE.md`, che dichiara MySQL come default — la documentazione è disallineata dalla configurazione reale.
- [ ] `database/migrations/2022_11_12_180953_edit_to_produzione_operatori.php` non è compatibile con SQLite (`ColumnDoesNotExist: importo_totale` su `produzioni_operatori` via Doctrine DBAL) — blocca `RefreshDatabase`/`php artisan test` per **l'intera suite**, non solo per questa tabella. Verificato eseguendo la suite in locale (nessun MySQL disponibile).
- [ ] Larastan/PHPStan sono nelle dipendenze (`composer.json`) ma:
  - manca `phpstan.neon`/`.dist`,
  - nessuno script composer per lanciarlo,
  - non è in CI — l'unica traccia è un `phpstan-report.txt` generato manualmente una volta (24 giugno), mai più aggiornato.

## Qualità codice / dead code

- [ ] 27+ controller hanno il check di eliminabilità stubbato: `if (false) { $eliminabile = '...'; } else { $eliminabile = true; }` (es. `ClienteController.php:185`, `PortafoglioController.php:453`, `ContrattoTelefoniaController.php:375,403`). La verifica reale delle dipendenze prima della cancellazione non è mai stata implementata — si può sempre cancellare un record anche quando le regole di business dovrebbero impedirlo.
- [ ] ~10 blocchi `catch (\Throwable $e) {}` vuoti che inghiottono errori senza log: `CartellaFilesController.php` (4 occorrenze), `DashboardController.php`, `ModalController.php` (3), `RegistriController.php`, `SpedizioneInpostController.php`, `SpedizioneBrtController.php`.
- [ ] Numerosi blocchi Blade `@if(false)` con sezioni UI disattivate invece che rimosse (menu sidebar, topbar agente, viste prodotti Sky, layout frontend) — codice morto che va ripulito o riattivato consapevolmente.

## Osservabilità

- [ ] `PerformanceTracker` / `LogSlowRequests` middleware raccolgono dati ma non esiste una dashboard o un alert collegato — i dati non vengono sfruttati.

## Note

- Il feature "soglia minima portafoglio" (dropdown + toast + email, admin/agente) è stato implementato e deployato in produzione tramite deploy manuale via SSH diretto (bypassando la pipeline GitHub Actions, che resta da correggere).
- Backup pre-deploy disponibile su NAS: `/home/Carmine/apps/gestiio-backup-20260706-212215`.
- Implementato modulo **Ebike B2B** (catalogo, ordini, bonifico istantaneo con verifica admin, tracking spedizione, SLA 10gg, permesso `ebike-b2b` abilitabile per singolo agente dal profilo in admin). Migrations validate in isolamento su SQLite in-memory (OK); non è stato possibile eseguire `php artisan test` end-to-end in questo ambiente perché l'intera suite viene bloccata da una migration preesistente e scollegata (`2022_11_12_180953_edit_to_produzione_operatori`, colonna `importo_totale` non trovata sotto SQLite) — stesso gap già segnalato sopra in "Test", non introdotto da questa feature. Nessun MySQL locale disponibile in questo ambiente per una verifica alternativa.
