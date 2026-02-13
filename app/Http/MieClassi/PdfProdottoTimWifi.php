<?php

namespace App\Http\MieClassi;

use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use setasign\Fpdi\Fpdi;

class PdfProdottoTimWifi
{
    protected $border = 0;
    protected $fpdf;
    protected $col = 0; // Current column
    protected $nomeDocumento;

    public function generaPdf(ContrattoTelefonia $contratto)
    {


        $contratto->load('prodotto');

        $this->nomeDocumento = 'contratto_' . $contratto->id . '.pdf';
        $this->fpdf = new Fpdi();
        $pagecount = $this->fpdf->setSourceFile(public_path('/pdf/pda_tim_wifi.pdf'));


        $tpl = $this->fpdf->importPage(1);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);

        $this->fpdf->SetFont('Arial');

        $this->fpdf->SetFontSize('8'); // set font size
        $this->fpdf->SetAutoPageBreak(false);


        $posY = 47;
        $this->fpdf->SetXY(73.5, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


        //Cogonme e nome

        $posX = 59.0;
        $posY = 60.0;
        $this->fpdf->SetFontSize('8'); // set font size
        $chars = str_split($contratto->cognome . ' ' . $contratto->nome);
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 4.05;
        }

        $posY += 9.8;
        $chars = str_split($contratto->codice_fiscale);
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 4.05;
        }

        $posY += 5;
        $chars = str_split($contratto->telefono);
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 4.05;
        }

        //no
        $this->fpdf->SetXY(181.5, 83);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        //no
        $this->fpdf->SetXY(179, 195);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        //no
        $this->fpdf->SetXY(182, 246.5);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


