<?php

declare(strict_types=1);

use Tests\Feature\Auth\AuthenticationTest;
use Tests\Feature\Api\TelemetryTest;
use Tests\Feature\Api\AgentAuthTest;
use Tests\Unit\Models\ServerTest;
use Tests\Unit\Models\CredentialTest;
use Tests\Unit\Services\AlertEvaluatorTest;

$tests = [
    AuthenticationTest::class,
    TelemetryTest::class,
    AgentAuthTest::class,
    ServerTest::class,
    CredentialTest::class,
    AlertEvaluatorTest::class,
];

return $tests;
