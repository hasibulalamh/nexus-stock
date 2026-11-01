<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSafeTestDatabase();

        // CSRF verification is a browser concern; feature tests exercise routing,
        // auth and validation. Disable it here so POST/PUT/DELETE requests are
        // not rejected with 419 before reaching the controller under test.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function assertSafeTestDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('Tests require the sqlite :memory: database; refusing to run against a real database.');
        }
    }
}
