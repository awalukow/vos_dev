<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed data for the 'schedule' table
        DB::table('schedule')->insert([
            [
                'program_date' => '2024-05-18 23:59:59',
                'program_until' => '2024-05-18 23:59:59',
                'event_name' => 'Konser Gibeon Bermazmur',
                'event_detail' => 'Konser VOS untuk membantu pelayanan dan pembangunan GPIB Gibeon melalui puji-pujian gereja',
                'event_image' => 'assets/images/ticc.jpg',
                'event_imageCaptionUrl' => 'assets/images/ticc.jpg',
                'rowstatus' => 1,
                'createdBy' => 'Seeder',
                'createdDate' => now(),
                'modifiedBy' => 'Seeder',
                'modifiedDate' => now(),
            ],
            [
                'program_date' => '2024-07-26 23:59:59',
                'program_until' => '2024-07-28 23:59:59',
                'event_name' => '2024 Tokyo International Choir Competition',
                'event_detail' => 'Kompetisi paduan suara Internasional yang akan dilaksanakan pertengahan tahun 2024 di Tokyo, Jepang.',
                'event_image' => 'assets/images/vos-logo.jpg',
                'event_imageCaptionUrl' => 'assets/images/vos-logo.jpg',
                'rowstatus' => 1,
                'createdBy' => 'Seeder',
                'createdDate' => now(),
                'modifiedBy' => 'Seeder',
                'modifiedDate' => now(),
            ]
        ]);
    }
}
