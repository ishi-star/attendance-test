<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class UserSeeder extends Seeder
{
    public function run()
    {
        // 既存のデータを削除（念のため）
        User::query()->delete();

        // 管理者ユーザーを作成
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true, // 管理者として設定
        ]);

        // 一般ユーザーを作成
        $faker = \Faker\Factory::create('ja_JP');
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => $faker->name,
                'email' => "dummy{$i}@example.com",
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]);
        }
    }
}