<?php

namespace App\Console\Commands;

use App\Models\HRD\EmployeeType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedEmployeeTypes extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'hrd:seed-employee-types {--company= : Specific company ID to seed}';

    /**
     * The console command description.
     */
    protected $description = 'Seed default employee types for companies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');

        if ($companyId) {
            $this->seedForCompany($companyId);
        } else {
            $this->seedAllCompanies();
        }

        return Command::SUCCESS;
    }

    /**
     * Seed employee types for all companies
     */
    protected function seedAllCompanies(): void
    {
        $companyIds = DB::table('companies')->pluck('id')->toArray();

        $this->info('Seeding employee types for ' . count($companyIds) . ' companies...');

        $bar = $this->output->createProgressBar(count($companyIds));
        $bar->start();

        foreach ($companyIds as $companyId) {
            $this->seedForCompany($companyId);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! Employee types have been seeded.');
    }

    /**
     * Seed employee types for a specific company
     */
    protected function seedForCompany(int $companyId): void
    {
        $defaults = [
            [
                'name' => 'Karyawan Tetap',
                'code' => 'TETAP',
                'description' => 'Karyawan dengan status tetap/permanen',
                'color' => '#22C55E',
                'sort_order' => 1,
            ],
            [
                'name' => 'Karyawan Kontrak',
                'code' => 'KONTRAK',
                'description' => 'Karyawan dengan perjanjian kerja tertentu',
                'color' => '#F59E0B',
                'sort_order' => 2,
            ],
            [
                'name' => 'Masa Percobaan',
                'code' => 'PERCOBAAN',
                'description' => 'Karyawan dalam masa percobaan',
                'color' => '#8B5CF6',
                'sort_order' => 3,
            ],
            [
                'name' => 'Magang',
                'code' => 'MAGANG',
                'description' => 'Karyawan magang/praktik kerja',
                'color' => '#3B82F6',
                'sort_order' => 4,
            ],
            [
                'name' => 'Freelance',
                'code' => 'FREELANCE',
                'description' => 'Karyawan bebas/freelance',
                'color' => '#EC4899',
                'sort_order' => 5,
            ],
            [
                'name' => 'Outsource',
                'code' => 'OUTSOURCE',
                'description' => 'Karyawan pihak ketiga/outsourcing',
                'color' => '#6366F1',
                'sort_order' => 6,
            ],
            [
                'name' => 'Part Time',
                'code' => 'PARTTIME',
                'description' => 'Karyawan paruh waktu',
                'color' => '#14B8A6',
                'sort_order' => 7,
            ],
        ];

        foreach ($defaults as $type) {
            $exists = EmployeeType::where('company_id', $companyId)
                ->where('code', $type['code'])
                ->exists();

            if (!$exists) {
                EmployeeType::create([
                    'company_id' => $companyId,
                    'name' => $type['name'],
                    'code' => $type['code'],
                    'description' => $type['description'],
                    'color' => $type['color'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
