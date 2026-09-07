<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('suppliers')->upsert([
            [
                'code' => 'supplier-a',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'supplier-b',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], ['updated_at']);
    }
}
