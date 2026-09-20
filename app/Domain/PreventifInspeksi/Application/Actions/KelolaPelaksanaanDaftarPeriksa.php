<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class KelolaPelaksanaanDaftarPeriksa
{
    public function __construct(
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function mulai(array $data, string $penggunaId): PelaksanaanDaftarPeriksa
    {
        return $this->transaksi->jalankan(function () use ($data, $penggunaId): PelaksanaanDaftarPeriksa {
            $organisasiId = $this->konteksOrganisasi->wajibId();
            $templat = TemplatDaftarPeriksa::query()
                ->with('butir')
                ->where('OrganisasiId', $organisasiId)
                ->findOrFail($data['TemplatDaftarPeriksaId']);

            if (! $templat->Aktif) {
                throw new AturanBisnisDilanggar('Templat daftar periksa tidak aktif.');
            }

            $pelaksanaan = PelaksanaanDaftarPeriksa::create([
                'OrganisasiId' => $organisasiId,
                'TemplatDaftarPeriksaId' => $templat->Id,
                'PerintahKerjaId' => $data['PerintahKerjaId'] ?? null,
                'AsetId' => $data['AsetId'] ?? null,
                'DilaksanakanOleh' => $data['DilaksanakanOleh'] ?? $penggunaId,
                'MulaiPada' => now(),
                'Status' => 'Draft',
                'Catatan' => $data['Catatan'] ?? null,
            ]);

            foreach ($templat->butir as $b) {
                JawabanDaftarPeriksa::create([
                    'OrganisasiId' => $organisasiId,
                    'PelaksanaanDaftarPeriksaId' => $pelaksanaan->Id,
                    'ButirTemplatDaftarPeriksaId' => $b->Id,
                    'Sesuai' => null,
                ]);
            }

            $this->layananAudit->catat(
                aksi: 'PelaksanaanDaftarPeriksa.Dimulai',
                jenisEntitas: 'PelaksanaanDaftarPeriksa',
                entitasId: $pelaksanaan->Id,
                dataSesudah: $pelaksanaan->toArray(),
            );

            return $pelaksanaan;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $daftarJawaban
     */
    public function simpanJawaban(PelaksanaanDaftarPeriksa $pelaksanaan, array $daftarJawaban, string $penggunaId): void
    {
        $this->transaksi->jalankan(function () use ($pelaksanaan, $daftarJawaban): void {
            /** @var PelaksanaanDaftarPeriksa $terkunci */
            $terkunci = PelaksanaanDaftarPeriksa::query()->lockForUpdate()->findOrFail($pelaksanaan->Id);

            if ($terkunci->Status === 'Selesai') {
                throw new AturanBisnisDilanggar('Pelaksanaan daftar periksa sudah selesai dan terkunci.');
            }

            $butirMap = ButirTemplatDaftarPeriksa::query()
                ->where('TemplatDaftarPeriksaId', $terkunci->TemplatDaftarPeriksaId)
                ->get()
                ->keyBy('Id');

            foreach ($daftarJawaban as $jawaban) {
                $butirId = $jawaban['ButirTemplatDaftarPeriksaId'] ?? null;
                if (! $butirId || ! isset($butirMap[$butirId])) {
                    continue;
                }

                /** @var ButirTemplatDaftarPeriksa $butir */
                $butir = $butirMap[$butirId];
                $sesuai = $this->evaluasiKesesuaian($butir, $jawaban);

                JawabanDaftarPeriksa::updateOrCreate(
                    [
                        'PelaksanaanDaftarPeriksaId' => $terkunci->Id,
                        'ButirTemplatDaftarPeriksaId' => $butir->Id,
                    ],
                    [
                        'OrganisasiId' => $terkunci->OrganisasiId,
                        'NilaiTeks' => $jawaban['NilaiTeks'] ?? null,
                        'NilaiAngka' => isset($jawaban['NilaiAngka']) && is_numeric($jawaban['NilaiAngka']) ? (float) $jawaban['NilaiAngka'] : null,
                        'NilaiBoolean' => isset($jawaban['NilaiBoolean']) ? (bool) $jawaban['NilaiBoolean'] : null,
                        'NilaiTanggal' => $jawaban['NilaiTanggal'] ?? null,
                        'NilaiJson' => $jawaban['NilaiJson'] ?? null,
                        'Sesuai' => $sesuai,
                        'Catatan' => $jawaban['Catatan'] ?? null,
                        'DijawabPada' => now(),
                    ]
                );
            }

            $terkunci->Status = 'SedangDikerjakan';
            $terkunci->save();
        });
    }

    public function finalisasi(PelaksanaanDaftarPeriksa $pelaksanaan, ?string $catatan, string $penggunaId): PelaksanaanDaftarPeriksa
    {
        return $this->transaksi->jalankan(function () use ($pelaksanaan, $catatan): PelaksanaanDaftarPeriksa {
            /** @var PelaksanaanDaftarPeriksa $terkunci */
            $terkunci = PelaksanaanDaftarPeriksa::query()->lockForUpdate()->findOrFail($pelaksanaan->Id);

            if ($terkunci->Status === 'Selesai') {
                throw new AturanBisnisDilanggar('Pelaksanaan daftar periksa sudah selesai.');
            }

            $butirWajib = ButirTemplatDaftarPeriksa::query()
                ->where('TemplatDaftarPeriksaId', $terkunci->TemplatDaftarPeriksaId)
                ->where('Wajib', true)
                ->get();

            $jawabanTersimpan = JawabanDaftarPeriksa::query()
                ->where('PelaksanaanDaftarPeriksaId', $terkunci->Id)
                ->get()
                ->keyBy('ButirTemplatDaftarPeriksaId');

            foreach ($butirWajib as $b) {
                $j = $jawabanTersimpan->get($b->Id);
                $terisi = $j !== null && (
                    $j->NilaiTeks !== null ||
                    $j->NilaiAngka !== null ||
                    $j->NilaiBoolean !== null ||
                    $j->NilaiTanggal !== null ||
                    $j->NilaiJson !== null
                );

                if (! $terisi) {
                    throw new AturanBisnisDilanggar("Butir '{$b->Pertanyaan}' wajib dijawab sebelum pelaksanaan dapat diselesaikan.");
                }
            }

            // Hitung skor kesesuaian
            $semuaJawaban = JawabanDaftarPeriksa::query()
                ->where('PelaksanaanDaftarPeriksaId', $terkunci->Id)
                ->whereNotNull('Sesuai')
                ->get();

            $totalDievaluasi = $semuaJawaban->count();
            $totalSesuai = $semuaJawaban->where('Sesuai', true)->count();

            $skor = $totalDievaluasi > 0
                ? round(($totalSesuai / $totalDievaluasi) * 100, 2)
                : 100.00;

            $sebelum = $terkunci->toArray();

            $terkunci->Status = 'Selesai';
            $terkunci->SelesaiPada = now();
            $terkunci->Skor = $skor;
            if ($catatan !== null) {
                $terkunci->Catatan = $catatan;
            }
            $terkunci->save();

            $this->layananAudit->catat(
                aksi: 'PelaksanaanDaftarPeriksa.Selesai',
                jenisEntitas: 'PelaksanaanDaftarPeriksa',
                entitasId: $terkunci->Id,
                dataSebelum: $sebelum,
                dataSesudah: $terkunci->toArray(),
            );

            return $terkunci;
        });
    }

    /**
     * @param  array<string, mixed>  $jawaban
     */
    private function evaluasiKesesuaian(ButirTemplatDaftarPeriksa $butir, array $jawaban): ?bool
    {
        // 1. Cek tipe Angka dengan batas minimum/maksimum
        if ($butir->TipeJawaban === 'Angka' && isset($jawaban['NilaiAngka']) && is_numeric($jawaban['NilaiAngka'])) {
            $nilai = (float) $jawaban['NilaiAngka'];
            if ($butir->NilaiMinimum !== null && $nilai < (float) $butir->NilaiMinimum) {
                return false;
            }
            if ($butir->NilaiMaksimum !== null && $nilai > (float) $butir->NilaiMaksimum) {
                return false;
            }

            return true;
        }

        // 2. Cek tipe YaTidak
        if ($butir->TipeJawaban === 'YaTidak') {
            $nilai = $jawaban['NilaiBoolean'] ?? null;
            if ($nilai === null && isset($jawaban['NilaiTeks'])) {
                $nilai = in_array(strtolower((string) $jawaban['NilaiTeks']), ['ya', 'true', '1', 'yes'], true);
            }

            if ($butir->MemicuTemuanJika !== null && is_array($butir->MemicuTemuanJika)) {
                $kondisi = $butir->MemicuTemuanJika['nilai'] ?? false;
                if ($nilai === $kondisi) {
                    return false;
                }
            }

            return $nilai === true;
        }

        // 3. Cek kondisi MemicuTemuanJika untuk pilihan/teks
        if ($butir->MemicuTemuanJika !== null && is_array($butir->MemicuTemuanJika)) {
            $pemicu = $butir->MemicuTemuanJika['nilai'] ?? null;
            if ($pemicu !== null) {
                $teks = $jawaban['NilaiTeks'] ?? null;
                if ($teks !== null && strcasecmp((string) $teks, (string) $pemicu) === 0) {
                    return false;
                }
            }
        }

        return true;
    }
}
