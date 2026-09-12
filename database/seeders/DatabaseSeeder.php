<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Imports the real station dataset and prepares the coming Daily Games.
     */
    public function run(): void
    {
        Artisan::call('stations:import', [], $this->command?->getOutput());
        Artisan::call('treinprikker:generate-daily', [], $this->command?->getOutput());
    }
}
