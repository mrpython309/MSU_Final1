<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\SupportTicketPriority;
use App\SupportTicketStatus;
use App\Models\User;
use App\Models\SupportTicketStatusLog;
use Illuminate\Support\Facades\Log;
use App\Traits\IconTrait;
use Illuminate\Support\Facades\Gate;
use App\AssetStatus;
use App\Models\AssetMaintenance;
use App\Jobs\SendPushNotification;
use App\Services\Procurement\DocumentNumberGenerator;


class SupportTicket extends Model
{
     use IconTrait;

     protected $table = 'support_tickets';

     const FILE_PATH = 'support-docs';

     /** Days a completed ticket waits for reject or close before it closes itself. */
     public const AUTO_CLOSE_AFTER_DAYS = 3;

     /** Set by the auto-close command so the activity log can explain the close. */
     public ?string $statusChangeNote = null;

    protected $fillable = [
        'subject',
        'company_id',
        'branch_id',
        'division_id',
        'department_id',
        'priority',
        'due_date',
        'asset_id',
        'description',
        'status',
        'ticket_file',
        'filename',
        'created_by',
        'updated_by',
        'vendor_id',
        'total_cost',
        'close_ticket_commands',
        'completed_file',
        'completed_files',
        'completed_ticket_commands',
        'completed_time',
        'completed_at',
        'marked_completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'marked_completed_at' => 'datetime',
        'completed_files' => 'array',
    ];

    protected $appends = [
        'status_label',
        'priority_label',
         'file_url',
         'doc_url', 
         'icon',
         'can_accept_ticket',
         'can_exit_ticket',
         'can_close_ticket',
         'can_assign_ticket',
         'can_edit_ticket',
         'can_delete_ticket',
         'can_reopen_ticket',
         'can_reject_ticket',
         'completed_file_url',
    ];

   

