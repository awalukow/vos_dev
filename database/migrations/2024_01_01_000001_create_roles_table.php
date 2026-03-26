<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // administrator, adm2, pengurus, timker, singers
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('level')->default(0); // Higher = more privileged
            $table->timestamps();
        });

        // Seed default roles
        DB::table('roles')->insert([
            ['name' => 'administrator',  'display_name' => 'Administrator',   'description' => 'Full unrestricted access to all system features.',                           'level' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'adm2',           'display_name' => 'Administrator 2', 'description' => 'Can add/remove users but cannot manage administrators.',                     'level' => 80,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'pengurus',       'display_name' => 'Pengurus',        'description' => 'Management/directors role. Access to all menus except user management.',    'level' => 60,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'timker',         'display_name' => 'Tim Kerja',       'description' => 'Work team role with defined menu access.',                                   'level' => 40,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'singers',        'display_name' => 'Singers',         'description' => 'Singer members role.',                                                       'level' => 20,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
