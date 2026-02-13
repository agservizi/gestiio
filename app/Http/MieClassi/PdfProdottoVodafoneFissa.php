<?php

namespace App\Http\MieClassi;

use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use App\Models\Nazione;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use setasign\Fpdi\Fpdi;

class PdfProdottoVodafoneFissa
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
        $pagecount = $this->fpdf->setSourceFile(public_path('/pdf/pda_vodafone_rete_fissa.pdf'));


        $tpl = $this->fpdf->importPage(1);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);

        $this->fpdf->SetFont('Arial');

        $this->fpdf->SetFontSize('10'); // set font size
        $this->fpdf->SetAutoPageBreak(false);

        //Dati del cliente
        $posY = 43.0;
        $incrY = 17.1;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, $contratto->nominativo(), $this->border, 0,);
        $citta = Comune::find($contratto->citta);

        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->indirizzo . ' ' . $contratto->civico . ' - ' . $citta->comuneConTarga()), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->indirizzo . ' ' . $contratto->civico . ' - ' . $citta->comuneConTarga()), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(match ($contratto->prodotto->offerta) {
            'con_chiamate' => 100,
            'senza_chiamate' => 156
        }, $posY);
        $this->fpdf->Cell(28, 8, 'X', $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->tecnologia), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->telefono), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->email), $this->border, 0,);

        $metodo = $contratto->prodotto->metodo_pagamento;
        if ($metodo == 'iban') {
            $metodo .= ': ' . $contratto->iban;
        }
        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($metodo), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->numero_da_migrare), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->gestore_linea_esistente), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(80, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->prodotto->codice_migrazione), $this->border, 0,);

        $posY += $incrY;
        $this->fpdf->SetXY(45, $posY + 7);
        $this->fpdf->Cell(28, 8, utf8_decode($contratto->data->format('d/m/Y')), $this->border, 0,);


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
