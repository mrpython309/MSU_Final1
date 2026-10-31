<?php

use App\Models\SupportTicket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('support_tickets', 'ticket_number')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->string('ticket_number', 64)->nullable()->unique()->after('id');
            });
        }

        SupportTicket::query()
            ->with(['company:id,code', 'branch:id,code,name,company_id', 'branch.company:id,code'])
            ->where(function ($query) {
                $query->whereNull('ticket_number')->orWhere('ticket_number', '');
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->each(function (SupportTicket $ticket) {
                $ticket->ticket_number = $ticket->generateTicketNumber($ticket->created_at);
                $ticket->timestamps = false;
                $ticket->saveQuietly();
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('support_tickets', 'ticket_number')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropUnique(['ticket_number']);
            $table->dropColumn('ticket_number');
        });
    }
};
