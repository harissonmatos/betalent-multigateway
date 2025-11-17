<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    public function run()
    {
        Gateway::insert([
            [
                'id' => 1,
                'name' => 'Gateway 1',
                'slug' => 'gateway1',
                'priority' => 1, // prioridade mais alta
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Gateway 2',
                'slug' => 'gateway2',
                'priority' => 2, // segunda opção
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
