<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompanyType;

class CompanyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyTypes = [
            [
                'name' => 'E-commerce',
                'slug' => 'ecommerce',
                'description' => 'For online retail businesses, order follow-ups, abandoned cart recovery',
                'default_settings' => [
                    'timezone' => 'UTC',
                    'business_hours' => [
                        'monday' => ['start' => '09:00', 'end' => '17:00'],
                        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                        'thursday' => ['start' => '09:00', 'end' => '17:00'],
                        'friday' => ['start' => '09:00', 'end' => '17:00'],
                        'saturday' => ['start' => '10:00', 'end' => '14:00'],
                        'sunday' => ['start' => null, 'end' => null],
                    ],
                    'call_settings' => [
                        'max_concurrency' => 5,
                        'max_retries' => 3,
                        'record_calls' => false,
                    ],
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Healthcare',
                'slug' => 'healthcare',
                'description' => 'For medical practices, appointment reminders, patient follow-ups',
                'default_settings' => [
                    'timezone' => 'UTC',
                    'business_hours' => [
                        'monday' => ['start' => '08:00', 'end' => '18:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '18:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '18:00'],
                        'thursday' => ['start' => '08:00', 'end' => '18:00'],
                        'friday' => ['start' => '08:00', 'end' => '18:00'],
                        'saturday' => ['start' => null, 'end' => null],
                        'sunday' => ['start' => null, 'end' => null],
                    ],
                    'call_settings' => [
                        'max_concurrency' => 3,
                        'max_retries' => 2,
                        'record_calls' => true,
                    ],
                    'compliance_settings' => [
                        'hipaa_mode' => true,
                        'data_retention_days' => 2555, // 7 years
                    ],
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Real Estate',
                'slug' => 'real-estate',
                'description' => 'For real estate agencies, lead follow-ups, property notifications',
                'default_settings' => [
                    'timezone' => 'UTC',
                    'business_hours' => [
                        'monday' => ['start' => '09:00', 'end' => '19:00'],
                        'tuesday' => ['start' => '09:00', 'end' => '19:00'],
                        'wednesday' => ['start' => '09:00', 'end' => '19:00'],
                        'thursday' => ['start' => '09:00', 'end' => '19:00'],
                        'friday' => ['start' => '09:00', 'end' => '19:00'],
                        'saturday' => ['start' => '10:00', 'end' => '16:00'],
                        'sunday' => ['start' => '12:00', 'end' => '16:00'],
                    ],
                    'call_settings' => [
                        'max_concurrency' => 8,
                        'max_retries' => 5,
                        'record_calls' => true,
                    ],
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Generic',
                'slug' => 'generic',
                'description' => 'For general business use, lead generation, customer outreach',
                'default_settings' => [
                    'timezone' => 'UTC',
                    'business_hours' => [
                        'monday' => ['start' => '09:00', 'end' => '17:00'],
                        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                        'thursday' => ['start' => '09:00', 'end' => '17:00'],
                        'friday' => ['start' => '09:00', 'end' => '17:00'],
                        'saturday' => ['start' => null, 'end' => null],
                        'sunday' => ['start' => null, 'end' => null],
                    ],
                    'call_settings' => [
                        'max_concurrency' => 5,
                        'max_retries' => 3,
                        'record_calls' => false,
                    ],
                ],
                'is_active' => true,
            ],
        ];

        foreach ($companyTypes as $companyType) {
            CompanyType::create($companyType);
        }
    }
}
