<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assets')
            ->where('status', 'disposed')
            ->where(function ($query) {
                $query->whereNull('operational_status')
                    ->orWhere('operational_status', '!=', 'disposed');
            })
            ->update(['operational_status' => 'disposed']);
    }

    public function down(): void
    {
        // Operational status is not restored; the previous value is not stored.
    }
};
