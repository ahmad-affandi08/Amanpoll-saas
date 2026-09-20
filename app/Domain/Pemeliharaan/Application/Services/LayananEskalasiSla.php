<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class LayananEskalasiSla
{
    public function __construct(private readonly LayananNotifikasi $notifikasi) {}

    public function proses(): int
    {
        $jumlah = 0;
        Keluhan::query()
            ->with('tingkatLayanan.eskalasi')
            ->whereNotNull('TingkatLayananId')
            ->whereNotIn('Status', [StatusKeluhan::Ditutup->value, StatusKeluhan::Ditolak->value, StatusKeluhan::Dibatalkan->value])
            ->orderBy('Id')
            ->chunkById(100, function ($daftarKeluhan) use (&$jumlah): void {
                foreach ($daftarKeluhan as $keluhan) {
                    foreach ($keluhan->tingkatLayanan?->eskalasi ?? [] as $aturan) {
                        if (! $aturan->Aktif) {
                            continue;
                        }
                        if ($keluhan->DiresponsPada === null && $keluhan->BatasResponsPada !== null) {
                            $jumlah += $this->prosesBatas($keluhan, $aturan, 'Respons', $keluhan->BatasResponsPada);
                        }
                        if ($keluhan->DiresolusikanPada === null && $keluhan->BatasPenyelesaianPada !== null) {
                            $jumlah += $this->prosesBatas($keluhan, $aturan, 'Penyelesaian', $keluhan->BatasPenyelesaianPada);
                        }
                    }
                }
            }, 'Id');

        return $jumlah;
    }

    private function prosesBatas(Keluhan $keluhan, EskalasiTingkatLayanan $aturan, string $jenisBatas, CarbonInterface $batas): int
    {
        $waktuPemicu = $aturan->Pemicu === EskalasiTingkatLayanan::PEMICU_MENJELANG
            ? $batas->subMinutes($aturan->SetelahMenit)
            : $batas->addMinutes($aturan->SetelahMenit);

        if (now()->lessThan($waktuPemicu)) {
            return 0;
        }
        if ($aturan->Pemicu === EskalasiTingkatLayanan::PEMICU_MENJELANG && now()->greaterThanOrEqualTo($batas)) {
            return 0;
        }

        $jenisPeristiwa = $aturan->Pemicu === EskalasiTingkatLayanan::PEMICU_MENJELANG
            ? 'Keluhan.Sla.Mendekati'
            : 'Keluhan.Sla.Terlewati';
        $judul = "SLA {$jenisBatas} {$keluhan->Nomor} - Tahap {$aturan->Tahap}";
        $jumlah = 0;

        foreach ($this->penerima($keluhan->OrganisasiId, $aturan) as $penggunaId) {
            $sudahDikirim = Notifikasi::query()
                ->where('PenggunaId', $penggunaId)
                ->where('JenisPeristiwa', $jenisPeristiwa)
                ->where('JenisEntitas', 'Keluhan')
                ->where('EntitasId', $keluhan->Id)
                ->where('Judul', $judul)
                ->exists();
            if ($sudahDikirim) {
                continue;
            }

            $this->notifikasi->kirim(
                penggunaId: $penggunaId,
                jenisPeristiwa: $jenisPeristiwa,
                isi: "Keluhan {$keluhan->Nomor} ({$keluhan->Judul}) memiliki SLA {$jenisBatas} yang {$this->labelPemicu($aturan)}.",
                judul: $judul,
                jenisEntitas: 'Keluhan',
                entitasId: $keluhan->Id,
                kanal: $aturan->Kanal ?: [Notifikasi::KANAL_IN_APP],
            );
            $jumlah++;
        }

        return $jumlah;
    }

    /** @return list<string> */
    private function penerima(string $organisasiId, EskalasiTingkatLayanan $aturan): array
    {
        $penerima = collect($aturan->PenggunaId ? [$aturan->PenggunaId] : []);
        if ($aturan->PeranId !== null) {
            $penerima = $penerima->merge(
                DB::table('PenggunaPeran')
                    ->where('OrganisasiId', $organisasiId)
                    ->where('PeranId', $aturan->PeranId)
                    ->where(fn ($query) => $query->whereNull('BerlakuMulai')->orWhere('BerlakuMulai', '<=', now()))
                    ->where(fn ($query) => $query->whereNull('BerlakuSampai')->orWhere('BerlakuSampai', '>=', now()))
                    ->pluck('PenggunaId'),
            );
        }

        return $penerima->filter()->unique()->values()->map(fn ($id): string => (string) $id)->all();
    }

    private function labelPemicu(EskalasiTingkatLayanan $aturan): string
    {
        return $aturan->Pemicu === EskalasiTingkatLayanan::PEMICU_MENJELANG
            ? "akan jatuh tempo dalam {$aturan->SetelahMenit} menit"
            : "telah terlewati {$aturan->SetelahMenit} menit";
    }
}
