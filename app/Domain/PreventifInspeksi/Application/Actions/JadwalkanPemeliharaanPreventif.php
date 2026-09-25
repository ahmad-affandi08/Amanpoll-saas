<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Pemeliharaan\Application\Actions\BuatPerintahKerja;
use App\Domain\PreventifInspeksi\Application\Services\PembacaMeterPreventif;
use App\Domain\PreventifInspeksi\Domain\Enums\StrategiJadwalPreventif;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Carbon\CarbonImmutable;

final class JadwalkanPemeliharaanPreventif
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly BuatPerintahKerja $buatPerintahKerja,
        private readonly KelolaRencanaPemeliharaan $kelolaRencana,
        private readonly LayananAudit $layananAudit,
        private readonly KalenderOrganisasi $kalender,
        private readonly PembacaMeterPreventif $pembacaMeter,
    ) {}

    /**
     * Menjalankan evaluasi penjadwalan preventif secara idempoten.
     *
     * @return array{jadwalDibuat: int, perintahKerjaDibuat: int, dilewati: int}
     */
    public function jalankan(
        ?CarbonImmutable $tanggalAcuan = null,
        ?int $horizonHari = null,
        ?string $organisasiId = null,
        ?string $penggunaId = null,
    ): array {
        // Tanpa pengguna (cron malam) perintah kerja tercatat dibuat sistem: DibuatOleh kosong.
        // Dulu diisi Id tetap yang ternyata milik baris Izin, sehingga FK menolak dan cron gagal.
        $penggunaSistem = $penggunaId;

        $query = RencanaPemeliharaanAset::query()
            ->with(['rencanaPemeliharaan', 'aset'])
            ->where('Aktif', true)
            ->whereHas('rencanaPemeliharaan', function ($q): void {
                $q->where('Aktif', true);
            });

        if ($organisasiId !== null) {
            $query->where('OrganisasiId', $organisasiId);
        }

        $daftarAsetPlan = $query->get();

        $jadwalDibuat = 0;
        $perintahKerjaDibuat = 0;
        $dilewati = 0;

        foreach ($daftarAsetPlan as $asetPlan) {
            $rencana = $asetPlan->rencanaPemeliharaan;
            $aset = $asetPlan->aset;

            if (! $rencana || ! $aset) {
                continue;
            }

            $strategi = StrategiJadwalPreventif::dari($rencana->StrategiJadwal);

            // Tanggal acuan dibaca per organisasi: rencana di Jayapura sudah
            // berganti hari dua jam sebelum rencana di Jakarta.
            $acuan = $tanggalAcuan ?? $this->kalender->hariIni($asetPlan->OrganisasiId);
            $hariSebelum = $horizonHari ?? ($rencana->BuatPerintahKerjaHariSebelum ?? 7);
            $batasJadwal = $acuan->addDays($hariSebelum);

            $tanggalJadwal = null;
            if ($strategi->memakaiKalender() && $asetPlan->TanggalBerikutnya !== null) {
                $jatuhTempo = CarbonImmutable::parse($asetPlan->TanggalBerikutnya);
                $tanggalJadwal = $jatuhTempo->greaterThan($batasJadwal) ? null : $jatuhTempo;
            }

            // Pemicu meter: ambang tercapai berarti servis jatuh tempo hari ini. Pada strategi
            // kombinasi, mana pun yang lebih dulu tercapai menentukan tanggal jadwalnya.
            $meter = null;
            $nilaiMeter = null;
            $meterTercapai = false;
            $ambangMeter = (float) $rencana->AmbangMeter;
            if ($strategi->memakaiMeter() && $ambangMeter > 0) {
                $meter = $this->pembacaMeter->meterUntuk($asetPlan);
            }

            if ($meter !== null) {
                $nilaiMeter = $this->pembacaMeter->nilaiTerkini($meter);

                if ($asetPlan->NilaiMeterBerikutnya === null) {
                    // Rencana yang ditetapkan sebelum ambangnya dievaluasi: pembacaan sekarang menjadi titik awal.
                    $asetPlan->update(['MeterAsetId' => $meter->Id, 'NilaiMeterBerikutnya' => $nilaiMeter + $ambangMeter]);
                } elseif ($nilaiMeter >= (float) $asetPlan->NilaiMeterBerikutnya) {
                    $meterTercapai = true;
                    $tanggalJadwal = $tanggalJadwal === null ? $acuan : $tanggalJadwal->min($acuan);
                }
            }

            if ($tanggalJadwal === null) {
                continue;
            }

            // IDEMPOTENSI: Cek apakah jadwal pada tanggal ini sudah pernah dibuat
            $jadwalAda = JadwalPemeliharaan::query()
                ->where('RencanaPemeliharaanAsetId', $asetPlan->Id)
                ->where('TanggalJadwal', $tanggalJadwal->toDateString())
                ->first();

            if ($jadwalAda !== null) {
                $dilewati++;

                continue;
            }

            $deskripsi = "Pekerjaan pemeliharaan preventif berkala berdasarkan rencana {$rencana->Kode}.";
            if ($meterTercapai && $meter !== null) {
                $deskripsi .= sprintf(
                    ' Pemakaian %s mencapai %s %s (ambang %s %s).',
                    $meter->Nama,
                    $this->angka($nilaiMeter),
                    $meter->Satuan,
                    $this->angka((float) $asetPlan->NilaiMeterBerikutnya),
                    $meter->Satuan,
                );
            }

            $hasil = $this->transaksi->jalankan(function () use ($asetPlan, $rencana, $aset, $tanggalJadwal, $penggunaSistem, $strategi, $meter, $nilaiMeter, $ambangMeter, $deskripsi): array {
                // Unit pengelola: aset lebih dulu, rencana sebagai cadangan (PRD 8.21).
                $perintahKerja = $this->buatPerintahKerja->jalankan([
                    'OrganisasiId' => $asetPlan->OrganisasiId,
                    'Jenis' => 'Preventif',
                    'Judul' => "Pemeliharaan Preventif: {$rencana->Nama} - {$aset->Nama}",
                    'Deskripsi' => $deskripsi,
                    'Prioritas' => $rencana->Prioritas ?? 'Normal',
                    'LokasiId' => $aset->LokasiId,
                    'AsetIds' => [$aset->Id],
                    'DijadwalkanMulaiPada' => $this->kalender->awalHari($tanggalJadwal, $asetPlan->OrganisasiId),
                ], $penggunaSistem, $rencana->UnitPengelolaId);

                // Buat Pelaksanaan Daftar Periksa jika templat terhubung
                if ($rencana->TemplatDaftarPeriksaId !== null) {
                    PelaksanaanDaftarPeriksa::create([
                        'OrganisasiId' => $asetPlan->OrganisasiId,
                        'TemplatDaftarPeriksaId' => $rencana->TemplatDaftarPeriksaId,
                        'PerintahKerjaId' => $perintahKerja->Id,
                        'AsetId' => $aset->Id,
                        'Status' => 'Draft',
                    ]);
                }

                // Catat Jadwal Pemeliharaan
                $jadwal = JadwalPemeliharaan::create([
                    'OrganisasiId' => $asetPlan->OrganisasiId,
                    'RencanaPemeliharaanAsetId' => $asetPlan->Id,
                    'PerintahKerjaId' => $perintahKerja->Id,
                    'TanggalJadwal' => $tanggalJadwal->toDateString(),
                    'Status' => 'Terjadwal',
                    'DihasilkanOtomatis' => true,
                ]);

                // Majukan kedua pemicu dari servis ini: pada strategi kombinasi, servis karena
                // meter juga mengatur ulang tanggal, dan sebaliknya.
                $tanggalBerikutnyaBaru = null;
                $perubahan = [];
                if ($strategi->memakaiKalender() && $rencana->IntervalNilai !== null) {
                    $tanggalBerikutnyaBaru = $this->kelolaRencana->hitungTanggalBerikutnya(
                        $tanggalJadwal,
                        $rencana->IntervalNilai,
                        (string) $rencana->IntervalSatuan,
                    );
                    $perubahan['TanggalBerikutnya'] = $tanggalBerikutnyaBaru->toDateString();
                }

                if ($meter !== null && $nilaiMeter !== null) {
                    $perubahan['MeterAsetId'] = $meter->Id;
                    $perubahan['NilaiMeterBerikutnya'] = $nilaiMeter + $ambangMeter;
                }

                if ($perubahan !== []) {
                    $asetPlan->update($perubahan);
                }

                $this->layananAudit->catat(
                    aksi: 'JadwalPemeliharaan.DibuatOtomatis',
                    jenisEntitas: 'JadwalPemeliharaan',
                    entitasId: $jadwal->Id,
                    dataSesudah: [
                        'Jadwal' => $jadwal->toArray(),
                        'PerintahKerjaId' => $perintahKerja->Id,
                        'TanggalBerikutnyaBaru' => $tanggalBerikutnyaBaru?->toDateString(),
                        'NilaiMeterBerikutnyaBaru' => $perubahan['NilaiMeterBerikutnya'] ?? null,
                    ],
                );

                return ['jadwal' => $jadwal, 'perintahKerja' => $perintahKerja];
            });

            if ($hasil['jadwal'] ?? null) {
                $jadwalDibuat++;
                $perintahKerjaDibuat++;
            }
        }

        return [
            'jadwalDibuat' => $jadwalDibuat,
            'perintahKerjaDibuat' => $perintahKerjaDibuat,
            'dilewati' => $dilewati,
        ];
    }

    private function angka(?float $nilai): string
    {
        return number_format((float) $nilai, 0, ',', '.');
    }
}
