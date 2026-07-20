<?php

namespace App\Http\Services\Billing;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use RuntimeException;

/**
 * Genera XML FatturaPA (FPR12/FPA12) a partire da una fattura InvoiceShelf.
 * Destinato all'upload su software di fatturazione elettronica (firma + invio SDI a carico di quel software).
 */
class FatturaPaXmlBuilder
{
    private const NS = 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2';

    /**
     * @param  array<string, mixed>  $invoice  Payload data da GET /api/v1/invoices/{id}
     * @return array{xml: string, filename: string}
     */
    public function buildFromInvoiceShelf(array $invoice): array
    {
        $invoice = $invoice['data'] ?? $invoice;
        if (! is_array($invoice) || empty($invoice['id'])) {
            throw new InvalidArgumentException('Fattura InvoiceShelf non valida.');
        }

        $cedente = $this->resolveCedente($invoice);
        $cessionario = $this->resolveCessionario($invoice);
        $lines = $this->buildLines($invoice);
        $riepiloghi = $this->buildRiepiloghi($lines);

        $formato = (string) config('fatturapa.formato_trasmissione', 'FPR12');
        $progressivoInvio = $this->progressivoInvio((int) $invoice['id']);
        $fileProgressivo = $this->fileProgressivo((int) $invoice['id']);
        $pivaTrasmittente = $cedente['partita_iva'];

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS(self::NS, 'FatturaElettronica');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('versione', $formato);
        $root->setAttribute(
            'xsi:schemaLocation',
            self::NS.' http://www.fatturapa.gov.it/export/fatturazione/sdi/schema/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd'
        );
        $dom->appendChild($root);

        $header = $this->el($dom, 'FatturaElettronicaHeader');
        $root->appendChild($header);

        $datiTrasmissione = $this->el($dom, 'DatiTrasmissione');
        $header->appendChild($datiTrasmissione);
        $idTrasm = $this->el($dom, 'IdTrasmittente');
        $datiTrasmissione->appendChild($idTrasm);
        $this->appendText($dom, $idTrasm, 'IdPaese', $cedente['nazione']);
        $this->appendText($dom, $idTrasm, 'IdCodice', $pivaTrasmittente);
        $this->appendText($dom, $datiTrasmissione, 'ProgressivoInvio', $progressivoInvio);
        $this->appendText($dom, $datiTrasmissione, 'FormatoTrasmissione', $formato);
        $this->appendText($dom, $datiTrasmissione, 'CodiceDestinatario', $cessionario['codice_destinatario']);
        if ($cessionario['pec'] !== '' && $cessionario['codice_destinatario'] === '0000000') {
            $this->appendText($dom, $datiTrasmissione, 'PECDestinatario', $cessionario['pec']);
        }

        $cedenteNode = $this->el($dom, 'CedentePrestatore');
        $header->appendChild($cedenteNode);
        $datiAnag = $this->el($dom, 'DatiAnagrafici');
        $cedenteNode->appendChild($datiAnag);
        $idFiscale = $this->el($dom, 'IdFiscaleIVA');
        $datiAnag->appendChild($idFiscale);
        $this->appendText($dom, $idFiscale, 'IdPaese', $cedente['nazione']);
        $this->appendText($dom, $idFiscale, 'IdCodice', $cedente['partita_iva']);
        if ($cedente['codice_fiscale'] !== '') {
            $this->appendText($dom, $datiAnag, 'CodiceFiscale', $cedente['codice_fiscale']);
        }
        $anagrafica = $this->el($dom, 'Anagrafica');
        $datiAnag->appendChild($anagrafica);
        $this->appendText($dom, $anagrafica, 'Denominazione', $cedente['denominazione']);
        $this->appendText($dom, $datiAnag, 'RegimeFiscale', (string) config('fatturapa.regime_fiscale', 'RF01'));

        $sede = $this->el($dom, 'Sede');
        $cedenteNode->appendChild($sede);
        $this->appendText($dom, $sede, 'Indirizzo', $cedente['indirizzo']);
        if ($cedente['numero_civico'] !== '') {
            $this->appendText($dom, $sede, 'NumeroCivico', $cedente['numero_civico']);
        }
        $this->appendText($dom, $sede, 'CAP', $cedente['cap']);
        $this->appendText($dom, $sede, 'Comune', $cedente['comune']);
        if ($cedente['provincia'] !== '' && $cedente['nazione'] === 'IT') {
            $this->appendText($dom, $sede, 'Provincia', substr($cedente['provincia'], 0, 2));
        }
        $this->appendText($dom, $sede, 'Nazione', $cedente['nazione']);

        if ($cedente['telefono'] !== '' || $cedente['email'] !== '') {
            $contatti = $this->el($dom, 'Contatti');
            $cedenteNode->appendChild($contatti);
            if ($cedente['telefono'] !== '') {
                $this->appendText($dom, $contatti, 'Telefono', mb_substr($cedente['telefono'], 0, 12));
            }
            if ($cedente['email'] !== '') {
                $this->appendText($dom, $contatti, 'Email', mb_substr($cedente['email'], 0, 60));
            }
        }

        $cessionarioNode = $this->el($dom, 'CessionarioCommittente');
        $header->appendChild($cessionarioNode);
        $datiAnagC = $this->el($dom, 'DatiAnagrafici');
        $cessionarioNode->appendChild($datiAnagC);
        if ($cessionario['partita_iva'] !== '') {
            $idFiscC = $this->el($dom, 'IdFiscaleIVA');
            $datiAnagC->appendChild($idFiscC);
            $this->appendText($dom, $idFiscC, 'IdPaese', $cessionario['nazione']);
            $this->appendText($dom, $idFiscC, 'IdCodice', $cessionario['partita_iva']);
        }
        if ($cessionario['codice_fiscale'] !== '') {
            $this->appendText($dom, $datiAnagC, 'CodiceFiscale', $cessionario['codice_fiscale']);
        }
        $anagC = $this->el($dom, 'Anagrafica');
        $datiAnagC->appendChild($anagC);
        $this->appendText($dom, $anagC, 'Denominazione', $cessionario['denominazione']);

        $sedeC = $this->el($dom, 'Sede');
        $cessionarioNode->appendChild($sedeC);
        $this->appendText($dom, $sedeC, 'Indirizzo', $cessionario['indirizzo']);
        $this->appendText($dom, $sedeC, 'CAP', $cessionario['cap']);
        $this->appendText($dom, $sedeC, 'Comune', $cessionario['comune']);
        if ($cessionario['provincia'] !== '' && $cessionario['nazione'] === 'IT') {
            $this->appendText($dom, $sedeC, 'Provincia', substr($cessionario['provincia'], 0, 2));
        }
        $this->appendText($dom, $sedeC, 'Nazione', $cessionario['nazione']);

        $body = $this->el($dom, 'FatturaElettronicaBody');
        $root->appendChild($body);

        $datiGenerali = $this->el($dom, 'DatiGenerali');
        $body->appendChild($datiGenerali);
        $datiDoc = $this->el($dom, 'DatiGeneraliDocumento');
        $datiGenerali->appendChild($datiDoc);
        $this->appendText($dom, $datiDoc, 'TipoDocumento', (string) config('fatturapa.tipo_documento', 'TD01'));
        $this->appendText($dom, $datiDoc, 'Divisa', 'EUR');
        $this->appendText($dom, $datiDoc, 'Data', $this->normalizeDate($invoice['invoice_date'] ?? null));
        $this->appendText($dom, $datiDoc, 'Numero', $this->normalizeInvoiceNumber((string) ($invoice['invoice_number'] ?? $invoice['id'])));
        $this->appendText($dom, $datiDoc, 'ImportoTotaleDocumento', $this->money((float) ($invoice['total'] ?? 0)));
        $notes = trim((string) ($invoice['notes'] ?? ''));
        if ($notes !== '') {
            $this->appendText($dom, $datiDoc, 'Causale', mb_substr($notes, 0, 200));
        }

        $beni = $this->el($dom, 'DatiBeniServizi');
        $body->appendChild($beni);
        foreach ($lines as $line) {
            $det = $this->el($dom, 'DettaglioLinee');
            $beni->appendChild($det);
            $this->appendText($dom, $det, 'NumeroLinea', (string) $line['numero']);
            $this->appendText($dom, $det, 'Descrizione', $line['descrizione']);
            $this->appendText($dom, $det, 'Quantita', $this->qty($line['quantita']));
            $this->appendText($dom, $det, 'PrezzoUnitario', $this->unitPrice($line['prezzo_unitario']));
            $this->appendText($dom, $det, 'PrezzoTotale', $this->money($line['prezzo_totale']));
            $this->appendText($dom, $det, 'AliquotaIVA', $this->money($line['aliquota']));
            if ($line['natura'] !== null) {
                $this->appendText($dom, $det, 'Natura', $line['natura']);
            }
        }
        foreach ($riepiloghi as $r) {
            $riep = $this->el($dom, 'DatiRiepilogo');
            $beni->appendChild($riep);
            $this->appendText($dom, $riep, 'AliquotaIVA', $this->money($r['aliquota']));
            if ($r['natura'] !== null) {
                $this->appendText($dom, $riep, 'Natura', $r['natura']);
            }
            $this->appendText($dom, $riep, 'ImponibileImporto', $this->money($r['imponibile']));
            $this->appendText($dom, $riep, 'Imposta', $this->money($r['imposta']));
            $this->appendText($dom, $riep, 'EsigibilitaIVA', 'I');
        }

        $dueDate = $invoice['due_date'] ?? null;
        if ($dueDate) {
            $pag = $this->el($dom, 'DatiPagamento');
            $body->appendChild($pag);
            $this->appendText($dom, $pag, 'CondizioniPagamento', 'TP02');
            $detPag = $this->el($dom, 'DettaglioPagamento');
            $pag->appendChild($detPag);
            $this->appendText($dom, $detPag, 'ModalitaPagamento', 'MP05');
            $this->appendText($dom, $detPag, 'DataScadenzaPagamento', $this->normalizeDate($dueDate));
            $this->appendText(
                $dom,
                $detPag,
                'ImportoPagamento',
                $this->money((float) ($invoice['due_amount'] ?? $invoice['total'] ?? 0))
            );
        }

        $xml = $dom->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Generazione XML FatturaPA fallita.');
        }

