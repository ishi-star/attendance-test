<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\StampCorrectionRequest;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日付情報がUIと同じ形式で出力されていることをテストする。
     *
     * @return void
     */
    public function test_current_date_is_displayed_on_stamping_screen()
    {
        // 開発環境とテスト環境で時刻がズレないよう、時間を固定
        // 2025年10月28日(火) 午前9時00分 に時間を固定します。
        Carbon::setTestNow(Carbon::create(2025, 10, 28, 9, 0, 0, 'Asia/Tokyo'));
        
        // 1. 一般ユーザーを作成し、ログイン
        $user = User::factory()->create();
        $this->actingAs($user);

        // 2. 勤怠打刻画面（/attendance）を開く
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 3. 画面に表示されている日時情報を確認する
        // 日付部分
        $expectedDateOnly = Carbon::now()->isoFormat('YYYY年MM月DD日');
        // 曜日部分
        $expectedDayOfWeek = Carbon::now()->isoFormat('ddd'); // 例: 火
        $response->assertSee($expectedDateOnly, false);
        // 現在の日時と一致する
        $response->assertSee('(' . $expectedDayOfWeek . ')', false);
        // テスト時間を解除
        Carbon::setTestNow();
    }



    public function test_status_is_勤務外_when_no_stamps()
    {
        // 1. ステータスが勤務外のユーザーにログイン (DBに今日の出勤記録を作成しない)
        $user = User::factory()->create();
        $this->actingAs($user);

        // 2. 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 3. 画面に表示されているステータスが「勤務外」となることを確認
        $response->assertSee('勤務外', false);
        // HTMLの出力: <p class="attendance-status">勤務外</p> を期待
    }

    public function test_status_is_出勤中_when_clocked_in()
    {
        // 1. ステータスが出勤中のユーザーにログイン (出勤記録のみを作成)
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // 💡 データベースに出勤記録を作成 (clock_in: 今日, clock_out: null)
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->startOfDay()->addHours(9), // 今日の午前9時
            'clock_out' => null,
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 3. 画面に表示されているステータスが「出勤中」となることを確認
        $response->assertSee('出勤中', false);
    }

    public function test_status_is_休憩中_when_breaking()
    {
        // 1. ステータスが休憩中のユーザーにログイン (出勤記録と休憩開始記録を作成)
        $user = User::factory()->create();
        $this->actingAs($user);

        // 💡 データベースに出勤記録を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->startOfDay()->addHours(9),
            'clock_out' => null,
        ]);

        // 💡 休憩開始記録を作成 (end_time: null)
        BreakModel::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::now()->startOfDay()->addHours(12),
            'end_time' => null,
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 3. 画面に表示されているステータスが「休憩中」となることを確認
        $response->assertSee('休憩中', false);
    }

    public function test_status_is_退勤済_when_clocked_out()
    {
        // 1. ステータスが退勤済のユーザーにログイン (出勤・退勤記録を作成)
        $user = User::factory()->create();
        $this->actingAs($user);

        // 💡 データベースに出勤・退勤記録を作成
        Attendance::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now()->startOfDay()->addHours(9),
            'clock_out' => Carbon::now()->startOfDay()->addHours(18), // 退勤済み
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 3. 画面に表示されているステータスが「退勤済」となることを確認
        $response->assertSee('退勤済', false);
    }

    public function test_buttons_for_勤務外_status()
    {
        // 勤務外ユーザーでログイン (今日の打刻記録なし)
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        // 期待値: 出勤ボタンのみが表示されていること
        $response->assertSee('<form action="/attendance/clock-in"', false); // 出勤ボタンのフォーム
        $response->assertDontSee('<form action="/attendance/clock-out"', false); // 退勤ボタンは非表示
        $response->assertDontSee('<form action="/attendance/break-start"', false); // 休憩入ボタンは非表示
        $response->assertDontSee('<form action="/attendance/break-end"', false); // 休憩戻ボタンは非表示
    }

    /** @test */
    public function test_buttons_for_出勤中_status()
    {
        // 出勤中ユーザーでログイン (出勤記録のみ)
        $user = User::factory()->create();
        $this->actingAs($user);
        Attendance::create(['user_id' => $user->id, 'clock_in' => now(), 'clock_out' => null]);

        $response = $this->get('/attendance');

        // 期待値: 退勤ボタンと休憩入ボタンが表示されていること
        $response->assertDontSee('<form action="/attendance/clock-in"', false); // 出勤ボタンは非表示
        $response->assertSee('<form action="/attendance/clock-out"', false); // 退勤ボタン
        $response->assertSee('<form action="/attendance/break-start"', false); // 休憩入ボタン
        $response->assertDontSee('<form action="/attendance/break-end"', false); // 休憩戻ボタンは非表示
    }

    /** @test */
    public function test_buttons_for_休憩中_status()
    {
        // 休憩中ユーザーでログイン (出勤記録と休憩開始記録あり)
        $user = User::factory()->create();
        $this->actingAs($user);
        $attendance = Attendance::create(['user_id' => $user->id, 'clock_in' => now(), 'clock_out' => null]);
        BreakModel::create(['attendance_id' => $attendance->id, 'start_time' => now(), 'end_time' => null]);

        $response = $this->get('/attendance');

        // 期待値: 休憩戻ボタンのみが表示されていること
        $response->assertDontSee('<form action="/attendance/clock-in"', false); // 出勤ボタンは非表示
        $response->assertDontSee('<form action="/attendance/clock-out"', false); // 退勤ボタンは非表示
        $response->assertDontSee('<form action="/attendance/break-start"', false); // 休憩入ボタンは非表示
        $response->assertSee('<form action="/attendance/break-end"', false); // 休憩戻ボタン
    }

    /** @test */
    public function test_buttons_for_退勤済_status()
    {
        // 退勤済ユーザーでログイン (出勤・退勤記録あり)
        $user = User::factory()->create();
        $this->actingAs($user);
        Attendance::create(['user_id' => $user->id, 'clock_in' => now()->subHours(8), 'clock_out' => now()]);

        $response = $this->get('/attendance');

        // 期待値: すべての打刻ボタンが表示されていないこと
        $response->assertDontSee('<form action="/attendance/clock-in"', false);
        $response->assertDontSee('<form action="/attendance/clock-out"', false);
        $response->assertDontSee('<form action="/attendance/break-start"', false);
        $response->assertDontSee('<form action="/attendance/break-end"', false);
    }

    /** @test */
    public function test_clock_in_functionality()
    {
        // 1. テスト時間を固定
        Carbon::setTestNow(Carbon::create(2025, 11, 1, 9, 0, 0));

        // 2. 勤務外ユーザーでログイン
        $user = User::factory()->create();
        $this->actingAs($user);

        // 3. 出勤処理を行う (POST /attendance/clock-in)
        $response = $this->post('/attendance/clock-in');
        
        // 4. 打刻画面にリダイレクトされることを確認
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance'); // リダイレクト後の画面を取得

        // 5. 画面上のステータスが「出勤中」になったことを確認
        $response->assertSee('出勤中', false);

        // 6. データベースに出勤時刻が正しく記録されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in' => '2025-11-01 09:00:00', // 固定した時刻
            'clock_out' => null,
        ]);

        Carbon::setTestNow();
    }

    public function test_clock_out_functionality()
    {
        // 1. テスト時間を固定 (出勤時と退勤時)
        Carbon::setTestNow(Carbon::create(2025, 11, 1, 9, 0, 0)); // 出勤時
        $user = User::factory()->create();
        $this->actingAs($user);
        $attendance = Attendance::create(['user_id' => $user->id, 'clock_in' => Carbon::now(), 'clock_out' => null]);

        Carbon::setTestNow(Carbon::create(2025, 11, 1, 18, 0, 0)); // 退勤時 (9時間後)

        // 2. 退勤処理を行う (POST /attendance/clock-out)
        $response = $this->post('/attendance/clock-out');
        
        // 3. 打刻画面にリダイレクトされることを確認
        $response->assertRedirect('/attendance');
        $response = $this->get('/attendance'); // リダイレクト後の画面を取得

        // 4. 画面上のステータスが「退勤済」になったことを確認
        $response->assertSee('退勤済', false);

        // 5. データベースに退勤時刻が正しく記録されていることと、勤務時間が計算されていることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_out' => '2025-11-01 18:00:00', // 固定した時刻
            'work_time' => 540, // 9時間 = 540分 (休憩がないため)
        ]);

        Carbon::setTestNow();
    }

    public function test_break_in_and_out_functionality()
    {
        // 1. 出勤中状態の準備
        $user = User::factory()->create();
        $this->actingAs($user);
        $attendance = Attendance::create(['user_id' => $user->id, 'clock_in' => now(), 'clock_out' => null]);
        $attendanceId = $attendance->id;

        // --- 休憩入 (12:00) ---
        Carbon::setTestNow(Carbon::create(2025, 11, 1, 12, 0, 0));
        $this->post('/attendance/break-start');
        $response = $this->get('/attendance');
        $response->assertSee('休憩中', false); // ステータス確認

        // データベースに休憩開始時刻が記録されていることを確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendanceId,
            'start_time' => '2025-11-01 12:00:00',
            'end_time' => null,
        ]);

        // --- 休憩戻 (12:30) ---
        Carbon::setTestNow(Carbon::create(2025, 11, 1, 12, 30, 0)); // 30分後
        $this->post('/attendance/break-end');
        $response = $this->get('/attendance');
        $response->assertSee('出勤中', false); // ステータス確認

        // データベースに休憩終了時刻が記録されていることを確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendanceId,
            'start_time' => '2025-11-01 12:00:00',
            'end_time' => '2025-11-01 12:30:00',
        ]);

        Carbon::setTestNow();
    }

