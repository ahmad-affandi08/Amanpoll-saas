<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\PemeriksaIzin;
use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\QueryException;

/**
 * Satu-satunya jalur yang boleh mengubah StokSukuCadang.JumlahTersedia
 * (Gate 10). Setiap baris detail dikunci (SELECT ... FOR UPDATE) di dalam
 * transaksi database sebelum saldonya dibaca dan ditulis ulang, supaya dua
 * posting bersamaan pada kombinasi gudang+suku cadang yang sama diserialkan
 * oleh database, bukan oleh aplikasi.
 */
final class PostingMutasiStok
{
    private const KUNCI_KONFIGURASI_STOK_NEGATIF = 'Persediaan.IzinkanStokNegatif';

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
        private readonly PemeriksaIzin $izin,
    ) {}

    public function jalankan(MutasiStok $mutasiStok, string $dipostingOleh): MutasiStok
    {
        if ($mutasiStok->Status === StatusMutasiStok::Diposting->value) {
            return $mutasiStok;
        }

        if ($mutasiStok->Status !== StatusMutasiStok::Draft->value) {
            throw new AturanBisnisDilanggar('Hanya mutasi berstatus draft yang bisa diposting.');
        }

        $detail = $mutasiStok->detailMutasiStok()->get();

        if ($detail->isEmpty()) {
            throw new AturanBisnisDilanggar('Tambahkan minimal satu baris detail sebelum memposting mutasi.');
        }

        $izinkanNegatif = $this->izinkanStokNegatif($mutasiStok->OrganisasiId)
            && $this->izin->boleh($dipostingOleh, 'Stok.Override');

        $this->transaksi->jalankan(function () use ($mutasiStok, $detail, $izinkanNegatif): void {
            foreach ($detail as $baris) {
                $this->terapkanBaris($mutasiStok, $baris, $izinkanNegatif);
            }

            $mutasiStok->Status = StatusMutasiStok::Diposting->value;
            $mutasiStok->save();
        });

        $this->layananAudit->catat(
            aksi: 'MutasiStok.Diposting',
            jenisEntitas: 'MutasiStok',
            entitasId: $mutasiStok->Id,
            dataSesudah: ['Status' => $mutasiStok->Status, 'JumlahBaris' => $detail->count()],
        );

        return $mutasiStok->refresh();
    }

    private function terapkanBaris(MutasiStok $mutasiStok, DetailMutasiStok $baris, bool $izinkanNegatif): void
    {
        $jumlah = (float) $baris->Jumlah;

        match ($mutasiStok->Jenis) {
            JenisMutasiStok::Penerimaan->value, JenisMutasiStok::Return->value => $this->ubahSaldo(
                $mutasiStok->OrganisasiId,
                (string) $mutasiStok->GudangTujuanId,
                $baris->LokasiGudangTujuanId,
                $baris->SukuCadangId,
                $baris->KelompokSukuCadangId,
                $jumlah,
                $izinkanNegatif,
            ),
            JenisMutasiStok::Pengeluaran->value => $this->ubahSaldo(
                $mutasiStok->OrganisasiId,
                (string) $mutasiStok->GudangAsalId,
                $baris->LokasiGudangAsalId,
                $baris->SukuCadangId,
                $baris->KelompokSukuCadangId,
                -1 * $jumlah,
                $izinkanNegatif,
            ),
            JenisMutasiStok::Adjustment->value => $this->ubahSaldo(
                $mutasiStok->OrganisasiId,
                (string) $mutasiStok->GudangAsalId,
                $baris->LokasiGudangAsalId,
                $baris->SukuCadangId,
                $baris->KelompokSukuCadangId,
                $jumlah,
                $izinkanNegatif,
            ),
            JenisMutasiStok::Transfer->value => $this->transfer($mutasiStok, $baris, $jumlah, $izinkanNegatif),
            default => throw new AturanBisnisDilanggar('Jenis mutasi stok tidak dikenal.'),
        };
    }

    private function transfer(MutasiStok $mutasiStok, DetailMutasiStok $baris, float $jumlah, bool $izinkanNegatif): void
    {
        $this->ubahSaldo(
            $mutasiStok->OrganisasiId,
            (string) $mutasiStok->GudangAsalId,
            $baris->LokasiGudangAsalId,
            $baris->SukuCadangId,
            $baris->KelompokSukuCadangId,
            -1 * $jumlah,
            $izinkanNegatif,
        );

        $this->ubahSaldo(
            $mutasiStok->OrganisasiId,
            (string) $mutasiStok->GudangTujuanId,
            $baris->LokasiGudangTujuanId,
            $baris->SukuCadangId,
            $baris->KelompokSukuCadangId,
            $jumlah,
            $izinkanNegatif,
        );
    }

    private function ubahSaldo(
        string $organisasiId,
        string $gudangId,
        ?string $lokasiGudangId,
        string $sukuCadangId,
        ?string $kelompokSukuCadangId,
        float $delta,
        bool $izinkanNegatif,
    ): void {
        $query = StokSukuCadang::query()
            ->where('GudangId', $gudangId)
            ->where('LokasiGudangId', $lokasiGudangId)
            ->where('SukuCadangId', $sukuCadangId)
            ->where('KelompokSukuCadangId', $kelompokSukuCadangId);

        $saldo = (clone $query)->lockForUpdate()->first();

        if (! $saldo) {
            try {
                $saldo = StokSukuCadang::create([
                    'OrganisasiId' => $organisasiId,
                    'GudangId' => $gudangId,
                    'LokasiGudangId' => $lokasiGudangId,
                    'SukuCadangId' => $sukuCadangId,
                    'KelompokSukuCadangId' => $kelompokSukuCadangId,
                    'JumlahTersedia' => 0,
                    'JumlahDipesan' => 0,
                    'JumlahDitahan' => 0,
                ]);
            } catch (QueryException $kesalahan) {
                $saldo = (clone $query)->lockForUpdate()->first();
                if (! $saldo) {
                    throw $kesalahan;
                }
            }
        }

        $saldoBaru = (float) $saldo->JumlahTersedia + $delta;

        if ($saldoBaru < 0 && ! $izinkanNegatif) {
            throw new AturanBisnisDilanggar('Mutasi ini akan membuat stok menjadi negatif. Aktifkan izin penyesuaian stok negatif untuk melanjutkan.');
        }

        $saldo->JumlahTersedia = $saldoBaru;
        $saldo->Versi = $saldo->Versi + 1;
        $saldo->save();
    }

    private function izinkanStokNegatif(string $organisasiId): bool
    {
        $konfigurasi = KonfigurasiOrganisasi::query()
            ->where('OrganisasiId', $organisasiId)
            ->where('Kunci', self::KUNCI_KONFIGURASI_STOK_NEGATIF)
            ->first();

        if (! $konfigurasi) {
            return false;
        }

        $nilai = $konfigurasi->Nilai;

        return (bool) (is_array($nilai) ? ($nilai['aktif'] ?? false) : $nilai);
    }
}
