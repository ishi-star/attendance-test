<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminLoginRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
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
                
                 // ★ 修正: 休憩開始時刻がない場合はスキップ（Blade側で非表示の場合などに備える）
            if (empty($breakData['start_time'])) {
                continue;
            }
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
        // 5. ★ 必須: 勤務時間と休憩時間を再計算して更新 ★
        $this->updateWorkAndBreakTimes($attendance);

        // 6. 修正後のリダイレクト（勤怠一覧画面に戻る）
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

    //個別スタッフの月次勤怠一覧を表示

    public function showUserAttendances($id, $month = null)
    {
        // 1. ユーザー情報の取得 (画面タイトル等で使用)
        $user = User::findOrFail($id);

        // 2. 表示する年月を設定
        $targetMonth = $month ? Carbon::parse($month) : Carbon::now();

        // 月の開始日と終了日を取得
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        // 3. 勤怠データの取得
        // 指定されたスタッフの、指定された月の勤怠データを全て取得
        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('clock_in', [$startDate, $endDate])
            ->with('breaks') // 休憩時間をまとめてロード
            ->orderBy('clock_in', 'asc')
            ->get();

        return view('admin.admin-attendance-staff', [
            'user' => $user,
            'targetMonth' => $targetMonth,
            'attendances' => $attendances,
        ]);
    }

    /**
     * 勤怠修正の申請一覧を表示する
     */
    public function showRequests()
    {
        // 1. 承認待ちの申請を取得し、グループ化
        $pendingRequests = StampCorrectionRequest::where('status', 'pending')
            ->with('user', 'attendance')
            ->orderBy('created_at', 'asc')
            ->get();

        $groupedPendingRequests = $this->groupRequests($pendingRequests); // ヘルパーメソッドでグループ化

        // 2. 承認済みの申請を取得し、グループ化
        $approvedRequests = StampCorrectionRequest::where('status', 'approved')
            ->with('user', 'attendance')
            ->orderBy('updated_at', 'desc') // 承認日時が新しい順
            ->get();

        $groupedApprovedRequests = $this->groupRequests($approvedRequests); // ヘルパーメソッドでグループ化

        return view('admin.stamp-correction-request-list', [
            'groupedPendingRequests' => $groupedPendingRequests, // 承認待ち
            'groupedApprovedRequests' => $groupedApprovedRequests, // 承認済み
        ]);
    }

    /**
     * 申請レコードのコレクションを attendance_id でグループ化し、整形するヘルパーメソッド
     */
    protected function groupRequests($requestCollection)
    {
        $groupedRequests = [];
        $groupedCollection = $requestCollection->groupBy('attendance_id');

        foreach ($groupedCollection as $attendanceId => $requests) {
            $firstRequest = $requests->first();

            // 勤怠情報がないレコードはスキップ (データ不備対策)
            if (!$firstRequest->attendance) continue;

            $groupedRequests[] = [
                'attendance_id' => $attendanceId,
                'user' => $firstRequest->user,
                'date' => $firstRequest->attendance->clock_in->format('Y/m/d'),
                'types' => $requests->pluck('type')->unique()->implode(', '),
                'requests' => $requests,
                'reason' => $firstRequest->reason,
                'status' => $firstRequest->status, // pending または approved
            ];
        }
        return $groupedRequests;
    }

    /**
     * 個別の勤怠修正申請の詳細を表示し、承認を行う画面
     * @param int $id 申請ID (StampCorrectionRequest ID)
     */
    public function showRequestDetail($id)
    {
        // 申請レコードを取得
        $requestDetail = StampCorrectionRequest::with('user', 'attendance.breaks')
                                                ->findOrFail($id);

        // 同じattendance_idの全ての申請をまとめて取得
        $allRequests = StampCorrectionRequest::where('attendance_id', $requestDetail->attendance_id)
                                            ->where('status', 'pending') // 承認待ちのみ
                                            ->get();

        // 申請内容を種類ごとに整理
        $requests = [
            'clock_in' => $allRequests->firstWhere('type', 'clock_in'),
            'clock_out' => $allRequests->firstWhere('type', 'clock_out'),
            'break_updates' => $allRequests->where('type', 'break_update'),
            'break_adds' => $allRequests->where('type', 'break_add'),
        ];

        return view('admin.stamp-correction-request-approve', [
            'requestDetail' => $requestDetail,
            'requests' => $requests,
            'attendance' => $requestDetail->attendance,
        ]);
    }
    /**
     * 勤怠修正申請の承認・却下を処理する
     */
    public function handleRequest(Request $request, $id)
    {
        // 申請レコードを取得
        $correctionRequest = StampCorrectionRequest::findOrFail($id);

        // 申請が既に処理されていないかチェック
        if ($correctionRequest->status !== 'pending') {
             return back()->with('error', 'この申請は既に処理済みです。');
        }

        // 関連する元の勤怠記録を取得
        $attendance = $correctionRequest->attendance;

        if ($request->action === 'approve') {

            // 承認: 申請内容を勤怠本体（attendancesテーブル）に反映
            if ($correctionRequest->type === 'clock_in') {
                $attendance->clock_in = $correctionRequest->requested_time;
            } elseif ($correctionRequest->type === 'clock_out') {
                $attendance->clock_out = $correctionRequest->requested_time;
            } elseif ($correctionRequest->type === 'break_update' || $correctionRequest->type === 'break_add') {

            // requested_data (JSON) から修正後の時刻を取得
            if ($correctionRequest->requested_data) {
                $data = json_decode($correctionRequest->requested_data, true);

                // JSON内の日付時刻文字列をCarbonオブジェクトに変換
                $startTime = Carbon::parse($data['start']);
                $endTime = Carbon::parse($data['end']); // end が空でないことを前提とする

                if ($correctionRequest->type === 'break_update') {
                    // 既存の休憩（BreakModel）を更新
                    $breakModel = BreakModel::find($correctionRequest->original_break_id);
                    if ($breakModel) {
                        $breakModel->update([
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ]);
                    }
                } elseif ($correctionRequest->type === 'break_add') {
                    // 新規休憩を追加
                    BreakModel::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ]);
                }
            }
        }

            // 勤怠本体を保存
            $attendance->save();

            // ★ 必須: 勤務時間と休憩時間を再計算して更新 ★
            $this->updateWorkAndBreakTimes($attendance);

            // 申請ステータスを承認済(approved)にする
            $correctionRequest->status = 'approved';
            $correctionRequest->save();

            // 勤務時間と休憩時間を再計算 (AttendanceControllerのメソッドを流用)
            // ※ AttendanceController::updateWorkAndBreakTimes メソッドを呼び出すか、
            //   AdminControllerに同様のロジックを実装する必要があります。
            //   ここでは処理の簡略化のため一旦省略しますが、本番では必須です。

            $message = '勤怠修正申請を承認しました。';

        } elseif ($request->action === 'reject') {
            // 却下: 申請ステータスを却下(rejected)にする
            $correctionRequest->status = 'rejected';
            $correctionRequest->save();
            $message = '勤怠修正申請を却下しました。';
        } else {
            return back()->with('error', '不正な操作です。');
        }

        return redirect()->route('admin.requests')->with('success', $message);
    }

        /**
     * 指定された勤怠記録について、休憩時間と勤務時間を再計算し、DBに保存する
     *
     * @param \App\Models\Attendance $attendance
     * @return void
     */
    protected function updateWorkAndBreakTimes(\App\Models\Attendance $attendance)
    {
        // 1. 最新の休憩記録をリロード
        // 休憩が修正・追加されている可能性があるため、リレーションをリロードします
        $attendance->load('breaks'); 
        
        // 2. 総休憩時間 (分) を計算
        $totalBreakMinutes = $attendance->breaks->sum(function ($break) {
            // 終了時間がない休憩は計算から除外
            if ($break->end_time && $break->start_time) {
                return $break->end_time->diffInMinutes($break->start_time);
            }
            return 0;
        });

        // 3. 総勤務時間 (分) を計算
        $totalWorkMinutes = 0;
        if ($attendance->clock_out && $attendance->clock_in) {
            // 出勤から退勤までの総時間
            $totalDuration = $attendance->clock_out->diffInMinutes($attendance->clock_in);
            
            // 総時間から総休憩時間を引く
            $totalWorkMinutes = $totalDuration - $totalBreakMinutes;

            // 勤務時間が負にならないよう、最小値は0とする
            if ($totalWorkMinutes < 0) {
                $totalWorkMinutes = 0;
            }
        }

        // 4. Attendanceレコードを更新
        $attendance->total_break_time = $totalBreakMinutes; // 休憩時間を更新
        $attendance->work_time = $totalWorkMinutes; // 勤務時間を更新
        $attendance->save();
    }
}