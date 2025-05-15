<?php
namespace App\Filament\Resources\EmployeeResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\Api\Requests\CreateEmployeeRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = EmployeeResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Employee
     *
     * @param CreateEmployeeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateEmployeeRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}