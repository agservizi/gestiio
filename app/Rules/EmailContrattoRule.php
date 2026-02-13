<?php

namespace App\Rules;

use App\Models\ContrattoTelefonia;
use App\Models\TipoContratto;
use Illuminate\Contracts\Validation\Rule;

class EmailContrattoRule implements Rule
{

    protected $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(protected $tipoContrattoId, protected $id)
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->id) {
            return true;
        }
        $tipoContratto = TipoContratto::with('gestore')->find($this->tipoContrattoId);
        $email = strtolower($value);
        $esiste = ContrattoTelefonia::whereRelation('tipoContratto', 'gestore_id', $tipoContratto->gestore->id)->where('email', $email)->exists();
        if ($esiste) {
            $this->message = 'Questo indirizzo email ha già stipulato un contratto con ' . $tipoContratto->gestore->nome;
            return false;
        } else {
            return true;
        }

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
