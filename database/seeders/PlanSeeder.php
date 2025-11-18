<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // الخطة الشهرية
        Plan::updateOrCreate(
            ['name' => 'اشتراك شهري'],
            [
                'price' => 100,
                'duration' => 30, // أيام
                'features' => json_encode([
                    'benefit' => '70%',
                ]),
            ]
        );

        // الخطة السنوية
        Plan::updateOrCreate(
            ['name' => 'اشتراك سنوي'],
            [
                'price' => 1000,
                'duration' => 365, // أيام
                'features' => json_encode([
                    'benefit' => '70%',
                ]),
            ]
        );
    }
}
