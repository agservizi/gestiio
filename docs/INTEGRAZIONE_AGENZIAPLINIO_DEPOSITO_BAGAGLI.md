# Integrazione Deposito Bagagli — agenziaplinio.it

Guida operativa per lo sviluppatore/agente che deve collegare il sito marketing **agenziaplinio.it** al backoffice **gestiio.agenziaplinio.it**.

**Versione:** 1.0  
**Data:** 2026-07-12  
**API Gestiio:** 1.1.0

---

## Cosa deve fare l'agente (in sintesi)

1. Ottenere la **chiave API** da chi gestisce Gestiio (non va nel JavaScript pubblico).
2. Creare **3 endpoint PHP server-side** su agenziaplinio.it che fanno da proxy verso Gestiio.
3. Creare la **pagina prenotazione** sul sito marketing che chiama solo i proxy locali.
4. Creare la **pagina conferma** (locale o redirect verso Gestiio).
5. Testare il flusso end-to-end e verificare che la prenotazione compaia su Gestiio.

> **Alternativa rapida (zero sviluppo):** linkare direttamente  
> `https://gestiio.agenziaplinio.it/deposito-bagagli`  
> La pagina è già pronta, sicura e collegata al backoffice.

---

## Architettura

```
┌─────────────────────┐         ┌──────────────────────────────┐
│  agenziaplinio.it   │         │  gestiio.agenziaplinio.it    │
│  (sito marketing)   │         │  (backoffice + API)          │
├─────────────────────┤         ├──────────────────────────────┤
│ Pagina HTML/JS      │  HTTP   │ /api/public/deposito-bagagli│
│        │            │ ──────► │   + x-api-key (solo server)  │
│        ▼            │         │                              │
│ Proxy PHP locale    │         │ Staff vede prenotazione in:  │
│ /api/luggage/*      │         │ /backend/deposito-bagagli    │
└─────────────────────┘         └──────────────────────────────┘
```

**Regola d'oro:** il browser del cliente **non deve mai** vedere `LUGGAGE_API_KEY`.  
Solo il server PHP di agenziaplinio.it la usa nelle chiamate a Gestiio.

---

## Cosa chiedere a chi gestisce Gestiio

| Elemento | Dove si trova | Note |
|----------|---------------|------|
| `LUGGAGE_API_KEY` | File `.env` su Gestiio | Stringa lunga casuale — **segreta** |
| URL API base | Fisso | `https://gestiio.agenziaplinio.it/api/public/deposito-bagagli` |
| Tariffa attuale | Impostazioni Gestiio | Es. €2,00/giorno per borsa |
| Prenotazioni online attive | Impostazioni Gestiio | Campo `onlineBookingEnabled` |

Test rapido che Gestiio funziona (senza chiave):

```bash
curl -s https://gestiio.agenziaplinio.it/api/public/deposito-bagagli/health
```

Risposta attesa: `"status": "ok"`

---

## Step 1 — Configurazione server agenziaplinio.it

### 1.1 Variabile ambiente

Aggiungere nel `.env` o nella configurazione hosting ( **fuori dalla web root** ):

```env
GESTIIO_LUGGAGE_API_BASE=https://gestiio.agenziaplinio.it/api/public/deposito-bagagli
GESTIIO_LUGGAGE_API_KEY=incollare-qui-la-chiave-ricevuta-da-gestiio
```

### 1.2 Struttura file consigliata

```
agenziaplinio.it/
├── deposito-bagagli/
│   ├── index.php              ← pagina prenotazione (HTML + form)
│   └── conferma.php           ← pagina conferma con codice
├── api/
│   └── deposito-bagagli/
│       ├── _client.php        ← helper condiviso (NON accessibile via web se possibile)
│       ├── pricing.php        ← GET tariffe
│       ├── availability.php   ← GET disponibilità
│       └── book.php           ← POST prenotazione
└── .env                       ← chiave API (MAI in public_html)
```

---

## Step 2 — Helper PHP condiviso

Creare `api/deposito-bagagli/_client.php`:

