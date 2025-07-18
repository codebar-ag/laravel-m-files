<?php

declare(strict_types=1);

use CodebarAg\MFiles\Connectors\MFilesConnector;
use CodebarAg\MFiles\DTO\Config\ConfigWithCredentials;
use CodebarAg\MFiles\DTO\User;
use CodebarAg\MFiles\Fixtures\AuthenticationTokenFixture;
use CodebarAg\MFiles\Fixtures\CurrentUserFixture;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use CodebarAg\MFiles\Requests\GetCurrentUserRequest;
use Saloon\Laravel\Facades\Saloon;

test('can get current user information', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
        GetCurrentUserRequest::class => new CurrentUserFixture,
    ]);

    $config = new ConfigWithCredentials;
    $connector = new MFilesConnector($config);

    $response = $connector->send(new GetCurrentUserRequest)->dto();

    expect($response)->toBeInstanceOf(User::class);
    expect($response->id)->toBe(123);
});
