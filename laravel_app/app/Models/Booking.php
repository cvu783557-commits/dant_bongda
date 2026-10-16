<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $table = 'bookings';

    public $timestamps = false;

    protected $fillable = [
        'pitch_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'booking_date',
        'time_slot_id',
        'child_pitch_no',
        'duration_hours',
        'total_price',
        'status',
        'notes',
    ];

    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }
}