```php
<?php
declare(strict_types=1);

function gestiio_luggage_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $apiBase = getenv('GESTIIO_LUGGAGE_API_BASE')
        ?: 'https://gestiio.agenziaplinio.it/api/public/deposito-bagagli';
    $apiKey = getenv('GESTIIO_LUGGAGE_API_KEY') ?: '';

    if ($apiKey === '') {
        throw new RuntimeException('GESTIIO_LUGGAGE_API_KEY non configurata.');
    }

    return $config = [
        'api_base' => rtrim($apiBase, '/'),
        'api_key'  => $apiKey,
    ];
}

/**
 * @return array{status:int, body:array|null, raw:string}
 */
function gestiio_luggage_request(string $method, string $path, ?array $payload = null): array
{
    $config = gestiio_luggage_config();
    $url = $config['api_base'] . $path;

    $ch = curl_init($url);
    $headers = [
        'Accept: application/json',
        'x-api-key: ' . $config['api_key'],
    ];

  $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
    ];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_HTTPHEADER] = $headers;
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $body = json_decode($raw ?: '', true);

    return [
        'status' => $status,
        'body'   => is_array($body) ? $body : null,
        'raw'    => (string) $raw,
    ];
}

function gestiio_luggage_json_response(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
```

---

## Step 3 — Endpoint proxy

### 3.1 `api/deposito-bagagli/pricing.php`

```php
<?php
declare(strict_types=1);

require __DIR__ . '/_client.php';

try {
    $result = gestiio_luggage_request('GET', '/pricing');
    gestiio_luggage_json_response($result['status'], $result['body'] ?? [
        'success' => false,
        'error' => ['code' => 'UPSTREAM_ERROR', 'message' => 'Risposta non valida da Gestiio'],
    ]);
} catch (Throwable $e) {
    gestiio_luggage_json_response(500, [
        'success' => false,
        'error' => ['code' => 'CONFIG_ERROR', 'message' => $e->getMessage()],
    ]);
}
```

### 3.2 `api/deposito-bagagli/availability.php`

```php
<?php
declare(strict_types=1);

require __DIR__ . '/_client.php';

$date = $_GET['date'] ?? '';
if ($date === '') {
    gestiio_luggage_json_response(400, [
        'success' => false,
        'error' => ['code' => 'MISSING_DATE', 'message' => 'Parametro date obbligatorio'],
    ]);
}

$result = gestiio_luggage_request('GET', '/availability?date=' . urlencode($date));
gestiio_luggage_json_response($result['status'], $result['body'] ?? [
    'success' => false,
    'error' => ['code' => 'UPSTREAM_ERROR', 'message' => 'Risposta non valida da Gestiio'],
]);
```

### 3.3 `api/deposito-bagagli/book.php`

```php
<?php
declare(strict_types=1);

require __DIR__ . '/_client.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gestiio_luggage_json_response(405, [
        'success' => false,
        'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Usare POST'],
    ]);
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (! is_array($input)) {
    $input = $_POST;
}

$payload = [
    'customerName'  => trim((string) ($input['customerName'] ?? '')),
    'customerEmail' => $input['customerEmail'] ?? null,
    'customerPhone' => $input['customerPhone'] ?? null,
    'bagCount'      => (int) ($input['bagCount'] ?? 1),
    'bookingDate'   => (string) ($input['bookingDate'] ?? ''),
    'expectedCheckOut' => $input['expectedCheckOut'] ?? null,
    'notes'         => $input['notes'] ?? null,
];

$result = gestiio_luggage_request('POST', '/book', $payload);
gestiio_luggage_json_response($result['status'], $result['body'] ?? [
    'success' => false,
    'error' => ['code' => 'UPSTREAM_ERROR', 'message' => 'Risposta non valida da Gestiio'],
]);
```

---

## Step 4 — Pagina prenotazione (frontend)

Creare `deposito-bagagli/index.php` (o integrare in WordPress/template esistente).

Il JavaScript chiama **solo** `/api/deposito-bagagli/*` sullo stesso dominio — mai Gestiio direttamente.

