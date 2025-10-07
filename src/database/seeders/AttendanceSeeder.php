<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakModel;
use App\Models\User;
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
        // 1. 一般ユーザー（user@example.com）を特定し、IDを取得する
        $generalUser = User::where('email', 'user@example.com')->first();

        if (!$generalUser) {
            // もしUserSeederが先に実行されていない場合、ここで処理を停止または警告
            echo "Warning: General User (user@example.com) not found. Skipping Attendance Seeding.\n";
            return;
        }

        $userId = $generalUser->id; // ユーザーの動的なIDを取得

        // 2025年9月1日〜30日までのデータ作成
        for ($day = 1; $day <= 30; $day++) {
            $date = Carbon::create(2025, 9, $day);

            // 土日は休みにする（もし土日も出勤なら削除）
            if ($date->isWeekend()) {
                continue;
            }

            $clockIn  = $date->copy()->setTime(9, 0);
            $clockOut = $date->copy()->setTime(18, 0);

            // 総勤務時間と総休憩時間を分単位で計算
            $totalBreakMinutes = 60; // 休憩時間を60分とする
            $totalWorkMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakMinutes;

            $attendance = Attendance::create([
                'user_id' => $userId,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'total_break_time' => $totalBreakMinutes, // 新しいカラム名
                'work_time' => $totalWorkMinutes, // 新しいカラム名
                'remarks' => null, // 備考欄のダミーデータ
                'status' => 'approved', // デフォルトは承認済みとする
            ]);

            // 休憩記録を作成
            BreakModel::create([
                'attendance_id' => $attendance->id,
                'start_time' => $date->copy()->setTime(12, 0),
                'end_time' => $date->copy()->setTime(13, 0),
            ]);
        }
    }
}
