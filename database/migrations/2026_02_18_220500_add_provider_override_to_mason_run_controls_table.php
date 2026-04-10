<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mason_run_controls', function (Blueprint $table): void {
            $table->string('provider_override', 100)->nullable()->after('heartbeat_payload');
        });
    }

    public function down(): void
    {
        Schema::table('mason_run_controls', function (Blueprint $table): void {
            $table->dropColumn('provider_override');
        });
    }
};
