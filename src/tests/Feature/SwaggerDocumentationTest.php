<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_swagger_ui_and_all_public_api_operations_are_available_without_authentication(): void
    {
        Artisan::call('l5-swagger:generate');

        $this->get('/api/documentation')->assertOk();

        $specification = $this->getJson('/docs')->assertOk()->json();

        $this->assertArrayHasKey('post', $specification['paths']['/api/imports']);
        $this->assertArrayHasKey('get', $specification['paths']['/api/imports/{import}']);
        $this->assertArrayHasKey('get', $specification['paths']['/api/properties']);
        $this->assertArrayHasKey('post', $specification['paths']['/api/offers/{offer}/reservations']);
        $this->assertArrayNotHasKey('securitySchemes', $specification['components'] ?? []);

        foreach ($specification['paths'] as $operations) {
            foreach ($operations as $operation) {
                $this->assertSame([], $operation['security']);
            }
        }
    }
}
