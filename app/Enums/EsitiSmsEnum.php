<?php

namespace App\Enums;

enum EsitiSmsEnum: string
{
    case _201 = '201';
    case _401 = '401';
    case _402 = '402';

    public function colore()
    {
        return match ($this) {
            self::_201 => 'success',
            self::_402 => 'danger',
            self::_401 => 'danger',
        };
    }

    public function testo()
    {
        return match ($this) {
            self::_201 => '201',
            self::_402 => '402',
            self::_401 => '401',
        };
    }

    public function badge()
    {

            return '<span class="badge badge-' . $this->colore() . ' fw-bolder me-2">' . $this->testo() . '</span>';


    }
}
