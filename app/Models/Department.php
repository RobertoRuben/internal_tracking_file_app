<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rupadana\ApiService\Contracts\{HasAllowedFields, HasAllowedFilters, HasAllowedSorts};

class Department extends Model implements HasAllowedFields, HasAllowedFilters, HasAllowedSorts
{
    protected $table = 'departments';
    protected $fillable = ['name'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by_department_id');
    }

    public function originDerivations(): HasMany
    {
        return $this->hasMany(Derivation::class, 'origin_department_id');
    }

    public function destinationDerivations(): HasMany
    {
        return $this->hasMany(Derivation::class, 'destination_department_id');
    }

    /**
     * Define los campos permitidos para seleccionar desde la API
     */
    public static function getAllowedFields(): array
    {
        return [
            'id',
            'name',
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
            'name',
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
            'name',
        ];
    }
}
