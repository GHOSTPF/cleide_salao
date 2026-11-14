<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ConfiguracaoSeeder::class,
            AdminSeeder::class,
            CatalogoSeeder::class,
            ProfissionalSeeder::class,
            SiteSeeder::class,
        ]);

        // Massa de dados de exemplo (~30 clientes, ~120 agendamentos) somente em ambiente local
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
