<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
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
}
