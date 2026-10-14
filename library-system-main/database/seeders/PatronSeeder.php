<?php

namespace Database\Seeders;

use App\Models\Patron;
use Illuminate\Database\Seeder;

/**
 * Optional sample borrowers for testing the Circulation module.
 * Run with: php artisan db:seed --class=PatronSeeder
 */
class PatronSeeder extends Seeder
{
    public function run(): void
    {
        $patrons = [
            ['id_number' => '2026-0001', 'name' => 'Juan Dela Cruz',  'patron_type' => 'Student', 'email' => 'juan@example.com'],
            ['id_number' => '2026-0002', 'name' => 'Maria Santos',    'patron_type' => 'Student', 'email' => 'maria@example.com'],
            ['id_number' => 'FAC-0001',  'name' => 'Prof. Ana Reyes', 'patron_type' => 'Faculty', 'email' => 'ana@example.com'],
            ['id_number' => 'STF-0001',  'name' => 'Pedro Garcia',    'patron_type' => 'Staff',   'email' => null],
        ];

        foreach ($patrons as $p) {
            Patron::firstOrCreate(['id_number' => $p['id_number']], $p + ['status' => 'active']);
        }
    }
}
