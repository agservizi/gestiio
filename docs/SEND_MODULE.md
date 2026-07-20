# SEND — Servizio Notifiche Digitali (modulo interno Gestiio)

## Descrizione

Workflow interno per gestire allo sportello le richieste relative al servizio SEND (punti di ritiro / notifiche digitali), senza integrazione automatica con i sistemi ufficiali SEND.

Riferimento operativo interno allo sportello (nessuna integrazione automatica con i sistemi ufficiali SEND).

## Stack

Laravel 9, Blade/Metronic, Spatie Permission, storage privato (`SensitiveFileService`).

## Permessi

Gate modulo: `servizio_send` (+ `send.access`).

Matrice azioni: `send.requests.*`, `send.documents.*`, `send.notes.*`, `send.audit.view`, `send.reports.view`, `send.settings.manage`.

`create` richiede `send.requests.create` (o admin). Il gate modulo resta su `viewAny` / `servizio_send`.

Rimosso: `send.requests.view-branch` (nessun modello filiale).

`servizio_send` è in `PERMESSI_SOLO_ADMIN` (attivazione solo da admin).

## Workflow stati

`draft` → `submitted` → `assigned` / `awaiting_assignment` → `taken_in_charge` → `processing` → (`integration_required` → `resubmitted` → …) → `completed` → `delivered` → `closed`

Anche: `rejected`, `cancelled`, `expired`.

Riapertura: `rejected` / `cancelled` / `expired` → `draft` (`POST …/riapri`).

Riassegnazione: da `awaiting_assignment` / `assigned` / `taken_in_charge` (`POST …/riassegna`).

Transizioni solo via `SendRequestStatusService`.

Policy azioni supervisore legate allo stato:

- `startProcessing` → `taken_in_charge`
- `requestIntegration` / `complete` / `reject` → `processing`

## Checklist tipologiche

- Destinatario: avviso, documento, CF
- Delegato: + delega, doc/CF delegato
- Impresa: + poteri, doc/CF rappresentante, dati impresa
- Delegato impresa: combinazione delle precedenti

Consenso **privacy** obbligatorio in submit. Allegato cliente (`visibility=citizen_receipt`) obbligatorio in complete.

## Schema

Tabelle `send_*` (requests, subjects, documents, checklist, status_history, assignments, notes, consents, deliveries, audit_logs, settings, number_counters).

Codice pratica: `SEND-{YYYY}-{NNNNNN}` (lock su contatore annuale).

## Configurazione

`config/send.php` + tabella `send_settings` (anche `prezzo_cliente` / `prezzo_agente` editabili da Impostazioni).

Env:

```
SEND_PROVIDER=manual
SEND_INTEGRATION_ENABLED=false
SEND_NUMBER_PREFIX=SEND
SEND_ASSIGNMENT_METHOD=least_open
SEND_MAX_UPLOAD_KB=20480
SEND_PRIVACY_VERSION=2026-07-01
SEND_PREZZO_CLIENTE=5
SEND_PREZZO_AGENTE=4
SEND_RETENTION_DAYS=0
```

## Prezzi e plafond

- **Importo cliente** (UI): da `send_settings.prezzo_cliente` poi config.
- **Addebito plafond servizi**: da `send_settings.prezzo_agente` alla creazione.
- Rimborso automatico su **annullamento**, **rifiuto** e **eliminazione bozza**.

## Report

- `GET /backend/send/report` — filtri data, conteggi stato, totali
- `GET /backend/send/report.csv` — export CSV

## Job schedulati

- `send:mark-sla-breaches` (orario) — nota interna + notifica supervisore
- `send:expire-stale` (giornaliero) — pratiche oltre SLA completamento → `expired`
- `send:retention-purge` (settimanale) — soft-delete se `retention_days > 0`

## Provider

`SendProviderInterface` / `ManualSendProvider`. Se `SEND_INTEGRATION_ENABLED=true`, `startProcessing` invoca `processRequest` e scrive audit.

## PDF

`GET /backend/send/{uuid}/ricevuta-consegna.pdf` dopo `delivered` / `closed`.

## Installazione

```bash
php artisan migrate
php artisan permission:cache-reset
```

## Route

Prefisso backend `/backend/send/` (auth + 2fa + role_or_permission).

## Test

```bash
php artisan test --filter=SendModule
```

## Troubleshooting

- Modulo non in sidebar: mancano `servizio_send` + `send.access`
- Invio bloccato: checklist incompleta, manca consenso privacy, o manca identificativo avviso/IUN
- Completamento bloccato: manca allegato SEND per il cliente
- Nessun supervisore / metodo `manual`: stato `awaiting_assignment` + notifica admin
