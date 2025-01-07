<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('pastevents')->insert([
            [
                'eventName' => '30 Days Before Christmas Concert',
                'eventDescription' => 'Konser tahunan berjudul "30 Days Before Christmas" dengan tema natal, dan juga membawakan lagu-lagu sekuler dari berbagai komposer. Konser tersebut juga merupakan konser ucapan syukur HUT Voice of Soul ke 18 Tahun.',
                'eventImage' => 'concert-1.jpg',
                'eventDate' => 'Jakarta, 2023',
                'rowstatus' => 0,
                'createdBy' => 'Seeder',
                'createdDate' => now(),
                'modifiedBy' => 'Seeder',
                'modifiedDate' => now(),
            ],
            [
                'eventName' => '2024 Tokyo International Choir Competition',
                'eventDescription' => 'TICC 2024 merupakan sebuah edisi kompetisi paduan suara yang diselenggarakan di Tokyo Jepang pada Juli-Agustus 2024. VOS berhasil memperoleh peringkat Silver Medalist di kategori Kontemporer',
                'eventImage' => 'tokyo2024.jpg',
                'eventDate' => 'Tokyo, 2024',
                'rowstatus' => 0,
                'createdBy' => 'Seeder',
                'createdDate' => now(),
                'modifiedBy' => 'Seeder',
                'modifiedDate' => now(),
            ],
            [
                'eventName' => 'Gibeon Bermazmur',
                'eventDescription' => 'Sebuah konser dengan cita-cita besar dan sebagai dasar pelayanan, menjadi salah satu perwujudan misi VOS dalam memuliakan Tuhan dan membangun gerejaNya. 100% hasil konser menjadi bagian dari kas pembangunan gereja',
                'eventImage' => 'gibeon-bermazmur.jpg',
                'eventDate' => 'Jakarta, 2024',
                'rowstatus' => 0,
                'createdBy' => 'Seeder',
                'createdDate' => now(),
                'modifiedBy' => 'Seeder',
                'modifiedDate' => now(),
            ]
        ]);
    }
}
