<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pitches')) {
            Schema::create('pitches', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->unsignedTinyInteger('type');
                $table->decimal('price_per_hour', 12, 0)->default(0);
                $table->string('address')->nullable();
                $table->time('opening_time')->default('06:00:00');
                $table->time('closing_time')->default('23:00:00');
                $table->string('hotline', 30)->nullable();
                $table->string('pitch_size', 100)->nullable();
                $table->unsignedInteger('field_count')->default(1);
                $table->boolean('is_open')->default(true);
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamp('created_at')->useCurrent();
            });
        } else {
            $pitchColumns = [
                'address' => fn (Blueprint $table) => $table->string('address')->nullable(),
                'opening_time' => fn (Blueprint $table) => $table->time('opening_time')->default('06:00:00'),
                'closing_time' => fn (Blueprint $table) => $table->time('closing_time')->default('23:00:00'),
                'hotline' => fn (Blueprint $table) => $table->string('hotline', 30)->nullable(),
                'pitch_size' => fn (Blueprint $table) => $table->string('pitch_size', 100)->nullable(),
                'field_count' => fn (Blueprint $table) => $table->unsignedInteger('field_count')->default(1),
                'is_open' => fn (Blueprint $table) => $table->boolean('is_open')->default(true),
            ];

            foreach ($pitchColumns as $column => $definition) {
                if (!Schema::hasColumn('pitches', $column)) {
                    Schema::table('pitches', $definition);
                }
            }
        }

        if (!Schema::hasTable('time_slots')) {
            Schema::create('time_slots', function (Blueprint $table) {
                $table->id();
                $table->time('start_time');
                $table->time('end_time');
            });

            $slots = [];
            foreach (range(6, 22) as $hour) {
                $slots[] = [
                    'start_time' => sprintf('%02d:00:00', $hour),
                    'end_time' => sprintf('%02d:00:00', $hour + 1),
                ];
            }
            DB::table('time_slots')->insert($slots);
        }

        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pitch_id')->constrained('pitches')->cascadeOnDelete();
                $table->string('customer_name', 100);
                $table->string('customer_phone', 20);
                $table->string('customer_email', 100)->nullable();
                $table->date('booking_date');
                $table->foreignId('time_slot_id')->constrained('time_slots')->restrictOnDelete();
                $table->unsignedInteger('child_pitch_no')->default(1);
                $table->unsignedInteger('duration_hours')->default(1);
                $table->decimal('total_price', 12, 0)->default(0);
                $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('booking_date', 'idx_booking_date');
                $table->index('status', 'idx_booking_status');
                $table->index('pitch_id', 'idx_booking_pitch');
                $table->index(['pitch_id', 'child_pitch_no', 'booking_date', 'status'], 'idx_booking_court_date');
            });
        } else {
            if (!Schema::hasColumn('bookings', 'child_pitch_no')) {
                Schema::table('bookings', fn (Blueprint $table) => $table->unsignedInteger('child_pitch_no')->default(1));
            }
            if (!Schema::hasColumn('bookings', 'duration_hours')) {
                Schema::table('bookings', fn (Blueprint $table) => $table->unsignedInteger('duration_hours')->default(1));
            }
        }
    }

    public function down(): void
    {
        // Domain data is shared with the legacy application and is intentionally preserved.
    }
};
