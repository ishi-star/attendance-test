<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use Carbon\Carbon;


class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userId = 1; // ダミーデータを紐づけたいユーザーID

        // 2025年6月1日〜30日までのデータ作成
        for ($day = 1; $day <= 30; $day++) {
            $date = Carbon::create(2025, 6, $day);

            // 土日は休みにする（もし土日も出勤なら削除）
            if ($date->isWeekend()) {
                continue;
            }

            $clockIn  = $date->copy()->setTime(9, 0);
            $clockOut = $date->copy()->setTime(18, 0);

            Attendance::create([
                'user_id'   => $userId,
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'break_time'=> '60',
                'work_time' => '480',
                // とりあえずtableに合わせて分数で管理
            ]);
        }
    }
}
