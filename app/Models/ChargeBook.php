<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ChargeBook extends Model
{
    protected $table = 'charge_books';
    
    protected $fillable = [
        'document_id',
        'sender_department_id',
        'sender_user_id',        
        'receiver_user_id',      
        'department_id',         
        'notes',
        'registration_number',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    public function senderDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'sender_department_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
    
    public function receiverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }
    
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    
    protected static function booted(): void
    {
        static::creating(function (ChargeBook $chargeBook) {
            if (empty($chargeBook->receiver_user_id) && Auth::check()) {
                $chargeBook->receiver_user_id = Auth::id();
            }
            
            if (empty($chargeBook->department_id) && Auth::check()) {
                $chargeBook->department_id = Auth::user()->employee->department_id ?? null;
            }
            
            if (empty($chargeBook->registration_number)) {
                $departmentId = $chargeBook->department_id;
                
                $lastEntry = self::where('department_id', $departmentId)
                    ->orderBy('registration_number', 'desc')
                    ->first();
                
                $newRegistrationNumber = $lastEntry 
                    ? $lastEntry->registration_number + 1 
                    : 1;
                
                $chargeBook->registration_number = $newRegistrationNumber;
            }
        });
    }
}