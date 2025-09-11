<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Brake;
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
                                ->whereDate('clock_in', Carbon::today())
                                ->first();

        // 記録が存在し、かつ退勤時間がまだ記録されていなければ
        if ($attendance && !$attendance->clock_out) {
            // 今日の全休憩記録を取得
            $allBreaks = $attendance->breaks()->get();
            $totalBreakTime = 0;

            foreach ($allBreaks as $break) {
                // 休憩終了時間がある場合にのみ、計算して合計する
                if ($break->end_time) {
                    $totalBreakTime += $break->end_time->diffInMinutes($break->start_time);
                }
            }

            // 勤務時間の計算
            $clockIn = new Carbon($attendance->clock_in);
            $clockOut = Carbon::now();
            $workTime = $clockIn->diffInMinutes($clockOut);

            // 総休憩時間を勤務時間から引く
            $totalWorkTime = $workTime - $totalBreakTime;

            $attendance->update([
                'clock_out' => $clockOut,
                'work_time' => $totalWorkTime,// 勤務時間を更新
            ]);
            return redirect()->back()->with('message', '退勤しました');
        }

        return redirect()->back()->with('message', '退勤しました');
    }

    // 休憩開始を記録する
    public function breakStart(Request $request) 
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('clock_in', Carbon::today())
                                ->first();

        if ($attendance && !$attendance->clock_out) {
            $latestBreak = Brake::where('attendance_id', $attendance->id)
                                ->whereNull('end_time')
                                ->first();

            if (!$latestBreak) {
                Brake::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => Carbon::now(),
                ]);
                return redirect()->back()->with('message', '休憩を開始しました');
            }
        }

        return redirect()->back()->with('message', '休憩を開始できません');
    }

    // 休憩終了を記録する
    public function breakEnd(Request $request)
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('clock_in', Carbon::today())
                                ->first();

        if ($attendance && !$attendance->clock_out) {
            $latestBreak = Brake::where('attendance_id', $attendance->id)
                                ->whereNull('end_time')
                                ->first();

            if ($latestBreak) {
                $latestBreak->update([
                    'end_time' => Carbon::now(),
                ]);
                return redirect()->back()->with('message', '休憩を終了しました');
            }
        }

        return redirect()->back()->with('message', '休憩を終了できません');
    }

     // 勤怠一覧画面を表示する
    public function showAttendanceList()
    {
        // ログイン中のユーザー情報を取得
        $user = Auth::user();

        // ログイン中のユーザーの勤怠記録をすべて取得し、作成日の新しい順に並び替える
        $attendances = Attendance::where('user_id', $user->id)
                                ->orderBy('clock_in', 'desc')
                                ->get();

        return view('auth.list-attendance', compact('attendances'));
    }

    // 勤怠詳細画面を表示する
    public function showAttendanceDetail($id)
    {
        // ログイン中のユーザー情報を取得
        $user = Auth::user();

        // 指定されたIDの勤怠記録を、関連する休憩記録と一緒に取得する
        $attendance = Attendance::where('user_id', $user->id)
                                ->with('breaks', 'user')
                                // brakesとuserテーブルのデータも一緒に取得する
                                ->findOrFail($id); // IDが見つからない場合は404エラーを返す

        return view('auth.detail-attendance', compact('attendance'));
    }
}
