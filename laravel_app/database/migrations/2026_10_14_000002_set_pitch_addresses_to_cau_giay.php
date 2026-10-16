<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pitches')->update([
            'address' => 'Ngõ 02, ngách 60, Cầu Giấy, Hà Nội',
        ]);
    }

    public function down(): void
    {
        // The previous addresses varied by pitch and cannot be restored reliably.
    }
};
