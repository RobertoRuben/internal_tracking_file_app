<?php
namespace App\Filament\Resources\DepartmentResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Department;

/**
 * @property Department $resource
 */
class DepartmentTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
