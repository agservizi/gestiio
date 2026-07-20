# Deposito Bagagli — API REST Pubbliche

Documentazione per l'integrazione tra **agenziaplinio.it** (sito marketing) e **gestiio.agenziaplinio.it** (backoffice).

> **Guida passo-passo per l'agente/sviluppatore del sito marketing:**  
> vedi [INTEGRAZIONE_AGENZIAPLINIO_DEPOSITO_BAGAGLI.md](./INTEGRAZIONE_AGENZIAPLINIO_DEPOSITO_BAGAGLI.md) (proxy PHP, checklist, file da creare).

**Versione API:** 1.2.0  
**Ultimo aggiornamento:** 2026-07-15

---

## Base URL

```
https://gestiio.agenziaplinio.it/api/public/deposito-bagagli
```

Documentazione OpenAPI machine-readable (senza autenticazione):

```
GET https://gestiio.agenziaplinio.it/api/public/deposito-bagagli/docs
```

---

## Autenticazione

| Aspetto | Dettaglio |
|---------|-----------|
| **Tipo** | API Key (non Bearer, non sessione) |
| **Header** | `x-api-key: <LA_TUA_CHIAVE>` |
| **HQ globale** | Variabile `.env` su Gestiio: `LUGGAGE_API_KEY` → depositi senza postazione |
| **Postazione agente** | Chiave dedicata generata da admin su `/backend/deposito-bagagli/postazioni` → depositi isolati della postazione |
| **Endpoint senza chiave** | `GET /health`, `GET /verify`, `GET /docs` |

### Chiavi postazione agente

1. L’agente richiede l’API da **Mia postazione bagagli**.
2. Un admin abilita/rigenera la chiave (mostrata una sola volta).
3. Il sito dell’agente usa quella chiave (solo server-side). Tutti book/list/availability/pricing restano isolati.
4. Header opzionale: `X-Station-Slug: <slug>` (se presente deve coincidere con la stazione della key).
5. Link pubblico prenotazione: `https://gestiio.agenziaplinio.it/deposito-bagagli/<slug>`

### Errori chiave

- `401 UNAUTHORIZED` — chiave mancante/errata  
- `403 API_DISABLED` — postazione con API non abilitate  
- `403 STATION_MISMATCH` — `X-Station-Slug` non corrisponde  

### Header richiesti (endpoint protetti)

```http
Content-Type: application/json
Accept: application/json
x-api-key: la-tua-chiave-segreta
```

### Errore 401 — chiave mancante o errata

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "API key mancante o non valida"
  }
}
```

> **Sicurezza:** la chiave non va esposta nel JavaScript del browser. Su Gestiio la pagina `/deposito-bagagli` usa il proxy server-side `POST /deposito-bagagli/prenota` (senza API key). Su **agenziaplinio.it** usa una route PHP/Node analoga che inoltra le chiamate a Gestiio con la chiave nel backend.

---

## Formato risposte

### Successo

```json
{
  "success": true,
  "data": { },
  "meta": { }
}
```

`meta` è presente solo su endpoint paginati o con intervalli di date.

### Errore applicativo

```json
{
  "success": false,
  "error": {
    "code": "CODICE_ERRORE",
    "message": "Descrizione leggibile"
  }
}
```

### Errore validazione Laravel (422) su `POST /book`

Formato diverso dal envelope standard:

```json
{
  "message": "The customer name field is required. (and 1 more error)",
  "errors": {
    "customerName": ["Il campo customer name è obbligatorio."],
    "bookingDate": ["Il campo booking date è obbligatorio."]
  }
}
```

---

## Elenco endpoint

| Endpoint | Metodo | Auth | Scopo |
|----------|--------|------|-------|
| `/health` | GET | No | Health check servizio |
| `/docs` | GET | No | Specifica OpenAPI 3.0 |
| `/pricing` | GET | Sì | Tariffe e limiti correnti |
| `/availability` | GET | Sì | Disponibilità per una data |
| `/availability/range` | GET | Sì | Disponibilità per intervallo |
| `/book` | POST | Sì | **Crea prenotazione** (compare su Gestiio) |
| `/verify` | GET | No | Verifica QR token |
| `/deposits` | GET | Sì | Elenco prenotazioni (filtrabile) |
| `/deposits/{code}` | GET | Sì | Dettaglio per codice (es. `LB-ABC123`) |
| `/deposits/{code}` | PATCH | Sì | Modifica (solo `PRENOTATO`) |
| `/deposits/{code}/cancel` | POST | Sì | Annulla (solo `PRENOTATO`) |

---

## 1. Health check

```http
GET /api/public/deposito-bagagli/health
```

### Risposta 200

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "service": "deposito-bagagli",
    "version": "1.1.0",
    "timestamp": "2026-07-12T15:00:00+02:00"
  }
}
```

