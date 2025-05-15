<?php

namespace App\Filament\Resources\DepartmentResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\DepartmentResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\DepartmentResource\Api\Transformers\DepartmentTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = DepartmentResource::class;


    /**
     * Show Department
     *
     * @param Request $request
     * @return DepartmentTransformer
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

        return new DepartmentTransformer($query);
    }
}
