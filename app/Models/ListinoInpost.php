<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListinoInpost extends Model
{
    use HasFactory;

    protected $table = 'inpost_listino';

    public const NOME_SINGOLARE = 'listino InPost';

    public const NOME_PLURALE = 'listini InPost';

    public static function trovaTariffa(string $packageType): ?self
    {
        return self::where('package_type', $packageType)->first();
    }

    public function calcolaPrezzo(string $deliveryType): ?float
    {
        $campo = $deliveryType === 'address' ? 'home_delivery' : 'locker_point';
        $value = $this->{$campo};

        return $value !== null ? (float) $value : null;
    }

    public function testoTariffa(string $deliveryType): string
    {
        $package = match ($this->package_type) {
            'small' => 'Piccolo (S)',
            'medium' => 'Medio (M)',
            'large' => 'Grande (L)',
            default => ucfirst((string) $this->package_type),
        };

        $delivery = $deliveryType === 'address'
            ? 'Indirizzo del destinatario'
            : 'Locker o punto di ritiro';

        return $package.' / '.$delivery;
    }
}
