<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
        public function clockIn(Request $request)
    {
        $user = Auth::user();

        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => now(),
        ]);

    // 勤怠一覧画面にリダイレクト
        return redirect('/attendance/list')->with('message', '出勤しました');
    }

        public function attendanceList()
    {
        $user = Auth::user();
        $attendances = Attendance::where('user_id', $user->id)->get();

        return view('auth.list-attendance', compact('attendances'));
    }
}
