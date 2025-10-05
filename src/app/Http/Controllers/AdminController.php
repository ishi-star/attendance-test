<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminLoginRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\BreakModel;
use App\Models\User;


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

        /**
     * 管理者向けの特定の勤怠記録の詳細表示（修正画面）
     * * @param int $id AttendanceモデルのID
     */
    public function showAttendanceDetail($id)
    {
        // 勤怠記録を休憩記録とユーザー情報と共に取得
        $attendance = Attendance::with(['breaks', 'user'])->findOrFail($id);
        
        // Carbonオブジェクトが必要な場合は、ここで取得（Eloquentの設定で自動取得されるはずですが、念のため確認）
        // $attendance->clock_in, $attendance->clock_out, $attendance->breaks[i]->start_time などが
        // Carbonオブジェクトであることを前提とします。

        // ここで計算は不要なため、そのままビューに渡します
        return view('admin.admin-detail-attendance', compact('attendance'));
    }

        /**
     * 管理者による勤怠データの修正処理
     * * @param Request $request
     * @param int $id AttendanceモデルのID
     */
    public function correctAttendance(Request $request, $id)
    {
        // 1. バリデーション（ユーザー修正時と同じルールを適用）
        $request->validate([
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'breaks' => 'nullable|array',
            'breaks.*.start_time' => 'required_with:breaks.*.end_time|date_format:H:i',
            'breaks.*.end_time' => 'nullable|date_format:H:i|after:breaks.*.start_time',
            'new_break.start_time' => 'nullable|date_format:H:i',
            'new_break.end_time' => 'nullable|date_format:H:i|after:new_break.start_time',
            'remarks' => 'nullable|string|max:500', // 備考欄のバリデーションを追加
        ]);

        $attendance = Attendance::findOrFail($id);
        $date = $attendance->clock_in->toDateString(); // 勤怠の日付を取得

        // 2. 出退勤時間の修正
        $attendance->clock_in = Carbon::parse($date . ' ' . $request->clock_in);
        // clock_outが空でなければ設定
        $attendance->clock_out = $request->clock_out 
            ? Carbon::parse($date . ' ' . $request->clock_out) 
            : null;
        // 備考欄の値をデータベースに保存
        $attendance->remarks = $request->remarks;
        $attendance->save();

        // 3. 既存の休憩時間の修正
        if ($request->has('breaks')) {
            foreach ($request->breaks as $breakId => $breakData) {
                $break = BreakModel::findOrFail($breakId);
                
                // 休憩開始・終了時間を修正
                $break->start_time = Carbon::parse($date . ' ' . $breakData['start_time']);
                $break->end_time = $breakData['end_time'] 
                    ? Carbon::parse($date . ' ' . $breakData['end_time']) 
                    : null;
                $break->save();
            }
        }

        // 4. 新規休憩の追加
        if (!empty($request->new_break['start_time']) && !empty($request->new_break['end_time'])) {
            $newBreak = new BreakModel();
            $newBreak->attendance_id = $id;
            $newBreak->start_time = Carbon::parse($date . ' ' . $request->new_break['start_time']);
            $newBreak->end_time = Carbon::parse($date . ' ' . $request->new_break['end_time']);
            $newBreak->save();
        }

        // 5. 修正後のリダイレクト（勤怠一覧画面に戻る）
        return redirect()->route('admin.attendances', ['date' => $date])
                         ->with('success', '勤怠データを修正しました。');
    }

    // 管理者向けのスタッフ一覧画面を表示
    public function showUsers()
    {
        // ユーザー情報を取得
        // 開発環境によっては管理ユーザーを除外するなどのフィルタリングが必要になる場合があります。
        // 今回はシンプルに全ユーザーを取得します。
        $users = User::orderBy('name')->get(); 

        return view('admin.admin-staff-list', compact('users'));
    }
}