```html
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Deposito bagagli | Agenzia Plinio</title>
</head>
<body>
  <h1>Prenota il deposito bagagli</h1>
  <p id="pricing-note">Caricamento tariffe...</p>
  <p>Posti disponibili oggi: <strong id="kpi-available">—</strong></p>

  <div id="booking-closed" hidden>
    <p>Le prenotazioni online sono temporaneamente sospese. Contattaci in agenzia.</p>
  </div>

  <form id="booking-form" hidden>
    <label>Nome * <input name="customerName" required></label>
    <label>Email <input type="email" name="customerEmail"></label>
    <label>Telefono <input name="customerPhone"></label>
    <label>Numero borse <input type="number" name="bagCount" min="1" value="1"></label>
    <label>Data deposito * <input type="date" name="bookingDate" id="bookingDate" required></label>
    <label>Ritiro previsto <input type="date" name="expectedCheckOut" id="expectedCheckOut"></label>
    <label>Note <textarea name="notes"></textarea></label>
    <button type="submit">Conferma prenotazione</button>
    <p id="error-box" style="color:red;display:none"></p>
  </form>

  <script>
  const apiLocal = '/api/deposito-bagagli';

  async function loadPricing() {
    const res = await fetch(apiLocal + '/pricing.php');
    const json = await res.json();
    if (!json.success) throw new Error(json.error?.message || 'Errore tariffe');

    const d = json.data;
    document.getElementById('pricing-note').textContent =
      `Tariffa € ${Number(d.dailyRate).toFixed(2)} al giorno per borsa.`;

    if (d.onlineBookingEnabled === false) {
      document.getElementById('booking-closed').hidden = false;
      return;
    }

    document.getElementById('booking-form').hidden = false;

    if (d.bookingInstructions) {
      const info = document.createElement('p');
      info.textContent = d.bookingInstructions;
      document.getElementById('booking-form').prepend(info);
    }
  }

  document.getElementById('bookingDate').min = new Date().toISOString().slice(0, 10);

  document.getElementById('bookingDate').addEventListener('change', async (e) => {
    const date = e.target.value;
    if (!date) return;
    const res = await fetch(apiLocal + '/availability.php?date=' + encodeURIComponent(date));
    const json = await res.json();
    if (json.success) {
      document.getElementById('kpi-available').textContent = json.data.available_bags;
    }
  });

  document.getElementById('booking-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const err = document.getElementById('error-box');
    err.style.display = 'none';

    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    payload.bagCount = parseInt(payload.bagCount || '1', 10);

    const res = await fetch(apiLocal + '/book.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload),
    });
    const json = await res.json();

    if (!json.success) {
      err.textContent = json.error?.message || json.message || 'Errore prenotazione';
      err.style.display = 'block';
      return;
    }

    // Opzione A: conferma su agenziaplinio.it
    window.location.href = '/deposito-bagagli/conferma.php?code=' + encodeURIComponent(json.data.code);

    // Opzione B: redirect alla conferma Gestiio (già pronta con QR)
    // window.location.href = 'https://gestiio.agenziaplinio.it/deposito-bagagli/conferma?code=' + encodeURIComponent(json.data.code);
  });

  loadPricing().catch(ex => {
    document.getElementById('pricing-note').textContent = 'Servizio temporaneamente non disponibile.';
    console.error(ex);
  });
  </script>
</body>
</html>
```

---

## Step 5 — Pagina conferma

### Opzione A — Pagina locale `deposito-bagagli/conferma.php`

Mostra il codice prenotazione. Per QR e verifica usare l'URL restituito da Gestiio al momento del book (`verifyUrl` nel JSON).

```php
<?php
$code = $_GET['code'] ?? '';
if ($code === '') {
    http_response_code(404);
    exit('Prenotazione non trovata');
}
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="utf-8"><title>Prenotazione confermata</title></head>
<body>
  <h1>Prenotazione confermata</h1>
  <p>Codice: <strong><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></strong></p>
  <p>Presentati in agenzia con questo codice.</p>
  <p><a href="https://gestiio.agenziaplinio.it/deposito-bagagli/conferma?code=<?= urlencode($code) ?>">
    Apri conferma con QR code
  </a></p>
</body>
</html>
```

### Opzione B — Redirect diretto a Gestiio (consigliata per QR)

Dopo il book, reindirizzare a:

```
https://gestiio.agenziaplinio.it/deposito-bagagli/conferma?code=LB-XXXXXX
```

