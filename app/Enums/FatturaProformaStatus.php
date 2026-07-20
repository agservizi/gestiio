<?php

namespace App\Enums;

enum FatturaProformaStatus: string
{
    case BOZZA = 'bozza';
    case EMESSA = 'emessa';
    case INVIATA = 'inviata';
    case PAGATA = 'pagata';

    public function label(): string
    {
        return match ($this) {
            self::BOZZA => 'Bozza',
            self::EMESSA => 'Emessa',
            self::INVIATA => 'Inviata',
            self::PAGATA => 'Pagata',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BOZZA => 'badge-light-secondary',
            self::EMESSA => 'badge-light-primary',
            self::INVIATA => 'badge-light-info',
            self::PAGATA => 'badge-light-success',
        };
    }

    public function canDelete(): bool
    {
        return $this !== self::PAGATA;
    }

    public function canRegenerate(): bool
    {
        return $this !== self::PAGATA;
    }

    public function canEmit(): bool
    {
        return $this === self::BOZZA;
    }

    public function canSendEmail(): bool
    {
        return in_array($this, [self::EMESSA, self::INVIATA], true);
    }

    public function canMarkPaid(): bool
    {
        return in_array($this, [self::EMESSA, self::INVIATA], true);
    }
}
