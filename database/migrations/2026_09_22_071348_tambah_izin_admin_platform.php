<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Izin granular untuk admin platform (MARKETING.md 26). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('AdminPlatform', function (Blueprint $table): void {
            $table->json('Izin')->nullable()->after('Status');
            $table->boolean('SuperAdmin')->default(false)->after('Izin');
        });
    }

    public function down(): void
    {
        Schema::table('AdminPlatform', function (Blueprint $table): void {
            $table->dropColumn(['Izin', 'SuperAdmin']);
        });
    }
};
