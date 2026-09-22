<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Field kampanye selengkapnya, biaya, target, dan konten (MARKETING.md 13, FASE 38.08). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Kampanye', function (Blueprint $table): void {
            $table->decimal('Budget', 14, 2)->nullable()->after('Objective');
            $table->string('Audience', 500)->nullable()->after('Budget');
            $table->string('Offer', 300)->nullable()->after('Audience');
            $table->char('HalamanId', 26)->nullable()->after('Offer');
            $table->char('FormulirId', 26)->nullable()->after('HalamanId');
            // utm_campaign selalu diambil dari Kode, jadi yang perlu disimpan hanya empat sisanya.
            $table->string('UtmSource', 100)->nullable()->after('FormulirId');
            $table->string('UtmMedium', 100)->nullable()->after('UtmSource');
            $table->string('UtmTerm', 100)->nullable()->after('UtmMedium');
            $table->string('UtmContent', 100)->nullable()->after('UtmTerm');

            $table->foreign('HalamanId')->references('Id')->on('HalamanPemasaran')->nullOnDelete();
            $table->foreign('FormulirId')->references('Id')->on('FormulirPemasaran')->nullOnDelete();
        });

        // Biaya per hari, bukan satu angka sebulan, supaya CAC terbaca pada rentang tanggal mana pun.
        Schema::create('KampanyeBiaya', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('KampanyeId', 26);
            $table->string('Channel', 40);
            $table->date('Tanggal');
            $table->decimal('Jumlah', 14, 2);
            $table->string('Catatan', 300)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['KampanyeId', 'Channel', 'Tanggal'], 'UnqKampanyeBiayaHarian');
            $table->index(['Tanggal'], 'IdxKampanyeBiayaTanggal');
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->cascadeOnDelete();
        });

        Schema::create('KampanyeTarget', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('KampanyeId', 26);
            $table->string('Metrik', 30);
            $table->decimal('Nilai', 14, 2);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['KampanyeId', 'Metrik'], 'UnqKampanyeTargetMetrik');
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->cascadeOnDelete();
        });

        Schema::create('KampanyeKonten', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('KampanyeId', 26);
            $table->string('Jenis', 30);
            $table->string('Judul', 190);
            $table->string('Tautan', 500)->nullable();
            $table->string('Catatan', 300)->nullable();
            $table->unsignedSmallInteger('Urutan')->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();

            $table->index(['KampanyeId', 'Urutan'], 'IdxKampanyeKontenUrutan');
            $table->foreign('KampanyeId')->references('Id')->on('Kampanye')->cascadeOnDelete();
        });

        Schema::table('MetrikKampanye', function (Blueprint $table): void {
            // Biaya harian kampanye disalin ke sini agar tabel kampanye di dashboard tidak perlu kueri kedua.
            $table->decimal('Biaya', 14, 2)->default(0)->after('Revenue');
        });
    }

    public function down(): void
    {
        Schema::table('MetrikKampanye', function (Blueprint $table): void {
            $table->dropColumn('Biaya');
        });

        Schema::dropIfExists('KampanyeKonten');
        Schema::dropIfExists('KampanyeTarget');
        Schema::dropIfExists('KampanyeBiaya');

        Schema::table('Kampanye', function (Blueprint $table): void {
            $table->dropForeign(['HalamanId']);
            $table->dropForeign(['FormulirId']);
            $table->dropColumn([
                'Budget', 'Audience', 'Offer', 'HalamanId', 'FormulirId',
                'UtmSource', 'UtmMedium', 'UtmTerm', 'UtmContent',
            ]);
        });
    }
};
