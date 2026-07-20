# Integrazione Locker Point (Fermo deposito) — agenziaplinio.it

Guida per collegare il sito **agenziaplinio.it** al backoffice **gestiio.agenziaplinio.it** per prenotare pacchi in **fermo deposito / Locker Point**.

**Versione API:** 1.0.0  
**Base URL:** `https://gestiio.agenziaplinio.it/api/public/locker-point`

---

## Cosa deve fare l’agente (sintesi)

1. Ottenere la **API key** da Gestiio (HQ o postazione agente) — **mai** nel JavaScript del browser.
2. Creare un **proxy PHP server-side** su agenziaplinio.it verso Gestiio.
3. UI prenotazione: check disponibilità → book → pagina conferma con codice `LP-XXXX`.
4. Test end-to-end: la prenotazione deve comparire in Gestiio → Locker Point.

> **Alternativa zero sviluppo:** link diretto  
> `https://gestiio.agenziaplinio.it/locker-point`  
> (o slug postazione se attivo).

---

## Autenticazione

| Header | Obbligatorio | Note |
|--------|--------------|------|
| `X-Api-Key` | Sì (endpoint protetti) | Chiave HQ (`LOCKER_API_KEY`) oppure chiave postazione agente |
| `Content-Type` | Sì su POST/PATCH | `application/json` |
| `Accept` | Consigliato | `application/json` |
| `X-Station-Slug` | Opzionale | Solo con chiave postazione: deve coincidere con lo slug |

- **HQ key** → scope globale (`station_id` null)
- **Station key** → solo pacchi di quella postazione (`api_enabled` deve essere true)

Test senza chiave:

```bash
curl -s https://gestiio.agenziaplinio.it/api/public/locker-point/health
```

Atteso: `"status":"ok"`, `"service":"locker-point"`.

---

## Endpoint

Prefix: `/api/public/locker-point`  
Throttle: 60 req/min

| Metodo | Path | Auth | Descrizione |
|--------|------|------|-------------|
| GET | `/health` | No | Healthcheck |
| GET | `/pricing` | Sì | Tariffe e capacità |
| GET | `/availability?date=YYYY-MM-DD` | Sì | Disponibilità giorno |
| GET | `/availability/range?from=&to=` | Sì | Range max 31 giorni |
| POST | `/book` | Sì | Crea prenotazione → `PRENOTATO` |
| GET | `/packages` | Sì | Elenco (paginato) |
| GET | `/packages/{code}` | Sì | Dettaglio (`LP-XXXXXX`) |
| PATCH | `/packages/{code}` | Sì | Modifica (solo `PRENOTATO`) |
| POST | `/packages/{code}/cancel` | Sì | Annulla prenotazione |

---

## Envelope JSON

Successo:

```json
{
  "success": true,
  "data": { },
  "meta": { }
}
```

Errore:

```json
{
  "success": false,
  "error": {
    "code": "NO_AVAILABILITY",
    "message": "..."
  }
}
```

### Codici errore comuni

| Code | HTTP | Quando |
|------|------|--------|
| `UNAUTHORIZED` | 401 | API key mancante/non valida |
| `API_DISABLED` | 403 | Postazione con API disabilitate |
| `STATION_MISMATCH` | 403 | `X-Station-Slug` non allineato |
| `BOOKING_DISABLED` | 403 | Prenotazione online/API spenta |
| `NO_AVAILABILITY` | 409 | Capacità esaurita quel giorno |
| `PACKAGE_NOT_FOUND` | 404 | Codice fuori scope / assente |
| `NOT_EDITABLE` | 409 | PATCH su stato ≠ `PRENOTATO` |
| `VALIDATION_ERROR` | 400 | Payload non valido |
| `MISSING_DATE` / `INVALID_DATE` | 400 | Parametro data |

---

## Flusso consigliato (prenotazione spedizione)

```
1. GET  /pricing
2. GET  /availability?date=2026-07-20
3. POST /book
4. Mostra al cliente: code + pickupUrl
```

### 1) Pricing

```bash
curl -s -H "X-Api-Key: YOUR_KEY" \
  https://gestiio.agenziaplinio.it/api/public/locker-point/pricing
```

`data` tipico:

```json
{
  "dailyRate": 0.5,
  "currency": "EUR",
  "minDays": 1,
  "maxPackagesPerBooking": 5,
  "maxDailyCapacity": 100,
  "onlineIntakeEnabled": true,
  "bookingInstructions": "...",
  "stationSlug": null,
  "pricingNote": "Tariffa giornaliera per pacco. Giorni parziali conteggiati come giorno intero."
}
```

