<?php

declare(strict_types=1);

use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Aturan bobot skor prospek (MARKETING.md 5.4, 24).
 *
 * Sebelumnya bobot ini hidup sebagai satu objek JSON di `KonfigurasiPemasaran`.
 * Bentuk itu memenuhi tuntutan "configurable, jangan di-hard-code", tetapi
 * membawa kegagalan yang tidak bergejala: kunci yang salah ketik tersimpan
 * dengan senang hati lalu diabaikan diam-diam oleh penghitungnya, dan tidak ada
 * yang memberi tahu siapa pun. Domain ini sudah menolak kegagalan sejenis di
 * tempat lain — `PerekamEventPemasaran` menolak jenis peristiwa asing — jadi
 * aturannya dipindahkan ke tabel yang kode peristiwanya dapat divalidasi saat
 * disimpan.
 *
 * Bobot yang sudah disetel operator dibawa serta. Migrasi yang menghapus
 * setelan orang lain lalu menggantinya dengan bawaan adalah migrasi yang
 * menghilangkan pekerjaan.
 */
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

        /*
         * Rincian skor menunjuk aturan yang menghasilkannya. Boleh kosong, dan
         * memang harus boleh: aturan yang dihapus tidak menghapus penjelasan
         * skor yang terlanjur dihitung darinya.
         */
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
            // Kunci yang tidak dikenal tidak ikut dipindahkan. Ia memang tidak
            // pernah berlaku, dan membawanya masuk hanya memindahkan kesalahan
            // yang sama ke tempat yang lebih sulit dilihat.
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
