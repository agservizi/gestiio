<?php

namespace App\Http\MieClassi;

use App\Http\Controllers\Backend\SpedizioneBrtController;
use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use App\Models\Nazione;
use App\Models\SpedizioneBrt;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use setasign\Fpdi\Fpdi;
use function App\importo;


class PdfProformaCh
{
    protected $border = 0;
    protected $fpdf;
    protected $col = 0; // Current column
    protected $y0;      // Ordinate of column start
    protected $nomeDocumento;


    public function generaPdf(SpedizioneBrt $record)
    {


        $this->nomeDocumento = 'proforma_' . \Str::slug($record->ragione_sociale_destinatario, '_') . '.pdf';
        $this->fpdf = new Fpdi();
        $pagecount = $this->fpdf->setSourceFile(public_path('/pdf/proforma_ch.pdf'));


        $tpl = $this->fpdf->importPage(1);
        $this->fpdf->AddPage();
        $this->fpdf->useTemplate($tpl);

        $this->fpdf->SetFont('Arial');

        $this->fpdf->SetFontSize('7'); // set font size
        $this->fpdf->SetAutoPageBreak(false);

        $this->fpdf->SetFont('Arial', '', 9);

        $this->fpdf->SetXY(27, 19);
        $this->fpdf->Cell(28, 8, $record->nome_mittente, $this->border, 0,);
        $this->fpdf->SetXY(31, 42);
        $this->fpdf->Cell(28, 8, 'Italy', $this->border, 0,);
        $this->fpdf->SetXY(43, 50);
        $this->fpdf->Cell(28, 8, $record->mobile_mittente, $this->border, 0,);


        $posY = 79.5;
        $incremento = 7.8;
        $this->fpdf->SetXY(27, $posY);
        $this->fpdf->Cell(28, 8, utf8_decode($record->ragione_sociale_destinatario), $this->border, 0,);
        $posY += $incremento ;
        $this->fpdf->SetXY(30, $posY+=$incremento);
        $this->fpdf->Cell(28, 8, utf8_decode($record->indirizzo_destinatario), $this->border, 0,);
        $this->fpdf->SetXY(42, $posY+=$incremento);
        $this->fpdf->Cell(32, 8, utf8_decode($record->cap_destinatario), $this->border, 0,);
        $this->fpdf->SetXY(30, $posY+=$incremento);
        $this->fpdf->Cell(32, 8, Nazione::find($record->nazione_destinazione)->langEN, $this->border, 0,);
        $this->fpdf->SetXY(42, $posY+=$incremento);
        $this->fpdf->Cell(32, 8, $record->mobile_referente_consegna, $this->border, 0,);


        $posY = 82.7;
        $this->fpdf->SetXY(137, $posY);
        $this->fpdf->Cell(28, 8, $record->created_at->format('d/m/Y'), $this->border, 0,);
        $this->fpdf->SetXY(175, $posY+=$incremento);
        $this->fpdf->Cell(28, 8, count($record->dati_colli), $this->border, 0,);
        $this->fpdf->SetXY(175, $posY+=$incremento);
        $this->fpdf->Cell(28, 8, importo($record->peso_totale).'Kg', $this->border, 0,);

        $posY = 156;
        $incremento = 7.8;
        for ($n=0;$n<=2;$n++){

            $this->fpdf->SetXY(10, $posY);
            $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf'][$n]['description']??'', $this->border, 0,);
            $this->fpdf->SetXY(82.5, $posY);
            $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf'][$n]['code']??'', $this->border, 0,);
            $this->fpdf->SetXY(110, $posY);
            $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf'][$n]['country']??'', $this->border, 0,);
            $this->fpdf->SetXY(133, $posY);
            $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf'][$n]['pieces']??'', $this->border, 0,);
            $this->fpdf->SetXY(150, $posY);
            $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf'][$n]['value']??'', $this->border, 0,);
            $this->fpdf->SetXY(175, $posY);
            $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf'][$n]['sub_total']??'', $this->border, 0,);
            $posY+=$incremento;
        }

        $this->fpdf->SetXY(180, 204);
        $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf']['total_value']??'', $this->border, 0,);

        $this->fpdf->SetXY(60, 222.5);
        $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf']['reason'] ?? '', $this->border, 0,);

        $this->fpdf->SetXY(60, 230);
        $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf']['terms'] ?? '', $this->border, 0,);

        $this->fpdf->SetXY(115, 253.5);
        $this->fpdf->Cell(28, 8, $record->altri_dati['dati_pdf']['are_of'] ?? '', $this->border, 0,);

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
