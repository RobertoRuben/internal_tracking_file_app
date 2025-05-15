<?php

namespace App\Filament\Resources\DocumentResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\DocumentResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\DocumentResource\Api\Transformers\DocumentTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = DocumentResource::class;


    /**
     * Show Document
     *
     * @param Request $request
     * @return DocumentTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');
        
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new DocumentTransformer($query);
    }
}
