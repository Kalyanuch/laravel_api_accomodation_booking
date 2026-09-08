<?php

namespace App\Actions\Properties;

use App\Data\Properties\SearchPropertiesData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SearchBestOffers
{
    public function handle(SearchPropertiesData $data): LengthAwarePaginator
    {
        $rankedOffers = DB::table('offers')
            ->join('properties', 'properties.id', '=', 'offers.property_id')
            ->join('suppliers', 'suppliers.id', '=', 'offers.supplier_id')
            ->where('offers.max_guests', '>=', $data->guests)
            ->where('offers.available_units', '>', 0)
            ->where('offers.expires_at', '>', now())
            ->when(
                $data->city !== null,
                fn (Builder $query): Builder => $query->where('properties.city', $data->city),
            )
            ->select([
                'properties.id as property_id',
                'properties.code as property_code',
                'properties.name as property_name',
                'properties.city as property_city',
                'offers.id as offer_id',
                'suppliers.code as supplier_code',
                'offers.price as offer_price',
                'offers.currency as offer_currency',
                'offers.available_units as offer_available_units',
                'offers.expires_at as offer_expires_at',
            ])
            ->selectRaw(
                'ROW_NUMBER() OVER (PARTITION BY offers.property_id ORDER BY offers.price ASC, offers.id ASC) as offer_rank',
            );

        if (DB::connection()->getDriverName() === 'sqlite') {
            $rankedOffers
                ->whereDate('offers.check_in', $data->checkIn)
                ->whereDate('offers.check_out', $data->checkOut);
        } else {
            $rankedOffers
                ->where('offers.check_in', $data->checkIn)
                ->where('offers.check_out', $data->checkOut);
        }

        return DB::query()
            ->fromSub($rankedOffers, 'ranked_offers')
            ->where('offer_rank', 1)
            ->orderBy('offer_price')
            ->orderBy('property_id')
            ->paginate((int) config('properties.search.per_page'))
            ->withQueryString();
    }
}
