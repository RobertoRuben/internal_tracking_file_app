<?php
namespace App\Filament\Resources\DocumentResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

/**
 * @property Document $resource
 */
class DocumentTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'date' => $this->resource->date,
            'reference_number' => $this->resource->reference_number,
            'document_type' => $this->resource->document_type,
            'subject' => $this->resource->subject,
            'origin' => $this->resource->origin,
            'priority' => $this->resource->priority,
            'status' => $this->resource->status,
            'is_confidential' => $this->resource->is_confidential,
            'created_by_department' => $this->resource->createdByDepartment ? [
                'id' => $this->resource->createdByDepartment->id,
                'name' => $this->resource->createdByDepartment->name,
            ] : null,
            'file_path' => $this->resource->file_path ? Storage::url($this->resource->file_path) : null,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
