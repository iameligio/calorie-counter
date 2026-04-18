<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('settings')->insert([
            ['key' => 'rate_limit_register', 'value' => '5', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'rate_limit_login', 'value' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'rate_limit_foods', 'value' => '20', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'rate_limit_logs', 'value' => '30', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
