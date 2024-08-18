<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function scopeToday($query) {
        return $query->where('date', date('Y-m-d'));
    }

    public function scopeSelf($query) {
        return $query->where('user_id', auth()->user()->id);
    }

    public function scopeMyAttendanceForToday($query) {
        return $query->where('user_id', auth()->user()->id)->where('date', date('Y-m-d'));
    }

    public function scopeCheckIn($query) {
        return $query->where('type', 1);
    }

    public function scopeCheckOut($query) {
        return $query->where('type', 2);
    }

    public function scopeBreakIn($query) {
        return $query->where('break_type', 1);
    }

    public function scopeBreakOut($query) {
        return $query->where('break_type', 2);
    }
}
