<?php

declare(strict_types=1);

use CodebarAg\MFiles\Requests\LogInToVaultRequest;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

test('can login to vault and get authentication token with caching', function () {

    Saloon::fake([
        LogInToVaultRequest::class => MockResponse::fixture('login-to-vault'),
    ]);

    $request = new LogInToVaultRequest(
        url: config('m-files.auth.url'),
        vaultGuid: config('m-files.vault_guid'),
        username: config('m-files.auth.username'),
        password: config('m-files.auth.password'),
    );

    $response = $request->send();
    $token = $response->dto();

    // Test that the response is processed correctly
    expect($response)->toBeInstanceOf(\Saloon\Http\Response::class);
    expect($response->status())->toBe(200);
    expect($token)->toBeString();
    expect($token)->not->toBeEmpty();
    // Verify the token format from LogInToVaultResponse processing
    expect($token)->toContain('cTLaCmVUEnLy23IYT-NyZGRtzEvSOOANbJWkHH2G39Zep0wsjfm9ieBtz3M02bcPraJhXVapSm-SPJ5hPP7pcLg44jS1oTgPajc9uDcPtDqDTO4aqdK5Rddw8-VSUISptQwp-f2o8NcIwgfrJGGbXMNOR15uKJtm_Shzb86FwKAR41Nc1hLCJfcQaNxIEwSNhnl--2GDORGgRmhYxFXiMnO0D_BO_gVeBDoONORngMgno24aE5egN7Gp-qJ5ln2yNh4dCfWx5A6Oq8HJQq463Bdhy1OLpuTZ1oLqemE3llVm-WjIB4P-5W4mqwmuzaHbQ152FNtgtAtZVgOz_P1mTw');

})->group('authentication', 'log-in-to-vault');
