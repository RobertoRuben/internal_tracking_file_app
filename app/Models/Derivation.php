<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Derivation extends Model
{
    protected $table = 'derivations';
    protected $fillable = [
        'origin_department_id',
        'destination_department_id',
        'document_id',
        'derivated_by_user_id',
        'status',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function originDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'origin_department_id');
    }

    public function destinationDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'destination_department_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DerivationDetail::class);
    }

    public function derivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'derivated_by_user_id');
    }
}
