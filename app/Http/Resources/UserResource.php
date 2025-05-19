<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{    /**
     * Transform the resource into an array.
     * 
     * This resource converts a User model into a structured API response including roles, permissions, and employee information when available.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'roles' => $this->roles->pluck('name'),
            'permissions' => $this->getAllPermissions()->pluck('name'),
        ];

        if ($this->relationLoaded('employee')) {
            $data['employee'] = [
                'id' => $this->employee->id,
                'dni' => $this->employee->dni,
                'names' => $this->employee->names,
                'paternal_surname' => $this->employee->paternal_surname,
                'maternal_surname' => $this->employee->maternal_surname,
                'full_name' => "{$this->employee->names} {$this->employee->paternal_surname} {$this->employee->maternal_surname}",
                'gender' => $this->employee->gender,
                'phone_number' => $this->employee->phone_number,
                'is_active' => $this->employee->is_active,
            ];

            if ($this->employee->relationLoaded('department')) {
                $data['employee']['department'] = [
                    'id' => $this->employee->department->id,
                    'name' => $this->employee->department->name,
                    'abbreviation' => $this->employee->department->abbreviation,
                ];
            }
        }

        return $data;
    }
}
