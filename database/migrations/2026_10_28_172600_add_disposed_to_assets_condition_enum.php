<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE assets
                MODIFY COLUMN `condition`
                ENUM('new','good','fair','poor','disposed')
                NULL
            ");
        }

        DB::table('assets')
            ->where('status', 'disposed')
            ->where(function ($query) {
                $query->whereNull('condition')
                    ->orWhere('condition', '!=', 'disposed');
            })
            ->update(['condition' => 'disposed']);
    }

    public function down(): void
    {
        DB::table('assets')
            ->where('condition', 'disposed')
            ->update(['condition' => 'poor']);

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE assets
            MODIFY COLUMN `condition`
            ENUM('new','good','fair','poor')
            NULL
        ");
    }
};
