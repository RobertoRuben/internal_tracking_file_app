<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DerivationDetail extends Model
{
    protected $table = 'derivation_details';
    protected $fillable = [
        'derivation_id',
        'comments',
        'user_id',  // añadir este campo para saber quién hizo el comentario
        'status'    // añadir este campo para registrar el estado en ese momento
    ];

    public function derivation(): BelongsTo
    {
        return $this->belongsTo(Derivation::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}