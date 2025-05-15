<?php
namespace App\Filament\Resources\DocumentResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\DocumentResource;
use Illuminate\Routing\Router;


class DocumentApiService extends ApiService
{
    protected static string | null $resource = DocumentResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];
    }
}
