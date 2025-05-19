<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends JsonResource
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
            'registration_number' => $this->registration_number,
            'registration_number_formatted' => str_pad($this->registration_number, 11, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'subject' => $this->subject,
            'pages' => $this->pages,
            'path' => $this->path,
            'file_url' => $this->path ? Storage::url($this->path) : null,
            'is_derived' => $this->is_derived,
            'registered_by' => $this->when($this->registeredBy, function () {
                return [
                    'id' => $this->registeredBy->id,
                    'name' => $this->registeredBy->name,
                    'email' => $this->registeredBy->email,
                ];
            }),
            'creator_department' => $this->when($this->creatorDepartment, function () {
                return [
                    'id' => $this->creatorDepartment->id,
                    'name' => $this->creatorDepartment->name,
                ];
            }),
            'derivations_count' => $this->whenLoaded('derivations', function () {
                return $this->derivations->count();
            }),
            'charge_books_count' => $this->whenLoaded('chargeBooks', function () {
                return $this->chargeBooks->count();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
