<?php
namespace App\Filament\Resources\DocumentResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\DocumentResource\Api\Transformers\DocumentTransformer;
use App\Models\Document;

class PaginationHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = DocumentResource::class;
    public static bool $public = true; // Esto hace que la ruta sea pública, sin autenticación

    /**
     * Obtiene el modelo desde el recurso
     */
    public static function getModel(): string
    {
        return Document::class;
    }


    /**
     * List of documents
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function handler()
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

        return DocumentTransformer::collection($query);
    }
}