---

## 2. Pricing

```http
GET /api/public/deposito-bagagli/pricing
x-api-key: ...
```

### Risposta 200

```json
{
  "success": true,
  "data": {
    "dailyRate": 2.00,
    "currency": "EUR",
    "minDays": 1,
    "maxBagsPerBooking": 10,
    "maxDailyCapacity": 50,
    "onlineBookingEnabled": true,
    "bookingInstructions": "",
    "pricingNote": "Tariffa giornaliera per bagaglio. Giorni parziali conteggiati come giorno intero."
  }
}
```

Controlla `onlineBookingEnabled` prima di mostrare il form: se `false`, disabilita le prenotazioni online.

---

## 3. Availability (singola data)

```http
GET /api/public/deposito-bagagli/availability?date=2026-07-15
x-api-key: ...
```

| Query | Obbligatorio | Formato |
|-------|--------------|---------|
| `date` | Sì | `YYYY-MM-DD` |

### Risposta 200

```json
{
  "success": true,
  "data": {
    "date": "2026-07-15",
    "max_capacity": 50,
    "booked_bags": 3,
    "available_bags": 47,
    "available": true
  }
}
```

### Errori

| HTTP | code | Messaggio |
|------|------|-----------|
| 400 | `MISSING_DATE` | Parametro date obbligatorio (YYYY-MM-DD) |
| 400 | `INVALID_DATE` | Formato data non valido |

---

## 4. Availability range

```http
GET /api/public/deposito-bagagli/availability/range?from=2026-07-12&to=2026-07-14
x-api-key: ...
```

| Query | Obbligatorio | Note |
|-------|--------------|------|
| `from` | Sì | `YYYY-MM-DD` |
| `to` | Sì | `YYYY-MM-DD`, intervallo massimo 60 giorni |

### Risposta 200

```json
{
  "success": true,
  "data": [
    {
      "date": "2026-07-12",
      "max_capacity": 50,
      "booked_bags": 2,
      "available_bags": 48,
      "available": true
    },
    {
      "date": "2026-07-13",
      "max_capacity": 50,
      "booked_bags": 0,
      "available_bags": 50,
      "available": true
    },
    {
      "date": "2026-07-14",
      "max_capacity": 50,
      "booked_bags": 5,
      "available_bags": 45,
      "available": true
    }
  ],
  "meta": {
    "from": "2026-07-12",
    "to": "2026-07-14",
    "count": 3
  }
}
```

### Errori

| HTTP | code | Messaggio |
|------|------|-----------|
| 400 | `MISSING_RANGE` | Parametri from e to obbligatori (YYYY-MM-DD) |
| 400 | `INVALID_DATE` | Formato data non valido |
| 400 | `INVALID_RANGE` | La data to deve essere >= from |
| 400 | `RANGE_TOO_LARGE` | Intervallo massimo 60 giorni |

---

## 5. Book — crea prenotazione

Endpoint principale: ogni prenotazione da `agenziaplinio.it` deve passare da qui. Su Gestiio compare con `source: "PORTALE"` e `status: "PRENOTATO"`.

```http
POST /api/public/deposito-bagagli/book
Content-Type: application/json
x-api-key: ...
```

### Campi body (camelCase)

| Campo | Tipo | Obbligatorio | Regole |
|-------|------|--------------|--------|
| `customerName` | string | **Sì** | max 255 caratteri |
| `bookingDate` | date | **Sì** | `YYYY-MM-DD`, data ≥ oggi |
| `customerEmail` | string | No | email valida, max 255 |
| `customerPhone` | string | No | max 50 caratteri |
| `bagCount` | integer | No | default `1`, min 1, max 100 (limite effettivo da settings) |
| `expectedCheckIn` | date/datetime | No | data/ora prevista consegna |
| `expectedCheckOut` | date/datetime | No | ≥ `expectedCheckIn` |
| `notes` | string | No | max 2000 caratteri |

