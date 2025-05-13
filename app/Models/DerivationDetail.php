<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DerivationDetail extends Model
{
    protected $table = 'derivation_details';
    protected $fillable = [
        'derivation_id',
        'comments'
    ];

    public function derivation(): BelongsTo
    {
        return $this->belongsTo(Derivation::class);
    }
}
