# 勤怠管理アプリ
## 環境構築
### Dockerビルド
- git clone git@github.com:ishi-star/attendance-test.git
- cd attendance-test
- DockerDesktopアプリを立ち上げる
- docker/php/DockerfileのFROM php:7.4.9-fpmをFROM php:7.4-fpmに書き換える
（私の環境の影響なのか書き換えないとビルドできませんでした）
- docker-compose up -d --build
### Laravel環境構築
- docker-compose exec php bash
- composer install
- 「.env.example」ファイルを 「.env」ファイルに命名を変更。または新しく.envファイルを作成
- 「.env」に以下の環境変数を追加
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
- アプリケーションキーの作成
php artisan key:generate

- マイグレーションの実行
php artisan migrate

- シーディングの実行
php artisan db:seed

- シンボリックリンク作成
php artisan storage:link

## 実行環境
- PHP8.3.6
- Laravel8.83.8
- MySQL8.0.43
- nginx1.21.1

## URL
- 開発環境：http://localhost/
- phpMyAdmin:：http://localhost:8080/
- ユーザー登録：http://localhost/register
- 管理者ログイン画面：http://localhost/admin/login