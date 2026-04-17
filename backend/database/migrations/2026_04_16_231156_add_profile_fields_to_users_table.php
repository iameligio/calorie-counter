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
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('calorie_target');
            $table->integer('age')->nullable()->after('gender');
            $table->decimal('height_cm', 5, 2)->nullable()->after('age');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('height_cm');
            $table->string('activity_level')->nullable()->after('weight_kg');
            $table->string('goal')->nullable()->after('activity_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'age', 'height_cm', 'weight_kg', 'activity_level', 'goal']);
        });
    }
};
