<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakModel extends Model
{

    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
        // 'duration'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

        // アクセサを使って休憩時間を計算
    public function getDurationMinutesAttribute()
    {
        // 休憩開始と終了の両方の時間がある場合にのみ計算
        if ($this->end_time && $this->start_time) {
            return $this->end_time->diffInMinutes($this->start_time);
        }
        return 0;
    }
}