### 2) Disponibilità

```bash
curl -s -H "X-Api-Key: YOUR_KEY" \
  "https://gestiio.agenziaplinio.it/api/public/locker-point/availability?date=2026-07-20"
```

Range:

```bash
curl -s -H "X-Api-Key: YOUR_KEY" \
  "https://gestiio.agenziaplinio.it/api/public/locker-point/availability/range?from=2026-07-20&to=2026-07-27"
```

### 3) Book (fermo deposito)

```bash
curl -s -X POST \
  -H "X-Api-Key: YOUR_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  https://gestiio.agenziaplinio.it/api/public/locker-point/book \
  -d '{
    "recipientName": "Mario Rossi",
    "recipientEmail": "mario@example.com",
    "recipientPhone": "+393331234567",
    "senderName": "Amazon Logistics",
    "senderPhone": "+39021234567",
    "carrier": "Amazon",
    "trackingCode": "TBA123456789",
    "expectedPickupDate": "2026-07-20",
    "notes": "Pacco fragile — fermo deposito"
  }'
```

#### Campi body

| Campo | Tipo | Obbligatorio |
|-------|------|--------------|
| `recipientName` | string | Sì |
| `expectedPickupDate` | date `YYYY-MM-DD` (≥ oggi) | Sì |
| `recipientEmail` | email | No |
| `recipientPhone` | string | No |
| `senderName` | string | No |
| `senderPhone` | string | No |
| `carrier` | string | No |
| `trackingCode` | string | No |
| `notes` | string (max 2000) | No |

HTTP **201** → prenotazione creata in stato `PRENOTATO`.

`data` (estratto):

```json
{
  "id": "01KXK...",
  "code": "LP-5VB8PA",
  "status": "PRENOTATO",
  "statusLabel": "Prenotato",
  "recipientName": "Mario Rossi",
  "carrier": "Amazon",
  "trackingCode": "TBA123456789",
  "expectedPickupDate": "2026-07-20",
  "dailyRate": 0.5,
  "qrToken": "...",
  "pickupUrl": "https://gestiio.agenziaplinio.it/locker-point/ritiro/01KXK...?t=...",
  "source": "api",
  "stationId": null
}
```

Mostrare al cliente almeno **`code`** (etichetta / barcode) e opzionalmente **`pickupUrl`**.

---

## Stati operativi (dopo la prenotazione)

| Stato | Chi lo imposta | Note |
|-------|----------------|------|
| `PRENOTATO` | API / web book | Modificabile / annullabile via API |
| `IN_GIACENZA` | Sportello Gestiio | Accettazione con **foto obbligatoria** |
| `CONSEGNATO` | Sportello / ritiro mobile | Scan `LP-XXXX` + firma |
| `ANNULLATO` | API cancel / sportello | — |
| `NO_SHOW` | Operativo | — |

L’agente sul sito marketing tipicamente fa solo **book + consulta + cancel** finché è `PRENOTATO`. Accettazione e consegna restano sullo sportello Gestiio.

---

## Proxy consigliato su agenziaplinio.it

```env
GESTIIO_LOCKER_API_BASE=https://gestiio.agenziaplinio.it/api/public/locker-point
GESTIIO_LOCKER_API_KEY=incollare-chiave-gestiio
```

Esempio PHP minimo (book):

```php
<?php
header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$ch = curl_init(rtrim(getenv('GESTIIO_LOCKER_API_BASE'), '/').'/book');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Api-Key: '.getenv('GESTIIO_LOCKER_API_KEY'),
    ],
    CURLOPT_POSTFIELDS => $payload,
]);
echo curl_exec($ch);
```

---

## Checklist test

- [ ] `GET /health` → ok  
- [ ] `GET /pricing` con key → tariffe  
- [ ] `GET /availability?date=...` → posti > 0  
- [ ] `POST /book` → 201 + `code` tipo `LP-...`  
- [ ] Pacco visibile in Gestiio → Locker Point (admin)  
- [ ] `POST /packages/{code}/cancel` mentre è `PRENOTATO`

---

## Riferimenti nel repo Gestiio

- Spec sintetica: `docs/LOCKER_API_PUBLIC.md`
- Route: `routes/api.php` → prefix `public/locker-point`
- Validazione book: `app/Http/Requests/StoreLockerBookingRequest.php`
