<?php

namespace App\Filament\Resources\EmployeeResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\EmployeeResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\EmployeeResource\Api\Transformers\EmployeeTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = EmployeeResource::class;


    /**
     * Show Employee
     *
     * @param Request $request
     * @return EmployeeTransformer
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

        return new EmployeeTransformer($query);
    }
}
