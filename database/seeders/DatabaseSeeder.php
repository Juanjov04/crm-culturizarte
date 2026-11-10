<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Usuarios de prueba (contraseña: "password"). Cambiarlas antes de producción.
        $admin = User::firstOrCreate(
            ['email' => 'admin@culturizarte.test'],
            ['name' => 'Administrador CRM', 'password' => 'password', 'email_verified_at' => now()]
        );
        $admin->syncRoles(['Administrador']);

        $asesor = User::firstOrCreate(
            ['email' => 'asesor@culturizarte.test'],
            ['name' => 'Asesor Comercial Demo', 'password' => 'password', 'email_verified_at' => now()]
        );
        $asesor->syncRoles(['Asesor']);
    }
}
