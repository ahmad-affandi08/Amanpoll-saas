<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PerencanaanPengadaan\Application\Services\LayananSaldoAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusAnggaran;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusRencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusUsulanAset;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailRencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use App\Shared\Domain\ValueObjects\Uang;
use Illuminate\Support\Str;

final class KelolaRencanaPengadaan
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $nomorDokumen,
        private readonly LayananSaldoAnggaran $layananSaldo,
        private readonly LayananAudit $audit,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data, string $penggunaId): RencanaPengadaan
    {
        return $this->transaksi->jalankan(function () use ($data, $penggunaId): RencanaPengadaan {
            $organisasiId = $this->konteksOrganisasi->wajibId();
            $pos = $this->temukanPos($data['PosAnggaranId'] ?? null);
            $nomor = $data['Nomor'] ?? null;
            if (! is_string($nomor) || trim($nomor) === '') {
                try {
                    $nomor = $this->nomorDokumen->berikutnya($organisasiId, 'RencanaPengadaan');
                } catch (DataTidakDitemukan) {
                    $nomor = 'RPG-'.$this->kalender->sekarang($organisasiId)->format('Ym').'-'.Str::upper(Str::random(6));
                }
            }

            $rencana = RencanaPengadaan::create([
                'OrganisasiId' => $organisasiId,
                'Nomor' => $nomor,
                'Nama' => $data['Nama'],
                'Tahun' => $data['Tahun'],
                'PosAnggaranId' => $pos?->Id,
                'Status' => StatusRencanaPengadaan::Draft->value,
                'TotalEstimasi' => '0.00',
                'DibuatOleh' => $penggunaId,
            ]);

            foreach ($data['UsulanAsetIds'] ?? [] as $usulanId) {
                $this->tambahkanUsulan($rencana, (string) $usulanId);
            }

            $this->hitungUlangTotal($rencana);
            $this->audit->catat('RencanaPengadaan.Dibuat', 'RencanaPengadaan', $rencana->Id, dataSesudah: $rencana->fresh()->toArray());

            return $rencana->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(RencanaPengadaan $rencana, array $data): RencanaPengadaan
    {
        $this->pastikanDraft($rencana);
        $pos = array_key_exists('PosAnggaranId', $data) ? $this->temukanPos($data['PosAnggaranId']) : $rencana->posAnggaran()->first();
        $sebelum = $rencana->toArray();

        $rencana->fill([
            'Nama' => $data['Nama'] ?? $rencana->Nama,
            'Tahun' => $data['Tahun'] ?? $rencana->Tahun,
            'PosAnggaranId' => $pos?->Id,
        ])->save();

        $this->audit->catat('RencanaPengadaan.Diperbarui', 'RencanaPengadaan', $rencana->Id, $sebelum, $rencana->fresh()->toArray());

        return $rencana->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function tambahDetail(RencanaPengadaan $rencana, array $data): DetailRencanaPengadaan
    {
        $this->pastikanDraft($rencana);

        return $this->transaksi->jalankan(function () use ($rencana, $data): DetailRencanaPengadaan {
            if (! empty($data['UsulanAsetId'])) {
                return $this->tambahkanUsulan($rencana, (string) $data['UsulanAsetId'], $data);
            }

            $detail = DetailRencanaPengadaan::create([
                'OrganisasiId' => $rencana->OrganisasiId,
                'RencanaPengadaanId' => $rencana->Id,
                'UsulanAsetId' => null,
                'SukuCadangId' => $data['SukuCadangId'] ?? null,
                'Deskripsi' => $data['Deskripsi'],
                'Jumlah' => $data['Jumlah'],
                'Satuan' => $data['Satuan'],
                'HargaEstimasi' => $data['HargaEstimasi'] ?? null,
                'BulanRencana' => $data['BulanRencana'] ?? null,
            ]);

            $this->hitungUlangTotal($rencana);
            $this->audit->catat('RencanaPengadaan.DetailDitambahkan', 'RencanaPengadaan', $rencana->Id, dataSesudah: $detail->toArray());

            return $detail;
        });
    }

    public function hapusDetail(RencanaPengadaan $rencana, DetailRencanaPengadaan $detail): void
    {
        $this->pastikanDraft($rencana);
        if ($detail->RencanaPengadaanId !== $rencana->Id) {
            throw new AturanBisnisDilanggar('Detail tidak termasuk dalam rencana pengadaan ini.');
        }

        $this->transaksi->jalankan(function () use ($rencana, $detail): void {
            $sebelum = $detail->toArray();
            $detail->delete();
            $this->hitungUlangTotal($rencana);
            $this->audit->catat('RencanaPengadaan.DetailDihapus', 'RencanaPengadaan', $rencana->Id, dataSebelum: $sebelum);
        });
    }

    public function finalisasi(RencanaPengadaan $rencana): RencanaPengadaan
    {
        $this->pastikanDraft($rencana);

        return $this->transaksi->jalankan(function () use ($rencana): RencanaPengadaan {
            $rencanaTerkunci = RencanaPengadaan::query()->lockForUpdate()->findOrFail($rencana->Id);
            $this->hitungUlangTotal($rencanaTerkunci);

            if (! $rencanaTerkunci->detail()->exists()) {
                throw new AturanBisnisDilanggar('Tambahkan minimal satu detail sebelum finalisasi rencana.');
            }

            if ($rencanaTerkunci->PosAnggaranId === null) {
                throw new AturanBisnisDilanggar('Pilih pos anggaran sebelum finalisasi rencana.');
            }

            $pos = PosAnggaran::query()->lockForUpdate()->findOrFail($rencanaTerkunci->PosAnggaranId);
            $anggaran = $pos->anggaran()->firstOrFail();
            if ($anggaran->Status !== StatusAnggaran::Aktif->value) {
                throw new AturanBisnisDilanggar('Pos anggaran harus berasal dari anggaran aktif.');
            }

            $saldo = $this->layananSaldo->hitung($pos);
            if (Uang::dariString((string) $rencanaTerkunci->TotalEstimasi)->lebihBesarDari(Uang::dariString($saldo['sisa']))) {
                throw new AturanBisnisDilanggar('Total estimasi rencana melebihi sisa pos anggaran.');
            }

            $rencanaTerkunci->Status = StatusRencanaPengadaan::Direncanakan->value;
            $rencanaTerkunci->save();
            $this->audit->catat('RencanaPengadaan.Difinalisasi', 'RencanaPengadaan', $rencanaTerkunci->Id, dataSesudah: $rencanaTerkunci->toArray());

            return $rencanaTerkunci->refresh();
        });
    }

    public function hapus(RencanaPengadaan $rencana): void
    {
        $this->pastikanDraft($rencana);
        $sebelum = $rencana->toArray();

        $this->transaksi->jalankan(function () use ($rencana): void {
            $rencana->detail()->delete();
            $rencana->delete();
        });

        $this->audit->catat('RencanaPengadaan.Dihapus', 'RencanaPengadaan', $rencana->Id, dataSebelum: $sebelum);
    }

    /**
     * @param  array<string, mixed>  $override
     */
    private function tambahkanUsulan(RencanaPengadaan $rencana, string $usulanId, array $override = []): DetailRencanaPengadaan
    {
        $usulan = UsulanAset::query()->findOrFail($usulanId);
        if ($usulan->Status !== StatusUsulanAset::Disetujui->value) {
            throw new AturanBisnisDilanggar('Hanya usulan yang sudah disetujui dapat masuk ke rencana pengadaan.');
        }

        $sudahDirencanakan = DetailRencanaPengadaan::query()
            ->where('UsulanAsetId', $usulan->Id)
            ->whereHas('rencanaPengadaan', fn ($query) => $query->where('Status', '!=', StatusRencanaPengadaan::Dibatalkan->value))
            ->exists();
        if ($sudahDirencanakan) {
            throw new AturanBisnisDilanggar("Usulan {$usulan->Nomor} sudah masuk ke rencana pengadaan lain.");
        }

        return DetailRencanaPengadaan::create([
            'OrganisasiId' => $rencana->OrganisasiId,
            'RencanaPengadaanId' => $rencana->Id,
            'UsulanAsetId' => $usulan->Id,
            'SukuCadangId' => $override['SukuCadangId'] ?? null,
            'Deskripsi' => $override['Deskripsi'] ?? $usulan->NamaKebutuhan,
            'Jumlah' => $override['Jumlah'] ?? $usulan->Jumlah,
            'Satuan' => $override['Satuan'] ?? 'unit',
            'HargaEstimasi' => $override['HargaEstimasi'] ?? $usulan->EstimasiHargaSatuan,
            'BulanRencana' => $override['BulanRencana'] ?? null,
        ]);
    }

    private function hitungUlangTotal(RencanaPengadaan $rencana): void
    {
        $total = DetailRencanaPengadaan::query()
            ->where('RencanaPengadaanId', $rencana->Id)
            ->selectRaw('COALESCE(SUM(Jumlah * COALESCE(HargaEstimasi, 0)), 0) AS Total')
            ->value('Total');

        $rencana->TotalEstimasi = (string) $total;
        $rencana->save();
    }

    private function temukanPos(?string $posAnggaranId): ?PosAnggaran
    {
        if ($posAnggaranId === null || $posAnggaranId === '') {
            return null;
        }

        return PosAnggaran::query()->findOrFail($posAnggaranId);
    }

    private function pastikanDraft(RencanaPengadaan $rencana): void
    {
        if ($rencana->Status !== StatusRencanaPengadaan::Draft->value) {
            throw new AturanBisnisDilanggar('Hanya rencana draft yang dapat diubah.');
        }
    }
}
