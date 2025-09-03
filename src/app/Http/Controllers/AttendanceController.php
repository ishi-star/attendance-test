<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
        public function clockIn(Request $request)
    {
        $user = Auth::user();

        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => now(),
        ]);

        return redirect()->back()->with('message', '出勤しました');
    }
}
