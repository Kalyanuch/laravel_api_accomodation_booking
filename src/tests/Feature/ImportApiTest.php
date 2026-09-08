<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Throwable;

class ImportApiTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Supplier::factory()->create(['code' => 'supplier-a']);
    }

    public function test_it_accepts_an_import_and_dispatches_its_processing_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/imports', $this->payload());

        $response
            ->assertAccepted()
            ->assertJsonPath('data.status', ImportStatus::Pending->value);

        $import = Import::query()->sole();

        $this->assertSame(1, $import->total_offers);
        $this->assertSame('offer-a-10001', $import->payload['offers'][0]['external_id']);
        Queue::assertPushed(
            ProcessImport::class,
            fn (ProcessImport $job): bool => $job->importId === $import->getKey(),
        );
    }

    public function test_repeated_import_is_idempotent_and_is_not_dispatched_again(): void
    {
        Queue::fake();

        $firstResponse = $this->postJson('/api/imports', $this->payload());
        $secondResponse = $this->postJson('/api/imports', $this->payload());

        $firstResponse->assertAccepted();
        $secondResponse
            ->assertAccepted()
            ->assertJsonPath('data.id', $firstResponse->json('data.id'));

        $this->assertDatabaseCount('imports', 1);
        Queue::assertPushed(ProcessImport::class, 1);
    }

    public function test_it_validates_supplier_and_offer_structure(): void
    {
        Queue::fake();
        $payload = $this->payload();
        $payload['supplier'] = 'unknown';
        $payload['offers'][0]['check_out'] = '2026-10-01';
        $payload['offers'][0]['currency'] = 'euro';

        $this->postJson('/api/imports', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'supplier',
                'offers.0.check_out',
                'offers.0.currency',
            ]);

        $this->assertDatabaseCount('imports', 0);
        Queue::assertNothingPushed();
    }

    public function test_it_returns_the_current_import_status(): void
    {
        Queue::fake();
        $importId = $this->postJson('/api/imports', $this->payload())->json('data.id');

        $this->getJson("/api/imports/{$importId}")
            ->assertOk()
            ->assertJsonPath('data.id', $importId)
            ->assertJsonPath('data.supplier', 'supplier-a')
            ->assertJsonPath('data.external_import_id', 'import-2026-09-01-001')
            ->assertJsonPath('data.status', ImportStatus::Pending->value)
            ->assertJsonPath('data.total_offers', 1)
            ->assertJsonPath('data.processed_offers', 0)
            ->assertJsonPath('data.error', null)
            ->assertJsonPath('data.completed_at', null);
    }

    public function test_job_creates_properties_and_offers_and_completes_import(): void
    {
        Queue::fake();
        $importId = $this->postJson('/api/imports', $this->payload())->json('data.id');

        (new ProcessImport($importId))->handle();

        $import = Import::query()->findOrFail($importId);

        $this->assertSame(ImportStatus::Completed, $import->status);
        $this->assertSame(1, $import->processed_offers);
        $this->assertNotNull($import->completed_at);
        $this->assertDatabaseHas('properties', [
            'code' => 'BCN-0001',
            'name' => 'Apartment near Sagrada Familia',
            'city' => 'Barcelona',
        ]);
        $this->assertDatabaseHas('offers', [
            'supplier_id' => $import->supplier_id,
            'external_id' => 'offer-a-10001',
            'property_id' => Property::query()->where('code', 'BCN-0001')->value('id'),
            'import_id' => $importId,
            'price' => 72500,
            'available_units' => 2,
        ]);
    }

    public function test_later_import_updates_existing_supplier_offer_without_duplicates(): void
    {
        Queue::fake();
        $firstImportId = $this->postJson('/api/imports', $this->payload())->json('data.id');
        (new ProcessImport($firstImportId))->handle();

        $payload = $this->payload();
        $payload['external_import_id'] = 'import-2026-09-01-002';
        $payload['offers'][0]['price'] = 70000;
        $payload['offers'][0]['available_units'] = 5;

        $secondImportId = $this->postJson('/api/imports', $payload)->json('data.id');
        (new ProcessImport($secondImportId))->handle();

        $this->assertSame(1, Offer::query()->count());
        $this->assertDatabaseHas('offers', [
            'external_id' => 'offer-a-10001',
            'import_id' => $secondImportId,
            'price' => 70000,
            'available_units' => 5,
        ]);
    }

    public function test_failed_job_marks_import_as_failed(): void
    {
        Queue::fake();
        $importId = $this->postJson('/api/imports', $this->payload())->json('data.id');
        $import = Import::query()->findOrFail($importId);
        $import->update(['payload' => ['offers' => [['property' => []]]]]);
        $job = new ProcessImport($importId);

        try {
            $job->handle();
            $this->fail('The malformed stored payload should fail processing.');
        } catch (Throwable $exception) {
            $job->failed($exception);
        }

        $import->refresh();
        $this->assertSame(ImportStatus::Failed, $import->status);
        $this->assertNotNull($import->error);
        $this->assertSame(0, Property::query()->count());
        $this->assertSame(0, Offer::query()->count());
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-2026-09-01-001',
            'sent_at' => '2026-09-01T10:00:00Z',
            'offers' => [[
                'external_id' => 'offer-a-10001',
                'property' => [
                    'code' => 'BCN-0001',
                    'name' => 'Apartment near Sagrada Familia',
                    'city' => 'Barcelona',
                ],
                'check_in' => '2026-10-10',
                'check_out' => '2026-10-15',
                'max_guests' => 4,
                'price' => 72500,
                'currency' => 'EUR',
                'available_units' => 2,
                'expires_at' => '2026-09-10T23:59:59Z',
            ]],
        ];
    }
}
