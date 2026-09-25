<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Organisasi\KonteksOrganisasi;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Bekal bersama seeder data demo PT Sinar Nusantara Industri.
 *
 * Seeder transaksi berjalan lewat Action resmi, bukan insert mentah, supaya
 * nomor dokumen, saldo stok, SLA, dan jejak audit sama persis dengan yang
 * dihasilkan aplikasi. Riwayat berbulan-bulan dibuat dengan memundurkan jam
 * (`padaWaktu`) sebelum Action dipanggil.
 */
trait KonteksDemo
{
    /** Kode organisasi demo; seeder lain menemukan organisasinya lewat kode ini. */
    protected const KODE_ORGANISASI_DEMO = 'SNI';

    private ?string $organisasiDemoId = null;

    /** @var array<string, string> */
    private array $cachePenggunaDemo = [];

    protected function organisasiId(): string
    {
        if ($this->organisasiDemoId !== null) {
            return $this->organisasiDemoId;
        }

        $id = DB::table('Organisasi')->where('Kode', self::KODE_ORGANISASI_DEMO)->value('Id');

        if (! is_string($id)) {
            throw new RuntimeException('Organisasi demo belum ada. Jalankan DemoFondasiSeeder lebih dulu.');
        }

        return $this->organisasiDemoId = $id;
    }

    /** Menetapkan organisasi demo sebagai konteks untuk Action dan global scope. */
    protected function masukKonteks(): void
    {
        app(KonteksOrganisasi::class)->tetapkan($this->organisasiId());
    }

    /** Id pengguna demo dari emailnya. */
    protected function pengguna(string $email): string
    {
        if (isset($this->cachePenggunaDemo[$email])) {
            return $this->cachePenggunaDemo[$email];
        }

        $id = DB::table('Pengguna')
            ->where('OrganisasiId', $this->organisasiId())
            ->where('Email', $email)
            ->value('Id');

        if (! is_string($id)) {
            throw new RuntimeException("Pengguna demo {$email} tidak ditemukan.");
        }

        return $this->cachePenggunaDemo[$email] = $id;
    }

    /**
     * Id baris milik organisasi demo, dicari dari kolom kuncinya (umumnya `Kode`).
     *
     * @param  array<string, mixed>  $kunci
     */
    protected function idDari(string $tabel, array $kunci): string
    {
        $id = DB::table($tabel)
            ->where('OrganisasiId', $this->organisasiId())
            ->where($kunci)
            ->value('Id');

        if (! is_string($id)) {
            throw new RuntimeException("Baris {$tabel} ".json_encode($kunci).' tidak ditemukan.');
        }

        return $id;
    }

    /**
     * Menjalankan pekerjaan dengan jam dimundurkan ke `$waktu`, sebagai pengguna tertentu.
     * Jam dan pengguna selalu dipulihkan, juga saat pekerjaannya melempar.
     *
     * @template T
     *
     * @param  callable(): T  $pekerjaan
     * @return T
     */
    protected function padaWaktu(CarbonInterface $waktu, callable $pekerjaan, ?string $emailPengguna = null): mixed
    {
        Date::setTestNow($waktu);
        $penggunaSebelumnya = Auth::user();

        // Kolom `DibuatPada` pada model tanpa timestamps Eloquent diisi DEFAULT CURRENT_TIMESTAMP
        // oleh basis data, yang tidak ikut jam PHP. Jam sesi MySQL/MariaDB ikut dimundurkan.
        $selaraskanBasisData = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
        if ($selaraskanBasisData) {
            DB::statement('SET timestamp = '.sprintf('%.6F', $waktu->getPreciseTimestamp(6) / 1000000));
        }

        if ($emailPengguna !== null) {
            Auth::onceUsingId($this->pengguna($emailPengguna));
        }

        try {
            return $pekerjaan();
        } finally {
            Date::setTestNow();
            if ($selaraskanBasisData) {
                DB::statement('SET timestamp = DEFAULT');
            }
            $penggunaSebelumnya === null ? Auth::forgetUser() : Auth::setUser($penggunaSebelumnya);
        }
    }

    /**
     * Titik waktu relatif hari ini: `$hariLalu` hari ke belakang pada jam kerja tertentu
     * (waktu lokal organisasi), disimpan UTC seperti seluruh aplikasi.
     */
    protected function hariLalu(int $hariLalu, int $jam = 9, int $menit = 0): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Jakarta')
            ->subDays($hariLalu)
            ->setTime($jam, $menit)
            ->utc();
    }

    /**
     * updateOrInsert yang mempertahankan Id baris yang sudah ada, supaya
     * menjalankan seeder berulang tidak memutus relasi yang menunjuknya.
     *
     * @param  array<string, mixed>  $kunci
     * @param  array<string, mixed>  $nilai
     */
    protected function simpan(string $tabel, array $kunci, array $nilai, bool $denganStempel = true): string
    {
        $ada = DB::table($tabel)->where($kunci)->value('Id');
        $id = is_string($ada) ? $ada : (string) Str::ulid();

        if ($ada === null) {
            $baris = [...$nilai, 'Id' => $id];
            if ($denganStempel) {
                $baris['DibuatPada'] ??= now();
            }
            DB::table($tabel)->insert([...$kunci, ...$baris]);
        } elseif ($nilai !== []) {
            DB::table($tabel)->where('Id', $id)->update($nilai);
        }

        return $id;
    }

    /**
     * Seeder transaksi tidak idempoten (setiap jalan menambah dokumen baru). Mereka
     * melewati dirinya bila tabel penandanya sudah berisi data organisasi demo.
     */
    protected function sudahDisemai(string $tabelPenanda): bool
    {
        $ada = DB::table($tabelPenanda)->where('OrganisasiId', $this->organisasiId())->exists();

        if ($ada) {
            $this->command?->warn(static::class.": {$tabelPenanda} sudah berisi data demo, dilewati.");
        }

        return $ada;
    }
}
