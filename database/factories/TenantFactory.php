<?php

namespace Worldesports\MultiTenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Worldesports\MultiTenancy\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->company,
        ];
    }
}