### Esempio richiesta

```json
{
  "customerName": "Mario Rossi",
  "customerEmail": "mario@example.com",
  "customerPhone": "+39 333 1234567",
  "bagCount": 2,
  "bookingDate": "2026-07-15",
  "expectedCheckIn": "2026-07-15T09:00:00+02:00",
  "expectedCheckOut": "2026-07-17T18:00:00+02:00",
  "notes": "2 valigie medie"
}
```

### Risposta 201 (successo)

```json
{
  "success": true,
  "data": {
    "id": "01KXB42Q8T68J1KRKXG0TD0CRS",
    "code": "LB-UMKGCK",
    "customerName": "Mario Rossi",
    "customerEmail": "mario@example.com",
    "customerPhone": "+39 333 1234567",
    "bagCount": 2,
    "bagTags": ["LB-UMKGCK-A", "LB-UMKGCK-B"],
    "status": "PRENOTATO",
    "statusLabel": "Prenotato",
    "bookingDate": "2026-07-15",
    "expectedCheckIn": "2026-07-15T07:00:00.000000Z",
    "expectedCheckOut": "2026-07-17T16:00:00.000000Z",
    "checkedInAt": null,
    "checkedOutAt": null,
    "dailyRate": 2.00,
    "totalAmount": null,
    "paymentMethod": null,
    "qrToken": "8fe41193-2d72-45f7-bf96-cb6f44efb296",
    "verifyUrl": "https://gestiio.agenziaplinio.it/deposito-bagagli/verify/01KXB...?t=8fe41193-...",
    "source": "PORTALE",
    "notes": "2 valigie medie",
    "createdAt": "2026-07-12T15:00:00.000000Z",
    "updatedAt": "2026-07-12T15:00:00.000000Z"
  }
}
```

### Errori

| HTTP | code | Quando |
|------|------|--------|
| 401 | `UNAUTHORIZED` | API key errata o mancante |
| 422 | — | Validazione Laravel (campi mancanti/invalidi) |
| 400 | `VALIDATION_ERROR` | Es. numero borse superiore al massimo consentito |
| 409 | `NO_AVAILABILITY` | Posti insufficienti per quella data |

```json
{
  "success": false,
  "error": {
    "code": "NO_AVAILABILITY",
    "message": "Disponibilità insufficiente: 0 posti per 2026-07-15"
  }
}
```

---

## 6. Verify (pubblico, senza API key)

```http
GET /api/public/deposito-bagagli/verify?token=<qrToken>
```

Usato per validare il QR code generato alla prenotazione.

| Query | Obbligatorio | Formato |
|-------|--------------|---------|
| `token` | Sì | UUID (`qrToken` restituito dal book) |

### Risposta 200

Stesso oggetto `Deposit` del book.

### Errori

| HTTP | code | Messaggio |
|------|------|-----------|
| 400 | `MISSING_TOKEN` | Parametro token mancante |
| 404 | `INVALID_TOKEN` | Token non valido |

---

## 7. Deposits — elenco

```http
GET /api/public/deposito-bagagli/deposits?email=mario@example.com&page=1&limit=20
x-api-key: ...
```

| Query | Descrizione |
|-------|-------------|
| `email` | Filtra per email cliente |
| `code` | Filtra per codice `LB-...` |
| `status` | `PRENOTATO`, `CHECK_IN`, `COMPLETATO`, `ANNULLATO`, `NO_SHOW` |
| `from` | Data inizio intervallo (`YYYY-MM-DD`) |
| `to` | Data fine intervallo (`YYYY-MM-DD`) |
| `q` | Ricerca libera (codice, nome cliente) |
| `page` | Pagina corrente (default `1`) |
| `limit` | Elementi per pagina (default `20`, max `100`) |

### Risposta 200

```json
{
  "success": true,
  "data": [
    {
      "id": "01KXB42Q8T68J1KRKXG0TD0CRS",
      "code": "LB-UMKGCK",
      "customerName": "Mario Rossi",
      "status": "PRENOTATO",
      "source": "PORTALE"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 42
  }
}
```

---

## 8. Deposits — dettaglio

```http
GET /api/public/deposito-bagagli/deposits/LB-UMKGCK
x-api-key: ...
```

