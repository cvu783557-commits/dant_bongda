<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pitch extends Model
{
    protected $table = 'pitches';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'price_per_hour',
        'address',
        'opening_time',
        'closing_time',
        'hotline',
        'pitch_size',
        'field_count',
        'is_open',
        'description',
        'image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'integer',
            'field_count' => 'integer',
            'is_open' => 'boolean',
            'type' => 'integer',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
