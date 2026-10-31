<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\SupportTicket;
use Illuminate\Console\Command;

class AutoCloseCompletedSupportTickets extends Command
{
    protected $signature = 'support-tickets:auto-close-completed';

    protected $description = 'Close support tickets that stayed completed for 3 days without a reject or close';

    public function handle(): int
    {
        $cutoff = now()->subDays(SupportTicket::AUTO_CLOSE_AFTER_DAYS);
        $closed = 0;

        SupportTicket::query()
            ->where('status', 'completed')
            ->whereNotNull('marked_completed_at')
            ->where('marked_completed_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use (&$closed) {
                foreach ($tickets as $ticket) {
                    $this->closeTicket($ticket);
                    $closed++;
                }
            });

        $this->info("Closed {$closed} support ticket(s).");

        return self::SUCCESS;
    }

    private function closeTicket(SupportTicket $ticket): void
    {
        $ticket->statusChangeNote = 'Automatically closed after '.SupportTicket::AUTO_CLOSE_AFTER_DAYS.' days with no reject or close.';
        $ticket->status = 'closed';
        $ticket->save();

        $ticketTokens = $ticket->getAssignedAndCreatorDeviceTokens();

        if ($ticketTokens->isNotEmpty()) {
            SendPushNotification::dispatch(
                $ticketTokens,
                'Ticket Closed',
                "Ticket #{$ticket->id} was closed automatically because it stayed completed for ".SupportTicket::AUTO_CLOSE_AFTER_DAYS.' days without a reject or close.',
                [
                    'type' => 'support_ticket',
                    'ref_id' => (string) $ticket->id,
                ]
            );
        }
    }
}
