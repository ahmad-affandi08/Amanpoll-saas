<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

final class KelolaRencanaPemeliharaan
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data, string $penggunaId): RencanaPemeliharaan
    {
        return $this->transaksi->jalankan(function () use ($data): RencanaPemeliharaan {
            $organisasiId = $this->konteksOrganisasi->wajibId();

            $ada = filled($data['Kode'] ?? null) && RencanaPemeliharaan::query()
                ->where('OrganisasiId', $organisasiId)
                ->where('Kode', $data['Kode'])
                ->exists();

            if ($ada) {
                throw new AturanBisnisDilanggar("Rencana pemeliharaan dengan kode '{$data['Kode']}' sudah ada.");
            }

            $rencana = RencanaPemeliharaan::create([
                'OrganisasiId' => $organisasiId,
                'Kode' => $data['Kode'] ?? null,
                'Nama' => $data['Nama'],
                'Jenis' => $data['Jenis'] ?? 'Preventif',
                'TemplatDaftarPeriksaId' => $data['TemplatDaftarPeriksaId'] ?? null,
                'Prioritas' => $data['Prioritas'] ?? 'Normal',
                'StrategiJadwal' => $data['StrategiJadwal'] ?? 'Interval',
                'IntervalNilai' => $data['IntervalNilai'] ?? 30,
                'IntervalSatuan' => $data['IntervalSatuan'] ?? 'Hari',
                'BerdasarkanMeter' => $data['BerdasarkanMeter'] ?? false,
                'AmbangMeter' => $data['AmbangMeter'] ?? null,
                'ToleransiHari' => $data['ToleransiHari'] ?? 0,
                'BuatPerintahKerjaHariSebelum' => $data['BuatPerintahKerjaHariSebelum'] ?? 7,
                'Aktif' => $data['Aktif'] ?? true,
                'UnitPengelolaId' => filled($data['UnitPengelolaId'] ?? null) ? $data['UnitPengelolaId'] : null,
            ]);

            $this->layananAudit->catat(
                aksi: 'RencanaPemeliharaan.Dibuat',
                jenisEntitas: 'RencanaPemeliharaan',
                entitasId: $rencana->Id,
                dataSesudah: $rencana->toArray(),
            );

            return $rencana;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(RencanaPemeliharaan $rencana, array $data, string $penggunaId): RencanaPemeliharaan
    {
        return $this->transaksi->jalankan(function () use ($rencana, $data): RencanaPemeliharaan {
            $sebelum = $rencana->toArray();

            if (isset($data['Kode']) && $data['Kode'] !== $rencana->Kode) {
                $ada = RencanaPemeliharaan::query()
                    ->where('OrganisasiId', $rencana->OrganisasiId)
                    ->where('Kode', $data['Kode'])
                    ->where('Id', '!=', $rencana->Id)
                    ->exists();

                if ($ada) {
                    throw new AturanBisnisDilanggar("Rencana pemeliharaan dengan kode '{$data['Kode']}' sudah ada.");
                }
            }

            $rencana->update([
                'Kode' => $data['Kode'] ?? $rencana->Kode,
                'Nama' => $data['Nama'] ?? $rencana->Nama,
                'Jenis' => $data['Jenis'] ?? $rencana->Jenis,
                'TemplatDaftarPeriksaId' => array_key_exists('TemplatDaftarPeriksaId', $data) ? $data['TemplatDaftarPeriksaId'] : $rencana->TemplatDaftarPeriksaId,
                'Prioritas' => $data['Prioritas'] ?? $rencana->Prioritas,
                'StrategiJadwal' => $data['StrategiJadwal'] ?? $rencana->StrategiJadwal,
                'IntervalNilai' => $data['IntervalNilai'] ?? $rencana->IntervalNilai,
                'IntervalSatuan' => $data['IntervalSatuan'] ?? $rencana->IntervalSatuan,
                'BerdasarkanMeter' => $data['BerdasarkanMeter'] ?? $rencana->BerdasarkanMeter,
                'AmbangMeter' => array_key_exists('AmbangMeter', $data) ? $data['AmbangMeter'] : $rencana->AmbangMeter,
                'ToleransiHari' => $data['ToleransiHari'] ?? $rencana->ToleransiHari,
                'BuatPerintahKerjaHariSebelum' => $data['BuatPerintahKerjaHariSebelum'] ?? $rencana->BuatPerintahKerjaHariSebelum,
                'Aktif' => $data['Aktif'] ?? $rencana->Aktif,
                'UnitPengelolaId' => array_key_exists('UnitPengelolaId', $data)
                    ? (filled($data['UnitPengelolaId']) ? $data['UnitPengelolaId'] : null)
                    : $rencana->UnitPengelolaId,
            ]);

            $this->layananAudit->catat(
                aksi: 'RencanaPemeliharaan.Diperbarui',
                jenisEntitas: 'RencanaPemeliharaan',
                entitasId: $rencana->Id,
                dataSebelum: $sebelum,
                dataSesudah: $rencana->fresh()->toArray(),
            );

            return $rencana;
        });
    }

    /**
     * Saran unit pengelola rencana: unit pengelola bersama seluruh asetnya (PRD 8.21).
     *
     * Hanya bila setiap aset rencana dikelola unit yang sama; satu aset tanpa
     * unit pengelola atau dua unit yang berbeda berarti tidak ada saran.
     * Unit rencana hanyalah cadangan -- tiket preventif mengambil unit
     * pengelola asetnya lebih dulu -- jadi saran ini tidak pernah disimpan
     * diam-diam; formulir hanya mengisikannya sebagai bawaan. Aset dibaca
     * lepas dari ScopeLingkup supaya saran sama bagi setiap koordinator.
     */
    public function saranUnitPengelola(RencanaPemeliharaan $rencana): ?string
    {
        $asetIds = RencanaPemeliharaanAset::query()
            ->where('RencanaPemeliharaanId', $rencana->Id)
            ->pluck('AsetId');

        if ($asetIds->isEmpty()) {
            return null;
        }

        $unit = Aset::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->whereIn('Id', $asetIds)
            ->pluck('UnitPengelolaId')
            ->unique()
            ->values();

        $satu = $unit->first();

        return $unit->count() === 1 && is_string($satu) && $satu !== '' ? $satu : null;
    }

    public function tetapkanAset(
        RencanaPemeliharaan $rencana,
        string $asetId,
        ?string $tanggalMulai = null,
        ?string $tanggalBerikutnya = null,
    ): RencanaPemeliharaanAset {
        return $this->transaksi->jalankan(function () use ($rencana, $asetId, $tanggalMulai, $tanggalBerikutnya): RencanaPemeliharaanAset {
            $aset = Aset::query()
                ->where('OrganisasiId', $rencana->OrganisasiId)
                ->findOrFail($asetId);

            $mulai = $tanggalMulai ? CarbonImmutable::parse($tanggalMulai) : $this->kalender->hariIni($rencana->OrganisasiId);

            if ($tanggalBerikutnya) {
                $berikutnya = CarbonImmutable::parse($tanggalBerikutnya);
            } else {
                $berikutnya = $this->hitungTanggalBerikutnya($mulai, $rencana->IntervalNilai, $rencana->IntervalSatuan);
            }

            $asetPlan = RencanaPemeliharaanAset::updateOrCreate(
                [
                    'RencanaPemeliharaanId' => $rencana->Id,
                    'AsetId' => $aset->Id,
                ],
                [
                    'OrganisasiId' => $rencana->OrganisasiId,
                    'TanggalMulai' => $mulai->toDateString(),
                    'TanggalBerikutnya' => $berikutnya->toDateString(),
                    'Aktif' => true,
                ]
            );

            $this->layananAudit->catat(
                aksi: 'RencanaPemeliharaanAset.Ditetapkan',
                jenisEntitas: 'RencanaPemeliharaanAset',
                entitasId: $asetPlan->Id,
                dataSesudah: $asetPlan->toArray(),
            );

            return $asetPlan;
        });
    }

    public function lepasAset(RencanaPemeliharaan $rencana, string $asetId): void
    {
        $this->transaksi->jalankan(function () use ($rencana, $asetId): void {
            $asetPlan = RencanaPemeliharaanAset::query()
                ->where('RencanaPemeliharaanId', $rencana->Id)
                ->where('AsetId', $asetId)
                ->first();

            if ($asetPlan) {
                $sebelum = $asetPlan->toArray();
                $asetPlan->delete();

                $this->layananAudit->catat(
                    aksi: 'RencanaPemeliharaanAset.Dilepas',
                    jenisEntitas: 'RencanaPemeliharaanAset',
                    entitasId: $asetPlan->Id,
                    dataSebelum: $sebelum,
                );
            }
        });
    }

    public function hitungTanggalBerikutnya(CarbonImmutable $dariTanggal, int $nilai, string $satuan): CarbonImmutable
    {
        return match (strtolower($satuan)) {
            'hari', 'day', 'days' => $dariTanggal->addDays($nilai),
            'minggu', 'week', 'weeks' => $dariTanggal->addWeeks($nilai),
            'bulan', 'month', 'months' => $dariTanggal->addMonths($nilai),
            'tahun', 'year', 'years' => $dariTanggal->addYears($nilai),
            default => $dariTanggal->addDays($nilai),
        };
    }
}
