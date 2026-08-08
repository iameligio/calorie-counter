<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Limits enforced in AppServiceProvider and App\Filament\Auth\Login. Seeding
     * them here makes them tunable from the admin Settings panel instead of
     * only via the hardcoded fallbacks.
     */
    private const NEW_SETTINGS = [
        [
            'key' => 'rate_limit_api',
            'value' => '60',
            'description' => 'Baseline requests per minute allowed on any API route, per user or IP.',
        ],
        [
            'key' => 'rate_limit_reads',
            'value' => '40',
            'description' => 'Requests per minute for read endpoints (dashboard, progress, food search, log history).',
        ],
        [
            'key' => 'rate_limit_login_ip',
            'value' => '30',
            'description' => 'Login attempts per minute from one IP across all accounts. Blocks password spraying.',
        ],
        [
            'key' => 'rate_limit_admin_login',
            'value' => '5',
            'description' => 'Failed admin panel login attempts per minute from one IP before lockout.',
        ],
    ];

    /**
     * The original four limits were seeded before the description column
     * existed, so they show up blank in the admin panel.
     */
    private const BACKFILLED_DESCRIPTIONS = [
        'rate_limit_register' => 'New account registrations per minute from one IP.',
        'rate_limit_login' => 'Login attempts per minute for one email address from one IP.',
        'rate_limit_foods' => 'Custom foods a user may create per minute.',
        'rate_limit_logs' => 'Food log entries a user may create per minute.',
    ];

    public function up(): void
    {
        foreach (self::NEW_SETTINGS as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        foreach (self::BACKFILLED_DESCRIPTIONS as $key => $description) {
            DB::table('settings')
                ->where('key', $key)
                ->whereNull('description')
                ->update(['description' => $description, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', array_column(self::NEW_SETTINGS, 'key'))
            ->delete();

        DB::table('settings')
            ->whereIn('key', array_keys(self::BACKFILLED_DESCRIPTIONS))
            ->update(['description' => null]);
    }
};
