<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Services;

use App\Domain\Langganan\Application\Actions\CatatPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;

/** Rekonsiliasi tagihan terhadap pembayarannya (22.06). */
final class LayananRekonsiliasiLangganan
{
    public function __construct(private readonly CatatPembayaranLangganan $catatPembayaran) {}

    /**
     * @return array{Diperiksa: int, Diperbaiki: list<array<string, mixed>>, Selisih: list<array<string, mixed>>}
     */
    public function jalankan(): array
    {
        $diperbaiki = [];
        $selisih = [];
        $diperiksa = 0;

        $tagihan = TagihanLangganan::query()
            ->withoutGlobalScopes()
            ->orderBy('Nomor')
            ->get();

        foreach ($tagihan as $satu) {
            $diperiksa++;
            $statusSebelum = (string) $satu->Status;
            $dibayar = $this->totalDibayar((string) $satu->Id);
            $total = (float) $satu->Total;

            if ($statusSebelum !== StatusTagihanLangganan::Dibatalkan->value) {
                $this->catatPembayaran->perbaruiStatusTagihan($satu);

                if ((string) $satu->refresh()->Status !== $statusSebelum) {
                    $diperbaiki[] = [
                        'Nomor' => (string) $satu->Nomor,
                        'StatusSebelum' => $statusSebelum,
                        'StatusSesudah' => (string) $satu->Status,
                    ];
                }
            }

            if ($dibayar > $total + 0.01) {
                $selisih[] = [
                    'Nomor' => (string) $satu->Nomor,
                    'Masalah' => 'DibayarBerlebih',
                    'Total' => $total,
                    'Dibayar' => $dibayar,
                ];
            }

            if ($statusSebelum === StatusTagihanLangganan::Dibatalkan->value && $dibayar > 0.0) {
                $selisih[] = [
                    'Nomor' => (string) $satu->Nomor,
                    'Masalah' => 'DibayarPadaTagihanDibatalkan',
                    'Total' => $total,
                    'Dibayar' => $dibayar,
                ];
            }
        }

        return ['Diperiksa' => $diperiksa, 'Diperbaiki' => $diperbaiki, 'Selisih' => $selisih];
    }

    private function totalDibayar(string $tagihanId): float
    {
        return (float) PembayaranLangganan::query()
            ->withoutGlobalScopes()
            ->where('TagihanLanggananId', $tagihanId)
            ->where('Status', StatusPembayaranLangganan::Berhasil->value)
            ->sum('Jumlah');
    }
}
