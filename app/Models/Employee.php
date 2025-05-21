<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class Employee extends Model
{
    protected $table = 'employees';
    protected $fillable = [
        'dni',
        'names',
        'paternal_surname',
        'maternal_surname',
        'gender',
        'phone_number',
        'is_active',
        'department_id'
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return "{$this->names} {$this->paternal_surname} {$this->maternal_surname}";
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
