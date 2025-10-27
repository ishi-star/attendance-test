<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User; // 正常系テストでユーザーを確認するために利用します

class AuthTest extends TestCase
{
    // ★テストごとにDBをリセットし、マイグレーションを再実行します
    use RefreshDatabase;

    /**
     *  ID:1 「名前が未入力の場合、バリデーションメッセージが表示される」のテスト。
     * @return void
     */
    public function test_name_is_required_for_registration()
    {
        // 1. テスト用の送信データを用意
        $formData = [
            'name' => '', // ★未入力（空欄）にする
            'email' => 'test@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ];

        // 2. 会員登録のPOSTリクエストを実行（パスは/register）
        $response = $this->post('/register', $formData);

        // 3. 期待挙動の確認（アサーション）

        // 期待挙動:
        // 「お名前を入力してください」というバリデーションメッセージが表示される。
        
        // ①リダイレクトしているかを確認（バリデーションエラー時は元の画面に戻るため、302リダイレクト）
        $response->assertStatus(302);
        
        // ② 'name' フィールドにエラーメッセージがあるかを確認
        $response->assertSessionHasErrors('name');
        
        // ③ 「お名前を入力してください」という指定された文言のエラーメッセージがあるかを確認
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
        
        // ④ ユーザーがデータベースに登録されていないことを確認
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }
    
    /**
     * メールアドレスが未入力の場合のバリデーションテスト (ID:2)
     */
    public function test_email_is_required_for_registration()
    {
        $formData = [
            'name' => 'テスト太郎',
            'email' => '', // ★未入力
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ];

        $response = $this->post('/register', $formData);

        // 期待挙動の確認
        $response->assertStatus(302); // リダイレクト（元の画面に戻る）
        $response->assertSessionHasErrors('email'); // 'email'フィールドにエラーがあるか
        
        // 機能要件FN003で指定されたメッセージ文言を確認
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
        
        // DBにユーザーが登録されていないことを確認
        $this->assertDatabaseCount('users', 0); // usersテーブルのレコード数が0であることを確認
    }
    /**
     * パスワードが8文字未満の場合のバリデーションテスト (ID:3)
     */
    public function test_password_is_less_than_eight_characters()
    {
        $shortPassword = 'pass123'; // ★8文字未満

        $formData = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => $shortPassword,
            'password_confirmation' => $shortPassword,
        ];

        $response = $this->post('/register', $formData);

        // 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        
        // 機能要件FN003で指定されたメッセージ文言を確認
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
        
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 確認用パスワードと一致しない場合のバリデーションテスト (ID:4)
     */
    public function test_password_and_confirmation_do_not_match()
    {
        $formData = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234X', // ★わざと不一致にする
        ];

        $response = $this->post('/register', $formData);

        // 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('password'); // パスワード不一致のエラーは'password'フィールドに出ます
        
        // 機能要件FN003で指定されたメッセージ文言を確認
        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
        
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * パスワードが未入力の場合のバリデーションテスト (ID:5)
     */
    public function test_password_is_required_for_registration()
    {
        $formData = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '', // ★未入力
            'password_confirmation' => '', // 確認用も未入力
        ];

        $response = $this->post('/register', $formData);

        // 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        
        // 機能要件FN003で指定されたメッセージ文言を確認
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
        
        $this->assertDatabaseCount('users', 0);
    }

    /**
     * 正常にフォームが入力された場合のユーザー登録テスト (ID:6)
     */
    public function test_a_new_user_can_be_registered_successfully()
    {
        $formData = [
            'name' => '登録成功ユーザー',
            'email' => 'success@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ];

        $response = $this->post('/register', $formData);

        // 1. 期待挙動: 登録後、打刻画面（/attendance）にリダイレクトしているか（FN005）
        $response->assertRedirect('/attendance');
        
        // 2. 期待挙動: データベースにデータが保存されているかを確認
        $this->assertDatabaseHas('users', [
            'name' => '登録成功ユーザー',
            'email' => 'success@example.com',
            // パスワードはハッシュ化されて保存されるため、平文ではチェックできません。
        ]);
        
        // 3. 期待挙動: ユーザーがログイン状態になっているかを確認
        // assertAuthenticated()は、Laravelのセッションにユーザー情報が格納され、
        // ログイン状態になっていることを確認するアサーションです。
        $this->assertAuthenticated();
    }

    /**
     * ⭐ログイン時：メールアドレスが未入力の場合のバリデーションテスト (FN009-1)
     */
    public function test_email_is_required_for_login()
    {
        // 1. 前準備：テスト用ユーザーを作成（ログインテストでは、ユーザーの有無は関係ないが、習慣として作成）
        \App\Models\User::factory()->create();

        // 2. ログインPOSTリクエストを実行
        $response = $this->post('/login', [
            'email' => '', // ★未入力
            'password' => 'password',
        ]);

        // 3. 期待挙動の確認（アサーション）
        
        // エラーでリダイレクトされるか
        $response->assertStatus(302);
        
        // 'email'フィールドにエラーがあるか
        $response->assertSessionHasErrors('email');
        
        // 機能要件FN009のメッセージ文言を確認（会員登録と同じメッセージと想定）
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください。']);
        
        // ログイン状態になっていないことを確認
        $this->assertGuest();
    }

    /**
     * ログイン時：パスワードが未入力の場合のバリデーションテスト (FN009-1)
     */
    public function test_password_is_required_for_login()
    {
        \App\Models\User::factory()->create();

        // ログインPOSTリクエストを実行
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '', // ★未入力
        ]);

        // 期待挙動の確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        
        // 機能要件FN009のメッセージ文言を確認（会員登録と同じメッセージと想定）
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください。']);
        
        $this->assertGuest();
    }

    /**
     * ログイン時：誤った認証情報の場合のテスト (FN009-2)
     */
    public function test_login_with_incorrect_credentials()
    {
        // 1. 前準備：正しい認証情報を持つユーザーを登録しておく
        $user = \App\Models\User::factory()->create([
            'email' => 'correct@example.com',
            'password' => bcrypt('password1234'), // ハッシュ化して保存
        ]);

        // 2. 誤った情報（メールアドレスを間違える）でログインPOSTリクエストを実行
        $response = $this->post('/login', [
            'email' => 'wrong@example.com', // ★登録されていないメールアドレス
            'password' => 'password1234',
        ]);

        // 3. 期待挙動の確認（アサーション）
        
        // エラーでリダイレクトされるか
        $response->assertStatus(302);
        
        // Laravelのデフォルトでは、認証エラーは 'email' フィールドに設定される
        $response->assertSessionHasErrors('email');
        
        // 機能要件FN009で指定されたメッセージ文言を確認
        // 認証失敗の場合、エラーメッセージは通常セッションの特定のキーに格納されます。
        // fortifyのデフォルトの認証失敗メッセージをLaravel側でカスタムメッセージに合わせる必要があります。
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
        
        // ログイン状態になっていないことを確認
        $this->assertGuest();
    }
}