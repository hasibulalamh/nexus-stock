<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_formats_amounts_with_the_configured_currency(): void
    {
        $this->assertSame('৳600.00', Money::format(600));
        $this->assertSame('৳1,234,567.50', Money::format(1234567.5));
    }

    public function test_it_uses_a_configured_symbol(): void
    {
        config(['currency.symbol' => 'Tk ']);

        $this->assertSame('Tk 600.00', Money::format(600));
    }
}
