<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dni' => $this->dni,
            'names' => $this->names,
            'paternal_surname' => $this->paternal_surname,
            'maternal_surname' => $this->maternal_surname,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'gender_text' => $this->gender === 'M' ? 'Male' : 'Female',
            'phone_number' => $this->phone_number,
            'is_active' => $this->is_active,
            'department' => [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ],
            'has_user' => $this->user()->exists(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
