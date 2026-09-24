<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Resources;

use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PerintahKerjaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $perintahKerja = $this->resource instanceof PerintahKerja ? $this->resource : null;

        return [
            'Id' => $this->Id,
            'Nomor' => $this->Nomor,
            'KeluhanId' => $this->KeluhanId,
            'NomorKeluhan' => $this->whenLoaded('keluhan', fn () => $this->keluhan?->Nomor),
            'Jenis' => $this->Jenis,
            'Judul' => $this->Judul,
            'Deskripsi' => $this->Deskripsi,
            'Prioritas' => $this->Prioritas,
            'Status' => $this->Status,
            'LokasiId' => $this->LokasiId,
            'NamaLokasi' => $this->whenLoaded('lokasi', fn () => $this->lokasi?->Nama),
            'UnitOrganisasiId' => $this->UnitOrganisasiId,
            'UnitPengelolaId' => $perintahKerja?->UnitPengelolaId,
            'UnitPengelola' => $this->whenLoaded('unitPengelola', function () use ($perintahKerja): ?array {
                $unit = $perintahKerja?->unitPengelola;

                return $unit === null ? null : ['Id' => $unit->Id, 'Kode' => $unit->Kode, 'Nama' => $unit->Nama];
            }),
            'DijadwalkanMulaiPada' => $this->DijadwalkanMulaiPada?->toIso8601String(),
            'DijadwalkanSelesaiPada' => $this->DijadwalkanSelesaiPada?->toIso8601String(),
            'DiterimaPada' => $this->DiterimaPada?->toIso8601String(),
            'DimulaiPada' => $this->DimulaiPada?->toIso8601String(),
            'DiselesaikanPada' => $this->DiselesaikanPada?->toIso8601String(),
            'DitutupPada' => $this->DitutupPada?->toIso8601String(),
            'BatasResponsPada' => $this->BatasResponsPada?->toIso8601String(),
            'BatasPenyelesaianPada' => $this->BatasPenyelesaianPada?->toIso8601String(),
            'PersentaseSelesai' => (float) $this->PersentaseSelesai,
            'MembutuhkanWaktuHenti' => (bool) $this->MembutuhkanWaktuHenti,
            'MembutuhkanPersetujuan' => (bool) $this->MembutuhkanPersetujuan,
            'RingkasanPenyelesaian' => $this->RingkasanPenyelesaian,
            // Keterangan "Menunggu konfirmasi penerima" (PRD 8.22); hanya bila pemanggil memuat `SudahDikonfirmasiPenerima`.
            'MenungguKonfirmasiPenerima' => $perintahKerja !== null && $perintahKerja->hasAttribute('SudahDikonfirmasiPenerima')
                ? $perintahKerja->Status === StatusPerintahKerja::MenungguVerifikasi->value && ! (bool) $perintahKerja->getAttribute('SudahDikonfirmasiPenerima')
                : null,
            'Versi' => $this->Versi,
            'Aset' => $this->whenLoaded('aset', fn () => $this->aset->map(fn ($aset) => [
                'Id' => $aset->Id,
                'KodeAset' => $aset->KodeAset,
                'Nama' => $aset->Nama,
                'Utama' => (bool) $aset->pivot->Utama,
                'KondisiAwal' => $aset->pivot->KondisiAwal,
                'KondisiAkhir' => $aset->pivot->KondisiAkhir,
            ])->values()),
            'Penugasan' => $this->whenLoaded('penugasan', fn () => $this->penugasan->map(fn ($item) => [
                'Id' => $item->Id,
                'PenggunaId' => $item->PenggunaId,
                'NamaPengguna' => $item->pengguna?->Nama,
                'PeranTugas' => $item->PeranTugas,
                'Status' => $item->Status,
                'DitugaskanPada' => $item->DitugaskanPada?->toIso8601String(),
                'DiterimaPada' => $item->DiterimaPada?->toIso8601String(),
                'SelesaiPada' => $item->SelesaiPada?->toIso8601String(),
            ])->values()),
            'RiwayatStatus' => $this->whenLoaded('riwayatStatus', fn () => $this->riwayatStatus->map(fn ($item) => [
                'Id' => $item->Id,
                'StatusSebelum' => $item->StatusSebelum,
                'StatusSesudah' => $item->StatusSesudah,
                'Catatan' => $item->Catatan,
                'NamaPengubah' => $item->diubahOleh?->Nama,
                'DiubahPada' => $item->DiubahPada?->toIso8601String(),
            ])->values()),
            'WaktuKerja' => $this->whenLoaded('waktuKerja', fn () => $this->waktuKerja->map(fn ($item) => [
                'Id' => $item->Id,
                'PenggunaId' => $item->PenggunaId,
                'NamaPengguna' => $item->pengguna?->Nama,
                'MulaiPada' => $item->MulaiPada?->toIso8601String(),
                'SelesaiPada' => $item->SelesaiPada?->toIso8601String(),
                'DurasiMenit' => $item->DurasiMenit,
                'JenisWaktu' => $item->JenisWaktu,
                'Catatan' => $item->Catatan,
            ])->values()),
            'WaktuHenti' => $this->whenLoaded('waktuHenti', fn () => $this->waktuHenti->map(fn ($item) => [
                'Id' => $item->Id,
                'AsetId' => $item->AsetId,
                'NamaAset' => $item->aset?->Nama,
                'MulaiPada' => $item->MulaiPada?->toIso8601String(),
                'SelesaiPada' => $item->SelesaiPada?->toIso8601String(),
                'DurasiMenit' => $item->DurasiMenit,
                'Jenis' => $item->Jenis,
                'Alasan' => $item->Alasan,
            ])->values()),
            'Biaya' => $this->whenLoaded('biaya', fn () => $this->biaya->map(fn ($item) => [
                'Id' => $item->Id,
                'JenisBiaya' => $item->JenisBiaya,
                'Deskripsi' => $item->Deskripsi,
                'Jumlah' => (float) $item->Jumlah,
                'MataUang' => $item->MataUang,
                'TanggalBiaya' => $item->TanggalBiaya?->format('Y-m-d'),
            ])->values()),
            'AnalisisKegagalan' => $this->whenLoaded('analisisKegagalan', fn () => $this->analisisKegagalan === null ? null : [
                'KodeMasalahId' => $this->analisisKegagalan->KodeMasalahId,
                'KodePenyebabId' => $this->analisisKegagalan->KodePenyebabId,
                'KodeTindakanId' => $this->analisisKegagalan->KodeTindakanId,
                'AkarMasalah' => $this->analisisKegagalan->AkarMasalah,
                'TindakanKorektif' => $this->analisisKegagalan->TindakanKorektif,
                'TindakanPencegahan' => $this->analisisKegagalan->TindakanPencegahan,
            ]),
            'ReservasiSukuCadang' => $this->whenLoaded('reservasiSukuCadang', fn () => $this->reservasiSukuCadang->map(fn ($item) => [
                'Id' => $item->Id,
                'SukuCadangId' => $item->SukuCadangId,
                'NamaSukuCadang' => $item->sukuCadang?->Nama,
                'NamaGudang' => $item->gudang?->Nama,
                'Jumlah' => (float) $item->Jumlah,
                'Status' => $item->Status,
            ])->values()),
            'PemakaianSukuCadang' => $this->whenLoaded('pemakaianSukuCadang', fn () => $this->pemakaianSukuCadang->map(fn ($item) => [
                'Id' => $item->Id,
                'NamaSukuCadang' => $item->sukuCadang?->Nama,
                'Jumlah' => (float) $item->Jumlah,
                'HargaSatuan' => (float) $item->HargaSatuan,
                'DipakaiPada' => $item->DipakaiPada?->toIso8601String(),
            ])->values()),
            'TotalWaktuKerjaMenit' => (int) ($this->TotalWaktuKerjaMenit ?? 0),
            'TotalDowntimeMenit' => (int) ($this->TotalDowntimeMenit ?? 0),
            'TotalBiaya' => (float) ($this->TotalBiaya ?? 0),
        ];
    }
}
