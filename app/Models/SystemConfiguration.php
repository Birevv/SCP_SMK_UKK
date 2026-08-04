<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $fillable = [
        'active_cycle',
        'is_schedule_locked',
        'updated_by',
    ];

    protected $casts = [
        'is_schedule_locked'=> 'boolean',
    ];
    
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
