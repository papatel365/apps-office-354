<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order of seeding is critical to maintain referential integrity.
     */
    public function run(): void
    {
        $this->command->info('===========================================');
        $this->command->info('Starting Database Seeding Process');
        $this->command->info('===========================================');

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('Creating Initial Data');
        $this->command->info('===========================================');

        $this->call([
            AdministratorSeeder::class,
        ], true);

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('Database Seeding Completed!');
        $this->command->info('===========================================');
    }
}
