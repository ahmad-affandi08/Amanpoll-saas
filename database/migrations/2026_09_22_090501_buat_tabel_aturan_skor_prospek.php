<?php

declare(strict_types=1);

use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Aturan bobot skor prospek (MARKETING.md 5.4, 24). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AturanSkorProspek', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Peristiwa', 80)->unique('UnqAturanSkorPeristiwa');
            $table->integer('Bobot');
            $table->boolean('Aktif')->default(true);
            $table->string('Keterangan', 500)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
        });

        // Rincian skor menunjuk aturan yang menghasilkannya.
        Schema::table('SkorProspek', function (Blueprint $table): void {
            $table->char('AturanSkorProspekId', 26)->nullable()->after('Peristiwa');
            $table->foreign('AturanSkorProspekId')
                ->references('Id')->on('AturanSkorProspek')->nullOnDelete();
        });

        $this->semai();
    }

    public function down(): void
    {
        Schema::table('SkorProspek', function (Blueprint $table): void {
            $table->dropForeign(['AturanSkorProspekId']);
            $table->dropColumn('AturanSkorProspekId');
        });

        Schema::dropIfExists('AturanSkorProspek');
    }

    private function semai(): void
    {
        $bobot = [...KatalogPeristiwaSkor::bobotBawaan(), ...$this->bobotTersimpan()];
        $sekarang = now();

        foreach ($bobot as $peristiwa => $nilai) {
            // Kunci yang tidak dikenal tidak ikut dipindahkan.
            if (! KatalogPeristiwaSkor::dikenal((string) $peristiwa)) {
                continue;
            }

            DB::table('AturanSkorProspek')->insert([
                'Id' => (string) Str::ulid(),
                'Peristiwa' => $peristiwa,
                'Bobot' => (int) $nilai,
                'Aktif' => true,
                'DibuatPada' => $sekarang,
                'DiperbaruiPada' => $sekarang,
            ]);
        }

        DB::table('KonfigurasiPemasaran')->where('Kunci', 'skor.aturan')->delete();
    }

    /** @return array<string, mixed> */
    private function bobotTersimpan(): array
    {
        $baris = DB::table('KonfigurasiPemasaran')->where('Kunci', 'skor.aturan')->value('Nilai');

        if (! is_string($baris)) {
            return [];
        }

        $nilai = json_decode($baris, true);

        return is_array($nilai) ? $nilai : [];
    }
};
