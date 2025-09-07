<?php
// 勤怠打刻管理
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                        ->constrained()
                            ->onDelete('cascade');
            // ->onDelete('cascade')もし親となるusersテーブルのユーザーが削除されたら、紐づいているattendancesテーブルの勤怠記録も一緒に削除する

            // 出勤
            $table->dateTime('clock_in')->nullable();
            // 退勤
            $table->dateTime('clock_out')->nullable();

             // 合計休憩時間（分）
            $table->integer('break_time')->nullable();

            // 勤務時間（分）
            $table->integer('work_time')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
