<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Services;

use App\Domain\Langganan\Domain\Contracts\PenyediaPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\ValueObjects\InstruksiPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PesananPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Request;

/**
 * Pembayaran transfer bank dengan konfirmasi manual (22.06, PRD 8.23).
 *
 * Rekening tujuan diatur di konsol platform; selama baris konsolnya belum aktif,
 * nilainya jatuh ke konfigurasi. Webhook-nya (dipakai alat konfirmasi internal)
 * tetap ditandatangani rahasia dari konfigurasi.
 */
final class PenyediaPembayaranTransferManual implements DeskripsiPenyediaLayanan, PenyediaPembayaran
{
    public const KODE = 'TransferManual';

    public function __construct(private readonly PembacaKredensialPenyedia $pembaca) {}

    public function kategori(): KategoriPenyediaLayanan
    {
        return KategoriPenyediaLayanan::Pembayaran;
    }

    public function kode(): string
    {
        return self::KODE;
    }

    public function nama(): string
    {
        return 'Transfer Bank (konfirmasi manual)';
    }

    public function keterangan(): string
    {
        return 'Tenant mentransfer ke rekening perusahaan; pelunasan dikonfirmasi tim keuangan.';
    }

    public function resmi(): bool
    {
        return true;
    }

    public function mendukungModeUji(): bool
    {
        return false;
    }

    /** @return list<IsianKredensial> */
    public function isian(): array
    {
        return [
            new IsianKredensial('Bank', 'Nama bank', petunjuk: 'Mis. Bank Mandiri.'),
            new IsianKredensial('NomorRekening', 'Nomor rekening'),
            new IsianKredensial('AtasNama', 'Atas nama'),
        ];
    }

    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
    {
        $kredensial = $this->pembaca->untuk(KategoriPenyediaLayanan::Pembayaran, self::KODE);

        return InstruksiPembayaran::rincian([
            'Jenis' => 'InstruksiTransfer',
            'Bank' => $kredensial?->ambilAtau('Bank')
                ?: (string) config('amanpoll.langganan.bank_nama', 'Bank Mandiri'),
            'NomorRekening' => $kredensial?->ambilAtau('NomorRekening')
                ?: (string) config('amanpoll.langganan.bank_rekening', '000-000-0000'),
            'AtasNama' => $kredensial?->ambilAtau('AtasNama')
                ?: (string) config('amanpoll.langganan.bank_atas_nama', 'PT Amanpoll Indonesia'),
            'Jumlah' => (float) $pesanan->jumlah,
            // Berita transfer memakai nomor tagihan.
            'BeritaTransfer' => (string) $tagihan->Nomor,
            'JatuhTempo' => $tagihan->JatuhTempo->toDateString(),
        ]);
    }

    public function webhookSah(Request $permintaan): bool
    {
        $rahasia = (string) config('amanpoll.langganan.rahasia_webhook', '');

        if ($rahasia === '') {
            // Tanpa rahasia yang dikonfigurasi, endpoint ditutup rapat.
            return false;
        }

        $tandaTangan = (string) $permintaan->header('x-amanpoll-tanda-tangan', '');
        $diharapkan = hash_hmac('sha256', $this->muatanKanonik($this->muatan($permintaan)), $rahasia);

        return $tandaTangan !== '' && hash_equals($diharapkan, $tandaTangan);
    }

    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
    {
        $muatan = $this->muatan($permintaan);

        foreach (['IdPeristiwa', 'NomorTagihan', 'Jumlah'] as $wajib) {
            if (! isset($muatan[$wajib]) || $muatan[$wajib] === '') {
                throw new AturanBisnisDilanggar("Muatan webhook tidak memuat {$wajib}.");
            }
        }

        $status = StatusPembayaranLangganan::tryFrom($this->teks($muatan['Status'] ?? ''))
            ?? StatusPembayaranLangganan::Berhasil;

        return new PeristiwaPembayaran(
            idPeristiwa: $this->teks($muatan['IdPeristiwa']),
            nomorTagihan: $this->teks($muatan['NomorTagihan']),
            jumlah: is_numeric($muatan['Jumlah']) ? (float) $muatan['Jumlah'] : 0.0,
            status: $status,
            referensiEksternal: isset($muatan['ReferensiEksternal']) ? $this->teks($muatan['ReferensiEksternal']) : null,
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

    /** @return array<string, mixed> */
    private function muatan(Request $permintaan): array
    {
        $muatan = [];
        foreach ($permintaan->json()->all() as $kunci => $nilai) {
            $muatan[(string) $kunci] = $nilai;
        }

        return $muatan;
    }

    private function teks(mixed $nilai): string
    {
        return is_scalar($nilai) ? (string) $nilai : '';
    }
}
