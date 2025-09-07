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

             // 修正対象（
            $table->enum('type', ['clock_in','clock_out','break_time']);

            // 希望する修正打刻時間
            $table->dateTime('requested_time');

            // 申請ステータス
            $table->enum('status', ['pending','approved','rejected'])->default('pending');

            // 理由（任意）
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_requests');
    }
};
