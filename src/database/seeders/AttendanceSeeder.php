<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakModel;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        // 一般ユーザー全員を取得（管理者以外）
        $users = User::where('is_admin', false)->get();

        if ($users->isEmpty()) {
            echo "⚠️ Warning: No general users found. Skipping Attendance Seeding.\n";
            return;
        }

        // 各ユーザーごとに勤怠データ作成
        foreach ($users as $user) {
            // 2025年9月1日〜30日までのデータ作成
            for ($day = 1; $day <= 30; $day++) {
                $date = Carbon::create(2025, 9, $day);

                // 土日は休みにする
                if ($date->isWeekend()) {
                    continue;
                }

                $clockIn  = $date->copy()->setTime(9, 0);
                $clockOut = $date->copy()->setTime(18, 0);

                // 勤務時間と休憩時間を計算
                $totalBreakMinutes = 60;
                $totalWorkMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakMinutes;

                // 勤怠データを作成
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'total_break_time' => $totalBreakMinutes,
                    'work_time' => $totalWorkMinutes,
                    'remarks' => null,
                    'status' => 'approved',
                ]);

                // 休憩データを作成
                BreakModel::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $date->copy()->setTime(12, 0),
                    'end_time' => $date->copy()->setTime(13, 0),
                ]);
            }

            // ログ出力（確認用）
            echo "✅ Attendance seeded for: {$user->name}\n";
        }
    }
}
