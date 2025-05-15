<?php
namespace App\Filament\Resources\DepartmentResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\DepartmentResource\Api\Requests\UpdateDepartmentRequest;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = DepartmentResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }


    /**
     * Update Department
     *
     * @param UpdateDepartmentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateDepartmentRequest $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (!$model) return static::sendNotFoundResponse();

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Update Resource");
    }
}