<?php

namespace Tests\Feature;

use App\Models\Offer;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_creates_a_reservation_and_decrements_availability(): void
    {
        $offer = $this->offer(['available_units' => 2]);

        $this->postJson("/api/offers/{$offer->getKey()}/reservations", $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.offer_id', $offer->getKey())
            ->assertJsonPath('data.client_reference', 'web-order-9f782b1c')
            ->assertJsonPath('data.customer_name', 'John Smith')
            ->assertJsonPath('data.customer_email', 'john@example.com');

        $this->assertSame(1, $offer->fresh()->available_units);
        $this->assertDatabaseHas('reservations', [
            'offer_id' => $offer->getKey(),
            'client_reference' => 'web-order-9f782b1c',
        ]);
    }

    public function test_it_does_not_book_more_than_the_last_available_unit(): void
    {
        $offer = $this->offer(['available_units' => 1]);

        $this->postJson("/api/offers/{$offer->getKey()}/reservations", $this->payload())
            ->assertCreated();

        $this->postJson("/api/offers/{$offer->getKey()}/reservations", $this->payload([
            'client_reference' => 'second-order',
        ]))
            ->assertConflict()
            ->assertJsonPath('message', 'The offer is no longer available.');

        $this->assertSame(0, $offer->fresh()->available_units);
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_it_rejects_sold_out_and_expired_offers(): void
    {
        $soldOut = $this->offer(['available_units' => 0]);
        $expired = $this->offer(['expires_at' => now()->subSecond()]);

        $this->postJson("/api/offers/{$soldOut->getKey()}/reservations", $this->payload())
            ->assertConflict();
        $this->postJson("/api/offers/{$expired->getKey()}/reservations", $this->payload())
            ->assertConflict();

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_it_validates_the_reservation_payload(): void
    {
        $offer = $this->offer();

        $this->postJson("/api/offers/{$offer->getKey()}/reservations", [
            'client_reference' => '',
            'customer_name' => '',
            'customer_email' => 'not-an-email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['client_reference', 'customer_name', 'customer_email']);

        $this->assertSame(2, $offer->fresh()->available_units);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_it_returns_not_found_for_an_unknown_offer(): void
    {
        $this->postJson('/api/offers/999999/reservations', $this->payload())
            ->assertNotFound();
    }

    /** @param array<string, mixed> $overrides */
    private function offer(array $overrides = []): Offer
    {
        return Offer::factory()->create(array_merge([
            'available_units' => 2,
            'expires_at' => now()->addDay(),
        ], $overrides));
    }

    /** @param array<string, string> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_reference' => 'web-order-9f782b1c',
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
        ], $overrides);
    }
}
