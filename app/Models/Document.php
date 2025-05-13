<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            // Asegurar que siempre se registre el usuario autenticado 
            if (empty($doc->registered_by_user_id) && Auth::check()) {
                $doc->registered_by_user_id = Auth::id();
            }
            
            // Asegurar que siempre se registre el departamento del usuario
            if (empty($doc->created_by_department_id) && Auth::check()) {
                $doc->created_by_department_id = Auth::user()->employee->department_id ?? null;
            }
            
            // Generar el número de registro basado en el departamento
            if (empty($doc->registration_number)) {
                $departmentId = $doc->created_by_department_id;
                
                // Obtener el último número de registro para este departamento
                $lastDocumentInDepartment = self::where('created_by_department_id', $departmentId)
                    ->orderBy('registration_number', 'desc')
                    ->first();
                
                // Si no hay documentos previos, comenzar desde 1, de lo contrario incrementar el último
                $newRegistrationNumber = $lastDocumentInDepartment 
                    ? $lastDocumentInDepartment->registration_number + 1 
                    : 1;
                
                $doc->registration_number = $newRegistrationNumber;
            }
            
            // Generar el código del documento
            if (empty($doc->doc_code)) {
                $timestamp = now()->format('dmYHi');
                $seq = str_pad($doc->registration_number, 11, '0', STR_PAD_LEFT);
                $doc->doc_code = "DOC{$timestamp}{$seq}";
            }
        });
        
        static::deleting(function (Document $doc) {
            if ($doc->path && Storage::disk('public')->exists($doc->path)) {

                Storage::disk('public')->delete($doc->path);
            }
        });
    }
}
