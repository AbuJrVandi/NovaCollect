<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            OrganizationSeeder::class,
            ProjectSeeder::class,
            FormSeeder::class,
            SubmissionSeeder::class,
        ]);
    }
}
