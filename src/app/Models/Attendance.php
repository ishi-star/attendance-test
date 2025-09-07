<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'break_time',
        'work_time'
    ];

        // 出勤時刻を「H:i」形式で返す
    public function getClockInFormattedAttribute()
    {
        return $this->clock_in ? $this->clock_in->format('H:i') : '';
    }

    // 退勤時刻を「H:i」形式で返す
    public function getClockOutFormattedAttribute()
    {
        return $this->clock_out ? $this->clock_out->format('H:i') : '';
    }
    // 勤務時間を「H:i」形式で返す
    public function getWorkTimeFormattedAttribute()
    {
        $hours = floor($this->work_time / 60);
        $minutes = $this->work_time % 60;
        return sprintf("%d:%02d", $hours, $minutes);
    }

        // 休憩時間を「H:i」形式で返す
    public function getBreakTimeFormattedAttribute()
    {
        $hours = floor($this->break_time / 60);
        $minutes = $this->break_time % 60;
        return sprintf("%d:%02d", $hours, $minutes);
    }

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_time' => 'integer',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakModel::class);
    }

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

        // 休憩時間合計を breaks テーブルから算出
    public function getTotalBreakMinutesAttribute()
    {
        return $this->breaks->sum(fn($break) => $break->duration_minutes);
    }
}
