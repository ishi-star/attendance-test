<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // 既存のデータを削除（念のため）
        User::truncate();
        
        // 管理者ユーザーを作成
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true, // 管理者として設定
        ]);

        // 一般ユーザーを作成
        User::create([
            'name' => 'General User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false, // 一般ユーザーとして設定
        ]);
    }
}