        return [
            'xml' => $xml,
            'filename' => 'IT'.$pivaTrasmittente.'_'.$fileProgressivo.'.xml',
        ];
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return array<string, string>
     */
    protected function resolveCedente(array $invoice): array
    {
        $cfg = config('fatturapa.cedente', []);
        $company = is_array($invoice['company'] ?? null) ? $invoice['company'] : [];
        $address = is_array($company['address'] ?? null) ? $company['address'] : [];

        $partitaIva = $this->digits((string) ($cfg['partita_iva'] ?? ''))
            ?: $this->digits((string) ($company['vat_id'] ?? ''));
        if (strlen($partitaIva) !== 11) {
            $partitaIva = '';
        }

        $codiceFiscale = $this->alnum((string) ($cfg['codice_fiscale'] ?? ''));
        $companyTax = $this->alnum((string) ($company['tax_id'] ?? ''));
        if ($codiceFiscale === '' && strlen($companyTax) === 16) {
            $codiceFiscale = $companyTax;
        } elseif ($codiceFiscale === '' && strlen($companyTax) === 11 && $partitaIva === '') {
            // tax_id company usato come P.IVA se non c'è vat_id
            $partitaIva = $companyTax;
        } elseif ($codiceFiscale === '' && strlen($companyTax) === 11) {
            $codiceFiscale = $companyTax;
        }

        $cedente = [
            'denominazione' => trim((string) ($cfg['denominazione'] ?? ''))
                ?: trim((string) ($company['name'] ?? '')),
            'partita_iva' => $partitaIva,
            'codice_fiscale' => $codiceFiscale,
            'indirizzo' => trim((string) ($cfg['indirizzo'] ?? ''))
                ?: trim((string) ($address['address_street_1'] ?? '')),
            'numero_civico' => trim((string) ($cfg['numero_civico'] ?? '')),
            'cap' => $this->digits((string) ($cfg['cap'] ?? ''))
                ?: $this->digits((string) ($address['zip'] ?? '')),
            'comune' => trim((string) ($cfg['comune'] ?? ''))
                ?: trim((string) ($address['city'] ?? '')),
            'provincia' => strtoupper(trim((string) ($cfg['provincia'] ?? '')))
                ?: strtoupper(trim((string) ($address['state'] ?? ''))),
            'nazione' => strtoupper(trim((string) ($cfg['nazione'] ?? 'IT'))) ?: 'IT',
            'telefono' => trim((string) ($cfg['telefono'] ?? ''))
                ?: trim((string) ($address['phone'] ?? '')),
            'email' => trim((string) ($cfg['email'] ?? '')),
        ];

        if ($cedente['denominazione'] === '') {
            throw new RuntimeException('Configura FATTURAPA_CEDENTE_DENOMINAZIONE o il nome company su InvoiceShelf.');
        }
        if (strlen($cedente['partita_iva']) !== 11) {
            throw new RuntimeException(
                'Configura FATTURAPA_CEDENTE_PARTITA_IVA (11 cifre) o vat_id della company InvoiceShelf.'
            );
        }
        if ($cedente['indirizzo'] === '' || $cedente['comune'] === '' || strlen($cedente['cap']) !== 5) {
            throw new RuntimeException(
                'Indirizzo cedente incompleto: imposta FATTURAPA_CEDENTE_INDIRIZZO/CAP/COMUNE (o indirizzo company IS).'
            );
        }
        if ($cedente['nazione'] === 'IT' && strlen($cedente['provincia']) !== 2) {
            throw new RuntimeException('Configura FATTURAPA_CEDENTE_PROVINCIA (2 lettere, es. NA).');
        }

        return $cedente;
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return array<string, string>
     */
    protected function resolveCessionario(array $invoice): array
    {
        $customer = is_array($invoice['customer'] ?? null) ? $invoice['customer'] : [];
        if ($customer === []) {
            throw new RuntimeException('Fattura senza cliente: impossibile generare FatturaPA.');
        }

        $billing = is_array($customer['billing'] ?? null) ? $customer['billing'] : [];
        $fields = is_array($customer['fields'] ?? null) ? $customer['fields'] : [];
        $aliases = config('fatturapa.customer_field_aliases', []);

        $partitaIva = $this->digits((string) ($this->customField($fields, $aliases['partita_iva'] ?? []) ?? ''))
            ?: $this->digits((string) ($customer['tax_id'] ?? ''));
        if (strlen($partitaIva) === 16) {
            $codiceFiscale = $this->alnum($partitaIva);
            $partitaIva = '';
        } else {
            $codiceFiscale = $this->alnum((string) ($this->customField($fields, $aliases['codice_fiscale'] ?? []) ?? ''));
            if ($codiceFiscale === '' && strlen($this->alnum((string) ($customer['tax_id'] ?? ''))) === 16) {
                $codiceFiscale = $this->alnum((string) ($customer['tax_id'] ?? ''));
            }
            if (strlen($partitaIva) !== 11) {
                $partitaIva = '';
            }
        }

        if ($partitaIva === '' && $codiceFiscale === '') {
            throw new RuntimeException(
                'Cliente senza Partita IVA né Codice Fiscale. Compila tax_id / campi custom su InvoiceShelf.'
            );
        }

        $codiceDest = strtoupper(trim((string) ($this->customField($fields, $aliases['codice_destinatario'] ?? []) ?? '')));
        if ($codiceDest === '') {
            $codiceDest = strtoupper((string) config('fatturapa.codice_destinatario_default', '0000000'));
        }
        $codiceDest = substr(preg_replace('/[^A-Z0-9]/', '', $codiceDest) ?: '0000000', 0, 7);
        if (strlen($codiceDest) < 7) {
            $codiceDest = str_pad($codiceDest, 7, '0');
        }

        $pec = trim((string) ($this->customField($fields, $aliases['pec'] ?? []) ?? ''));
        $country = is_array($billing['country'] ?? null) ? $billing['country'] : [];
        $nazione = strtoupper((string) ($country['code'] ?? 'IT'));
        if (strlen($nazione) !== 2) {
            $nazione = 'IT';
        }

        $denominazione = trim((string) ($customer['company_name'] ?? ''))
            ?: trim((string) ($billing['name'] ?? ''))
            ?: trim((string) ($customer['name'] ?? ''));
        if ($denominazione === '') {
            throw new RuntimeException('Cliente senza denominazione su InvoiceShelf.');
        }

        $indirizzo = trim((string) ($billing['address_street_1'] ?? ''));
        $cap = $this->digits((string) ($billing['zip'] ?? ''));
        $comune = trim((string) ($billing['city'] ?? ''));
        $provincia = strtoupper(trim((string) ($billing['state'] ?? '')));

        if ($indirizzo === '' || $comune === '' || strlen($cap) !== 5) {
            throw new RuntimeException(
                'Indirizzo di fatturazione del cliente incompleto su InvoiceShelf (via, CAP 5 cifre, comune).'
            );
        }
        if ($nazione === 'IT' && strlen($provincia) !== 2) {
            throw new RuntimeException('Provincia cliente mancante o non valida (2 lettere) su InvoiceShelf.');
        }

        return [
            'denominazione' => $denominazione,
            'partita_iva' => $partitaIva,
            'codice_fiscale' => $codiceFiscale,
            'indirizzo' => $indirizzo,
            'cap' => $cap,
            'comune' => $comune,
            'provincia' => $provincia,
            'nazione' => $nazione,
            'codice_destinatario' => $codiceDest,
            'pec' => $pec,
        ];
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return list<array{numero: int, descrizione: string, quantita: float, prezzo_unitario: float, prezzo_totale: float, aliquota: float, natura: ?string}>
     */
    protected function buildLines(array $invoice): array
    {
        $items = $invoice['items'] ?? [];
        if ($items instanceof \Illuminate\Support\Collection) {
            $items = $items->all();
        }
        if (! is_array($items) || $items === []) {
            throw new RuntimeException('Fattura senza righe: impossibile generare FatturaPA.');
        }

        $taxIncluded = (bool) ($invoice['tax_included'] ?? false);
        $lines = [];
        $n = 0;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $n++;
            $qty = (float) ($item['quantity'] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }
            $price = (float) ($item['price'] ?? 0);
            $discountVal = (float) ($item['discount_val'] ?? 0);
            $aliquota = $this->lineAliquota($item, $invoice);
            $unitNet = $price;
            if ($taxIncluded && $aliquota > 0) {
                $unitNet = $price / (1 + ($aliquota / 100));
            }
            // Incorpora sconto nel prezzo unitario così Quantità × PrezzoUnitario = PrezzoTotale
            $lineNet = ($unitNet * $qty) - $discountVal;
            if ($lineNet < 0) {
                $lineNet = 0.0;
            }
            $prezzoTotale = round($lineNet, 2);
            $prezzoUnitario = $qty > 0 ? round($prezzoTotale / $qty, 8) : 0.0;
            // Ricalcola totale da unitario arrotondato (coerenza SDI)
            $prezzoTotale = round($prezzoUnitario * $qty, 2);

            $natura = $aliquota <= 0.0
                ? (string) config('fatturapa.natura_zero_default', 'N4')
                : null;

            $desc = trim((string) ($item['name'] ?? 'Voce'));
            $extra = trim((string) ($item['description'] ?? ''));
            if ($extra !== '') {
                $desc .= ' — '.$extra;
            }

            $lines[] = [
                'numero' => $n,
                'descrizione' => mb_substr($desc, 0, 1000),
                'quantita' => $qty,
                'prezzo_unitario' => $prezzoUnitario,
                'prezzo_totale' => $prezzoTotale,
                'aliquota' => $aliquota,
                'natura' => $natura,
            ];
        }

        if ($lines === []) {
            throw new RuntimeException('Fattura senza righe valide.');
        }

        return $lines;
    }

    /**
     * @param  list<array{aliquota: float, prezzo_totale: float, natura: ?string}>  $lines
     * @return list<array{aliquota: float, imponibile: float, imposta: float, natura: ?string}>
     */
    protected function buildRiepiloghi(array $lines): array
    {
        $groups = [];
        foreach ($lines as $line) {
            $key = number_format($line['aliquota'], 2, '.', '').'|'.($line['natura'] ?? '');
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'aliquota' => $line['aliquota'],
                    'imponibile' => 0.0,
                    'natura' => $line['natura'],
                ];
            }
            $groups[$key]['imponibile'] += $line['prezzo_totale'];
        }

