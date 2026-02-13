<?php

namespace App\Http\Funzioni;

use function App\telefonoWhatsapp;

trait FunzioniContatti
{
    public function contatti()
    {
        $html = '';
        if ($this->email) {
            $html .= '<a href = "mailto:' . $this->email . '" class="text-gray-900 text-hover-primary" >' . $this->email . '</a >';
        }
        if ($this->telefono) {
            if ($html) {
                $html .= '<br>';
            }
            $html .= telefonoWhatsapp($this->telefono);
        }

        return $html;
    }

    public function telefonoWhatsapp()
    {
        return telefonoWhatsapp($this->telefono);
    }

}
