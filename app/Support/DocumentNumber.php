<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class DocumentNumber
{
    /**
     * Generate PREFIX-YYYY-NNNNNN for the supplied prefix and year.
     *
     * The sequence row is locked inside the caller's transaction, so two
     * simultaneous requests can never receive the same number on MySQL.
     */
    public static function next(string $prefix, ?CarbonInterface $date = null): string
    {
        $prefix = strtoupper(substr($prefix, 0, 10));
        $year = (int) ($date ?? now())->format('Y');

        return DB::transaction(function () use ($prefix, $year): string {
            $row = DB::table('document_sequences')
                ->where('prefix', $prefix)->where('year', $year)
                ->lockForUpdate()->first();

            if (! $row) {
                DB::table('document_sequences')->insertOrIgnore([
                    'prefix' => $prefix, 'year' => $year, 'last_number' => 0,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $row = DB::table('document_sequences')
                    ->where('prefix', $prefix)->where('year', $year)
                    ->lockForUpdate()->first();
            }

            $next = (int) $row->last_number + 1;
            DB::table('document_sequences')->where('id', $row->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return sprintf('%s-%d-%06d', $prefix, $year, $next);
        });
    }
}
