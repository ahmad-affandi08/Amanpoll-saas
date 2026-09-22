<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Application\Services\PenjadwalWhatsAppPemasaran;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateWhatsAppPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Menjadwalkan satu pesan WhatsApp; consent dan cap tetap diperiksa pengirimnya saat berangkat. */
final class TindakanKirimWhatsApp implements TindakanOtomasi
{
    public function __construct(private readonly PenjadwalWhatsAppPemasaran $penjadwal) {}

    public function kode(): string
    {
        return 'KirimWhatsApp';
    }

    public function label(): string
    {
        return 'Kirim WhatsApp';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return ['TemplateKode' => ['required', 'string', 'exists:TemplateWhatsAppPemasaran,Kode']];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $prospek = $konteks->wajibProspek();
        $kode = (string) ($konfigurasi['TemplateKode'] ?? '');

        $template = TemplateWhatsAppPemasaran::query()->where('Kode', $kode)->first();

        if ($template === null) {
            throw new AturanBisnisDilanggar("Template WhatsApp {$kode} tidak ada.");
        }

        $pengiriman = $this->penjadwal->jadwalkan(
            $prospek,
            $template,
            CarbonImmutable::now(),
            $konteks->kunciLangkah,
        );

        if ($pengiriman === null) {
            return "Prospek {$prospek->Nama} belum punya nomor WhatsApp; tidak ada yang dijadwalkan.";
        }

        return "WhatsApp {$kode} dijadwalkan untuk {$pengiriman->Nomor}.";
    }
}
