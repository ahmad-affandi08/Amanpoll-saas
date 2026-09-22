<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Services;

use App\Domain\Langganan\Domain\Contracts\PenyediaPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Pembayaran transfer bank dengan konfirmasi manual (22.06). */
final class PenyediaPembayaranTransferManual implements PenyediaPembayaran
{
    public const KODE = 'TransferManual';

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'Transfer Bank (konfirmasi manual)';
    }

    /** @return array<string, mixed> */
    public function mulaiPembayaran(TagihanLangganan $tagihan): array
    {
        return [
            'Jenis' => 'InstruksiTransfer',
            'Bank' => (string) config('amanpoll.langganan.bank_nama', 'Bank Mandiri'),
            'NomorRekening' => (string) config('amanpoll.langganan.bank_rekening', '000-000-0000'),
            'AtasNama' => (string) config('amanpoll.langganan.bank_atas_nama', 'PT Amanpoll Indonesia'),
            'Jumlah' => (float) $tagihan->Total,
            // Berita transfer memakai nomor tagihan.
            'BeritaTransfer' => (string) $tagihan->Nomor,
            'JatuhTempo' => $tagihan->JatuhTempo->toDateString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $muatan
     * @param  array<string, string>  $header
     */
    public function webhookSah(array $muatan, array $header): bool
    {
        $rahasia = (string) config('amanpoll.langganan.rahasia_webhook', '');

        if ($rahasia === '') {
            // Tanpa rahasia yang dikonfigurasi, endpoint ditutup rapat.
            return false;
        }

        $tandaTangan = $header['x-amanpoll-tanda-tangan'] ?? '';
        $diharapkan = hash_hmac('sha256', $this->muatanKanonik($muatan), $rahasia);

        return $tandaTangan !== '' && hash_equals($diharapkan, $tandaTangan);
    }

    /** @param array<string, mixed> $muatan */
    public function terjemahkanWebhook(array $muatan): PeristiwaPembayaran
    {
        foreach (['IdPeristiwa', 'NomorTagihan', 'Jumlah'] as $wajib) {
            if (! isset($muatan[$wajib]) || $muatan[$wajib] === '') {
                throw new AturanBisnisDilanggar("Muatan webhook tidak memuat {$wajib}.");
            }
        }

        $status = StatusPembayaranLangganan::tryFrom((string) ($muatan['Status'] ?? ''))
            ?? StatusPembayaranLangganan::Berhasil;

        return new PeristiwaPembayaran(
            idPeristiwa: (string) $muatan['IdPeristiwa'],
            nomorTagihan: (string) $muatan['NomorTagihan'],
            jumlah: (float) $muatan['Jumlah'],
            status: $status,
            referensiEksternal: isset($muatan['ReferensiEksternal']) ? (string) $muatan['ReferensiEksternal'] : null,
            metode: 'Transfer Bank',
            muatanMentah: $muatan,
        );
    }

    /**
     * Penandatanganan memakai bentuk kanonik, bukan badan mentah, supaya
     * perbedaan urutan kunci atau spasi tidak membatalkan tanda tangan yang sah.
     *
     * @param  array<string, mixed>  $muatan
     */
    public function muatanKanonik(array $muatan): string
    {
        ksort($muatan);

        return (string) json_encode($muatan, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
