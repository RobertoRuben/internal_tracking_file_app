<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Rupadana\ApiService\Contracts\{HasAllowedFields, HasAllowedFilters, HasAllowedSorts};

class Document extends Model implements HasAllowedFields, HasAllowedFilters, HasAllowedSorts
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
        'created_by_department_id'
    ];

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
    
    public function chargeBooks(): HasMany
    {
        return $this->hasMany(ChargeBook::class);
    }
    
    /**
     * Obtiene el estado de derivación del documento para un departamento específico
     * 
     * @param int $departmentId ID del departamento a verificar
     * @return string|null Estado de la derivación ('Enviado', 'Recibido', 'Rechazado') o null si no existe
     */
    public function getDerivationStatusForDepartment(int $departmentId): ?string
    {
        // Obtener la última derivación dirigida a este departamento
        $derivation = $this->derivations()
            ->where('destination_department_id', $departmentId)
            ->latest()
            ->first();
            
        return $derivation ? $derivation->status : null;
    }

    protected static function booted(): void
    {
        static::creating(function (Document $doc) {
            if (empty($doc->registered_by_user_id) && Auth::check()) {
                $doc->registered_by_user_id = Auth::id();
            }
            
            if (empty($doc->created_by_department_id) && Auth::check()) {
                $doc->created_by_department_id = Auth::user()->employee->department_id ?? null;
            }
            

            if (empty($doc->registration_number)) {
                $departmentId = $doc->created_by_department_id;
                
                $lastDocumentInDepartment = self::where('created_by_department_id', $departmentId)
                    ->orderBy('registration_number', 'desc')
                    ->first();
                
                $newRegistrationNumber = $lastDocumentInDepartment 
                    ? $lastDocumentInDepartment->registration_number + 1 
                    : 1;
                
                $doc->registration_number = $newRegistrationNumber;
            }
            
            if (empty($doc->doc_code)) {
                $timestamp = now()->format('dmYHi');
                
                $lastDocument = self::orderBy('id', 'desc')->first();
                
                $lastSeqNumber = 0;
                if ($lastDocument && !empty($lastDocument->doc_code)) {
                    $lastSeqNumber = (int) substr($lastDocument->doc_code, -11);
                }
                
                $nextSeqNumber = $lastSeqNumber + 1;
                
                $seq = str_pad($nextSeqNumber, 11, '0', STR_PAD_LEFT);
                
                $doc->doc_code = "DOC{$timestamp}{$seq}";
            }
        });
        
        static::deleting(function (Document $doc) {
            if ($doc->path && Storage::disk('public')->exists($doc->path)) {

                Storage::disk('public')->delete($doc->path);
            }
        });
    }

    /**
     * Define los campos permitidos para seleccionar desde la API
     */
    public static function getAllowedFields(): array
    {
        return [
            'id',
            'registration_number',
            'doc_code',
            'name',
            'subject',
            'date',
            'reference_number',
            'document_type',
            'origin',
            'priority',
            'status',
            'is_confidential',
            'registered_by_user_id',
            'created_by_department_id',
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
            'registration_number',
            'date',
            'priority',
            'status',
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
            'registration_number',
            'doc_code',
            'subject',
            'document_type',
            'origin',
            'priority',
            'status',
            'is_confidential',
            'created_by_department_id',
            'date',
        ];
    }
}
