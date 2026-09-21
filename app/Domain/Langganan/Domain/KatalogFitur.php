<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain;

use App\Domain\Langganan\Domain\Enums\TipeBatasFitur;
use App\Domain\Langganan\Domain\ValueObjects\DefinisiFitur;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

/**
 * Master fitur paket (22.01).
 *
 * Kode fitur tertanam di rute, di middleware, dan di baris PaketFitur milik
 * setiap pelanggan, jadi kode adalah kontrak jangka panjang: menghapus atau
 * mengganti satu kode akan membuka modul berbayar atau menutup modul yang sudah
 * dibayar. Karena itu katalog ini yang menjadi sumber kebenaran — tabel
 * FiturPaket disemai dari sini, bukan sebaliknya, sehingga tidak mungkin ada
 * kode yang dipakai kode program tetapi tidak ada masternya.
 */
final class KatalogFitur
{
    /** Modul bernilai tambah yang dijual terpisah dari paket dasar. */
    public const MODUL_KALIBRASI = 'modul.kalibrasi';

    public const MODUL_KEPATUHAN = 'modul.kepatuhan';

    public const MODUL_PELAPORAN_LANJUTAN = 'modul.pelaporan_lanjutan';

    public const MODUL_INTEGRASI = 'modul.integrasi';

    /** Batas kuota yang hanya dapat ditegakkan saat baris baru dibuat. */
    public const BATAS_ASET = 'batas.aset';

    public const BATAS_PENGGUNA = 'batas.pengguna';

    /** @var array<string, DefinisiFitur>|null */
    private static ?array $katalog = null;

    /** @return array<string, DefinisiFitur> */
    public static function semua(): array
    {
        return self::$katalog ??= self::susun();
    }

    /** @return list<string> */
    public static function kode(): array
    {
        return array_keys(self::semua());
    }

    public static function ada(string $kode): bool
    {
        return isset(self::semua()[$kode]);
    }

    public static function ambil(string $kode): DefinisiFitur
    {
        return self::semua()[$kode] ?? throw new DataTidakDitemukan("Fitur paket {$kode} tidak dikenal.");
    }

    /** @return list<DefinisiFitur> */
    public static function bertipe(TipeBatasFitur $tipe): array
    {
        return array_values(array_filter(
            self::semua(),
            fn (DefinisiFitur $definisi): bool => $definisi->tipeBatas === $tipe,
        ));
    }

    /** @return array<string, DefinisiFitur> */
    private static function susun(): array
    {
        $definisi = [
            new DefinisiFitur(
                self::MODUL_KALIBRASI,
                'Modul Kalibrasi',
                'Rencana kalibrasi, pelaksanaan, sertifikat, dan pengingat jatuh tempo.',
                TipeBatasFitur::Boolean,
            ),
            new DefinisiFitur(
                self::MODUL_KEPATUHAN,
                'Modul Kepatuhan',
                'Standar dan persyaratan kepatuhan, sertifikasi aset, serta pemeriksaannya.',
                TipeBatasFitur::Boolean,
            ),
            new DefinisiFitur(
                self::MODUL_PELAPORAN_LANJUTAN,
                'Pelaporan Lanjutan',
                'Laporan tersimpan, dasbor kustom, dan ekspor CSV/XLSX/PDF.',
                TipeBatasFitur::Boolean,
            ),
            new DefinisiFitur(
                self::MODUL_INTEGRASI,
                'Integrasi dan API',
                'Kunci API, webhook keluar, dan pemetaan sistem eksternal.',
                TipeBatasFitur::Boolean,
            ),
            new DefinisiFitur(
                self::BATAS_ASET,
                'Batas Jumlah Aset',
                'Banyaknya aset aktif yang boleh tercatat pada organisasi.',
                TipeBatasFitur::Angka,
                diizinkanBawaan: true,
                satuanBatas: 'aset',
            ),
            new DefinisiFitur(
                self::BATAS_PENGGUNA,
                'Batas Jumlah Pengguna',
                'Banyaknya pengguna aktif yang boleh masuk ke organisasi.',
                TipeBatasFitur::Angka,
                diizinkanBawaan: true,
                satuanBatas: 'pengguna',
            ),
        ];

        $katalog = [];
        foreach ($definisi as $satu) {
            $katalog[$satu->kode] = $satu;
        }

        return $katalog;
    }
}
