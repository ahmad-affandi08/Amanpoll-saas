<?php

declare(strict_types=1);

namespace App\Core\Kesehatan;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pemeriksaan yang dijalankan rute `/up` lewat peristiwa DiagnosingHealth.
 *
 * Tanpa pemeriksaan ini `/up` hanya membuktikan PHP hidup dan framework
 * termuat -- ia tetap menjawab 200 meski kredensial basis datanya salah.
 * Health endpoint yang hijau di atas basis data mati lebih buruk daripada
 * tidak ada sama sekali, karena ia memberi rasa aman yang keliru tepat pada
 * saat deployment paling mungkin rusak.
 *
 * Yang diperiksa adalah tiga hal yang diam-diam gagal di hosting bersama:
 * kredensial basis data, direktori tulis yang tidak ikut terunggah karena
 * diabaikan git, dan cache store yang diarahkan ke layanan yang tidak ada
 * di sana.
 */
final class PeriksaKesehatanSistem
{
    /**
     * Direktori yang harus ada dan dapat ditulis.
     *
     * Isi `storage/framework` diabaikan git, jadi unggahan baru kerap sampai
     * tanpa direktori itu sama sekali; ketiadaannya sama fatalnya dengan izin
     * yang salah.
     *
     * @var list<string>|null
     */
    private readonly ?array $direktori;

    /** @param  list<string>|null  $direktori  Diisi hanya oleh pengujian. */
    public function __construct(?array $direktori = null)
    {
        $this->direktori = $direktori;
    }

    public function handle(): void
    {
        $gagal = [];

        foreach ($this->pemeriksaan() as $nama => $periksa) {
            try {
                $periksa();
            } catch (Throwable $e) {
                $gagal[] = $nama.': '.$e->getMessage();
            }
        }

        if ($gagal !== []) {
            // Seluruh kegagalan dikumpulkan, bukan berhenti di yang pertama:
            // satu kali pemeriksaan sebaiknya menyebut semua yang rusak
            // daripada memaksa operator memperbaikinya satu per satu.
            throw new KesehatanSistemTerganggu(implode(' | ', $gagal));
        }
    }

    /** @return array<string, callable(): void> */
    private function pemeriksaan(): array
    {
        return [
            'basis data' => fn () => $this->periksaBasisData(),
            'direktori tulis' => fn () => $this->periksaDirektori(),
            'cache' => fn () => $this->periksaCache(),
        ];
    }

    private function periksaBasisData(): void
    {
        DB::connection()->select('select 1');
    }

    private function periksaDirektori(): void
    {
        $bermasalah = [];

        foreach ($this->direktori ?? $this->direktoriBawaan() as $satu) {
            if (! is_dir($satu)) {
                $bermasalah[] = $satu.' (tidak ada)';

                continue;
            }

            if (! is_writable($satu)) {
                $bermasalah[] = $satu.' (tidak dapat ditulis)';
            }
        }

        if ($bermasalah !== []) {
            throw new KesehatanSistemTerganggu(implode(', ', $bermasalah));
        }
    }

    /**
     * Perjalanan bolak-balik, bukan sekadar memanggil store-nya: driver yang
     * salah konfigurasi kerap menerima tulisan lalu mengembalikan null.
     */
    private function periksaCache(): void
    {
        $kunci = 'kesehatan:uji';
        $nilai = (string) Str::ulid();

        Cache::put($kunci, $nilai, 10);
        $terbaca = Cache::get($kunci);
        Cache::forget($kunci);

        if ($terbaca !== $nilai) {
            throw new KesehatanSistemTerganggu('nilai yang ditulis tidak terbaca kembali');
        }
    }

    /** @return list<string> */
    private function direktoriBawaan(): array
    {
        return [
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];
    }
}