//Data
        $posX = 23.5;

        $posY = 257;
        $chars = str_split($contratto->data->format('dmy'));
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 7.7;
        }

        //////////////////////////// SECONDA PAGINA   ////////////////////////////

        $tpl = $this->fpdf->importPage(2);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);

        //I tuoi dati
        $posY = 60.5;
        $this->fpdf->SetXY(32, $posY);
        $this->fpdf->Cell(28, 8, $contratto->nominativo(), $this->border, 0,);

        $posX = 120;
        $chars = str_split($contratto->codice_fiscale);
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY - 0.6);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 4.9;
        }
        $posY += 5.2;
        $this->fpdf->SetXY(57, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->citta_di_nascita, $this->border, 0,);

        $this->fpdf->SetXY(114, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->provincia_di_nascita, $this->border, 0,);


        $parserCodiceFiscale = new CodiceFiscale();
        if ($parserCodiceFiscale->parse($contratto->codice_fiscale) !== false) {
            $data_di_nascita = $parserCodiceFiscale->getBirthdate()->format('d/m/Y');
            $this->fpdf->SetXY(124, $posY);
            $this->fpdf->Cell(28, 8, $data_di_nascita, $this->border, 0,);
        }

        $posY += 5.0;
        $this->fpdf->SetXY(33, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->indirizzo), $this->border, 0,);
        $this->fpdf->SetXY(170, $posY);
        $this->fpdf->Cell(28, 8, $contratto->civico, $this->border, 0,);


        $posY += 3.8;
        $this->fpdf->SetXY(33, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->comune->comune), $this->border, 0,);

        $this->fpdf->SetXY(120, $posY);
        $this->fpdf->Cell(28, 8, $contratto->comune->targa, $this->border, 0,);

        $this->fpdf->SetXY(172, $posY);
        $this->fpdf->Cell(28, 8, $contratto->cap, $this->border, 0,);

        $posY += 4.1;
        $this->fpdf->SetXY(60, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->indirizzo_impianto), $this->border, 0,);

        $this->fpdf->SetXY(172, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->civico_impianto, $this->border, 0,);

        $posY += 3.9;
        $this->fpdf->SetXY(20, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->piano_impianto, $this->border, 0,);
        $this->fpdf->SetXY(40, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->scala_impianto, $this->border, 0,);
        $this->fpdf->SetXY(67, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->interno_impianto, $this->border, 0,);
        $this->fpdf->SetXY(98, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->citofono_impianto, $this->border, 0,);
        $citta = Comune::find($contratto->prodotto->localita_impianto);
        $this->fpdf->SetXY(127, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($citta->comune), $this->border, 0,);
        $this->fpdf->SetXY(174, $posY);
        $this->fpdf->Cell(28, 8, $citta->targa, $this->border, 0,);

        $posY += 4.1;
        $this->fpdf->SetXY(105, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->indirizzo_fattura), $this->border, 0,);
        $this->fpdf->SetXY(172, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->civico_fattura, $this->border, 0,);

        $posY += 4.1;
        $this->fpdf->SetXY(24, $posY);
        if ($contratto->prodotto->citta_fattura) {
            $citta = Comune::find($contratto->prodotto->citta_fattura);
            $this->fpdf->Cell(28, 8, utf8_decode($citta->comune), $this->border, 0,);
            $this->fpdf->SetXY(127, $posY);
            $this->fpdf->Cell(28, 8, $citta->targa, $this->border, 0,);

        }
        $this->fpdf->SetXY(172, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->cap_fattura, $this->border, 0,);


        $posY += 4.1;
        $this->fpdf->SetXY(120, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->indirizzo_timcard), $this->border, 0,);
        $posY += 4.1;
        $this->fpdf->SetXY(20, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->civico_timcard, $this->border, 0,);
        if ($contratto->prodotto->citta_timcard) {
            $citta = Comune::find($contratto->prodotto->citta_timcard);
            $this->fpdf->Cell(30, 8, utf8_decode($citta->comune), $this->border, 0,);
            $this->fpdf->SetXY(127, $posY);
            $this->fpdf->Cell(28, 8, $citta->targa, $this->border, 0,);

        }
        $this->fpdf->SetXY(172, $posY);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->cap_timcard, $this->border, 0,);

        $posY += 4.1;
        $this->fpdf->SetXY(37, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode(ContrattoTelefonia::TIPI_DOCUMENTO[$contratto->tipo_documento]), $this->border, 0,);
        $this->fpdf->SetXY(70, $posY);
        $this->fpdf->Cell(28, 8, $contratto->numero_documento, $this->border, 0,);
        $this->fpdf->SetXY(132, $posY);
        $this->fpdf->Cell(28, 8, $contratto->rilasciato_da, $this->border, 0,);

        $posY += 4.1;
        $this->fpdf->SetXY(37, $posY);
        $citta = Comune::find($contratto->comune_rilascio);
        $this->fpdf->Cell(28, 8, utf8_decode($citta->comune), $this->border, 0,);
        $this->fpdf->SetXY(78, $posY);
        $this->fpdf->Cell(28, 8, $contratto->data_rilascio?->format('d/m/Y'), $this->border, 0,);
        $this->fpdf->SetXY(132, $posY);
        $this->fpdf->Cell(28, 8, $contratto->data_scadenza?->format('d/m/Y'), $this->border, 0,);

        $posY += 4.1;
        $this->fpdf->SetXY(50, $posY);
        $this->fpdf->Cell(60, 8, $contratto->prodotto->numero_cellulare, $this->border, 0,);
        $this->fpdf->SetXY(110, $posY);
        $this->fpdf->Cell(28, 8, $contratto->telefono, $this->border, 0,);
        $this->fpdf->SetXY(170, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->recapito_alternativo), $this->border, 0,);

        $posY += 4.2;
        $email = explode('@', $contratto->email);
        $this->fpdf->SetXY(50, $posY);
        $this->fpdf->Cell(60, 8, utf8_decode($email[0]), $this->border, 0,);
        $this->fpdf->SetXY(120, $posY);
        $this->fpdf->Cell(60, 8, utf8_decode($email[1]), $this->border, 0,);

        $posY += 7.2;
        $this->fpdf->SetXY(30, $posY);
        $this->fpdf->Cell(60, 8, utf8_decode($contratto->prodotto->firmatario_nome_cognome), $this->border, 0,);
        $this->fpdf->SetXY(133, $posY);
        $this->fpdf->Cell(60, 8, utf8_decode($contratto->prodotto->firmatario_indirizzo_completo), $this->border, 0,);

        $posY += 4.2;
        $this->fpdf->SetXY(30, $posY);
        $this->fpdf->Cell(60, 8, $contratto->prodotto->firmatario_tipo_documento, $this->border, 0,);
        $this->fpdf->SetXY(78, $posY);
        $this->fpdf->Cell(60, 8, $contratto->prodotto->firmatario_rilasciato_da, $this->border, 0,);


        $this->fpdf->SetXY(120, $posY);
        $this->fpdf->Cell(60, 8, utf8_decode($contratto->prodotto->firmatario_data_emissione), $this->border, 0,);
        $this->fpdf->SetXY(160, $posY);
        $this->fpdf->Cell(60, 8, utf8_decode($contratto->prodotto->firmatario_data_scadenza), $this->border, 0,);


        //no
        $posY = match ($contratto->prodotto->la_tua_linea_di_casa) {
            'ATTIVAZIONE NUOVA LINEA FISSA' => 139.5,
            'ATTIVAZIONE LINEA FISSA PER PASSAGGIO DA ALTRO OPERATORE' => 144

        };

        $this->fpdf->SetXY(11.5, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


        //no
        $posX = match ($contratto->prodotto->la_tua_offerta) {
            'TIM WiFi Power FIBRA' => 9, //9
            'TIM WiFi Power MEGA FTTCF' => 73,
            'TIM WiFi Power MEGA FTTE' => 73,
        };

        $this->fpdf->SetXY($posX, 216);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        if ($contratto->prodotto->la_tua_offerta !== 'TIM WiFi Power FIBRA') {
            $posX = match ($contratto->prodotto->la_tua_offerta) {
                'TIM WiFi Power MEGA FTTCF' => 96,
                'TIM WiFi Power MEGA FTTE' => 105,
            };

            $this->fpdf->SetXY($posX, 220);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }

        if (is_array($contratto->prodotto->opzione_inclusa)) {
            if (in_array('MODEM', $contratto->prodotto->opzione_inclusa)) {
                $this->fpdf->SetXY(14, 232);
                $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            }
            if (in_array('VOCE', $contratto->prodotto->opzione_inclusa)) {
                $this->fpdf->SetXY(36, 232);
                $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            }
            if (in_array('TIM NAVIGAZIONE SICURA', $contratto->prodotto->opzione_inclusa)) {
                $this->fpdf->SetXY(56, 232);
                $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            }
            if (in_array('PREMIUM FLEXY', $contratto->prodotto->opzione_inclusa)) {
                $this->fpdf->SetXY(98.5, 232);
                $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            }
            if (in_array('SMART HOME', $contratto->prodotto->opzione_inclusa)) {
                $this->fpdf->SetXY(132, 232);
                $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            }
        }

        $this->fpdf->SetXY(match ($contratto->prodotto->qualora) {
            0 => 138.7,
            1 => 132.5
        }, 245);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        $this->fpdf->SetXY(159, match ($contratto->prodotto->modem_tim) {
            'TIM HUB+ in 48 rate' => 234,
            'TIM HUB+ in 24 rate' => 238,
            'TIM HUB+ in 12 rate' => 242,
            'TIM HUB+ in unica soluzione' => 246,
        });
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


        if (in_array('TIMVISION con Disney+', $contratto->prodotto->offerta_scelta ?? [])) {
            $this->fpdf->SetXY(7, 258);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('TIMVISION con Netflix', $contratto->prodotto->offerta_scelta ?? [])) {
            $this->fpdf->SetXY(40, 258);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('TIMVISION Intrattenimento', $contratto->prodotto->offerta_scelta ?? [])) {
            $this->fpdf->SetXY(71, 258);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('Opzione + 5G Power', $contratto->prodotto->offerta_scelta ?? [])) {
            $this->fpdf->SetXY(110, 258);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }


        //////////////////////////// TERZA PAGINA   ////////////////////////////

        $tpl = $this->fpdf->importPage(3);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);


        //////////////////////////// QUARTA PAGINA   ////////////////////////////

        $tpl = $this->fpdf->importPage(4);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);

        //no

        $this->fpdf->SetXY(172, 56);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        $this->fpdf->SetXY(172, 61.2);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        $this->fpdf->SetXY(172, 66.5);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        $this->fpdf->SetXY(172, 72);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


        if ($contratto->prodotto->linea_mobile_tim) {
            $this->fpdf->SetXY(193.8, 56);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            $this->fpdf->SetXY(193.8, 61.2);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            $this->fpdf->SetXY(193.8, 66.5);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            $this->fpdf->SetXY(193.8, 72);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }

        $this->fpdf->SetXY(163, 96.4);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


