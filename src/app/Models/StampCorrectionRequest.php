<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    protected $fillable = [
        'attendance_id',
        'user_id',
        'type',
        'requested_time',
        'requested_data',
        'status',
        'reason'
    ];

    protected $casts = [
        'requested_time' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}