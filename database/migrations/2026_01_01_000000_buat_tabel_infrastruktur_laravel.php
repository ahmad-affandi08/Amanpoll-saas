<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('TokenResetKataSandi')) {
            // Keyed oleh PenggunaId (bukan email) karena email hanya unik per
            // organisasi, bukan global -- lihat App\Domain\Platform\Application\Actions\MintaResetKataSandi.
            Schema::create('TokenResetKataSandi', function (Blueprint $table): void {
                $table->char('PenggunaId', 26)->primary();
                $table->string('TokenHash', 255);
                $table->timestamp('DibuatPada')->useCurrent();
            });
        }

        if (!Schema::hasTable('AntrianPekerjaan')) {
            Schema::create('AntrianPekerjaan', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('KelompokAntrianPekerjaan')) {
            Schema::create('KelompokAntrianPekerjaan', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (!Schema::hasTable('PekerjaanGagal')) {
            Schema::create('PekerjaanGagal', function (Blueprint $table): void {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('PekerjaanGagal');
        Schema::dropIfExists('KelompokAntrianPekerjaan');
        Schema::dropIfExists('AntrianPekerjaan');
        Schema::dropIfExists('TokenResetKataSandi');
    }
};
