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
        Schema::table('settings', function (Blueprint $table) {
            $table->text('description')->nullable()->after('value');
        });

        // Set default descriptions for generated keys
        $descriptions = [
            'rate_limit_register' => 'Max number of registrations allowed per IP per minute (default 5).',
            'rate_limit_login' => 'Max number of login attempts allowed per IP per minute (default 10).',
            'rate_limit_foods' => 'Max number of custom foods created per minute (default 20).',
            'rate_limit_logs' => 'Max number of food logs generated per minute (default 30).',
            'admin_email' => 'The contact email displayed to banned users (default support@example.com).',
        ];

        foreach ($descriptions as $key => $desc) {
            \Illuminate\Support\Facades\DB::table('settings')
                ->where('key', $key)
                ->update(['description' => $desc]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
