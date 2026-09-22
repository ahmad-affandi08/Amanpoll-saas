<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Host\PetaHost;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use Carbon\CarbonImmutable;

/** Mengisi variabel template; nilai tak dikenal menjadi teks kosong, bukan kurung kurawal mentah di badan surat (MARKETING.md 15). */
final class PerenderTemplateEmail
{
    public function __construct(
        private readonly PetaHost $host,
        private readonly LayananLangganan $layananLangganan,
    ) {}

    /** @return array{Subjek: string, IsiHtml: string, IsiTeks: string|null} */
    public function render(TemplateEmailPemasaran $template, Prospek $prospek): array
    {
        $variabel = $this->variabel($prospek);

        return [
            'Subjek' => $this->ganti($template->Subjek, $variabel),
            'IsiHtml' => $this->ganti($template->IsiHtml, $variabel),
            'IsiTeks' => $template->IsiTeks === null ? null : $this->ganti($template->IsiTeks, $variabel),
        ];
    }

    /** @return array<string, string> */
    public function variabel(Prospek $prospek): array
    {
        $dashboard = $this->host->dashboard();
        $publik = $this->host->urlKanonik('/');

        return [
            'Nama' => (string) $prospek->Nama,
            'NamaPerusahaan' => (string) ($prospek->organisasiProspek->Nama ?? ''),
            'Industri' => (string) ($prospek->organisasiProspek->Industri ?? ''),
            'HariTrialTersisa' => (string) $this->hariTrialTersisa($prospek),
            'LinkDashboard' => 'https://'.$dashboard,
            'LinkDemo' => $publik === null ? '' : rtrim($publik, '/').'/demo',
            'LinkHarga' => $publik === null ? '' : rtrim($publik, '/').'/harga',
        ];
    }

    /** @return list<string> */
    public function variabelDikenal(): array
    {
        return ['Nama', 'NamaPerusahaan', 'Industri', 'HariTrialTersisa', 'LinkDashboard', 'LinkDemo', 'LinkHarga'];
    }

    /**
     * Variabel yang ditulis di naskah tetapi tidak dikenal; dipakai untuk menolak salah ketik saat menyimpan.
     *
     * @return list<string>
     */
    public function variabelAsing(string ...$naskah): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', implode("\n", $naskah), $cocok);

        return array_values(array_unique(array_diff($cocok[1], $this->variabelDikenal())));
    }

    /** @param array<string, string> $variabel */
    private function ganti(string $naskah, array $variabel): string
    {
        $kunci = array_map(static fn (string $nama): string => '{{'.$nama.'}}', array_keys($variabel));
        $naskah = str_replace($kunci, array_values($variabel), $naskah);

        // Variabel yang tidak dikenal dibuang, bukan ditinggalkan mentah di badan surat.
        return (string) preg_replace('/\{\{\s*[A-Za-z0-9_]+\s*\}\}/', '', $naskah);
    }

    private function hariTrialTersisa(Prospek $prospek): int
    {
        if ($prospek->OrganisasiId === null) {
            return 0;
        }

        $trial = Trial::query()->where('OrganisasiId', $prospek->OrganisasiId)->first();
        $akhir = $trial?->BerakhirPada;

        if ($akhir === null) {
            $langganan = $this->layananLangganan->untukOrganisasi($prospek->OrganisasiId);
            $akhir = $langganan?->UjiCobaSampai === null
                ? null
                : CarbonImmutable::parse($langganan->UjiCobaSampai);
        }

        if ($akhir === null) {
            return 0;
        }

        $sisa = (int) ceil(CarbonImmutable::now()->diffInDays($akhir, absolute: false));

        return max($sisa, 0);
    }
}
