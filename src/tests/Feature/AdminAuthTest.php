<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User; // Userモデルを使う
// 管理者ユーザーを区別するフィールドが User モデルにある前提で進めます

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
    

}