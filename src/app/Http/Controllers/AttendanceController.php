<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
        // 勤怠打刻画面を表示する
    public function showUserAttendance()
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', now())
            ->first();

        return view('auth.attendance', compact('attendance'));
    }

        // 出勤を記録する
    public function clockIn(Request $request)
    {
        // ログイン中のユーザーIDを取得
        $user = Auth::user();

        // 既に同じ日に出勤記録がないかチェック
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', now())
            ->first();

        // もし出勤記録がなければ、新規レコードを作成
        if ($attendance) {
            return redirect()->back()->with('message', '今日の出勤はすでに記録済みです。');
        }

        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => now(),
        ]);

        return redirect()->back()->with('message', '出勤しました');
    }

    // 退勤を記録する
    public function clockOut(Request $request)
    {
        $user = Auth::user();

        // 今日の出勤記録を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', now())
            ->first();

        // 記録が存在し、かつ退勤時間がまだ記録されていなければ
        if (!$attendance && !$attendance->clock_out) {
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
