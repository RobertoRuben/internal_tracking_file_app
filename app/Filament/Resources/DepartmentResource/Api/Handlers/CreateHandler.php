<?php
namespace App\Filament\Resources\DepartmentResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\DepartmentResource\Api\Requests\CreateDepartmentRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = DepartmentResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Department
     *
     * @param CreateDepartmentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateDepartmentRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}