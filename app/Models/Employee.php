<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Rupadana\ApiService\Contracts\{HasAllowedFields, HasAllowedFilters, HasAllowedSorts};

class Employee extends Model implements HasAllowedFields, HasAllowedFilters, HasAllowedSorts
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

    /**
     * Define los campos permitidos para seleccionar desde la API
     */
    public static function getAllowedFields(): array
    {
        return [
            'id',
            'dni',
            'names',
            'paternal_surname',
            'maternal_surname',
            'gender',
            'phone_number',
            'is_active',
            'department_id',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Define los campos permitidos para ordenar resultados desde la API
     */
    public static function getAllowedSorts(): array
    {
        return [
            'id',
            'dni',
            'names',
            'paternal_surname',
            'maternal_surname',
            'department_id',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Define los campos permitidos para filtrar resultados desde la API
     */
    public static function getAllowedFilters(): array
    {
        return [
            'id',
            'dni',
            'names',
            'paternal_surname',
            'maternal_surname',
            'gender',
            'is_active',
            'department_id',
        ];
    }
}
