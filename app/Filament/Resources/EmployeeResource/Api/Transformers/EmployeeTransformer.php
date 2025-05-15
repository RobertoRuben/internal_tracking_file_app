<?php
namespace App\Filament\Resources\EmployeeResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Employee;

/**
 * @property Employee $resource
 */
class EmployeeTransformer extends JsonResource
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
