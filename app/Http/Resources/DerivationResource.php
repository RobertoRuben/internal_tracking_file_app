<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DerivationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'document' => $this->whenLoaded('document', function () {
                return [
                    'id' => $this->document->id,
                    'name' => $this->document->name,
                    'subject' => $this->document->subject,
                    'registration_number' => $this->document->registration_number
                ];
            }),
            'origin_department_id' => $this->origin_department_id,
            'origin_department' => $this->whenLoaded('originDepartment', function () {
                return [
                    'id' => $this->originDepartment->id,
                    'name' => $this->originDepartment->name
                ];
            }),
            'destination_department_id' => $this->destination_department_id,
            'destination_department' => $this->whenLoaded('destinationDepartment', function () {
                return [
                    'id' => $this->destinationDepartment->id,
                    'name' => $this->destinationDepartment->name
                ];
            }),
            'derivated_by_user_id' => $this->derivated_by_user_id,
            'derivated_by' => $this->whenLoaded('derivatedBy', function () {
                return [
                    'id' => $this->derivatedBy->id,
                    'name' => $this->derivatedBy->name,
                    'email' => $this->derivatedBy->email
                ];
            }),
            'details' => $this->whenLoaded('details', function () {
                return $this->details->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'comments' => $detail->comments,
                        'status' => $detail->status,
                        'created_at' => $detail->created_at,
                        'user' => $detail->whenLoaded('user', function () use ($detail) {
                            return [
                                'id' => $detail->user->id,
                                'name' => $detail->user->name,
                                'email' => $detail->user->email
                            ];
                        })
                    ];
                });
            }),
            'current_status' => $this->whenLoaded('details', function () {
                return $this->details->sortByDesc('created_at')->first()?->status ?? 'derived';
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
