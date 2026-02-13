<?php

namespace App\Http\MieClassi;

use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use setasign\Fpdi\Fpdi;

class PdfProdottoSkyGlass
{
    protected $border = 0;
    protected $fpdf;
    protected $col = 0; // Current column
    protected $nomeDocumento;

    public function generaPdf($contratto)
    {


        $contratto->load('prodotto');

        $this->nomeDocumento = 'contratto_' . $contratto->id . '.pdf';
        $this->fpdf = new Fpdi();
        $pagecount = $this->fpdf->setSourceFile(public_path('/pdf/pda_sky_glass.pdf'));


        $tpl = $this->fpdf->importPage(1);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);

        $this->fpdf->SetFont('Arial');

        $this->fpdf->SetFontSize('7'); // set font size
        $this->fpdf->SetAutoPageBreak(false);


        switch ($contratto->prodotto->tipologia_cliente) {
            case 'persona_fisica':
                $posY = 47;
                break;
            case 'ditta_individuale':
                $posY = 51;
                break;
            case 'azienda':
                $posY = 55;
                break;
        }
        $this->fpdf->SetXY(18.3, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


        //Dati del cliente
        $posY = 61.6;
        $incrY = 3.7;
        $this->fpdf->SetXY(34.5, $posY);
        $this->fpdf->Cell(28, 8, $contratto->cognome, $this->border, 0,);
        $this->fpdf->SetXY(72.5, $posY);
        $this->fpdf->Cell(28, 8, $contratto->nome, $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(39.3, $posY);
        $this->fpdf->Cell(32, 8, $contratto->codice_fiscale, $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(42.3, $posY);
        $this->fpdf->Cell(32, 8, $contratto->email, $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(48.3, $posY);
        $this->fpdf->Cell(32, 8, $contratto->telefono, $this->border, 0,);
        //Dati documento
        $posY = 79.9;
        $this->fpdf->SetXY(43.3, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode(ContrattoTelefonia::TIPI_DOCUMENTO[$contratto->tipo_documento]), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(24.3, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode($contratto->numero_documento), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(41.3, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode($contratto->rilasciato_da), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(41.3, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode($contratto->data_rilascio->format('d/m/Y')), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(41.3, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode($contratto->data_scadenza->format('d/m/Y')), $this->border, 0,);

        //Dati a dx

        if ($contratto->prodotto->tipologia_cliente !== 'persona_fisica') {
            $this->fpdf->SetXY(155.5, 46.8);
            $this->fpdf->Cell(28, 8, utf8_decode($contratto->codice_fiscale), $this->border, 0,);

        }


        $posY = 57.8;
        $incrY = 3.6;
        $citta = Comune::find($contratto->citta);
        $this->fpdf->SetXY(120.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($citta->comune), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(120.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->indirizzo), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(120.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->civico), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(121.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($citta->provincia->provincia), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(116.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->cap), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(146.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->nome_citofono), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(116.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->scala), $this->border, 0,);
        $posY += $incrY;
        $this->fpdf->SetXY(116.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->piano), $this->border, 0,);

        //Dati skyglass
        switch ($contratto->prodotto->dimensione) {
            case '43':
                $posY = 121.7;
                break;
            case '55':
                $posY = 125.6;
                break;

            case 65:
                $posY = 129.6;

                break;
        }
        $this->fpdf->SetXY(18.0, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        //Colore sky glass
        switch ($contratto->prodotto->colore_sky_glass) {
            case 'bianco':
                $posY = 140.4;
                break;
            case 'blu':
                $posY = 144.4;
                break;
            case 'nero':
                $posY = 148.4;
                break;
            case 'verde':
                $posY = 152.2;
                break;
            case 'rosa':
                $posY = 156.2;
                break;
        }
        $this->fpdf->SetXY(18.0, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        if ($contratto->prodotto->colore_front_cover !== 'no') {
            $this->fpdf->SetXY(18.0, 167);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            switch ($contratto->prodotto->colore_front_cover) {
                case 'bianco':
                    $posY = 178;
                    break;
                case 'blu':
                    $posY = 182;
                    break;
                case 'verde':
                    $posY = 186;
                    break;
                case 'rosa':
                    $posY = 190;
                    break;
            }
            $this->fpdf->SetXY(18.0, $posY);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }

        if ($contratto->prodotto->sky_stream) {
            $this->fpdf->SetXY(18.0, 197);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            $this->fpdf->SetXY(19.0, 204);
            $this->fpdf->Cell(28, 8, $contratto->prodotto->sky_stream, $this->border, 0,);
        }

        if ($contratto->prodotto->installazione_a_muro) {
            $this->fpdf->SetXY(18.0, 211.5);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }

        switch ($contratto->prodotto->pacchetti_netflix) {
            case 'base':
                $posY = 132.5;
                break;
            case 'standard':
                $posY = 136.5;
                break;
            case 'premium':
                $posY = 140.5;
                break;
        }
        $this->fpdf->SetXY(64.5, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


        //Paccketti sky
        $pacchettiSky = $contratto->prodotto->pacchetti_sky ?? [];

        if (in_array('kids', $pacchettiSky)) {
            $this->fpdf->SetXY(64.5, 154.5);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('cinema', $pacchettiSky)) {
            $this->fpdf->SetXY(64.5, 158.5);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('sport', $pacchettiSky)) {
            $this->fpdf->SetXY(64.5, 162.5);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('calcio', $pacchettiSky)) {
            $this->fpdf->SetXY(64.5, 166.5);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }


        //servizi opzionali
        $serviziOpzionali = $contratto->prodotto->servizi_opzionali ?? [];
        if (in_array('ultra_hd', $serviziOpzionali)) {
            $this->fpdf->SetXY(64.5, 184.0);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('glass_multiscreen', $serviziOpzionali)) {
            $this->fpdf->SetXY(64.5, 188.0);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }


        //Frequenza pagamento
        switch ($contratto->prodotto->frequenza_pagamento_sky_glass) {
            case 'unica':
                $posY = 247.3;
                break;
            case 'acconto+24_mesi':
                $posY = 251.2;
                break;
            case 'acconto+48_mesi':
                $posY = 255.1;
                break;
        }
        $this->fpdf->SetXY(17.4, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        //Frequenza pagamento
        switch ($contratto->prodotto->metodo_pagamento_sky_glass) {
            case 'carta_credito':
                $posY = 266.3;
                $this->fpdf->SetXY(17.3, $posY);
                $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
                break;

            case 'iban':
                $posY = 266.3;
                $this->fpdf->SetXY(107.3, $posY);
                $this->fpdf->Cell(28, 8, $contratto->iban, $this->border, 0,);

        }

        //Frequenza pagamento
        switch ($contratto->prodotto->metodo_pagamento_tv) {
            case 'carta_credito':
                $posY = 247.3;
                break;
            case 'sdd':
                $posY = 251.2;
                break;
        }
        $this->fpdf->SetXY(107.3, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


    }




    public function render($dest = '')
    {
        return $this->fpdf->Output($dest, $this->nomeDocumento);

    }

    /**
     * @return mixed
     */
    public function getNomeDocumento()
    {
        return $this->nomeDocumento;
    }


}
