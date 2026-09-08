<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Property;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PropertyOfferSeederTest extends TestCase
{
    use DatabaseMigrations;

    public function test_offer_factory_creates_a_complete_valid_graph(): void
    {
        $offer = Offer::factory()->create();

        $this->assertTrue($offer->supplier()->exists());
        $this->assertTrue($offer->property()->exists());
        $this->assertTrue($offer->import()->exists());
        $this->assertSame($offer->supplier_id, $offer->import->supplier_id);
        $this->assertTrue($offer->check_out->isAfter($offer->check_in));
    }

    public function test_database_seeder_creates_ten_properties_with_one_to_five_offers_each(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(10, Property::query()->count());
        $this->assertSame(30, Offer::query()->count());

        Property::query()->withCount('offers')->each(function (Property $property): void {
            $this->assertGreaterThanOrEqual(1, $property->offers_count);
            $this->assertLessThanOrEqual(5, $property->offers_count);
        });
    }
}
