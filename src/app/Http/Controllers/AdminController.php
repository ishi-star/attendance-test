<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminLoginRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\BreakModel;


class AdminController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.admin-login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // 認証されたユーザーを取得
            $user = Auth::user();

            // is_adminカラムがtrueかチェック
            if ($user->is_admin) {
                // 管理者であれば、管理者用のトップページにリダイレクト
                return redirect()->intended(route('admin.attendances'));
            }

            // 管理者でなければ、ログイン失敗として扱う
            Auth::logout(); // ログイン状態をリセット
            return back()->withErrors([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        // 認証に失敗したら、ログイン画面に戻る
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function showAttendances(Request $request)
    {
        // 1. Requestから 'date' パラメータを取得。なければ Carbon::today() を使う
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        // 2. 取得した日付を Carbon オブジェクトに変換
        $date = Carbon::parse($date);

        // 指定日の勤怠データを取得し、関連するユーザー情報も一緒にロード
        $attendances = Attendance::whereDate('clock_in', $date)
            ->with('user')
            ->get();

        foreach ($attendances as $attendance) {
            // 勤怠記録に紐づくすべての休憩時間の合計を計算
            // ユーザー側のロジックに基づき、休憩時間（分）を直接データベースから取得
            $totalBreakMinutes = $attendance->breaks->sum(function ($break) {
                // 終了時間がない休憩は計算から除外
                if ($break->end_time) {
                    return $break->end_time->diffInMinutes($break->start_time);
                }
                return 0;
            });
            $attendance->total_break_time = $totalBreakMinutes;

            // 勤務時間を計算
            $totalWorkMinutes = 0;
            if ($attendance->clock_out) {
                $totalWorkMinutes = $attendance->clock_out->diffInMinutes($attendance->clock_in);
                $totalWorkMinutes -= $totalBreakMinutes;
            }
            $attendance->work_time = $totalWorkMinutes;
        }

        return view('admin.admin-list-attendance', compact('attendances', 'date'));
    }
}