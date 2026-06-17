<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds convenient test data for LOCAL development only.
 *
 * Hard-guarded against production so it can never create junk
 * accounts or products in the live database.
 */
class LocalDevSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('LocalDevSeeder is blocked in production. Aborting.');

            return;
        }

        // Admin account (reuse the existing idempotent seeder)
        $this->call(AdminUserSeeder::class);

        // A sales rep account to test the non-admin role
        if (! User::where('email', 'rep@local.test')->exists()) {
            User::create([
                'name' => 'Local Sales Rep',
                'email' => 'rep@local.test',
                'password' => Hash::make('password123'),
                'role' => 'sales_rep',
            ]);
            $this->command->info('Sales rep created: rep@local.test / password123');
        }

        // Sample products covering in-stock, low-stock and out-of-stock states
        $products = [
            ['name' => '100W Solar Panel',     'sku' => 'SP-100W', 'price' => 1499.00, 'cost' => 950.00,  'stock_quantity' => 40, 'low_stock_threshold' => 10, 'category' => 'Panels'],
            ['name' => '200W Solar Panel',     'sku' => 'SP-200W', 'price' => 2799.00, 'cost' => 1800.00, 'stock_quantity' => 8,  'low_stock_threshold' => 10, 'category' => 'Panels'],
            ['name' => '12V 100Ah Battery',    'sku' => 'BAT-100', 'price' => 3499.00, 'cost' => 2400.00, 'stock_quantity' => 15, 'low_stock_threshold' => 5,  'category' => 'Batteries'],
            ['name' => '5kVA Inverter',        'sku' => 'INV-5K',  'price' => 8999.00, 'cost' => 6200.00, 'stock_quantity' => 0,  'low_stock_threshold' => 3,  'category' => 'Inverters'],
            ['name' => 'MC4 Connector Pair',   'sku' => 'CON-MC4', 'price' => 49.00,   'cost' => 18.00,   'stock_quantity' => 250, 'low_stock_threshold' => 50, 'category' => 'Accessories'],
            ['name' => 'DC Cable (per metre)', 'sku' => 'CAB-DC1', 'price' => 35.00,   'cost' => 14.00,   'stock_quantity' => 600, 'low_stock_threshold' => 100, 'category' => 'Accessories', 'unit_of_measurement' => 'm'],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(
                ['sku' => $data['sku']],
                array_merge([
                    'is_active' => true,
                    'track_stock' => true,
                    'unit_of_measurement' => 'pcs',
                ], $data),
            );
        }

        $this->command->info('Seeded ' . count($products) . ' sample products.');
    }
}
