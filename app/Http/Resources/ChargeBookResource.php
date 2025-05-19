<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeBookResource extends JsonResource
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
            'sender_department_id' => $this->sender_department_id,
            'sender_department' => $this->whenLoaded('senderDepartment', function () {
                return [
                    'id' => $this->senderDepartment->id,
                    'name' => $this->senderDepartment->name
                ];
            }),
            'sender_user_id' => $this->sender_user_id,
            'sender_user' => $this->whenLoaded('senderUser', function () {
                return [
                    'id' => $this->senderUser->id,
                    'name' => $this->senderUser->name,
                    'email' => $this->senderUser->email
                ];
            }),
            'receiver_user_id' => $this->receiver_user_id,
            'receiver_user' => $this->whenLoaded('receiverUser', function () {
                return [
                    'id' => $this->receiverUser->id,
                    'name' => $this->receiverUser->name,
                    'email' => $this->receiverUser->email
                ];
            }),
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'name' => $this->department->name
                ];
            }),
            'notes' => $this->notes,
            'registration_number' => $this->registration_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
