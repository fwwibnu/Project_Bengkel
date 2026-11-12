<?php

namespace Database\Factories;

use App\Models\Sparepart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Catatan: di aplikasi, stok hanya diubah lewat StockService.
 * Factory boleh mengisi stok langsung khusus untuk pengujian.
 *
 * @extends Factory<Sparepart>
 */
class SparepartFactory extends Factory
{
    protected $model = Sparepart::class;

    public function definition(): array
    {
        return [
            'code' => 'SP-'.fake()->unique()->numerify('9##'),
            'name' => fake()->randomElement(['Oli mesin 1L', 'Busi standar', 'Filter udara', 'Kampas rem']).' '.fake()->unique()->numberBetween(1, 9999),
            'stock' => 10,
            'min_stock' => 3,
            'purchase_price' => 40_000,
            'selling_price' => 55_000,
        ];
    }
}
