<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $table = 'time_slots';

    public $timestamps = false;

    protected $fillable = ['start_time', 'end_time'];
}