        $out = [];
        foreach ($groups as $g) {
            $imponibile = round($g['imponibile'], 2);
            $out[] = [
                'aliquota' => $g['aliquota'],
                'imponibile' => $imponibile,
                'imposta' => round($imponibile * ($g['aliquota'] / 100), 2),
                'natura' => $g['natura'],
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $invoice
     */
    protected function lineAliquota(array $item, array $invoice): float
    {
        $taxes = $item['taxes'] ?? [];
        if (is_array($taxes)) {
            foreach ($taxes as $tax) {
                if (is_array($tax) && isset($tax['percent'])) {
                    return (float) $tax['percent'];
                }
            }
        }

        $invoiceTaxes = $invoice['taxes'] ?? [];
        if (is_array($invoiceTaxes)) {
            foreach ($invoiceTaxes as $tax) {
                if (is_array($tax) && isset($tax['percent'])) {
                    return (float) $tax['percent'];
                }
            }
        }

        return 0.0;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  list<string>  $aliases
     */
    protected function customField(array $fields, array $aliases): ?string
    {
        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $cf = is_array($field['custom_field'] ?? null) ? $field['custom_field'] : [];
            $label = strtolower(trim(
                (string) ($cf['slug'] ?? $cf['name'] ?? $cf['label'] ?? $field['name'] ?? '')
            ));
            if ($label === '') {
                continue;
            }
            foreach ($aliases as $alias) {
                $alias = strtolower($alias);
                if ($label === $alias || str_contains($label, $alias)) {
                    $value = $field['default_answer']
                        ?? $field['string_answer']
                        ?? $field['number_answer']
                        ?? null;
                    if ($value !== null && trim((string) $value) !== '') {
                        return trim((string) $value);
                    }
                }
            }
        }

        return null;
    }

    protected function progressivoInvio(int $invoiceId): string
    {
        return substr(str_pad((string) $invoiceId, 5, '0', STR_PAD_LEFT), -10);
    }

    protected function fileProgressivo(int $invoiceId): string
    {
        $base = strtoupper(base_convert((string) max(1, $invoiceId), 10, 36));

        return str_pad(substr($base, -5), 5, '0', STR_PAD_LEFT);
    }

    protected function normalizeDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return now()->format('Y-m-d');
        }
        try {
            return \Carbon\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return now()->format('Y-m-d');
        }
    }

    protected function normalizeInvoiceNumber(string $number): string
    {
        $number = trim($number);

        return $number === '' ? '1' : mb_substr($number, 0, 20);
    }

    protected function money(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }

    protected function unitPrice(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 8, '.', ''), '0'), '.') ?: '0';
    }

    protected function qty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 8, '.', ''), '0'), '.') ?: '0';
    }

    protected function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    protected function alnum(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');
    }

    protected function el(DOMDocument $dom, string $name): DOMElement
    {
        return $dom->createElementNS(self::NS, $name);
    }

    protected function appendText(DOMDocument $dom, DOMElement $parent, string $name, string $value): void
    {
        $el = $this->el($dom, $name);
        $el->appendChild($dom->createTextNode($value));
        $parent->appendChild($el);
    }
}
