<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function clockIn()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', now())
            ->first();

        if ($attendance) {
            return redirect()->back()->with('message', '今日の出勤はすでに記録済みです。');
        }

        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => now(),
        ]);

        return redirect()->back()->with('message', '出勤しました');
    }

    public function clockOut()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', now())
            ->first();

        if (!$attendance || $attendance->clock_out) {
            return redirect()->back()->with('message', '退勤できません');
        }

        $attendance->update([
            'clock_out' => now()
        ]);

        return redirect()->back()->with('message', '退勤しました');
    }

    public function breakStart()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', now())
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('message', '出勤していません');
        }

        $attendance->update([
            'break_time' => now()
        ]);

        return redirect()->back()->with('message', '休憩開始');
    }

    public function breakEnd()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', now())
            ->first();

        if (!$attendance || !$attendance->break_time) {
            return redirect()->back()->with('message', '休憩中ではありません');
        }

        $breakDuration = now()->diffInMinutes($attendance->break_time);

        $attendance->update([
            'break_time' => null,
            'work_time' => ($attendance->work_time ?? 0) + $breakDuration
        ]);

        return redirect()->back()->with('message', '休憩終了');
    }

    public function attendanceList()
    {
        $user = Auth::user();
        $attendances = Attendance::where('user_id', $user->id)
            ->orderBy('clock_in', 'desc')
            ->get();

        return view('auth.list-attendance', compact('attendances'));
    }
}
