<?php

namespace App\Support;

final class Money
{
    public static function format(int|float|string|null $amount): string
    {
        $decimals = (int) config('currency.decimals', 2);
        $number = number_format((float) $amount, $decimals, '.', ',');
        $symbol = (string) config('currency.symbol', '');

        return config('currency.symbol_position', 'before') === 'after'
            ? $number . $symbol
            : $symbol . $number;
    }
}
