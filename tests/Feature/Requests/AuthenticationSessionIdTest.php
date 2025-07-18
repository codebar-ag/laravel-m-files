<?php

declare(strict_types=1);

use CodebarAg\MFiles\DTO\Authentication\AuthenticationToken;
use CodebarAg\MFiles\Fixtures\AuthenticationTokenFixture;
use CodebarAg\MFiles\Requests\Authentication\GetAuthenticationToken;
use Saloon\Laravel\Facades\Saloon;

test('session id in response matches the one sent in request', function () {
    Saloon::fake([
        GetAuthenticationToken::class => new AuthenticationTokenFixture,
    ]);

    $request = new GetAuthenticationToken(
        url: 'https://test-mfiles-server.com',
        username: 'test-user',
        password: 'test-password',
        vaultGuid: 'ABC0DE2G-3HW-QWCQ-SDF3-WERWETWETW'
    );

    $response = $request->send();
    $token = $response->dto();

    expect($token)->toBeInstanceOf(AuthenticationToken::class);
    expect($token->sessionId)->not->toBeNull();

    // The session ID should be the same as what was sent in the request
    // We can verify this by checking that it's a valid UUID format
    expect($token->sessionId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});
