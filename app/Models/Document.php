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
}
