<?php

declare(strict_types=1);

namespace Tests\Dukungan;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\ValueObjects\PesanWhatsApp;
use App\Domain\Pemasaran\Domain\ValueObjects\StatusKirimanWhatsApp;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Str;

/** Penyedia WhatsApp palsu: mencatat apa yang dikirim, dan dapat disuruh gagal (MARKETING.md 28). */
final class PenyediaWhatsAppPalsu implements PenyediaWhatsApp
{
    /** @var list<PesanWhatsApp> */
    public array $terkirim = [];

    public bool $gagalkan = false;

    public StatusPersetujuanTemplateWa $keputusan = StatusPersetujuanTemplateWa::Diajukan;

    public ?string $alasan = null;

    public function kode(): string
    {
        return 'Palsu';
    }

    public function kirim(PesanWhatsApp $pesan): string
    {
        if ($this->gagalkan) {
            throw new AturanBisnisDilanggar('Penyedia palsu sengaja menolak.');
        }

        $this->terkirim[] = $pesan;

        return (string) Str::ulid();
    }

    /**
     * @param  list<string>  $idPesan
     * @return list<StatusKirimanWhatsApp>
     */
    public function statusKiriman(array $idPesan): array
    {
        return [];
    }

    public function ajukanTemplate(
        string $kode,
        string $bahasa,
        string $kategori,
        string $isiTeks,
    ): PersetujuanTemplateWa {
        return new PersetujuanTemplateWa($this->keputusan, 'wa-'.$kode, $this->alasan);
    }

    public function periksaTemplate(string $kode): PersetujuanTemplateWa
    {
        return new PersetujuanTemplateWa($this->keputusan, 'wa-'.$kode, $this->alasan);
    }

    /** @return list<string> */
    public function nomorTerkirim(): array
    {
        return array_map(fn (PesanWhatsApp $satu): string => $satu->kepada, $this->terkirim);
    }
}
