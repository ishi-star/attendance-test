<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// モデルを追加
use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;

class StampCorrectionRequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $user = User::where('email', 'dummy1@example.com')->first();

        $attendance = Attendance::first();

        if ($user && $attendance) {
            StampCorrectionRequest::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'type' => 'clock_in',
                'requested_time' => '2025-09-01 09:15:00',
                'status' => 'pending',
                'reason' => '遅刻修正のテスト',
            ]);
            $attendance->status = 'pending';
            $attendance->save();
        }
    }
}
