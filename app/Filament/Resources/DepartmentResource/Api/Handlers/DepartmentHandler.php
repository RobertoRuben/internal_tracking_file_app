<?php
namespace App\Filament\Resources\DepartmentResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\DepartmentResource;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\DepartmentResource\Api\Transformers\DepartmentTransformer;
use App\Models\Department;

class DepartmentHandler extends Handlers {
    public static string | null $uri = '/public';
    public static string | null $resource = DepartmentResource::class;
    public static bool $public = true; // Ruta pública

    public static function getMethod()
    {
        return Handlers::GET;
    }

    public static function getModel() {
        return Department::class;
    }

    public function handler(Request $request)
    {
        $query = static::getEloquentQuery();
        
        // Obtenemos el modelo para acceder a los métodos de filtrado
        $model = static::getModel();
        
        $allowedFields = method_exists($model, 'getAllowedFields') ? $model::getAllowedFields() : [];
        $allowedSorts = method_exists($model, 'getAllowedSorts') ? $model::getAllowedSorts() : [];
        $allowedFilters = method_exists($model, 'getAllowedFilters') ? $model::getAllowedFilters() : [];
        $allowedIncludes = method_exists($model, 'getAllowedIncludes') ? $model::getAllowedIncludes() : [];

        $query = QueryBuilder::for($query)
            ->allowedFields($allowedFields)
            ->allowedSorts($allowedSorts)
            ->allowedFilters($allowedFilters)
            ->allowedIncludes($allowedIncludes)
            ->paginate(request()->query('per_page'))
            ->appends(request()->query());

        return DepartmentTransformer::collection($query);
    }
}