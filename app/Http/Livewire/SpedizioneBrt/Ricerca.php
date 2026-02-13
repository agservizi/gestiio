<?php

namespace App\Http\Livewire\SpedizioneBrt;

use Livewire\Component;

class Ricerca extends Component
{
    public $record;
    public $ragioneSociale;

    public $mostra=false;

    public function render()
    {

        if($this->ragioneSociale){
            $this->mostra=true;
        }else{
            $this->mostra=false;
        }
        return view('livewire.spedizione-brt.ricerca');
    }
}
