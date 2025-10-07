<?php
// 打刻修正申請管理
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stamp_correction_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // 修正対象のtypeを拡張: break_update, break_add を追加
            $table->enum('type', ['clock_in', 'clock_out', 'break_update', 'break_add']); // ★ 修正 ★

            // 既存の休憩の修正の場合、どの休憩IDを修正したかを特定 (NULLを許容)
            $table->foreignId('original_break_id')
                  ->nullable()
                  ->constrained('breaks')
                  ->onDelete('cascade'); // ★ 追加 ★

            // 打刻時間修正（出勤/退勤用）。休憩申請には使用しない。
            $table->dateTime('requested_time')->nullable(); // ★ NULL許容に変更 ★

            // 休憩時間の修正データ（JSON形式で開始・終了時刻を保存）
            $table->json('requested_data')->nullable(); // ★ 追加 ★

            // 申請ステータス
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // 理由（備考）
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_requests');
    }
};
