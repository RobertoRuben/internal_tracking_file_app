<?php
namespace App\Filament\Resources\DocumentResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\DocumentResource\Api\Requests\CreateDocumentRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = DocumentResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Document
     *
     * @param CreateDocumentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateDocumentRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}