<?php

use Ddr\ForgeTestBranches\Http\Controllers\WebhookController;
use Ddr\ForgeTestBranches\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::post(config('forge-test-branches.webhook.path', 'forge-test-branches/webhook'), [WebhookController::class, 'handle'])
    ->middleware(VerifyWebhookSignature::class)
    ->name('forge-test-branches.webhook');