La pagina Gestiio mostra codice + QR + link verifica.

---

## Step 6 — Gestione errori

| HTTP | code | Cosa mostrare all'utente |
|------|------|--------------------------|
| 401 | `UNAUTHORIZED` | "Servizio non disponibile" (problema configurazione — avvisare admin) |
| 403 | `BOOKING_DISABLED` | "Prenotazioni online sospese" |
| 409 | `NO_AVAILABILITY` | "Posti esauriti per la data scelta, prova un'altra data" |
| 422 | — | Mostrare messaggi campo per campo (`errors.customerName`, ecc.) |
| 400 | `VALIDATION_ERROR` | Messaggio da `error.message` |

---

## Step 7 — Dove vede lo staff la prenotazione

Dopo un `POST /book` riuscito, su Gestiio:

| Schermata | URL |
|-----------|-----|
| Elenco | https://gestiio.agenziaplinio.it/backend/deposito-bagagli |
| Dashboard | https://gestiio.agenziaplinio.it/backend/deposito-bagagli/dashboard |
| Dettaglio | Cercare il codice es. `LB-UMKGCK` |

La prenotazione ha:
- `source: PORTALE`
- `status: PRENOTATO`

Flusso in agenzia: **check-in** → stampa tag bagagli → firma documento → **check-out** con pagamento.

---

## Checklist test per l'agente

- [ ] `GET /api/deposito-bagagli/pricing.php` → `success: true`, `dailyRate` valorizzato
- [ ] Se `onlineBookingEnabled: false` → form nascosto, messaggio sospensione visibile
- [ ] Cambio data → `available_bags` si aggiorna
- [ ] Prenotazione test → risposta `201`, codice `LB-...`
- [ ] Prenotazione compare su Gestiio backend entro pochi secondi
- [ ] Pagina conferma mostra codice (e QR se redirect Gestiio)
- [ ] **DevTools browser:** nessuna richiesta con header `x-api-key` visibile
- [ ] Doppia prenotazione oltre capacità → errore `NO_AVAILABILITY`

### Comandi curl (da server, non da browser)

```bash
# Pricing (con chiave — solo da server)
curl -s -H "x-api-key: LA_CHIAVE" \
  https://gestiio.agenziaplinio.it/api/public/deposito-bagagli/pricing

# Book di test
curl -s -X POST \
  -H "Content-Type: application/json" \
  -H "x-api-key: LA_CHIAVE" \
  -d '{"customerName":"Test Agente","bookingDate":"2026-07-15","bagCount":1}' \
  https://gestiio.agenziaplinio.it/api/public/deposito-bagagli/book
```

---

## Documentazione tecnica completa

| Documento | Contenuto |
|-----------|-----------|
| [LUGGAGE_API_PUBLIC.md](./LUGGAGE_API_PUBLIC.md) | Tutti gli endpoint REST pubblici |
| [LUGGAGE_API_ADMIN.md](./LUGGAGE_API_ADMIN.md) | API staff (solo Gestiio, sessione) |
| OpenAPI live | https://gestiio.agenziaplinio.it/api/public/deposito-bagagli/docs |

---

## FAQ

**Posso usare JavaScript che chiama Gestiio direttamente?**  
No. La chiave API finirebbe esposta nel browser.

**Posso solo linkare la pagina Gestiio?**  
Sì. `https://gestiio.agenziaplinio.it/deposito-bagagli` è già operativa e sicura.

**Serve WordPress?**  
No. I file PHP funzionano su qualsiasi hosting con PHP 8.1+ e cURL. Su WordPress si può usare un plugin "Insert PHP" o creare un tema child con template custom.

**Quante richieste posso fare?**  
60 al minuto per IP (rate limit Gestiio).

**Chi genera il codice prenotazione?**  
Gestiio, formato `LB-XXXXXX` (es. `LB-UMKGCK`).

---

## Contatti / supporto

Per chiavi API, tariffe, capacità o problemi su Gestiio → contattare l'amministratore del sistema Gestiio (Carmine / team Plinio).

Per problemi sul sito agenziaplinio.it → sviluppatore del sito marketing.
