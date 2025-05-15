<?php

namespace App\Filament\Resources\DocumentResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
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
			'doc_code' => 'required|string',
			'registration_number' => 'required',
			'name' => 'required|string',
			'path' => 'required|string',
			'subject' => 'required|string',
			'pages' => 'required|integer',
			'registered_by_user_id' => 'required',
			'is_derived' => 'required',
			'created_by_department_id' => 'required'
		];
    }
}