/** @test */
    public function test_attendance_list_displays_correct_data()
    {
        // 1. テスト時間を固定して出勤・退勤記録を作成
        Carbon::setTestNow(Carbon::create(2025, 11, 1, 9, 0, 0)); // 1日目 出勤
        $user1 = User::factory()->create(['name' => 'テスト太郎']);
        $this->actingAs($user1);
        
        // 休憩なしで8時間勤務 (8時間 = 480分)
        Attendance::create([
            'user_id' => $user1->id,
            'clock_in' => Carbon::create(2025, 11, 1, 9, 0, 0),
            'clock_out' => Carbon::create(2025, 11, 1, 17, 0, 0), // 8時間拘束 (休憩なしの想定)
            'work_time' => 480,
        ]);

        // 勤務時間を9:00〜18:00で休憩1時間（合計480分）に変更
        Attendance::where('user_id', $user1->id)->update([
            'clock_in' => Carbon::create(2025, 11, 1, 9, 0, 0),
            'clock_out' => Carbon::create(2025, 11, 1, 18, 0, 0), // 9時間拘束
            'work_time' => 480, // 休憩1時間(60分)を引いた実働8時間
        ]);


        // 2. 勤怠一覧画面を開く
        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        // 3. 画面に勤怠データが正しく表示されていることを確認
        // Blade出力の形式 '11/01(土)' に合わせる
        $response->assertSee('11/01(土)', false);

        
        // Blade出力の形式 '09:00' に合わせる
        $response->assertSee('09:00', false);
        
        // Blade出力の形式 '18:00' に合わせる
        $response->assertSee('18:00', false);
        
        // Blade出力の形式 '8:00' に合わせる
        $response->assertSee('8:00', false);
    
        Carbon::setTestNow();
    }

    public function test_attendance_list_navigation_and_details()
    {
        // 1. テストデータを準備
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $this->actingAs($user);
        
        // 11月1日の勤怠レコードが存在すると仮定（ID=10は前回のHTML出力から想定）
        $attendanceNov = Attendance::create([
            'id' => 10,
            'user_id' => $user->id,
            'clock_in' => Carbon::create(2025, 11, 1, 9, 0, 0),
            'clock_out' => Carbon::create(2025, 11, 1, 18, 0, 0),
            'work_time' => 480,
        ]);

        // 10月と12月のデータを作成（ナビゲーションテスト用）
        Attendance::create([
            'id' => 1,
            'user_id' => $user->id,
            'clock_in' => Carbon::create(2025, 10, 15, 9, 0, 0),
            'clock_out' => Carbon::create(2025, 10, 15, 18, 0, 0),
            'work_time' => 480,
        ]);
        
        Attendance::create([
            'id' => 100,
            'user_id' => $user->id,
            'clock_in' => Carbon::create(2025, 12, 15, 9, 0, 0),
            'clock_out' => Carbon::create(2025, 12, 15, 18, 0, 0),
            'work_time' => 480,
        ]);
        
        // 現在の日付を11月に固定
        Carbon::setTestNow(Carbon::create(2025, 11, 1));

        // --- 【要件2: 現在の月が表示される】 & 【要件1: 勤怠情報が全て表示されている】の再確認
        $responseNov = $this->get('/attendance/list');
        $responseNov->assertStatus(200);
        $responseNov->assertSee('2025年11月', false);
        $responseNov->assertSee('11/01(土)', false);
        $responseNov->assertDontSee('10/15', false); // 10月は表示されない

        // --- 【要件3: 前月への遷移】
        $responseOct = $this->get('/attendance/list/2025/10');
        $responseOct->assertStatus(200);
        $responseOct->assertSee('2025年10月', false);
        $responseOct->assertSee('10/15', false);
        $responseOct->assertDontSee('11/01', false);

        // --- 【要件4: 翌月への遷移】
        $responseDec = $this->get('/attendance/list/2025/12');
        $responseDec->assertStatus(200);
        $responseDec->assertSee('2025年12月', false);
        $responseDec->assertSee('12/15', false);
        $responseDec->assertDontSee('11/01', false);

        // --- 【要件5: 詳細画面への遷移】
        // 11/01の勤怠ID（10）で詳細ページにアクセス
        $responseDetail = $this->get('/attendance/detail/' . $attendanceNov->id);
        $responseDetail->assertStatus(200);
        $responseDetail->assertSee('勤怠詳細', false); // 詳細画面のタイトル
        $responseDetail->assertSee('11月01日', false);// 日付
        $responseDetail->assertSee('09:00', false); // 出勤時間
        
        Carbon::setTestNow();
    }

    // public function test_attendance_correction_application_workflow()
    // {
    //     // 1. ユーザーと勤怠データの準備
    //     $user = User::factory()->create(['name' => 'テストユーザー申請']);
    //     // 管理者ユーザーを作成 (ID: 2と仮定)
    //     $adminUser = User::factory()->create(['name' => '管理者', 'is_admin' => true]);
        
    //     // 修正対象となる勤怠レコード
    //     $attendanceToCorrect = Attendance::create([
    //         'id' => 20, // 仮のID
    //         'user_id' => $user->id,
    //         'clock_in' => Carbon::create(2025, 11, 5, 9, 0, 0),
    //         'clock_out' => Carbon::create(2025, 11, 5, 18, 0, 0),
    //         'work_time' => 480,
    //     ]);

    //     $this->actingAs($user); // 一般ユーザーでログイン
    //     $detailUrl = '/attendance/detail/' . $attendanceToCorrect->id;
    //     $requestUrl = '/attendance/request/' . $attendanceToCorrect->id; // 修正申請のPOSTルート

    //     // --- 1-1. 【バリデーション：出勤 > 退勤】のテスト
    //     $response = $this->post($requestUrl, [
    //         'clock_in' => '19:00', // 不正な値
    //         'clock_out' => '18:00',
    //         'remarks' => 'テスト備考',
    //     ]);
    //     $response->assertSessionHasErrors(['clock_out']);
        
    //     // --- 1-2. 【バリデーション：休憩開始 > 退勤】のテスト (休憩データなしの場合、休憩の開始・終了バリデーションは複雑なため、ここでは時刻の基本チェックのみに絞る)
    //     $response = $this->post($requestUrl, [
    //         'clock_in' => '09:00',
    //         'clock_out' => '18:00',
    //         'new_break' => [
    //             'start_time' => '19:00', // 不正な値
    //             'end_time' => '20:00',
    //         ],
    //         'remarks' => 'テスト備考',
    //     ]);
    //     // 休憩時間が不適切な値、または退勤時間が不適切な値というメッセージを検証
    //     $response->assertSessionHasErrors(['new_break.start_time']);

    //     // --- 1-3. 【バリデーション：備考欄が未入力】のテスト
    //     $response = $this->post($requestUrl, [
    //         'clock_in' => '09:00',
    //         'clock_out' => '18:00',
    //         'remarks' => '', // 不正な値
    //     ]);
    //     $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
        
    //     // --- 2. 【修正申請処理が実行される】のテスト
    //     $newClockIn = '09:30';
    //     $newClockOut = '18:30';
    //     $remarkText = '修正テスト申請';
        
    //     $response = $this->post($requestUrl, [
    //         'clock_in' => $newClockIn,
    //         'clock_out' => $newClockOut,
    //         'remarks' => $remarkText,
    //     ]);

    //     $response->assertRedirect('/attendance/list'); // 勤怠一覧にリダイレクト
        
    //     // 修正申請レコードが作成されたことを確認
    //     $this->assertDatabaseHas('stamp_correction_requests', [
    //         'user_id' => $user->id,
    //         'attendance_id' => $attendanceToCorrect->id,
    //         'clock_in' => $newClockIn, // 修正後の出勤時間
    //         'clock_out' => $newClockOut, // 修正後の退勤時間
    //         'status' => '承認待ち',
    //         'remarks' => $remarkText,
    //     ]);
        
    //     // --- 3-1. 【申請一覧 (承認待ち) に自分の申請が表示】のテスト
    //     $response = $this->get('/stamp_correction_request/list');
    //     $response->assertStatus(200);
    //     $response->assertSee('承認待ち', false);
    //     $response->assertSee('修正テスト申請', false);
        
    //     // --- 3-2. 【承認済み】のテスト
    //     // 申請を管理者が承認する (データベースを直接操作)
    //     $pendingRequest = StampCorrectionRequest::where('user_id', $user->id)->first();
    //     $pendingRequest->update(['status' => '承認済み', 'admin_id' => $adminUser->id]);

    //     // ユーザーの申請一覧を再度確認
    //     $response = $this->get('/stamp_correction_request/list');
    //     $response->assertStatus(200);
    //     $response->assertSee('承認済み', false); // 承認済みのタブが表示されるか
        
    //     // --- 3-3. 【各申請の「詳細」を押下すると勤怠詳細画面に遷移】のテスト
    //     $response = $this->get('/stamp_correction_request/detail/' . $pendingRequest->id);
    //     $response->assertStatus(200);
    //     $response->assertSee('修正申請詳細', false); // 申請詳細画面のタイトル
    //     $response->assertSee('テストユーザー申請', false); // ユーザー名
    //     $response->assertSee($newClockIn, false); // 修正後の出勤時間
    // }

}