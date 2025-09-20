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
    // work_timeというデータベースに保存されているカラムの値をフォーマットするためのもの
    public function getWorkTimeFormattedAttribute()
    {
        $hours = floor($this->work_time / 60);
        $minutes = $this->work_time % 60;
        return sprintf("%d:%02d", $hours, $minutes);
    }

    // 勤務時間を計算して「H:i」形式で返す
public function getTotalWorkTimeAttribute()
{
    // 出勤と退勤が記録されているか確認
    if ($this->clock_in && $this->clock_out) {
        // 総勤務時間（分）を計算
        $totalMinutes = $this->clock_in->diffInMinutes($this->clock_out);

        // 総休憩時間を差し引く
        $totalMinutes -= $this->total_break_time;

        // 分を時間と分に変換
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    return '--:--'; // 退勤していない場合は表示しない
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

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakModel::class);
    }

        //  総休憩時間を計算して「H:i」形式で返す
    public function getTotalBreakTimeAttribute()
    {
    $totalMinutes = $this->breaks->sum(function ($break) {
        // 終了時間が存在する場合にのみ計算
        if ($break->end_time) {
            return $break->start_time->diffInMinutes($break->end_time);
        }
        return 0;
    });

    // 分を時間と分に変換
    $hours = floor($totalMinutes / 60);
    $minutes = $totalMinutes % 60;

    return sprintf('%02d:%02d', $hours, $minutes);
    }
}
