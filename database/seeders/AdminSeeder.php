<?php

namespace Database\Seeders;

use App\Enums\PapelUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $senha = env('ADMIN_PASSWORD');
        if (! $senha) {
            throw new RuntimeException('Defina ADMIN_PASSWORD no .env antes de rodar os seeders.');
        }

        User::updateOrCreate(
            ['email' => 'admin@cleideluna.com.br'],
            ['nome' => 'Cleide Luna', 'password' => $senha, 'papel' => PapelUsuario::Admin, 'ativo' => true],
        );
    }
}
