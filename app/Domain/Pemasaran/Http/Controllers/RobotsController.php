<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/** `robots.txt` dan `sitemap.xml` (MARKETING.md 1.2). */
final class RobotsController extends Controller
{
    public function __construct(private readonly PetaHost $host) {}

    /** Host publik: mengundang perayapan dan menunjuk peta situsnya. */
    public function robotsPublik(): Response
    {
        $peta = $this->host->urlKanonik('sitemap.xml');

        return $this->teks("User-agent: *\nAllow: /\nSitemap: {$peta}\n");
    }

    /** Host sistem: menutup seluruh perayapan. */
    public function robotsTertutup(): Response
    {
        return $this->teks("User-agent: *\nDisallow: /\n");
    }

    /** Peta situs berisi akar dan setiap halaman yang benar-benar terbit. */
    public function sitemap(): Response
    {
        $jalur = HalamanPemasaran::query()
            ->where('Status', StatusHalamanPemasaran::Terbit->value)
            ->whereNotNull('VersiTerbitId')
            ->where('NoIndex', false)
            ->orderBy('Slug')
            ->pluck('Slug')
            ->all();

        $url = array_values(array_unique(['/', ...$jalur]));

        $baris = array_map(
            fn (string $satu): string => '  <url><loc>'.e((string) $this->host->urlKanonik($satu)).'</loc></url>',
            $url,
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode("\n", $baris)."\n"
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function teks(string $isi): Response
    {
        return response($isi, 200, ['Content-Type' => 'text/plain']);
    }
}
