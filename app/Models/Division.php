<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = [
        'division_name',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeVisibleToUser($query, $user)
    {
        if ($user->hasAnyRole(['superadmin', 'Super Admin', 'Admin'])) {
            return $query;
        }

        if ($user->division_id) {
            return $query->where('id', $user->division_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
