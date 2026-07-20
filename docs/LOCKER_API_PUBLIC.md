# Locker Point — API pubblica

Base URL: `/api/public/locker-point`

## Autenticazione

Header `X-Api-Key`:
- Chiave HQ (`LOCKER_API_KEY` in `.env`) — scope globale, pacchi con `station_id` null
- Chiave postazione agente — scope limitato alla postazione

Opzionale: `X-Station-Slug` per verifica coerenza con chiave stazione.

## Endpoint pubblici (senza chiave)

- `GET /health` — stato servizio

## Endpoint protetti (con chiave)

- `GET /pricing` — tariffe e capacità
- `GET /availability?date=YYYY-MM-DD` — disponibilità giornaliera
- `GET /availability/range?from=&to=` — intervallo max 31 giorni
- `POST /book` — crea prenotazione `PRENOTATO`
- `GET /packages` — elenco paginato
- `GET /packages/{code}` — dettaglio pacco
- `PATCH /packages/{code}` — modifica prenotazione (solo PRENOTATO)
- `POST /packages/{code}/cancel` — annulla prenotazione

## Book payload (JSON)

```json
{
  "recipientName": "Mario Rossi",
  "recipientEmail": "mario@example.com",
  "recipientPhone": "+393331234567",
  "senderName": "Amazon",
  "carrier": "BRT",
  "trackingCode": "1234567890",
  "expectedPickupDate": "2026-07-20",
  "notes": "Paccco fragile"
}
```

## Flusso operativo

1. Prenotazione online/API → stato `PRENOTATO`
2. Accettazione sportello con **foto obbligatoria** → `IN_GIACENZA`
3. Ritiro mobile: scan barcode `LP-XXXX` + firma → `CONSEGNATO`

## Codici errore

- `UNAUTHORIZED` — API key mancante/non valida
- `NO_AVAILABILITY` — capacità esaurita
- `BOOKING_DISABLED` — prenotazioni online disabilitate
- `PACKAGE_NOT_FOUND` — pacco non trovato o fuori scope
