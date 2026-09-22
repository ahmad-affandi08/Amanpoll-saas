<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\PenyimpanIsiKonten;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiKontenPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Mengunci satu versi konten sebagai yang tayang (MARKETING.md 9). */
final class TerbitkanKonten
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly PenyimpanIsiKonten $isi,
    ) {}

    public function jalankan(KontenPemasaran $konten, ?VersiKontenPemasaran $versi = null): KontenPemasaran
    {
        return $this->transaksi->jalankan(function () use ($konten, $versi): KontenPemasaran {
            // Dibaca dari kolomnya, bukan dari relasi yang mungkin tertinggal di versi sebelumnya.
            $versi ??= $konten->VersiDrafId === null
                ? null
                : VersiKontenPemasaran::query()->whereKey($konten->VersiDrafId)->first();

            if ($versi === null) {
                throw new AturanBisnisDilanggar('Konten belum punya versi yang dapat diterbitkan.');
            }

            if ($versi->KontenPemasaranId !== $konten->Id) {
                throw new AturanBisnisDilanggar('Versi tersebut bukan milik konten ini.');
            }

            $statusSebelum = $konten->Status;

            if (! $statusSebelum->bolehPindahKe(StatusHalamanPemasaran::Terbit)) {
                throw new AturanBisnisDilanggar(
                    "Konten berstatus {$statusSebelum->value} tidak dapat langsung diterbitkan.",
                );
            }

            $versiSebelum = $konten->VersiTerbitId;

            $konten->VersiTerbitId = $versi->Id;
            $konten->Status = StatusHalamanPemasaran::Terbit;
            $konten->TerbitPada = CarbonImmutable::now();
            // Jadwal tarik yang sudah lewat tidak diwariskan ke penerbitan baru.
            if ($konten->TarikPada !== null && $konten->TarikPada->isPast()) {
                $konten->TarikPada = null;
            }
            $konten->save();

            $this->isi->buang($konten->Slug);

            $this->audit->catat(
                'KontenPemasaran.Diterbitkan',
                'KontenPemasaran',
                $konten->Id,
                dataSebelum: ['Status' => $statusSebelum->value, 'VersiTerbitId' => $versiSebelum],
                dataSesudah: [
                    'Status' => StatusHalamanPemasaran::Terbit->value,
                    'VersiTerbitId' => $versi->Id,
                    'Nomor' => $versi->Nomor,
                    'Slug' => $konten->Slug,
                ],
            );

            return $konten;
        });
    }
}
