<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;

class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase, WithFaker;

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Ensure Sanctum spa detection is mocked.
     *
     * @param string $method
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return TestResponse
     */
    public function json($method, $uri, array $data = [], array $headers = []): TestResponse
    {
        $headers = array_merge(
            $headers,
            ['Referer' => config('app.url'), 'Origin' => config('app.url')]
        );

        return parent::json($method, $uri, $data, $headers);
    }
}
