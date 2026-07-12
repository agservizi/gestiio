# Deposito Bagagli — API REST Admin

Documentazione per integrazioni interne e automazioni staff su **gestiio.agenziaplinio.it**.

**Versione API:** 1.1.0  
**Ultimo aggiornamento:** 2026-07-12

---

## Base URL

```
https://gestiio.agenziaplinio.it/api/admin/deposito-bagagli
```

OpenAPI machine-readable (richiede sessione staff autenticata):

```
GET https://gestiio.agenziaplinio.it/api/admin/deposito-bagagli/docs
```

---

## Autenticazione

| Aspetto | Dettaglio |
|---------|-----------|
| **Tipo** | Sessione Laravel (cookie) + 2FA |
| **Middleware** | `auth`, ruolo `admin\|agente\|supervisore\|operatore`, `2fa` |
| **CSRF** | Richiesto su `POST`/`PATCH`/`DELETE` (header `X-XSRF-TOKEN` o campo `_token`) |

Le chiamate vanno effettuate **dallo stesso dominio** Gestiio (es. fetch da pagina backend con token CSRF) oppure tramite client che gestisce cookie di sessione.

> Per integrazioni esterne pubbliche usare l'[API pubblica](LUGGAGE_API_PUBLIC.md) con proxy server-side.

---

## Endpoint principali

| Endpoint | Metodo | Scopo |
|----------|--------|-------|
| `/` | GET | Elenco depositi (filtri `view`, `q`, `status`, `source`) |
| `/` | POST | Crea deposito sportello |
| `/{deposit}` | GET | Dettaglio |
| `/{deposit}` | PATCH | Modifica (solo `PRENOTATO` / `NO_SHOW` parziale) |
| `/{deposit}` | DELETE | Elimina (solo `admin`) |
| `/{deposit}/actions` | POST | check-in, check-out, cancel, no-show |
| `/settings` | GET/POST | Tariffe, capacità, notifiche |
| `/stats/overview` | GET | KPI dashboard |
| `/export/csv` | GET | Export CSV |
| `/{deposit}/pdf` | GET | Ricevuta PDF |
| `/{deposit}/pdf/tags` | GET | Tag bagagli PDF |
| `/{deposit}/pdf/agreement` | GET | Documento firma cliente PDF |

---

## Azioni deposito

```http
POST /api/admin/deposito-bagagli/{deposit}/actions
Content-Type: application/json

{
  "action": "check-in"
}
```

Azioni supportate: `check-in`, `check-out`, `cancel`, `no-show`.

Check-out con metodo pagamento:

```json
{
  "action": "check-out",
  "paymentMethod": "Contanti"
}
```

---

## Impostazioni

`POST /settings` accetta gli stessi campi del form backend:

- `daily_rate`, `max_capacity`, `min_days`, `max_bags_per_booking`, `currency`
- `luggage_online_booking_enabled`
- `luggage_notify_staff`, `luggage_notify_customer_receipt`
- `luggage_staff_notification_email`
- `luggage_booking_instructions`

---

## Prenotazione pubblica (senza API key nel browser)

Il sito `/deposito-bagagli` usa proxy server-side:

| Route web | Scopo |
|-----------|-------|
| `POST /deposito-bagagli/prenota` | Crea prenotazione (no API key) |
| `GET /deposito-bagagli/disponibilita?date=` | Disponibilità giornaliera |

Per **agenziaplinio.it** replicare lo stesso pattern PHP/cURL descritto in `LUGGAGE_API_PUBLIC.md`.

---

## Registro incassi

Al check-out viene creato un record in `luggage_cash_movements` collegato al deposito via `cash_movement_id`.

---

## Test

```bash
php artisan test --filter=Luggage
```
