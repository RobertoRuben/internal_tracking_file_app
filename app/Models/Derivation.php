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

    protected static function booted(): void
    {
        // Cuando se crea una nueva derivación, actualizar el estado del documento a "derivado"
        static::created(function (Derivation $derivation) {
            if ($derivation->document) {
                $derivation->document->update(['is_derived' => true]);
            }
        });
        
        // Cuando se elimina una derivación, actualizar el estado del documento a "no derivado"
        static::deleted(function (Derivation $derivation) {
            if ($derivation->document) {
                // Verificar si el documento aún tiene otras derivaciones activas
                $hasOtherDerivations = $derivation->document->derivations()
                    ->where('id', '!=', $derivation->id)
                    ->exists();
                
                // Si no tiene otras derivaciones, marcarlo como no derivado
                if (!$hasOtherDerivations) {
                    $derivation->document->update(['is_derived' => false]);
                }
            }
        });
    }

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
