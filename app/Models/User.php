<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [        
        // AUTH
        'name',
        'emp_code',
        'date_of_joining',
        'mobile_number',
        'email',
        'designation_id',
        'division_id',
        'is_user',
        'password',
        'role_id',
        'employee_status',
        'company_id',
        'branch_id',
        'is_department_head',
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast attributes
     */
    protected $casts = [
        'email_verified_at'  => 'datetime',
        'password'           => 'hashed',
        'date_of_joining'    => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {

           

            if (empty($user->password)) {
                $user->password = Hash::make(Str::random(16));
            } 

           
        });

        
    }

    

    /* ===========================
       RELATIONSHIPS
       =========================== */

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user')->withTimestamps();
    }

    public function hasBranch(?int $branchId): bool
    {
        if ($branchId === null) {
            return false;
        }

        return $this->branches->contains('id', $branchId);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_user')->withTimestamps();
    }


    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')->withTimestamps();
    }

    public function reportedSupportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'created_by');
    }

    public function scopeVisibleToUser($query, $user)
    {
        // SUPER ADMIN → See all users
        if ($user->hasAnyRole(['superadmin', 'Super Admin', 'Admin'])) {
            return $query;
        }

        // DEPARTMENT HEAD → Branch + Department based users
        if ($user->is_department_head) {

            $branchIds = $user->branches()->pluck('branches.id');           

            return $query->where(function ($q) use ($branchIds, $user) {

                $q->where(function ($sub) use ($branchIds) {

                    $sub->whereHas('branches', function ($b) use ($branchIds) {
                        $b->whereIn('branches.id', $branchIds);
                    });

                })
                ->orWhere('id', $user->id);
            });
        }

        // NORMAL USER → Only self
        return $query->where('id', $user->id);
    }

    public function scopeEmployeesInBranch(Builder $query, int $branchId): Builder
    {
        if ($branchId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($branchId): void {
            $q->where('branch_id', $branchId)
                ->orWhereHas('branches', function (Builder $b) use ($branchId): void {
                    $b->where('branches.id', $branchId);
                });
        });
    }

    /**
     * Active settings records (User + Employee) eligible for asset assignment on a branch.
     */
    public function scopeActiveAssigneesForBranch(Builder $query, int $branchId): Builder
    {
        return $query
            ->where('employee_status', 'active')
            ->where('id', '!=', 1)
            ->employeesInBranch($branchId)
            ->orderBy('name');
    }

    /**
     * Staff employees only (excludes system users with login access).
     */
    public function scopeEmployeesOnly(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('is_user', '!=', 'yes')
                ->orWhereNull('is_user');
        });
    }

    public function scopeActiveEmployeesForBranch(Builder $query, int $branchId): Builder
    {
        return $query
            ->activeAssigneesForBranch($branchId)
            ->employeesOnly();
    }

    /**
     * Asset create, edit, or delete. View-only roles stay false so financial
     * fields, list filters, and exports stay hidden.
     */
    public function canManageAssets(): bool
    {
        try {
            if ($this->hasAnyRole(['superadmin', 'Super Admin'])) {
                return true;
            }
        } catch (\Throwable) {
            // Missing role tables should fall through to the permission check.
        }

        return $this->canAny(['asset:create', 'asset:edit', 'asset:delete']);
    }

    

    
}
