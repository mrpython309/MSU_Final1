<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_ticket_progress_statuses')) {
            Schema::create('support_ticket_progress_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);
                $table->string('slug', 64)->unique();
                $table->string('color', 20)->default('info');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        $defaults = [
            ['name' => 'Work In Progress', 'slug' => 'work_in_progress', 'color' => 'warning', 'sort_order' => 1],
            ['name' => 'Hold', 'slug' => 'hold', 'color' => 'secondary', 'sort_order' => 2],
            ['name' => 'In Purchase', 'slug' => 'in_purchase', 'color' => 'dark', 'sort_order' => 3],
        ];

        foreach ($defaults as $row) {
            $exists = DB::table('support_ticket_progress_statuses')->where('slug', $row['slug'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('support_ticket_progress_statuses')->insert($row + [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::getConnection()->getDriverName() === 'mysql' && Schema::hasColumn('support_tickets', 'status')) {
            DB::statement("ALTER TABLE support_tickets MODIFY status VARCHAR(64) NOT NULL DEFAULT 'open'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_progress_statuses');
    }
};
