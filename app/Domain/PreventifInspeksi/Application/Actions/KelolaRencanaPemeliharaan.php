<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\ScopeLingkup;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\JenisMeterAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Domain\PreventifInspeksi\Application\Services\PembacaMeterPreventif;
use App\Domain\PreventifInspeksi\Domain\Enums\StrategiJadwalPreventif;
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
        private readonly PembacaMeterPreventif $pembacaMeter,
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

            $strategi = StrategiJadwalPreventif::dari($data['StrategiJadwal'] ?? null);

            $rencana = RencanaPemeliharaan::create([
                'OrganisasiId' => $organisasiId,
                'Kode' => $data['Kode'] ?? null,
                'Nama' => $data['Nama'],
                'Jenis' => $data['Jenis'] ?? 'Preventif',
                'TemplatDaftarPeriksaId' => $data['TemplatDaftarPeriksaId'] ?? null,
                'Prioritas' => $data['Prioritas'] ?? 'Normal',
                'StrategiJadwal' => $strategi->value,
                'IntervalNilai' => $strategi->memakaiKalender() ? ($data['IntervalNilai'] ?? 30) : null,
                'IntervalSatuan' => $strategi->memakaiKalender() ? ($data['IntervalSatuan'] ?? 'Hari') : null,
                'BerdasarkanMeter' => $strategi->memakaiMeter(),
                'AmbangMeter' => $strategi->memakaiMeter() ? ($data['AmbangMeter'] ?? null) : null,
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

            $strategiLama = StrategiJadwalPreventif::dari($rencana->StrategiJadwal);
            $ambangLama = $rencana->AmbangMeter === null ? null : (float) $rencana->AmbangMeter;
            $strategi = StrategiJadwalPreventif::dari($data['StrategiJadwal'] ?? $rencana->StrategiJadwal);
            $ambang = $strategi->memakaiMeter()
                ? (array_key_exists('AmbangMeter', $data) ? $data['AmbangMeter'] : $rencana->AmbangMeter)
                : null;

            $rencana->update([
                'Kode' => $data['Kode'] ?? $rencana->Kode,
                'Nama' => $data['Nama'] ?? $rencana->Nama,
                'Jenis' => $data['Jenis'] ?? $rencana->Jenis,
                'TemplatDaftarPeriksaId' => array_key_exists('TemplatDaftarPeriksaId', $data) ? $data['TemplatDaftarPeriksaId'] : $rencana->TemplatDaftarPeriksaId,
                'Prioritas' => $data['Prioritas'] ?? $rencana->Prioritas,
                'StrategiJadwal' => $strategi->value,
                'IntervalNilai' => $strategi->memakaiKalender() ? ($data['IntervalNilai'] ?? $rencana->IntervalNilai ?? 30) : null,
                'IntervalSatuan' => $strategi->memakaiKalender() ? ($data['IntervalSatuan'] ?? $rencana->IntervalSatuan ?? 'Hari') : null,
                'BerdasarkanMeter' => $strategi->memakaiMeter(),
                'AmbangMeter' => $ambang,
                'ToleransiHari' => $data['ToleransiHari'] ?? $rencana->ToleransiHari,
                'BuatPerintahKerjaHariSebelum' => $data['BuatPerintahKerjaHariSebelum'] ?? $rencana->BuatPerintahKerjaHariSebelum,
                'Aktif' => $data['Aktif'] ?? $rencana->Aktif,
                'UnitPengelolaId' => array_key_exists('UnitPengelolaId', $data)
                    ? (filled($data['UnitPengelolaId']) ? $data['UnitPengelolaId'] : null)
                    : $rencana->UnitPengelolaId,
            ]);

            $this->selaraskanPemicuAset($rencana, $strategiLama, $ambangLama);

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
        ?string $meterAsetId = null,
    ): RencanaPemeliharaanAset {
        return $this->transaksi->jalankan(function () use ($rencana, $asetId, $tanggalMulai, $tanggalBerikutnya, $meterAsetId): RencanaPemeliharaanAset {
            $aset = Aset::query()
                ->where('OrganisasiId', $rencana->OrganisasiId)
                ->findOrFail($asetId);

            $strategi = StrategiJadwalPreventif::dari($rencana->StrategiJadwal);
            $mulai = $tanggalMulai ? CarbonImmutable::parse($tanggalMulai) : $this->kalender->hariIni($rencana->OrganisasiId);

            $berikutnya = null;
            if ($strategi->memakaiKalender()) {
                $berikutnya = $tanggalBerikutnya
                    ? CarbonImmutable::parse($tanggalBerikutnya)
                    : $this->hitungTanggalBerikutnya($mulai, (int) $rencana->IntervalNilai, (string) $rencana->IntervalSatuan);
            }

            $meter = null;
            $nilaiMeterBerikutnya = null;
            if ($strategi->memakaiMeter()) {
                $meter = $this->meterPemicu($aset, $meterAsetId);
                $nilaiMeterBerikutnya = $this->pembacaMeter->nilaiTerkini($meter) + (float) $rencana->AmbangMeter;
            }

            $asetPlan = RencanaPemeliharaanAset::updateOrCreate(
                [
                    'RencanaPemeliharaanId' => $rencana->Id,
                    'AsetId' => $aset->Id,
                ],
                [
                    'OrganisasiId' => $rencana->OrganisasiId,
                    'TanggalMulai' => $mulai->toDateString(),
                    'TanggalBerikutnya' => $berikutnya?->toDateString(),
                    'MeterAsetId' => $meter?->Id,
                    'NilaiMeterBerikutnya' => $nilaiMeterBerikutnya,
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

    /**
     * Meter kumulatif aktif milik aset yang memicu rencana berbasis pemakaian. Tanpa meter
     * rencana ini tidak akan pernah jatuh tempo, jadi penetapannya ditolak di muka.
     */
    private function meterPemicu(Aset $aset, ?string $meterAsetId): MeterAset
    {
        if (filled($meterAsetId)) {
            $meter = MeterAset::query()
                ->whereKey($meterAsetId)
                ->where('AsetId', $aset->Id)
                ->where('Jenis', JenisMeterAset::Kumulatif->value)
                ->where('Aktif', true)
                ->first();

            if ($meter === null) {
                throw new AturanBisnisDilanggar('Meter yang dipilih bukan meter kumulatif aktif milik aset ini.');
            }

            return $meter;
        }

        $meter = $this->pembacaMeter->satuSatunyaMeter($aset->Id);
        if ($meter !== null) {
            return $meter;
        }

        $adaMeter = MeterAset::query()
            ->where('AsetId', $aset->Id)
            ->where('Jenis', JenisMeterAset::Kumulatif->value)
            ->where('Aktif', true)
            ->exists();

        throw new AturanBisnisDilanggar($adaMeter
            ? "Aset {$aset->Nama} punya lebih dari satu meter kumulatif. Pilih meter yang menjadi pemicu."
            : "Aset {$aset->Nama} belum punya meter kumulatif aktif. Tambahkan meter di halaman aset lebih dulu.");
    }

    /**
     * Menjaga pemicu aset tetap sah setelah strategi atau ambang rencana diubah: ambang baru
     * dihitung dari pembacaan servis terakhir yang sama, dan aset yang mulai memakai kalender
     * diberi tanggal jatuh tempo pertama.
     */
    private function selaraskanPemicuAset(RencanaPemeliharaan $rencana, StrategiJadwalPreventif $strategiLama, ?float $ambangLama): void
    {
        $strategi = StrategiJadwalPreventif::dari($rencana->StrategiJadwal);
        $ambang = $rencana->AmbangMeter === null ? null : (float) $rencana->AmbangMeter;
        $hariIni = $this->kalender->hariIni($rencana->OrganisasiId);

        foreach (RencanaPemeliharaanAset::query()->where('RencanaPemeliharaanId', $rencana->Id)->get() as $asetPlan) {
            $perubahan = [];

            if ($strategi->memakaiKalender() && $asetPlan->TanggalBerikutnya === null) {
                $perubahan['TanggalBerikutnya'] = $this->hitungTanggalBerikutnya($hariIni, (int) $rencana->IntervalNilai, (string) $rencana->IntervalSatuan)->toDateString();
            }

            if ($strategi->memakaiMeter() && $asetPlan->NilaiMeterBerikutnya !== null && $ambang !== $ambangLama) {
                $perubahan['NilaiMeterBerikutnya'] = $strategiLama->memakaiMeter() && $ambangLama !== null && $ambang !== null
                    ? (float) $asetPlan->NilaiMeterBerikutnya - $ambangLama + $ambang
                    : null;
            }

            if ($perubahan !== []) {
                $asetPlan->update($perubahan);
            }
        }
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
