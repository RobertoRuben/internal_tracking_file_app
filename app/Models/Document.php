<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Str;

class Document extends Model
{
    protected $table = 'documents';
    protected $fillable = [
        'registration_number',
        'name',
        'subject',
        'pages',
        'path',
        'registered_by_user_id',
        'is_derived',
        'employee_id',
        'created_by_department_id'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creatorDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'created_by_department_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function derivations(): HasMany
    {
        return $this->hasMany(Derivation::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Document $doc) {
            if (empty($doc->doc_code)) {
                $timestamp = now()->format('dmYHi');
                $seq = str_pad($doc->registration_number, 11, '0', STR_PAD_LEFT);
                $doc->doc_code = "DOC{$timestamp}{$seq}";
            }
        });
    }
}
