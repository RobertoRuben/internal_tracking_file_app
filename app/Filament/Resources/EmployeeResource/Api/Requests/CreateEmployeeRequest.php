<?php

namespace App\Filament\Resources\EmployeeResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'dni' => 'required',
			'names' => 'required|string',
			'paternal_surname' => 'required|string',
			'maternal_surname' => 'required|string',
			'gender' => 'required|string',
			'phone_number' => 'required',
			'department_id' => 'required',
			'is_active' => 'required'
		];
    }
}
