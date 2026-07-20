# Sicurezza — Modulo SEND

## Autorizzazioni

- Ogni route passa da middleware auth/2fa e `SendRequestPolicy`.
- Scope lista: `view-all` / owner / supervisore assegnato.
- IDOR: download allegati verifica `send_request_id` + policy documento.

## Allegati

- Disk privato `sensitive` via `SensitiveFileService`.
- Nomi interni ULID; whitelist estensioni; limiti size; blocco firme eseguibili.
- Nessun URL pubblico; download solo controller autorizzato.
- Audit su upload/download/delete.

## Isolamento

Gestiio non ha multi-tenant classico: isolamento per utente creatore e permessi Spatie.

## Logging

`send_request_audit_logs` maschera CF/email/telefono/documento nei payload before/after.
Log applicativi non devono contenere contenuti file.

## Cifratura

Campi anagrafici in chiaro a DB (come CAF). Valutare cifratura applicativa in fase successiva se richiesto dal titolare.

## Retention

`SEND_RETENTION_DAYS` / settings: **nessuna cancellazione automatica** finché non approvata policy formale.

## Rischi residui

- Dipendenza da correttezza checklist operatore.
- Supervisore lavora fuori piattaforma: Gestiio non certifica l’esito legale SEND.
- Email di notifica contengono solo codice pratica e link autenticato.
