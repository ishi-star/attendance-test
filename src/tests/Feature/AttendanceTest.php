<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}