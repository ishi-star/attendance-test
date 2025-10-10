<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\Localization\CarbonLocale;
use Carbon\CarbonPeriod;
use App\Models\StampCorrectionRequest;

class AttendanceController extends Controller
{
        // 勤怠打刻画面を表示する
    public function showUserAttendance()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
                                    ->whereDate('clock_in', now())
                                    ->first();

        // 休憩開始打刻はしたが、休憩終了打刻をしていないかチェック
        $isBreaking = false;
        if ($attendance) {
            $latestBreak = BreakModel::where('attendance_id', $attendance->id)
                                    ->whereNull('end_time')
                                    ->first();
            if ($latestBreak) {
                $isBreaking = true;
            }
        }

         // 今日の曜日を取得
        $dayOfWeek = Carbon::now()->locale('ja')->shortDayName;

        return view('auth.attendance', compact('attendance', 'isBreaking', 'dayOfWeek'));
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
            return redirect()->back();
        }

        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => now(),
        ]);

        return redirect()->back();
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
            return redirect()->back()->with('message', 'お疲れ様でした');
        }

        return redirect()->back();
    }

    // 休憩開始を記録する
    public function breakStart(Request $request)
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('clock_in', Carbon::today())
                                ->first();

        if ($attendance && !$attendance->clock_out) {
            $latestBreak = BreakModel::where('attendance_id', $attendance->id)
                                ->whereNull('end_time')
                                ->first();

            if (!$latestBreak) {
                BreakModel::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => Carbon::now(),
                ]);
                return redirect()->back();
            }
        }

        return redirect()->back();
    }

    // 休憩終了を記録する
    public function breakEnd(Request $request)
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('clock_in', Carbon::today())
                                ->first();

        if ($attendance && !$attendance->clock_out) {
            $latestBreak = BreakModel::where('attendance_id', $attendance->id)
                                ->whereNull('end_time')
                                ->first();

            if ($latestBreak) {
                $latestBreak->update([
                    'end_time' => Carbon::now(),
                ]);
                return redirect()->back();
            }
        }

        return redirect()->back();
    }

     // 勤怠一覧画面を表示する
    public function showAttendanceList(Request $request, $year = null, $month = null)
    {

        // URLパラメータが直接渡されない場合、クエリパラメータから取得
        if (is_null($year)) {
            $year = $request->input('year', Carbon::now()->year);
        }
        if (is_null($month)) {
            $month = $request->input('month', Carbon::now()->month);
        }

        $currentMonth = Carbon::createFromDate($year, $month, 1);

        // ログイン中のユーザー情報を取得
        $user = Auth::user();

        // ログイン中のユーザーの勤怠記録をすべて取得し、作成日の新しい順に並び替える
        $attendances = Attendance::where('user_id', $user->id)
                                ->whereYear('clock_in', $currentMonth->year)
                                ->whereMonth('clock_in', $currentMonth->month)
                                ->get()
                                ->keyBy(function ($attendance) {
                                    return $attendance->clock_in->format('Y-m-d');
                                });

        // 今月の全日付を生成
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $dates = CarbonPeriod::create($startOfMonth, '1 day', $endOfMonth);

        // 前月と翌月の日付を計算
        $previousMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        return view('auth.list-attendance', compact('dates', 'attendances', 'currentMonth', 'previousMonth', 'nextMonth'));
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

        $pendingRequests = StampCorrectionRequest::where('attendance_id', $id)
            ->where('status', 'pending')
            ->get();

        return view('auth.detail-attendance', compact('attendance', 'pendingRequests'));
    }

    /**
 * 勤怠修正を申請テーブルに記録する
 */
    public function requestCorrection(Request $request, $id)
    {
    // ★★★ この dd($request->all()); を削除する ★★★
    // dd($request->all()); 
    // ★★★ 削除後、必ず保存してください ★★★
        // 1. バリデーション
        $request->validate([
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            // ★★★ 新規追加の休憩時間のバリデーションを追加 ★★★
            'new_break.start_time' => 'nullable|date_format:H:i',
            'new_break.end_time' => 'nullable|date_format:H:i|after:new_break.start_time',
            'remarks' => 'required|string|max:500', // 理由が必須であると仮定
        ]);

        $attendance = Attendance::findOrFail($id);

        // ユーザー自身の勤怠記録か確認
        if ($attendance->user_id !== Auth::id()) {
            return back()->with('error', '不正なアクセスです。');
        }

        $date = $attendance->clock_in->toDateString();
        $hasRequested = false;

        // 2. 出勤時刻の申請
        if ($request->filled('clock_in') && $attendance->clock_in->format('H:i') !== $request->clock_in) {
            // 申請中のレコードが既に存在しないかチェック（任意だが推奨）
            if (!StampCorrectionRequest::where('attendance_id', $id)
                ->where('type', 'clock_in')
                ->where('status', 'pending')
                ->exists()) {
                
                StampCorrectionRequest::create([
                    'attendance_id' => $id,
                    'user_id' => $attendance->user_id,
                    'type' => 'clock_in',
                    'requested_time' => Carbon::parse($date . ' ' . $request->clock_in),
                    'reason' => $request->input('remarks'), // フォームから理由を受け取る前提
                    'status' => 'pending',
                ]);
                $hasRequested = true;
            }
        }

        // 3. 退勤時刻の申請
        if ($request->filled('clock_out') && optional($attendance->clock_out)->format('H:i') !== $request->clock_out) {
            // 申請中のレコードが既に存在しないかチェック（任意だが推奨）
            if (!StampCorrectionRequest::where('attendance_id', $id)
                ->where('type', 'clock_out')
                ->where('status', 'pending')
                ->exists()) {
                    
                StampCorrectionRequest::create([
                    'attendance_id' => $id,
                    'user_id' => $attendance->user_id,
                    'type' => 'clock_out',
                    'requested_time' => Carbon::parse($date . ' ' . $request->clock_out),
                    'reason' => $request->input('remarks'), // フォームから理由を受け取る前提
                    'status' => 'pending',
                ]);
                $hasRequested = true;
            }
        }
        
        // 4. ★★★ 既存の休憩時間の修正申請ロジックを追加 ★★★
        if ($request->filled('breaks')) {
            foreach ($request->input('breaks') as $breakId => $breakTimes) {
                $breakModel = BreakModel::find($breakId);

                if ($breakModel) {
                    $originalStart = $breakModel->start_time->format('H:i');
                    $originalEnd = optional($breakModel->end_time)->format('H:i');

                    $requestedStart = $breakTimes['start_time'];
                    $requestedEnd = $breakTimes['end_time'];

                    // 開始時刻または終了時刻に修正がある場合
                    if ($originalStart !== $requestedStart || $originalEnd !== $requestedEnd) {

                        // 申請中のレコードが既に存在しないかチェック（任意）
                        if (!StampCorrectionRequest::where('attendance_id', $id)
                            ->where('type', 'break_update')
                            ->where('original_break_id', $breakId) // どの休憩を修正したか特定するカラムが必要
                            ->where('status', 'pending')
                            ->exists()) {

                            StampCorrectionRequest::create([
                                'attendance_id' => $id,
                                'user_id' => $attendance->user_id,
                                'type' => 'break_update',
                                'original_break_id' => $breakId, // ★ break IDを記録 ★
                                'requested_time' => null, // ★ requested_time はNULLにする ★
                                'requested_data' => json_encode([ // ★ requested_dataにJSONで保存 ★
                                    'start' => $date . ' ' . $requestedStart,
                                    'end' => $date . ' ' . $requestedEnd,
                                ]),
                                'reason' => $request->input('remarks'), // 備考欄の値を理由として共有
                                'status' => 'pending',
                            ]);
                            $hasRequested = true;
                        }
                    }
                }
            }
        }

            // 5. ★★★ 新規追加の休憩時間の申請ロジックを修正 ★★★
            // $request->input('new_break')が配列であり、かつstart_timeとend_timeの両方が入力されているかチェ// 5. 新規追加の休憩時間の申請ロジック (上書き方式)
        if (
            $request->filled('new_break') &&
            is_array($request->input('new_break')) &&
            !empty($request->input('new_break.start_time')) &&
            !empty($request->input('new_break.end_time'))
        ) {
            $requestedStart = $request->input('new_break.start_time');
            $requestedEnd = $request->input('new_break.end_time');

            // 保存するデータの配列を定義
            $dataToSave = [
                'attendance_id' => $id,
                'user_id' => $attendance->user_id,
                'type' => 'break_add',
                'original_break_id' => null,
                'requested_time' => null,
                'requested_data' => json_encode([
                    'start' => $date . ' ' . $requestedStart,
                    'end' => $date . ' ' . $requestedEnd,
                ]),
                'reason' => $request->input('remarks'),
                'status' => 'pending', // ステータスは保留中
            ];

            // 1. 既存の保留中の同じ申請を検索
            $existingRequest = StampCorrectionRequest::where('attendance_id', $id)
                ->where('type', 'break_add')
                ->where('status', 'pending')
                ->first(); // exists() ではなく first() でレコードを取得

            if ($existingRequest) {
                // 2. 既に申請がある場合、そのレコードを最新の内容で更新（上書き）する
                $existingRequest->update($dataToSave);
            } else {
                // 3. 申請がない場合、新規作成する
                StampCorrectionRequest::create($dataToSave);
            }
            
            $hasRequested = true;
        }


        if ($hasRequested) {
            return redirect()->back()->with('success', '勤怠修正の申請を送信しました。');
        }

        return redirect()->back()->with('error', '修正内容に変更がないか、既に申請中です。');
    }

        protected function updateWorkAndBreakTimes(Attendance $attendance)
    {
        // 総休憩時間を計算
        $totalBreakMinutes = $attendance->breaks()->whereNotNull('end_time')->get()->sum(function ($break) {
            return $break->start_time->diffInMinutes($break->end_time);
        });

        // 勤務時間を計算
        $totalWorkMinutes = 0;
        if ($attendance->clock_in && $attendance->clock_out) {
            $totalWorkMinutes = $attendance->clock_out->diffInMinutes($attendance->clock_in);
            $totalWorkMinutes -= $totalBreakMinutes;
        }

        // モデルの値を更新
        $attendance->total_break_time = $totalBreakMinutes;
        $attendance->work_time = $totalWorkMinutes;
        $attendance->save();
    }
}
