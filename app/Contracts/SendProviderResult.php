<?php

namespace App\Contracts;

class SendProviderResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message = '',
        public readonly array $meta = [],
    ) {
    }

    public static function manual(): self
    {
        return new self(true, 'Lavorazione manuale: nessun provider esterno attivo.');
    }
}