### Risposta 200

Oggetto `Deposit` completo (come nel book).

### Errore 404

```json
{
  "success": false,
  "error": {
    "code": "DEPOSIT_NOT_FOUND",
    "message": "Deposito non trovato"
  }
}
```

---

## 9. Deposits — modifica

```http
PATCH /api/public/deposito-bagagli/deposits/LB-UMKGCK
Content-Type: application/json
x-api-key: ...
```

Consentita **solo** se `status === "PRENOTATO"`.

### Body (tutti i campi opzionali)

```json
{
  "customerName": "Mario Rossi",
  "customerEmail": "mario@example.com",
  "customerPhone": "+39 333 1234567",
  "bagCount": 3,
  "bookingDate": "2026-07-16",
  "expectedCheckIn": "2026-07-16T09:00:00+02:00",
  "expectedCheckOut": "2026-07-18T18:00:00+02:00",
  "notes": "Aggiornamento note"
}
```

### Errore 409

```json
{
  "success": false,
  "error": {
    "code": "NOT_EDITABLE",
    "message": "Modifica consentita solo per PRENOTATO"
  }
}
```

---

## 10. Deposits — annulla

```http
POST /api/public/deposito-bagagli/deposits/LB-UMKGCK/cancel
x-api-key: ...
```

Body vuoto. Consentita **solo** se `status === "PRENOTATO"`.

### Risposta 200

```json
{
  "success": true,
  "data": {
    "id": "01KXB42Q8T68J1KRKXG0TD0CRS",
    "code": "LB-UMKGCK",
    "status": "ANNULLATO"
  }
}
```

### Errore 409

```json
{
  "success": false,
  "error": {
    "code": "NOT_CANCELLABLE",
    "message": "Cancellazione consentita solo per PRENOTATO"
  }
}
```

---

## Stati prenotazione

| Valore | Label | Descrizione |
|--------|-------|-------------|
| `PRENOTATO` | Prenotato | Creata, in attesa di check-in |
| `CHECK_IN` | In custodia | Bagagli depositati |
| `COMPLETATO` | Completato | Check-out effettuato |
| `ANNULLATO` | Annullato | Cancellata |
| `NO_SHOW` | No show | Cliente non si è presentato |

---

## Flusso integrazione agenziaplinio.it → Gestiio

```
agenziaplinio.it                    gestiio API                      Backoffice Gestiio
      |                                  |                                    |
      |  GET /pricing (x-api-key)        |                                    |
      |--------------------------------->|                                    |
      |<---------------------------------|  tariffe + limiti                  |
      |                                  |                                    |
      |  GET /availability?date=...      |                                    |
      |--------------------------------->|                                    |
      |<---------------------------------|  posti disponibili                 |
      |                                  |                                    |
      |  POST /book (dati cliente)       |                                    |
      |--------------------------------->|  crea deposito source=PORTALE      |
      |<---------------------------------|  201 + code + qrToken              |
      |                                  |----------------------------------->| visibile in /backend/deposito-bagagli
      |  mostra conferma al cliente      |                                    |
```

### Minimo per far comparire la prenotazione su Gestiio

1. `POST /book` con almeno `customerName` + `bookingDate`
2. API key valida nell'header `x-api-key`
3. Disponibilità sufficiente per `bagCount` nella data scelta

---

## Limiti e policy

| Aspetto | Valore |
|---------|--------|
| **Rate limit** | 60 richieste/minuto per IP (`throttle:60,1` sul gruppo route + middleware `api`) |
| **CORS** | Abilitato su `api/*`, `allowed_origins: *`, tutti i metodi e header |
| **IP whitelist** | Non presente — solo API key |
| **Bearer / OAuth** | Non usato |
| **Capacità giornaliera** | Da `LUGGAGE_MAX_CAPACITY` (default 50 borse/giorno) |
| **Max borse/prenotazione** | Da `LUGGAGE_MAX_BAGS_PER_BOOKING` (default 10) |
| **Tariffa giornaliera** | Da tabella `luggage_settings` (attualmente €2/giorno) |
| **Valuta** | `EUR` (configurabile via `LUGGAGE_CURRENCY`) |

---

## Variabili ambiente (Gestiio `.env`)

