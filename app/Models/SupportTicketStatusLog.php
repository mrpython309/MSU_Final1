<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class SupportTicketStatusLog extends Model
{
     use HasFactory;

     protected $table = 'support_ticket_status_logs';

     protected $fillable = [
        'support_ticket_id',
        'old_status',
        'new_status',
        'changed_by',
    ];

     protected $appends = [
        'old_status_label',
        'new_status_label',
    ];

    protected $casts = [
        'created_at',
        'updated_at'
    ];

    public function getOldStatusLabelAttribute()
    {
        return SupportTicketProgressStatus::badge($this->old_status);
    }

    public function getNewStatusLabelAttribute()
    {
        return SupportTicketProgressStatus::badge($this->new_status);
    }

    /**
     * Relationship: Ticket
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * Relationship: User (who changed status)
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
