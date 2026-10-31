<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_tickets') && ! Schema::hasColumn('support_tickets', 'marked_completed_at')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->timestamp('marked_completed_at')->nullable()->after('completed_at');
            });
        }

        if (Schema::hasTable('support_tickets') && Schema::hasColumn('support_tickets', 'marked_completed_at')) {
            $completedTickets = DB::table('support_tickets')
                ->where('status', 'completed')
                ->whereNull('marked_completed_at')
                ->get(['id', 'updated_at']);

            foreach ($completedTickets as $ticket) {
                $markedAt = null;

                if (Schema::hasTable('support_ticket_status_logs')) {
                    $markedAt = DB::table('support_ticket_status_logs')
                        ->where('support_ticket_id', $ticket->id)
                        ->where('new_status', 'completed')
                        ->max('created_at');
                }

                DB::table('support_tickets')
                    ->where('id', $ticket->id)
                    ->update([
                        'marked_completed_at' => $markedAt ?: $ticket->updated_at,
                    ]);
            }
        }

        if (Schema::hasTable('support_ticket_activities') && Schema::hasColumn('support_ticket_activities', 'user_id')) {
            $userColumn = collect(DB::select('SHOW COLUMNS FROM support_ticket_activities WHERE Field = ?', ['user_id']))->first();

            if ($userColumn && strtoupper((string) $userColumn->Null) === 'NO') {
                DB::statement('ALTER TABLE support_ticket_activities MODIFY user_id BIGINT UNSIGNED NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('support_ticket_activities') && Schema::hasColumn('support_ticket_activities', 'user_id')) {
            DB::table('support_ticket_activities')->whereNull('user_id')->delete();
            DB::statement('ALTER TABLE support_ticket_activities MODIFY user_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasTable('support_tickets') && Schema::hasColumn('support_tickets', 'marked_completed_at')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropColumn('marked_completed_at');
            });
        }
    }
};