```env
LUGGAGE_API_KEY=chiave-segreta-lunga-e-casuale
LUGGAGE_DEFAULT_RATE=2
LUGGAGE_MAX_CAPACITY=50
LUGGAGE_MAX_BAGS_PER_BOOKING=10
LUGGAGE_MIN_DAYS=1
LUGGAGE_CURRENCY=EUR
```

Sul sito `agenziaplinio.it` configurare la stessa chiave in una variabile server-side (es. `GESTIIO_LUGGAGE_API_KEY`), **non** nel JavaScript pubblico.

---

## Esempio integrazione PHP (server-side su agenziaplinio.it)

```php
<?php

$apiBase = 'https://gestiio.agenziaplinio.it/api/public/deposito-bagagli';
$apiKey  = getenv('GESTIIO_LUGGAGE_API_KEY');

$payload = [
    'customerName'  => $_POST['nome'],
    'customerEmail' => $_POST['email'] ?? null,
    'customerPhone' => $_POST['telefono'] ?? null,
    'bagCount'      => (int) ($_POST['borse'] ?? 1),
    'bookingDate'   => $_POST['data'],
    'notes'         => $_POST['note'] ?? null,
];

$ch = curl_init("$apiBase/book");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        "x-api-key: $apiKey",
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = json_decode(curl_exec($ch), true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 201 && ($response['success'] ?? false)) {
    $code = $response['data']['code'];
    $verifyUrl = $response['data']['verifyUrl'];
    // Redirect a pagina conferma con $code
} elseif ($httpCode === 409) {
    // Disponibilità esaurita: $response['error']['message']
} elseif ($httpCode === 422) {
    // Errori validazione: $response['errors']
} else {
    // Errore generico: $response['error']['message'] ?? 'Errore sconosciuto'
}
```

---

## Esempio integrazione JavaScript (fetch)

> Usare solo se la chiave è protetta da un proxy server-side. Non esporre `x-api-key` nel frontend pubblico.

```javascript
const apiBase = 'https://gestiio.agenziaplinio.it/api/public/deposito-bagagli';

// Via proxy locale sul sito marketing (consigliato)
const res = await fetch('/api/deposito-bagagli/book', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({
    customerName: 'Mario Rossi',
    customerEmail: 'mario@example.com',
    bagCount: 2,
    bookingDate: '2026-07-15',
  }),
});

const json = await res.json();

if (json.success) {
  console.log('Prenotazione creata:', json.data.code);
} else {
  console.error(json.error?.message);
}
```

---

## Dove vede lo staff la prenotazione

Dopo un `POST /book` riuscito:

| Dove | URL |
|------|-----|
| Elenco depositi | `https://gestiio.agenziaplinio.it/backend/deposito-bagagli` |
| Dashboard modulo | `https://gestiio.agenziaplinio.it/backend/deposito-bagagli/dashboard` |
| Fonte in elenco | Colonna **Online** (`source = PORTALE`) |
| Stato iniziale | `PRENOTATO` |

Lo staff riceve email di notifica se l'impostazione `luggage_notify_staff` è attiva in **Impostazioni deposito bagagli**.

---

## Checklist setup

- [ ] Impostare `LUGGAGE_API_KEY` nel `.env` di Gestiio (chiave lunga e casuale)
- [ ] Condividere la stessa chiave al backend di `agenziaplinio.it` (variabile server-side)
- [ ] Testare `GET /health` (senza chiave)
- [ ] Testare `GET /pricing` con chiave
- [ ] Testare `POST /book` di prova
- [ ] Verificare su Gestiio che compaia in elenco con fonte **Online**
- [ ] Configurare pagina conferma su agenziaplinio.it con `code` e/o `verifyUrl`

---

## Riferimenti nel codice

| File | Contenuto |
|------|-----------|
| `routes/api.php` | Definizione route pubbliche |
| `app/Http/Middleware/ValidateLuggageApiKey.php` | Validazione API key |
| `app/Http/Controllers/Api/Public/*` | Controller endpoint |
| `app/Http/Requests/StoreLuggageBookingRequest.php` | Validazione book |
| `app/Http/Resources/LuggageDepositResource.php` | Formato risposta deposito |
| `app/Http/OpenApi/LuggageDepositOpenApiSpec.php` | Specifica OpenAPI |
| `config/luggage.php` | Configurazione default |
| `tests/Feature/LuggagePublicApiTest.php` | Test automatici |
