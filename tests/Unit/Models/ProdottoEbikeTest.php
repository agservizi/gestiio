<?php

namespace Tests\Unit\Models;

use App\Models\ProdottoEbike;
use Tests\TestCase;

class ProdottoEbikeTest extends TestCase
{
    public function test_scope_attivi_returns_only_active_products()
    {
        $attivo = ProdottoEbike::create([
            'nome' => 'Ebike Urban',
            'sku' => 'EB-URBAN-1',
            'prezzo' => 999.00,
            'giacenza' => 5,
            'attivo' => true,
        ]);

        ProdottoEbike::create([
            'nome' => 'Ebike Fuori Catalogo',
            'sku' => 'EB-OLD-1',
            'prezzo' => 799.00,
            'giacenza' => 0,
            'attivo' => false,
        ]);

        $attivi = ProdottoEbike::attivi()->get();

        $this->assertCount(1, $attivi);
        $this->assertTrue($attivi->first()->is($attivo));
    }

    public function test_disponibile_checks_stock_and_active_flag()
    {
        $prodotto = ProdottoEbike::create([
            'nome' => 'Ebike Trekking',
            'sku' => 'EB-TREK-1',
            'prezzo' => 1299.00,
            'giacenza' => 2,
            'attivo' => true,
        ]);

        $this->assertTrue($prodotto->disponibile(2));
        $this->assertFalse($prodotto->disponibile(3));

        $prodotto->attivo = false;
        $this->assertFalse($prodotto->disponibile(1));
    }
}
