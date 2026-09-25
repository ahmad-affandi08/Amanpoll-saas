<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Generator nomor dokumen sequential per organisasi+JenisDokumen. */
final class LayananNomorDokumen
{
    private const MAKS_PERCOBAAN = 5;

    /**
     * Awalan bawaan per jenis dokumen yang dinomori aplikasi.
     *
     * Tenant baru (pendaftaran trial maupun organisasi yang dibuat konsol) tidak
     * pernah dibekali pola nomor, sehingga keluhan, perintah kerja, dan mutasi stok
     * pertamanya gagal dengan "pola belum diatur". Pola bawaan dipasang saat pertama
     * kali dibutuhkan; admin tetap bebas mengubahnya di Pengaturan Nomor Dokumen.
     *
     * @var array<string, string>
     */
    public const AWALAN_BAWAAN = [
        'Keluhan' => 'KLH',
        'PerintahKerja' => 'PK',
        'Inspeksi' => 'INS',
        'Kalibrasi' => 'KAL',
        'MutasiStok' => 'MS',
        'ReservasiSukuCadang' => 'RSV',
        'UsulanAset' => 'UA',
        'RencanaPengadaan' => 'RP',
        'PermintaanPembelian' => 'PP',
        'PermintaanPenawaran' => 'RFQ',
        'PesananPembelian' => 'PO',
        'PenerimaanPembelian' => 'GRN',
        'Kontrak' => 'KTR',
        'PermintaanMutasiAset' => 'MUT',
        'PengajuanPenghapusanAset' => 'PHA',
        'SerahTerimaAset' => 'STA',
    ];

    public function __construct(private readonly KalenderOrganisasi $kalender) {}

    public function berikutnya(string $organisasiId, string $jenisDokumen): string
    {
        return DB::transaction(function () use ($organisasiId, $jenisDokumen): string {
            $this->pastikanPolaBawaan($organisasiId, $jenisDokumen);

            $baris = DB::table('NomorDokumen')
                ->where('OrganisasiId', $organisasiId)
                ->where('JenisDokumen', $jenisDokumen)
                ->lockForUpdate()
                ->first();

            if (! $baris) {
                throw new DataTidakDitemukan("Pola nomor dokumen untuk '{$jenisDokumen}' belum diatur.");
            }

            $sekarang = $this->kalender->sekarang($organisasiId);
            $periodeSaatIni = $this->periodeSaatIni($baris->ResetPeriode, $sekarang);
            $nomorBaru = ($baris->PeriodeAktif === $periodeSaatIni) ? $baris->NomorTerakhir + 1 : 1;

            DB::table('NomorDokumen')->where('Id', $baris->Id)->update([
                'NomorTerakhir' => $nomorBaru,
                'PeriodeAktif' => $periodeSaatIni,
                'DiperbaruiPada' => now(),
            ]);

            return $this->format($baris->FormatNomor, (string) $baris->Awalan, $nomorBaru, $periodeSaatIni, $sekarang);
        }, self::MAKS_PERCOBAAN);
    }

    /** Pratinjau nomor berikutnya TANPA mengubah NomorTerakhir. */
    public function pratinjau(string $organisasiId, string $jenisDokumen): string
    {
        $this->pastikanPolaBawaan($organisasiId, $jenisDokumen);

        $baris = DB::table('NomorDokumen')
            ->where('OrganisasiId', $organisasiId)
            ->where('JenisDokumen', $jenisDokumen)
            ->first();

        if (! $baris) {
            throw new DataTidakDitemukan("Pola nomor dokumen untuk '{$jenisDokumen}' belum diatur.");
        }

        $sekarang = $this->kalender->sekarang($organisasiId);
        $periodeSaatIni = $this->periodeSaatIni($baris->ResetPeriode, $sekarang);
        $nomorBerikutnya = ($baris->PeriodeAktif === $periodeSaatIni) ? $baris->NomorTerakhir + 1 : 1;

        return $this->format($baris->FormatNomor, (string) $baris->Awalan, $nomorBerikutnya, $periodeSaatIni, $sekarang);
    }

    /**
     * Memasang pola `{Awalan}/{Tahun}/{Nomor:4}` bila jenis dokumen yang dikenal belum
     * punya pola. `insertOrIgnore` di atas indeks unik organisasi+jenis membuat dua
     * permintaan yang berbarengan tetap berakhir dengan satu baris.
     */
    private function pastikanPolaBawaan(string $organisasiId, string $jenisDokumen): void
    {
        $awalan = self::AWALAN_BAWAAN[$jenisDokumen] ?? null;

        if ($awalan === null) {
            return;
        }

        $ada = DB::table('NomorDokumen')
            ->where('OrganisasiId', $organisasiId)
            ->where('JenisDokumen', $jenisDokumen)
            ->exists();

        if ($ada) {
            return;
        }

        DB::table('NomorDokumen')->insertOrIgnore([
            'Id' => (string) Str::ulid(),
            'OrganisasiId' => $organisasiId,
            'JenisDokumen' => $jenisDokumen,
            'Awalan' => $awalan,
            'FormatNomor' => '{Awalan}/{Tahun}/{Nomor:4}',
            'NomorTerakhir' => 0,
            'ResetPeriode' => 'Tahunan',
            'PeriodeAktif' => null,
            'DibuatPada' => now(),
            'DiperbaruiPada' => now(),
        ]);
    }

    /**
     * Periode penomoran di kalender organisasi.
     *
     * Tahun dan bulan dibaca di zona rumah sakit: dengan jam UTC, dokumen
     * yang dibuat 1 Januari pukul 06:00 WIB masih bernomor tahun lalu dan
     * melanjutkan urutannya, alih-alih memulai dari 1.
     */
    private function periodeSaatIni(string $resetPeriode, CarbonImmutable $sekarang): string
    {
        return match ($resetPeriode) {
            'Tahunan' => $sekarang->format('Y'),
            'Bulanan' => $sekarang->format('Y-m'),
            default => '',
        };
    }

    private function format(string $formatNomor, string $awalan, int $nomor, string $periode, CarbonImmutable $sekarang): string
    {
        return preg_replace_callback(
            '/\{(Awalan|Nomor|Tahun|TahunPendek|Bulan|Periode)(?::(\d+))?\}/',
            function (array $cocok) use ($awalan, $nomor, $periode, $sekarang): string {
                $lebar = isset($cocok[2]) ? (int) $cocok[2] : null;

                return match ($cocok[1]) {
                    'Awalan' => $awalan,
                    'Nomor' => $lebar ? str_pad((string) $nomor, $lebar, '0', STR_PAD_LEFT) : (string) $nomor,
                    'Tahun' => $sekarang->format('Y'),
                    'TahunPendek' => $sekarang->format('y'),
                    'Bulan' => $sekarang->format('m'),
                    'Periode' => $periode,
                };
            },
            $formatNomor,
        ) ?? $formatNomor;
    }
}
