<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAudit extends Model
{
    use HasFactory;

    protected $table = 'asset_audits';

    protected $fillable = [
        'asset_id',
        'auditor_id',
        'audit_status',
        'previous_status',
        'location_status',
        'remarks',
        'audited_at',
        'source',
        'company_id',
        'branch_id',
        'location_id',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'audited_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function location()
    {
        return $this->belongsTo(BranchLocation::class, 'location_id');
    }

    public function auditStatusLabel(): string
    {
        $status = strtolower(trim(str_replace(['-', ' '], '_', (string) $this->audit_status)));

        return match ($status) {
            'audited', 'found' => 'Audited',
            'not_found', 'notfound' => 'Not Found',
            'pending_audit', 'pending' => 'Pending Audit',
            default => ucfirst(str_replace('_', ' ', (string) $this->audit_status)),
        };
    }

    public function auditStatusBadge(): string
    {
        $label = $this->auditStatusLabel();
        $class = match ($label) {
            'Audited' => 'bg-label-success',
            'Not Found' => 'bg-label-danger',
            'Pending Audit' => 'bg-label-warning',
            default => 'bg-label-secondary',
        };

        return '<span class="badge '.$class.'">'.e($label).'</span>';
    }

    public function sourceBadge(): string
    {
        return match ($this->source) {
            'mobile_app' => '<span class="badge bg-label-info"><i class="bx bx-mobile-alt me-1"></i>Mobile App</span>',
            'web' => '<span class="badge bg-label-primary"><i class="bx bx-laptop me-1"></i>Web</span>',
            'qr_scan' => '<span class="badge bg-label-secondary"><i class="bx bx-qr me-1"></i>QR Scan</span>',
            'system_backfill' => '<span class="badge bg-label-secondary"><i class="bx bx-history me-1"></i>System</span>',
            default => '<span class="badge bg-label-secondary">'.e(ucfirst(str_replace('_', ' ', (string) ($this->source ?? 'Unknown')))).'</span>',
        };
    }

    public function auditorName(): string
    {
        return trim((string) ($this->auditor?->name ?? '')) !== '' ? $this->auditor->name : 'System';
    }

    public function formattedDate(): string
    {
        $dt = $this->audited_at ?? $this->created_at;

        return $dt ? $dt->format('d M Y, h:i A') : '-';
    }
}

