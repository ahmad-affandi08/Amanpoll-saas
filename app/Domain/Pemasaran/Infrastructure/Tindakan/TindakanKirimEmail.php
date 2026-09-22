<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Tindakan;

use App\Domain\Pemasaran\Application\Services\PenjadwalEmailPemasaran;
use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Menjadwalkan satu email dari template; consent tetap diperiksa pengirimnya saat berangkat. */
final class TindakanKirimEmail implements TindakanOtomasi
{
    public function __construct(private readonly PenjadwalEmailPemasaran $penjadwal) {}

    public function kode(): string
    {
        return 'KirimEmail';
    }

    public function label(): string
    {
        return 'Kirim email';
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        return ['TemplateKode' => ['required', 'string', 'exists:TemplateEmailPemasaran,Kode']];
    }

    /** @param array<string, mixed> $konfigurasi */
    public function jalankan(KonteksOtomasi $konteks, array $konfigurasi): string
    {
        $prospek = $konteks->wajibProspek();
        $kode = (string) ($konfigurasi['TemplateKode'] ?? '');

        $template = TemplateEmailPemasaran::query()->where('Kode', $kode)->where('Aktif', true)->first();

        if ($template === null) {
            throw new AturanBisnisDilanggar("Template email {$kode} tidak ada atau nonaktif.");
        }

        $this->penjadwal->jadwalkan($prospek, $template, CarbonImmutable::now(), $konteks->kunciLangkah);

        return "Email {$kode} dijadwalkan untuk {$prospek->Email}.";
    }
}
