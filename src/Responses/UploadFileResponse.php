<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\Responses;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Saloon\Http\Response;

final class UploadFileResponse
{
    public static function createDtoFromResponse(Response $response, string $fileName): array
    {
        $data = $response->json();
        $data = Arr::add($data, 'Title', Str::beforeLast($fileName, '.'));
        $data = Arr::add($data, 'Extension', Str::afterLast($fileName, '.'));
        Arr::forget($data, 'FileInformationType');

        return $data;
    }
}
