<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
    $this->call([
        UserSeeder::class,     // 先にユーザー作成
        AttendanceSeeder::class,     // その後で勤怠作成
        StampCorrectionRequestsSeeder::class,
    ]);
    }
}
