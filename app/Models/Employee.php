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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
