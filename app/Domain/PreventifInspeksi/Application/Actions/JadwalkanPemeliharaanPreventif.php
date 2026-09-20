<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Application\Actions\BuatPerintahKerja;
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
        $acuan = $tanggalAcuan ?? CarbonImmutable::now();
        $penggunaSistem = $penggunaId ?? '01JAMANPOLL000000000000001';

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

            if (! $rencana || ! $aset || ! $asetPlan->TanggalBerikutnya) {
                continue;
            }

            $hariSebelum = $horizonHari ?? ($rencana->BuatPerintahKerjaHariSebelum ?? 7);
            $batasJadwal = $acuan->addDays($hariSebelum);
            $tanggalJadwal = CarbonImmutable::parse($asetPlan->TanggalBerikutnya);

            if ($tanggalJadwal->greaterThan($batasJadwal)) {
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

            $hasil = $this->transaksi->jalankan(function () use ($asetPlan, $rencana, $aset, $tanggalJadwal, $penggunaSistem): array {
                // Buat Perintah Kerja Preventif
                $perintahKerja = $this->buatPerintahKerja->jalankan([
                    'OrganisasiId' => $asetPlan->OrganisasiId,
                    'Jenis' => 'Preventif',
                    'Judul' => "Pemeliharaan Preventif: {$rencana->Nama} - {$aset->Nama}",
                    'Deskripsi' => "Pekerjaan pemeliharaan preventif berkala berdasarkan rencana {$rencana->Kode}.",
                    'Prioritas' => $rencana->Prioritas ?? 'Normal',
                    'LokasiId' => $aset->LokasiId,
                    'AsetIds' => [$aset->Id],
                    'DijadwalkanMulaiPada' => $tanggalJadwal->startOfDay(),
                ], $penggunaSistem);

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

                // Majukan TanggalBerikutnya pada RencanaPemeliharaanAset
                $tanggalBerikutnyaBaru = $this->kelolaRencana->hitungTanggalBerikutnya(
                    $tanggalJadwal,
                    $rencana->IntervalNilai,
                    $rencana->IntervalSatuan
                );

                $asetPlan->update([
                    'TanggalBerikutnya' => $tanggalBerikutnyaBaru->toDateString(),
                ]);

                $this->layananAudit->catat(
                    aksi: 'JadwalPemeliharaan.DibuatOtomatis',
                    jenisEntitas: 'JadwalPemeliharaan',
                    entitasId: $jadwal->Id,
                    dataSesudah: [
                        'Jadwal' => $jadwal->toArray(),
                        'PerintahKerjaId' => $perintahKerja->Id,
                        'TanggalBerikutnyaBaru' => $tanggalBerikutnyaBaru->toDateString(),
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
}
