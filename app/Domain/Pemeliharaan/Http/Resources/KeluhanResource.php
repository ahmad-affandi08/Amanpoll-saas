<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Resources;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class KeluhanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $keluhan = $this->resource instanceof Keluhan ? $this->resource : null;
        $usulan = $keluhan?->UsulanUrgensi;

        return [
            'Id' => $this->Id,
            'Nomor' => $this->Nomor,
            'KategoriKeluhanId' => $this->KategoriKeluhanId,
            'TingkatLayananId' => $this->TingkatLayananId,
            'AsetId' => $this->AsetId,
            'LokasiId' => $this->LokasiId,
            'UnitPengelolaId' => $keluhan?->UnitPengelolaId,
            'Judul' => $this->Judul,
            'Deskripsi' => $this->Deskripsi,
            'Prioritas' => $this->Prioritas,
            // Usulan pelapor (PRD 8.20): hanya ditampilkan, tidak pernah menjadi prioritas sendiri.
            'UsulanUrgensi' => $usulan?->value,
            'LabelUsulanUrgensi' => $usulan?->label(),
            'PrioritasUsulan' => $usulan?->prioritas()->value,
            'Status' => $this->Status,
            'Sumber' => $this->Sumber,
            'PelaporId' => $this->PelaporId,
            'NamaKategori' => $this->whenLoaded('kategoriKeluhan', fn () => $this->kategoriKeluhan?->Nama),
            'NamaTingkatLayanan' => $this->whenLoaded('tingkatLayanan', fn () => $this->tingkatLayanan?->Nama),
            'NamaAset' => $this->whenLoaded('aset', fn () => $this->aset?->Nama),
            'KodeAset' => $this->whenLoaded('aset', fn () => $this->aset?->KodeAset),
            'NamaLokasi' => $this->whenLoaded('lokasi', fn () => $this->lokasi?->Nama),
            'NamaPelapor' => $this->whenLoaded('pelapor', fn () => $this->pelapor?->Nama),
            // Bagian yang memelihara (PRD 8.21); ditampilkan "Dikelola: <unit>".
            'UnitPengelola' => $this->whenLoaded('unitPengelola', function () use ($keluhan): ?array {
                $unit = $keluhan?->unitPengelola;

                return $unit === null ? null : ['Id' => $unit->Id, 'Kode' => $unit->Kode, 'Nama' => $unit->Nama];
            }),
            'DilaporkanPada' => $this->DilaporkanPada?->toIso8601String(),
            'DiresponsPada' => $this->DiresponsPada?->toIso8601String(),
            'BatasResponsPada' => $this->BatasResponsPada?->toIso8601String(),
            'BatasPenyelesaianPada' => $this->BatasPenyelesaianPada?->toIso8601String(),
            'DiresolusikanPada' => $this->DiresolusikanPada?->toIso8601String(),
            'DitutupPada' => $this->DitutupPada?->toIso8601String(),
            'Versi' => $this->Versi,
            'RiwayatStatus' => RiwayatStatusKeluhanResource::collection($this->whenLoaded('riwayatStatus')),
        ];
    }
}