     protected static function booted()
    {
        static::creating(function ($ticket) {
             if (auth()->check()) {
            $ticket->created_by = Auth::id();
             }

             if (blank($ticket->ticket_number)) {
                $ticket->ticket_number = $ticket->generateTicketNumber();
             }
        });

        static::created(function ($ticket) {

            // Only if ticket has asset
            if (!$ticket->asset_id) {
                return;
            }

            // Prevent duplicate maintenance
            if (AssetMaintenance::where('support_ticket_id', $ticket->id)->exists()) {
                return;
            }

            AssetMaintenance::create([
                'asset_id'          => $ticket->asset_id, 
                'source_type'       => 'support_ticket',
                'support_ticket_id' => $ticket->id,
                'maintenance_date'  => now(),
                'maintenance_notes' => $ticket->description,
                'priority'          => match ($ticket->priority) {
                    'low' => 'low',
                    'normal' => 'medium',
                    'high' => 'high',
                    'urgent' => 'critical',
                    default => 'low'
                },
                'status'      => 'pending',
                'reported_by' => $ticket->created_by,
                'created_by'  => $ticket->created_by,
            ]);         


        });

        static::updating(function ($ticket) {
             if (auth()->check()) {
            $ticket->updated_by = Auth::id();
             }

             if ($ticket->isDirty('status') && $ticket->status === 'completed') {
                $ticket->marked_completed_at = now();
             }
        });

        static::updated(function ($ticket) {

        $changes = $ticket->getChanges();     // new values only
        $original = $ticket->getOriginal();  // old values

        $userId = Auth::id();

         if (array_key_exists('status', $changes)) {

            if($original['status'] == 'open' && $changes['status'] == 'accepted') {

                return;
            }

            $statusLog = new SupportTicketStatusLog();
            $statusLog->support_ticket_id = $ticket->id;
            $statusLog->old_status   = $original['status'];
            $statusLog->new_status       = $changes['status'];
            $statusLog->changed_by   = $userId;
            $statusLog->save();

            $meta = [
                'old' => $original['status'],
                'new' => $changes['status'],
            ];

            if ($ticket->statusChangeNote) {
                $meta['note'] = $ticket->statusChangeNote;
            }

            SupportTicketActivity::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $userId,
                'event' => 'status_changed',
                'meta' => $meta,
            ]);

        }

        if (array_key_exists('status', $changes) && $changes['status'] === 'closed') {

                AssetMaintenance::completeFromSupportTicket($ticket);

                 $completed_time_minutes = $ticket->created_at->diffInMinutes(now());

                $ticket->updateQuietly([
            'completed_time' => $completed_time_minutes,
            'completed_at' => now(),
        ]);

                
            }

            
        });


        static::deleting(function ($ticket) {

                    // Get related maintenance records
                    $maintenances = AssetMaintenance::where('support_ticket_id', $ticket->id)->get();

                    foreach ($maintenances as $maintenance) {

                        $assetId = $maintenance->asset_id;

                        // Delete maintenance
                        $maintenance->delete();

                        // After delete, check if asset has any other active maintenance
                        $hasActiveMaintenance = AssetMaintenance::where('asset_id', $assetId)
                            ->whereNotIn('status', ['completed', 'cancelled'])
                            ->exists();

                        if (!$hasActiveMaintenance) {
                            Asset::where('id', $assetId)
                                ->where(function ($query) {
                                    $query->whereNull('status')
                                        ->orWhere('status', '!=', AssetStatus::DISPOSED->value);
                                })
                                ->update(['operational_status' => 'working']);
                        }
                    }
                });
    }

    public function getDocUrlAttribute()
    {
        return ($this->filename) ? asset_url_local_s3(SupportTicket::FILE_PATH . '/' . $this->filename) : '';
    }

    public function getCompletedFileUrlAttribute()
    {
        $filename = $this->completed_file ?: ($this->completed_files[0] ?? null);

        return $filename ? asset_url_local_s3(self::FILE_PATH.'/'.$filename) : '';
    }

    /** @return array<int, string> */
    public function getCompletedFilenamesAttribute(): array
    {
        if (is_array($this->completed_files) && $this->completed_files !== []) {
            return array_values(array_filter($this->completed_files));
        }

        return $this->completed_file ? [$this->completed_file] : [];
    }

    /** @return array<int, string> */
    public function getCompletedFileUrlsAttribute(): array
    {
        return array_map(
            fn (string $filename) => asset_url_local_s3(self::FILE_PATH.'/'.$filename),
            $this->completed_filenames
        );
    }


    public function getFileUrlAttribute()
    {
        return ($this->ticket_file) ? asset_url_local_s3(SupportTicket::FILE_PATH . '/' . $this->ticket_file) : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function displayTicketNumber(): string
    {
        $number = trim((string) $this->ticket_number);

        return $number !== '' ? $number : '#'.$this->id;
    }

    public function generateTicketNumber(?\DateTimeInterface $date = null): string
    {
        $branch = $this->branch_id
            ? Branch::with('company:id,code')->find($this->branch_id)
            : null;

        $companyCode = $branch?->company?->code;
        if (! $companyCode && $this->company_id) {
            $companyCode = Company::query()->whereKey($this->company_id)->value('code');
        }

        $branchCode = $branch?->code ?: ($branch?->name ?: 'BRN');

        return app(DocumentNumberGenerator::class)->nextSupportTicketNumber(
            $companyCode ?: 'MSULLP',
            $branchCode,
            $date ?? $this->created_at ?? now()
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function vendor(){

        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors (Labels for UI)
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute()
    {
        return SupportTicketProgressStatus::badge($this->status);
    }

    public function getPriorityLabelAttribute()
    {
        return match ($this->priority) {
            'low' => '<span class="badge bg-label-secondary">Low</span>',
            'normal' => '<span class="badge bg-label-primary">Normal</span>',
            'high' => '<span class="badge bg-label-warning">High</span>',
            'urgent' => '<span class="badge bg-label-danger">Urgent</span>',
            default => '<span class="badge bg-label-light">Unknown</span>',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes (Filters)
    |--------------------------------------------------------------------------
    */

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function assignments()
    {
        return $this->hasMany(SupportTicketAssignment::class);
    }

    public function activeAssignments()
    {
        return $this->hasMany(SupportTicketAssignment::class)->Where('is_active', true);
    }

    public function commands()
    {
        return $this->hasMany(SupportTicketCommand::class)->orderBy('created_at', 'desc');
    }

    public function activities()
    {
        return $this->hasMany(SupportTicketActivity::class)->latest();
    }

    public function canBeAcceptedBy(User $user): bool
    {
        return $user->branches()
                ->where('branches.id', $this->branch_id)
                ->exists()
            && $this->division_id
            && (int) $user->division_id === (int) $this->division_id;
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assignments()
        ->active()
        ->where('user_id', $user->id)
        ->exists();
    }

    public function statusChangeLogs(){
        return $this->hasMany(SupportTicketStatusLog::class)->latest();
    }

    public function scopeVisibleToUser($query, $user)
    {
        // SUPER ADMIN / ADMIN → See all tickets
        if ($user->hasAnyRole(['superadmin', 'Super Admin', 'Admin'])) {
            return $query;
        }

        $branchIds = $user->branches()->pluck('branches.id');
        $divisionId = $user->division_id;

        // Division head (department-head flag) sees every status in their branch and division.
        if ($user->is_department_head && $divisionId) {
            return $query->where(function ($q) use ($user, $branchIds, $divisionId) {
                $q->where(function ($sub) use ($branchIds, $divisionId) {
                    $sub->whereIn('branch_id', $branchIds)
                        ->where('division_id', $divisionId);
                })
                ->orWhere('created_by', $user->id);
            });
        }

        return $query->where(function ($q) use ($user, $branchIds, $divisionId) {
            $q->where('created_by', $user->id)
              ->orWhere(function ($sub) use ($branchIds, $divisionId) {
                  $sub->where('status', 'open')
                      ->whereIn('branch_id', $branchIds)
                      ->where('division_id', $divisionId ?: 0);
              })
              ->orWhereHas('assignments', function ($a) use ($user) {
                  $a->where('user_id', $user->id);
              });
        });
    }

    public function releaseBy(User $user): void
    {
        $assignment = $this->assignments()
              ->active()
             ->where('user_id', $user->id)
             ->first();

        if (!$assignment) {
            return;
        }

        // deactivate assignment
        $assignment->update([
            'is_active'   => false,
            'released_at'=> now(),
        ]);

        if($this->assignments()->where('is_active', true)->count()==0){

        // update ticket status
        $this->update([
            'status' => SupportTicketStatus::OPEN,
        ]);

        }

       
    }

    public function getCanAcceptTicketAttribute()
    {

        $user = auth()->user();

        if (!$user) { return false; }

        return Gate::allows('accept', $this);

    }

    public function getCanExitTicketAttribute()
    {

        $user = auth()->user();

        if (!$user) { return false; }

        return Gate::allows('exit', $this);

    }

    public function getCanCloseTicketAttribute()
    {

        $user = auth()->user();

        if (!$user) { return false; }

        return Gate::allows('close', $this);

    }


    public function getCanAssignTicketAttribute()
    {
        $user = auth()->user();

        if (!$user) { return false; }

        if($this->isAssignedTo($user)){
            return true;
        }

        return false;
    }

    public function getCanEditTicketAttribute(){

         $user = auth()->user();

        if (!$user) { return false; }

        return Gate::allows('update', $this);

    }

    public function getCanDeleteTicketAttribute(){

         $user = auth()->user();

        if (!$user) { return false; }

        return Gate::allows('delete', $this);

    }

    public function getCanReopenTicketAttribute(){

         $user = auth()->user();

        if (!$user) { return false; }

        return Gate::allows('reopen', $this);
    }

    public function getCanRejectTicketAttribute(){

         $user = auth()->user();

        if (!$user) { return false; }

        return Gate::allows('reject', $this);
    }

    public function getDepartmentAllUsers()
    {
            if (! $this->division_id) {
                return collect();
            }

            $tokens = DeviceToken::join('users', 'users.id', '=', 'device_tokens.user_id')
                    ->join('company_user', 'users.id', '=', 'company_user.user_id')
                    ->join('branch_user', 'users.id', '=', 'branch_user.user_id')
                    ->where('company_user.company_id', $this->company_id)
                    ->where('branch_user.branch_id', $this->branch_id)
                    ->when($this->division_id, fn ($q) => $q->where('users.division_id', $this->division_id))
                    ->where('users.id', '!=', $this->created_by)
                    ->whereNotNull('device_tokens.device_token')
                    ->distinct()
                    ->pluck('device_tokens.device_token');

            return $tokens;
    }

    public function getAssignedAndCreatorDeviceTokens()
    {
        // Active assigned users
        $assignedUserIds = $this->activeAssignments()
            ->pluck('user_id');

        // Ticket creator
        $creatorId = collect([$this->created_by]);

        // Merge and remove duplicates
        $userIds = $assignedUserIds
            ->merge($creatorId)
            ->unique();

        return DeviceToken::whereIn('user_id', $userIds)
            ->whereNotNull('device_token')
            ->pluck('device_token');
    }


    
}
