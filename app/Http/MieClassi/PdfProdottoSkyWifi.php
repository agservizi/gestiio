<?php

namespace App\Http\MieClassi;

use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use App\Models\Nazione;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use setasign\Fpdi\Fpdi;


class PdfProdottoSkyWifi
{
    protected $border = 0;
    protected $fpdf;
    protected $col = 0; // Current column
    protected $y0;      // Ordinate of column start
    protected $nomeDocumento;


    public function generaPdf(ContrattoTelefonia $contratto)
    {


        $contratto->load('prodotto');

        $this->nomeDocumento = 'contratto_' . $contratto->id . '.pdf';
        $this->fpdf = new Fpdi();
        $pagecount = $this->fpdf->setSourceFile(public_path('/pdf/pda_sky.pdf'));


        $tpl = $this->fpdf->importPage(1);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);

        $this->fpdf->SetFont('Arial');

        $this->fpdf->SetFontSize('7'); // set font size
        $this->fpdf->SetAutoPageBreak(false);

        //Internet
        $this->fpdf->SetXY(107, 30);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);




        $luogoNascita = null;
        $dataNascita = null;
        $sesso = null;



        //Codice cliente
        $chars = str_split($contratto->prodotto->codice_cliente);
        $posX = 41.2;
        $posY=24.8;
        $this->fpdf->SetFontSize('8'); // set font size

        foreach ($chars as $char) {
            $this->fpdf->SetXY($posX, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posX += 3.28;
        }
        $this->fpdf->SetFontSize('7'); // set font size


        switch ($contratto->prodotto->tipologia_cliente) {
            case 'persona_fisica':
                $posX = 33.2;

                $parserCodiceFiscale = new CodiceFiscale();
                if ($parserCodiceFiscale->parse($contratto->codice_fiscale) !== false) {
                    $sesso = $parserCodiceFiscale->getGender();
                    $dataNascita = $parserCodiceFiscale->getBirthdate()->format('d/m/Y');
                    $luogoNascita = $parserCodiceFiscale->getBirthPlace();
                    $luogoNascita = $parserCodiceFiscale->getBirthPlaceComplete();
                }


                break;
            case 'societa':
                $posX = 62.4;
                break;
            case 'ditta_individuale':
                $posX = 87;
                break;
            case 'associazione':
                $posX = 133;
                break;
        }
        $this->fpdf->SetXY($posX, 57.5);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
//✔

        //Dati del cliente
        $posY = 62.3;
        $incrY = 4.18;
        $this->fpdf->SetFont('Arial');
        $this->fpdf->SetXY(25, $posY);
        $this->fpdf->Cell(28, 8, $contratto->cognome, $this->border, 0,);
        $this->fpdf->SetXY(82, $posY);
        $this->fpdf->Cell(28, 8, $contratto->nome, $this->border, 0,);
        $this->fpdf->SetXY(139, $posY);
        $this->fpdf->Cell(28, 8, $contratto->ragione_sociale, $this->border, 0,);

        if ($sesso) {
            $this->fpdf->SetXY(match ($sesso) {
                'M' => 184.5,
                'F' => 188.5
            }, $posY - 0.4);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }


        $posY += $incrY;
        $this->fpdf->SetXY(38.0, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->indirizzo), $this->border, 0,);
        $this->fpdf->SetXY(115.0, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->civico), $this->border, 0,);
        $this->fpdf->SetXY(154.5, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->scala), $this->border, 0,);
        $this->fpdf->SetXY(167, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->piano), $this->border, 0,);

        $citta = Comune::find($contratto->citta);

        $posY += $incrY;
        $this->fpdf->SetXY(20.3, $posY);
        $this->fpdf->Cell(32, 8, $contratto->cap, $this->border, 0,);
        $this->fpdf->SetXY(46.3, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($citta->comune), $this->border, 0,);
        $this->fpdf->SetXY(92.7, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($citta->targa), $this->border, 0,);
        $this->fpdf->SetXY(108, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->telefono), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(20.0, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->email), $this->border, 0,);
        if ($luogoNascita) {
            $this->fpdf->SetXY(93.5, $posY);
            $this->fpdf->Cell(28, 8, utf8_decode($luogoNascita), $this->border, 0,);
        }
        if ($dataNascita) {
            $this->fpdf->SetXY(153.5, $posY);
            $this->fpdf->Cell(28, 8, $dataNascita, $this->border, 0,);
        }


        $posY += $incrY;
        //$this->fpdf->SetFont('Courier');
        $chars = str_split($contratto->codice_fiscale);
        $posX = 24;
        $this->fpdf->SetFontSize('8'); // set font size

        foreach ($chars as $char) {
            $this->fpdf->SetXY($posX, $posY);
            $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
            $posX += 3.28;
        }
        $this->fpdf->SetFontSize('7'); // set font size

        $comuneRilascio = Comune::find($contratto->comune_rilascio);


        $this->fpdf->SetXY(88, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode(ContrattoTelefonia::TIPI_DOCUMENTO[$contratto->tipo_documento]), $this->border, 0,);
        $this->fpdf->SetXY(116, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode($contratto->numero_documento), $this->border, 0,);
        if ($comuneRilascio) {
            $this->fpdf->SetXY(144.5, $posY);
            $this->fpdf->Cell(32, 8, utf8_decode($comuneRilascio->targa), $this->border, 0,);
        }
        $this->fpdf->SetXY(168, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode($contratto->rilasciato_da), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(27, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode($contratto->data_rilascio->format('d/m/Y')), $this->border, 0,);
        if ($comuneRilascio) {
            $this->fpdf->SetXY(64.2, $posY);
            $this->fpdf->Cell(32, 8, utf8_decode($comuneRilascio->comune), $this->border, 0,);
        }

        if ($contratto->cittadinanza) {
            $nazione = Nazione::find($contratto->cittadinanza);
            if ($nazione) {
                $this->fpdf->SetXY(101, $posY);
                $this->fpdf->Cell(32, 8, utf8_decode($nazione->nazionalitaIT), $this->border, 0,);
            }
            $this->fpdf->SetXY(147, $posY);
            $this->fpdf->Cell(32, 8, utf8_decode($contratto->permesso_soggiorno_numero), $this->border, 0,);
            $this->fpdf->SetXY(177, $posY);
            $this->fpdf->Cell(32, 8, utf8_decode($contratto->permesso_soggiorno_scadenza?->format('d/m/Y') . 'aa'), $this->border, 0,);
        }


        $this->fpdf->SetXY(178, $posY);
        $this->fpdf->Cell(32, 8, utf8_decode($contratto->data_scadenza->format('d/m/Y')), $this->border, 0,);


        if ($contratto->partita_iva) {
            $posY = 94.6;
            $posX = 22;
            $this->fpdf->SetFontSize('8'); // set font size
            $chars = str_split($contratto->partita_iva);
            foreach ($chars as $char) {
                $this->fpdf->SetXY($posX, $posY);
                $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
                $posX += 3.28;
            }
            $this->fpdf->SetFontSize('7'); // set font size

        }


        //Offerta
        $this->fpdf->SetXY( match ($contratto->prodotto->offerta) {
            'fibra100' => 125,
            'super_internet' => 159,

        },172);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        $this->fpdf->SetXY( match ($contratto->prodotto->modem_wifi_hub) {
            0 => 168.7,
            1 => 156.7

        },180);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        if($contratto->prodotto->ultra_wifi){
            $this->fpdf->SetXY(147.4, 189.7);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            if($contratto->prodotto->wifi_spot){
                $this->fpdf->SetXY(167.9, 195.2);
                $this->fpdf->Cell(28, 8, $contratto->prodotto->wifi_spot, $this->border, 0,);

            }

        }
        $pacchettiSky = $contratto->prodotto->pacchetti_voce ?? [];


        $posX=147.4;
        if (in_array('internet_senza_voce', $pacchettiSky)) {
            $this->fpdf->SetXY($posX, 207.5);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('chiamate_consumo', $pacchettiSky)) {
            $this->fpdf->SetXY($posX, 213);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('voce_unlimited', $pacchettiSky)) {
            $this->fpdf->SetXY($posX, 218.5);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }
        if (in_array('voce_estero', $pacchettiSky)) {
            $this->fpdf->SetXY($posX, 223.8);
            $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        }





        $this->fpdf->SetXY( match ($contratto->prodotto->linea_telefonica) {
            'richiesta_nuova_linea' => 13,
            'mantenimento_numero' => 49.5,

        },250);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        $this->fpdf->SetXY(46, 259.2);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->numero_da_migrare, $this->border, 0,);

        $this->fpdf->SetXY(130, 259.2);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->codice_migrazione_voce, $this->border, 0,);
        $this->fpdf->SetXY(130, 264);
        $this->fpdf->Cell(28, 8, $contratto->prodotto->codice_migrazione_dati, $this->border, 0,);




        //////////////////////////// SECONDA PAGINA   ////////////////////////////

        $tpl = $this->fpdf->importPage(2);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);

        //Frequenza pagamento
        $this->fpdf->SetXY(113.4, match ($contratto->prodotto->metodo_pagamento_internet) {
            'carta_credito' => 20.6,
            'sdd' => 24.8,

        });
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
        $this->fpdf->SetXY(137.8, 20.5);
        $this->fpdf->Cell(27, 8, 'X', $this->border, 0,);

        if ($contratto->prodotto->metodo_pagamento_internet == 'carta_credito') {
            $this->fpdf->SetXY(match ($contratto->prodotto->carta_di_credito_tipo) {
                'american_express' => 37.5,
                'diners' => 66,
                'master_card' => 83,
                'visa' => 106.5,

            }, 30.6);
            $this->fpdf->Cell(27, 8, 'X', $this->border, 0,);

            $chars = str_split(str_replace(' ','',$contratto->prodotto->carta_di_credito_numero));
            $posX = 127;
            $posY = 30.6;
            $this->fpdf->SetFontSize('8'); // set font size
            foreach ($chars as $char) {
                $this->fpdf->SetXY($posX, $posY);
                $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
                $posX += 3.28;
            }

            $chars = str_split(str_replace('/', '', $contratto->prodotto->carta_di_credito_valida_al));
            $posX = 28;
            $posY = 39.7;
            $this->fpdf->SetFontSize('8'); // set font size
            foreach ($chars as $char) {
                $this->fpdf->SetXY($posX, $posY);
                $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
                $posX += 3.28;
            }


            $this->fpdf->SetFontSize('7'); // set font size


            $this->fpdf->SetXY(45.5, 36);
            $this->fpdf->Cell(28, 8, utf8_decode($contratto->cognome), $this->border, 0,);
            $this->fpdf->SetXY(105.5, 36);
            $this->fpdf->Cell(28, 8, utf8_decode($contratto->nome), $this->border, 0,);

        }
        if ($contratto->prodotto->metodo_pagamento_internet == 'sdd') {
            $this->fpdf->SetXY(39, 57.5);
            $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->sepa_banca), $this->border, 0,);
            $this->fpdf->SetXY(120, 57.5);
            $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->sepa_agenzia), $this->border, 0,);

            $posY = 62.5;
            $posX = 26.7;
            $this->fpdf->SetFontSize('8'); // set font size
            $chars = str_split($contratto->prodotto->sepa_iban);
            foreach ($chars as $char) {
                $this->fpdf->SetXY($posX, $posY);
                $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
                $posX += 2.88;
            }
            $this->fpdf->SetFontSize('7'); // set font size
            $this->fpdf->SetXY(117, 64.7);
            $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->sepa_intestatario), $this->border, 0,);
            $this->fpdf->SetXY(13, 69);
            $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->sepa_via), $this->border, 0,);

            $chars = str_split($contratto->codice_fiscale);
            $posX = 22.5;
            $posY = 76;
            $this->fpdf->SetFontSize('8'); // set font size

            foreach ($chars as $char) {
                $this->fpdf->SetXY($posX, $posY);
                $this->fpdf->Cell(32, 8, $char, $this->border, 0,);
                $posX += 3.28;
            }
            $this->fpdf->SetFontSize('7'); // set font size

            if ($dataNascita) {
                $this->fpdf->SetXY(102, $posY);
                $this->fpdf->Cell(28, 8, utf8_decode($dataNascita . ' - ' . $luogoNascita), $this->border, 0,);

            }

            if ($sesso) {
                $this->fpdf->SetXY(match ($sesso) {
                    'M' => 184.6,
                    'F' => 188.5
                }, $posY - 0.1);
                $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);
            }


        }


        //Data internet
        $this->fpdf->SetXY(113.5, 171.1);
        $this->fpdf->Cell(28, 8, $contratto->data->format('d/m/Y'), $this->border, 0,);



        $posXSi = 159.2;
        $posXNo = 180.4;

        if ($contratto->prodotto->consenso_1) {
            $posX = $posXSi;
        } else {
            $posX = $posXNo;
        }
        $this->fpdf->SetXY($posX, 248.2);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        if ($contratto->prodotto->consenso_2) {
            $posX = $posXSi;
        } else {
            $posX = $posXNo;
        }
        $this->fpdf->SetXY($posX, 251.2);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        if ($contratto->prodotto->consenso_3) {
            $posX = $posXSi;
        } else {
            $posX = $posXNo;
        }
        $this->fpdf->SetXY($posX, 256.0);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        if ($contratto->prodotto->consenso_4) {
            $posX = $posXSi;
        } else {
            $posX = $posXNo;
        }
        $this->fpdf->SetXY($posX, 259.0);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        if ($contratto->prodotto->consenso_5) {
            $posX = $posXSi;
        } else {
            $posX = $posXNo;
        }
        $this->fpdf->SetXY($posX, 265.2);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        if ($contratto->prodotto->consenso_6) {
            $posX = $posXSi;
        } else {
            $posX = $posXNo;
        }
        $this->fpdf->SetXY($posX, 268.8);
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
