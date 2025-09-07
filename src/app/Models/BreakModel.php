<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakModel extends Model
{
    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
        'duration'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

        // duration が null の場合は動的に計算
    public function getDurationMinutesAttribute()
    {
        if ($this->duration !== null) {
            return $this->duration;
        }
        if ($this->end_time && $this->start_time) {
            return $this->end_time->diffInMinutes($this->start_time);
        }
        return 0;
    }
}