//si
        $this->fpdf->SetXY(134, 191.5);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        $this->fpdf->SetXY(134, 194.8);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        $this->fpdf->SetXY(134, 198);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        $this->fpdf->SetXY(134, 201.1);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


        $posX = 74;
        $this->fpdf->SetXY($posX, 212);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->nominativo()), $this->border, 0,);

        $posY = 216;
        $chars = str_split($contratto->codice_fiscale);
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 5.85;
        }


        $posY = 227;
        $chars = str_split($contratto->iban);
        $posXIncr = 20;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 4.01;
        }


        //////////////////////////// QUINTA PAGINA   ////////////////////////////

        $tpl = $this->fpdf->importPage(5);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);


        //////////////////////////// SESTA PAGINA   ////////////////////////////


        $this->sestaPagina($contratto);


        //////////////////////////// SETTIMA PAGINA   ////////////////////////////

        $this->settimaPagina($contratto);
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


    protected function sestaPagina($contratto)
    {
        $pagecount = $this->fpdf->setSourceFile(public_path('/pdf/dbu_tim.pdf'));


        $tpl = $this->fpdf->importPage(1);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);


        $posY = 53;
        $this->fpdf->SetXY(73.5, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


        //Cogonme e nome

        $posX = 59.0;
        $posY += 13;
        $this->fpdf->SetFontSize('8'); // set font size
        $chars = str_split($contratto->cognome . ' ' . $contratto->nome);
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 4.05;
        }

        $posY += 9.8;
        $chars = str_split($contratto->codice_fiscale);
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 4.05;
        }

        $posY += 5;
        $chars = str_split($contratto->telefono);
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 4.05;
        }

        //no
        $this->fpdf->SetXY(181.5, 91);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        //no
        $this->fpdf->SetXY(179, 211);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        //no
        $this->fpdf->SetXY(99, 256);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


//Data
        $posX = 23.5;

        $posY = 263;
        $chars = str_split($contratto->data->format('dmy'));
        $posXIncr = $posX;
        foreach ($chars as $char) {
            $this->fpdf->SetXY($posXIncr, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posXIncr += 7.7;
        }

    }


    protected function settimaPagina($contratto)
    {
        $tpl = $this->fpdf->importPage(2);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);


        $posY = 43.5;
        $this->fpdf->SetXY(16, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        $posY = 62.5;
        $this->fpdf->SetXY(63.5, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        $posY = 222;
        $this->fpdf->SetXY(51.3, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);


    }


}
