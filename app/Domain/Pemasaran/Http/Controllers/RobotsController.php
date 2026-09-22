<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * `robots.txt` dan `sitemap.xml` (MARKETING.md 1.2).
 *
 * Perbedaan antar host diselesaikan oleh grup rute, bukan oleh percabangan di
 * dalam controller: tiap host memanggil metode yang memang miliknya.
 */
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

    public function sitemap(): Response
    {
        $url = (string) $this->host->urlKanonik('/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .'  <url><loc>'.e($url).'</loc></url>'."\n"
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function teks(string $isi): Response
    {
        return response($isi, 200, ['Content-Type' => 'text/plain']);
    }
}
