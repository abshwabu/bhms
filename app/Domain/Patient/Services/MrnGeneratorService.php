<?php

namespace App\Domain\Patient\Services;

use App\Domain\Shared\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MrnGeneratorService
{
    /**
     * Generate an atomic, guaranteed unique, sequential MRN.
     *
     * Format: {PREFIX}-{YEAR}-{BRANCH_CODE}-{SEQUENCE}
     * Example: MRN-2026-MAIN-000042
     */
    public function generate(Branch $branch, string $registrationType = 'walk_in'): string
    {
        $year = (int) date('Y');
        $prefix = ($registrationType === 'emergency') ? 'EMG' : 'MRN';
        $branchCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $branch->code ?? 'HSP'));

        // Atomic sequence increment using PostgreSQL transactions and row locking
        $sequence = DB::transaction(function () use ($branch, $year, $prefix) {
            // Check if sequence record exists
            $row = DB::table('mrn_sequences')
                ->where('branch_id', $branch->id)
                ->where('year', $year)
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                // Initialize sequence
                $newId = (string) Str::uuid();
                DB::table('mrn_sequences')->insert([
                    'id' => $newId,
                    'organization_id' => $branch->organization_id,
                    'branch_id' => $branch->id,
                    'year' => $year,
                    'prefix' => $prefix,
                    'current_sequence' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $next = $row->current_sequence + 1;

            DB::table('mrn_sequences')
                ->where('id', $row->id)
                ->update([
                    'current_sequence' => $next,
                    'updated_at' => now(),
                ]);

            return $next;
        });

        // Pad sequence to 6 digits (e.g. 000001)
        $paddedSequence = str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);

        return sprintf('%s-%d-%s-%s', $prefix, $year, $branchCode, $paddedSequence);
    }
}
