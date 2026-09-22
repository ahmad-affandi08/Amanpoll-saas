<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kolom TokenIngat dibutuhkan Laravel Auth (remember me). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Pengguna', function (Blueprint $table): void {
            $table->string('TokenIngat', 100)->nullable()->after('KataSandi');
        });
    }

    public function down(): void
    {
        Schema::table('Pengguna', function (Blueprint $table): void {
            $table->dropColumn('TokenIngat');
        });
    }
};
