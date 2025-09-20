<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'total_break_time', // total_break_time に修正
        'work_time',        // work_time に修正
        'remarks',          // remarks を追加
        'status',           // status を追加
    ];

    protected $dates = [
        'clock_in',
        'clock_out',
    ];

    // userとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // breaksとのリレーション
    public function breaks(): HasMany
    {
        return $this->hasMany(BreakModel::class);
    }

    // 総勤務時間（work_time）を計算して「H:i」形式で返す
    public function getFormattedWorkTimeAttribute(): string
    {
        // $this->work_time は分単位で保存されていると仮定
        if ($this->work_time === null) {
            return '--:--';
        }
        $hours = floor($this->work_time / 60);
        $minutes = $this->work_time % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    // 総休憩時間（total_break_time）を計算して「H:i」形式で返す
    public function getFormattedBreakTimeAttribute(): string
    {
        // $this->total_break_time は分単位で保存されていると仮定
        if ($this->total_break_time === null) {
            return '--:--';
        }
        $hours = floor($this->total_break_time / 60);
        $minutes = $this->total_break_time % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

}