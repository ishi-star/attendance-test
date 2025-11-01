<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Database\Factories\AttendanceFactory;
// 管理者ユーザーを区別するフィールドが User モデルにある

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    // 前準備として、管理者ユーザーを簡単に作成するヘルパーメソッドを用意します
    protected function createAdminUser($email = 'admin@test.com', $password = 'password1234')
    {
        // ★重要: User モデルに管理者であるかを区別するカラム（例: is_admin, role_idなど）
        // があることを前提とし、管理者として作成します。
        // ここでは仮に 'role' カラム（1=一般, 10=管理者）を想定します。
        return User::factory()->create([
            'email' => $email,
            'password' => bcrypt($password),
            'is_admin' => true, // 管理者ロール（あなたのDB設計に合わせてください）
        ]);
    }

    // --- 1. メールアドレス未入力のテスト ---

    /**
     * 管理者ログイン時：メールアドレスが未入力の場合のバリデーションテスト
     */
    public function test_admin_email_is_required_for_login()
    {
        // ログインPOSTリクエストを実行（パスは /admin/login）
        $response = $this->post('/admin/login', [
            'email' => '', // ★未入力
            'password' => 'password',
        ]);

        // 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        // メッセージは一般ユーザーと同じと想定（句読点なしで進めます）
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']); 
        
        $this->assertGuest('admin'); // 管理者ガードで認証されていないことを確認
    }

    // --- 2. パスワード未入力のテスト ---

    /**
     * 管理者ログイン時：パスワードが未入力の場合のバリデーションテスト
     */
    public function test_admin_password_is_required_for_login()
    {
        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => '', // ★未入力
        ]);

        // 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
        
        $this->assertGuest('admin');
    }

    // --- 3. 誤った認証情報の場合のテスト ---

    /**
     * 管理者ログイン時：誤った認証情報の場合のテスト
     */
    public function test_admin_login_with_incorrect_credentials()
    {
        // 1. 前準備：正しい管理者ユーザーを登録
        $this->createAdminUser('correct_admin@test.com');

        // 2. 誤った情報（メールアドレスを間違える）でログインPOSTリクエストを実行
        $response = $this->post('/admin/login', [
            'email' => 'wrong_admin@test.com', // ★登録されていないメールアドレス
            'password' => 'password1234',
        ]);

        // 3. 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        // 機能要件FN009と同様のメッセージを想定
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        
        $this->assertGuest('admin');
    }

    public function test_admin_attendance_list_displays_and_navigates_daily()
    {
        // 1. テストデータを準備 (日付を2025-11-01に固定)
        $today = Carbon::create(2025, 11, 1);
        Carbon::setTestNow($today); 

        $yesterday = $today->copy()->subDay(); // 2025-10-31
        $tomorrow = $today->copy()->addDay(); // 2025-11-02
        
        // Adminユーザーを作成し、ログイン
        $adminUser = User::factory()->create(['name' => '管理者', 'is_admin' => true]); 
        $this->actingAs($adminUser);
        
        // 一般ユーザーと勤怠レコードを作成
        $userB = User::factory()->create(['name' => '田中 次郎']);
        $userC = User::factory()->create(['name' => '佐藤 花子']);

        // --- 勤怠レコード (UserB, UserC) ---
        // UserB: 10/31 (昨日)
        Attendance::create([
            'user_id' => $userB->id,
            'clock_in' => $yesterday->copy()->setTime(9, 0, 0),
            'clock_out' => $yesterday->copy()->setTime(18, 0, 0),
            'work_time' => 480,
        ]);
        // UserB: 11/01 (今日)
        Attendance::create([
            'user_id' => $userB->id,
            'clock_in' => $today->copy()->setTime(9, 30, 0),
            'clock_out' => $today->copy()->setTime(17, 30, 0),
            'work_time' => 420,
        ]);
        // UserC: 11/01 (今日)
        Attendance::create([
            'user_id' => $userC->id,
            'clock_in' => $today->copy()->setTime(10, 0, 0),
            'clock_out' => $today->copy()->setTime(19, 0, 0),
            'work_time' => 480,
        ]);
        // UserC: 11/02 (明日)
        Attendance::create([
            'user_id' => $userC->id,
            'clock_in' => $tomorrow->copy()->setTime(8, 0, 0),
            'clock_out' => $tomorrow->copy()->setTime(17, 0, 0),
            'work_time' => 480,
        ]);

        $adminListRoute = '/admin/attendances';

        // --- 1. 【要件: その日の全ユーザーの勤怠情報が正確に確認できる & 現在の日付が表示される】 ---
        // ルートを /admin/users に修正
        $responseToday = $this->get($adminListRoute); 
        $responseToday->assertStatus(200);

        // 要件2: 現在の日付が表示されていることを確認
        $responseToday->assertSee($today->format('Y/m/d'), false);
        $responseToday->assertSee($today->format('Y年n月j日'), false);
        
        // 要件1: 全ユーザーのデータが表示されていることを確認
        // UserB (今日)
        $responseToday->assertSee('田中 次郎', false);
        $responseToday->assertSee('09:30', false);
        $responseToday->assertSee('17:30', false); 
        // UserC (今日)
        $responseToday->assertSee('佐藤 花子', false);
        $responseToday->assertSee('10:00', false);
        $responseToday->assertSee('19:00', false);
        
        
        // --- 2. 【要件: 「前日」を押下した時に前の日の勤怠情報が表示される】 ---
        // ルートは /attendance/users/{date} と想定
        $responseYesterday = $this->get("{$adminListRoute}?date={$yesterday->format('Y-m-d')}");
        $responseYesterday->assertStatus(200);

        // 日付を確認
        $responseYesterday->assertSee($yesterday->format('Y/m/d'), false);

        // 昨日のデータが表示されていることを確認
        $responseYesterday->assertSee('田中 次郎', false);
        $responseYesterday->assertSee('09:00', false);
        $responseYesterday->assertSee('18:00', false); 
        
        // --- 3. 【要件: 「翌日」を押下した時に次の日の勤怠情報が表示される】 ---
        $responseTomorrow = $this->get("{$adminListRoute}?date={$tomorrow->format('Y-m-d')}");
        $responseTomorrow->assertStatus(200);

        // 日付を確認
        $responseTomorrow->assertSee($tomorrow->format('Y/m/d'), false); 

        // 明日のデータが表示されていることを確認
        $responseTomorrow->assertSee('佐藤 花子', false);
        $responseTomorrow->assertSee('08:00', false);
        $responseTomorrow->assertSee('17:00', false); 
        
        Carbon::setTestNow();
    }

