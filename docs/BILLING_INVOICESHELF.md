# Fatturazione Gestiio + InvoiceShelf

InvoiceShelf è il **motore** di fatture/proforma/pagamenti (stack Docker **indipendente**).  
Gestiio genera i documenti via API; la stessa UI InvoiceShelf è raggiungibile su dominio dedicato.

| Ruolo | URL |
|-------|-----|
| UI InvoiceShelf (staff/admin fatture) | **https://invoice.agenziaplinio.it** |
| UI operativa Gestiio (Metronic) | https://gestiio.agenziaplinio.it/backend |
| API interna Gestiio → IS | `http://invoiceshelf:8080` (rete Docker) |

## Riferimenti ufficiali

- [InvoiceShelf GitHub](https://github.com/InvoiceShelf/InvoiceShelf)
- [Documentazione](https://docs.invoiceshelf.com/)
- [API](https://api-docs.invoiceshelf.com/)
- Compose: [`ops/docker-compose.invoiceshelf.yml`](../ops/docker-compose.invoiceshelf.yml)
- Tunnel Cloudflare: [`ops/remote-add-invoice-tunnel.sh`](../ops/remote-add-invoice-tunnel.sh)

## Architettura

```
Internet → Cloudflare Tunnel (corehost)
        → http://127.0.0.1:8093 → container invoiceshelf
                                   ↕ rete gestiio-…_default
                              gestiio-app (API create estimate/invoice)
```

Stesso volume SQLite: ciò che Gestiio crea via API compare subito su `invoice.agenziaplinio.it`.

## Setup NAS

```bash
mkdir -p /home/Carmine/apps/invoiceshelf
# copia ops/docker-compose.invoiceshelf.yml → docker-compose.yml lì
cd /home/Carmine/apps/invoiceshelf
docker compose up -d
# DNS + ingress tunnel
bash /path/to/ops/remote-add-invoice-tunnel.sh
```

`.env` Gestiio (container):

```env
INVOICESHELF_ENABLED=true
INVOICESHELF_URL=http://invoiceshelf:8080
INVOICESHELF_TOKEN=...
INVOICESHELF_COMPANY_ID=1
INVOICESHELF_CUSTOMER_FORNITORE_ID=1
INVOICESHELF_FORNITORE_NAME="Vincenzo Cinque"
INVOICESHELF_FORNITORE_EMAIL=vincenzo@studioschettino.com
```

Poi: `php artisan config:clear` in `gestiio-app`.

## Prezzi fornitore (solo admin)

| Modulo | Schermata |
|--------|-----------|
| SEND | `/backend/send/impostazioni` → **Importo fornitore** |
| CAF/Patronato | `/backend/tipo-caf-patronato` → campo **Importo fornitore** |

Snapshot su pratica; **mai** mostrato agli agenti.

## Menu Gestiio

- **Documenti e pagamenti** → Proforma CAF/Patronato, Proforma SEND
- **Fatturazione** → Produzioni, Proforma agenti, Documenti fatturazione

## Flusso mensile fornitore

1. Hub CAF/SEND in Gestiio → genera proforma (Estimate su InvoiceShelf).
2. Controlla/edita anche su https://invoice.agenziaplinio.it
3. Emetti / Segna pagata / PDF da Gestiio o dalla UI IS.

```bash
php artisan billing:genera-proforma-fornitore
```

Schedulato il giorno 1 del mese alle 06:30 (periodo = mese precedente).

## Export XML FatturaPA (Agenzia delle Entrate)

InvoiceShelf **non** genera XML SDI nativo. Gestiio esporta XML **FatturaPA** (FPR12) dalle fatture IS,
da caricare nel software di fatturazione elettronica (firma + invio SDI).

| Dove | URL |
|------|-----|
| Elenco fatture IS + download | `/backend/fatturazione/invoiceshelf` |
| Da documento Gestiio (solo se già fattura IS) | `/backend/fatturazione/{id}/xml` |
| Menu | Fatturazione → **XML FatturaPA (SDI)** |

### Config `.env` (Cedente / emittente)

```env
FATTURAPA_CEDENTE_DENOMINAZIONE="Ragione Sociale SRL"
FATTURAPA_CEDENTE_PARTITA_IVA=01234567890
FATTURAPA_CEDENTE_CODICE_FISCALE=01234567890
FATTURAPA_CEDENTE_INDIRIZZO="Via Roma"
FATTURAPA_CEDENTE_NUMERO_CIVICO=1
FATTURAPA_CEDENTE_CAP=00100
FATTURAPA_CEDENTE_COMUNE=Roma
FATTURAPA_CEDENTE_PROVINCIA=RM
FATTURAPA_REGIME_FISCALE=RF01
FATTURAPA_CODICE_DESTINATARIO_DEFAULT=0000000
```

Se i campi cedente sono vuoti, Gestiio prova `vat_id` / `tax_id` / indirizzo della **company** InvoiceShelf.

### Clienti su InvoiceShelf

- `tax_id`: Partita IVA (11 cifre) oppure Codice Fiscale (16 caratteri)
- Campi custom consigliati: `codice_destinatario` (7 char SDI), `pec` (se codice `0000000`)
- Indirizzo di fatturazione completo (via, CAP, comune, provincia)

L’XML **non** è firmato: la firma e l’invio allo SDI restano a carico del programma di FE.