public function test_admin_attendance_detail_displays_correct_data()
    {
        // 1. テストデータを準備 (日付を2025-11-01に固定)
        $today = Carbon::create(2025, 11, 1);
        Carbon::setTestNow($today); 

        // Adminユーザーを作成し、ログイン
        $adminUser = $this->createAdminUser('admin@test.com');
        $this->actingAs($adminUser);
        
        // 検証対象の一般ユーザーと勤怠レコードを作成
        $userA = User::factory()->create(['name' => 'テスト太郎']);
        
        // 検証対象の勤怠レコード
        $attendance = Attendance::create([
            'user_id' => $userA->id,
            'clock_in' => $today->copy()->setTime(9, 30, 0),
            'clock_out' => $today->copy()->setTime(17, 30, 0),
            'total_break_time' => 60, // 休憩時間
            'work_time' => 420,       // 勤務時間
        ]);
        
        // 2. 勤怠詳細ページを開く
        $response = $this->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        // 3. 期待挙動の確認
        $response->assertStatus(200);
        $response->assertSee('テスト太郎', false);
        $response->assertSee('09:30', false); // 出勤時刻
        $response->assertSee('17:30', false); // 退勤時刻

    
        Carbon::setTestNow();
    }

    /*
     * 管理者：出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_attendance_correction_fail_when_clock_in_is_after_clock_out()
    {
        // 1. 前準備: 勤怠レコードを作成し、Adminとしてログイン
        $adminUser = $this->createAdminUser('admin@test.com');
        $this->actingAs($adminUser);

        $userA = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $userA->id,
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);
        // 2. POSTリクエストを実行 (出勤時間を退勤時間より後に設定)
        $response = $this->post(route('admin.attendance.correct', ['id' => $attendance->id]), [
            // 不適切なデータ: 出勤 18:00, 退勤 09:00
            'clock_in' => '18:00', 
            'clock_out' => '09:00',
            'breaks' => [
                ['start_time' => '12:00', 'end_time' => '13:00'],
            ],
            'remarks' => '修正備考',
        ]);

        // 3. 期待挙動の確認
        $response->assertStatus(302); // バリデーションエラーでリダイレクト
        $response->assertSessionHasErrors();
        // 期待メッセージ: 「出勤時間もしくは退勤時間が不適切な値です」
        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }


    /**
     * 管理者：休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_admin_attendance_correction_fail_when_break_start_is_after_clock_out()
    {
        // 1. 前準備: 勤怠レコードを作成し、Adminとしてログイン
        $adminUser = $this->createAdminUser('admin@test.com');
        $this->actingAs($adminUser);

        $userA = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $userA->id,
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);
        // 2. POSTリクエストを実行 (休憩開始時間を退勤時間より後に設定)
        $response = $this->post(route('admin.attendance.correct', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                ['start_time' => '19:00', 'end_time' => '20:00'], // 不適切
            ],
            'remarks' => '修正備考',
        ]);

        // 3. 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        // 期待メッセージ: 「休憩時間が不適切な値です」
        $response->assertSessionHasErrors(['breaks.0.start_time' => '休憩時間が不適切な値です']);
    }

    /**
     *
     * 管理者：備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_admin_attendance_correction_fail_when_note_is_empty()
    {
        // 1. 前準備: 勤怠レコードを作成し、Adminとしてログイン
        $adminUser = $this->createAdminUser('admin@test.com');
        $this->actingAs($adminUser);

        $userA = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $userA->id,
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);
        // 2. POSTリクエストを実行 (備考欄を空に設定)
        $response = $this->post(route('admin.attendance.correct', ['id' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                ['start_time' => '12:00', 'end_time' => '13:00'],
            ],
            'remarks' => '', // 未入力
        ]);

        // 3. 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        // 期待メッセージ: 「備考を記入してください」
        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }

}