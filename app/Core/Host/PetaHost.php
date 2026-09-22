<?php

declare(strict_types=1);

namespace App\Core\Host;

/**
 * Satu-satunya tempat host dibaca (PRD 5.4, MARKETING.md 1).
 *
 * Rute, middleware, dan prop yang dikirim ke frontend semuanya menanyakan ke
 * sini, sehingga tidak ada host yang tertulis di source maupun di berkas React
 * — syarat yang diuji Gate 24.5.
 */
final class PetaHost
{
    public function publik(): ?string
    {
        return $this->bersihkan(config('amanpoll.domain.publik'));
    }

    public function dashboard(): string
    {
        return $this->bersihkan(config('amanpoll.domain.dashboard')) ?? 'localhost';
    }

    public function partner(): ?string
    {
        return $this->bersihkan(config('amanpoll.domain.partner'));
    }

    /**
     * Situs publik baru hidup setelah hostnya dikonfigurasi. Sebelum itu grup
     * rutenya tidak didaftarkan, sehingga root tetap milik dashboard dan tidak
     * ada rute yang bertabrakan.
     */
    public function situsPublikAktif(): bool
    {
        $publik = $this->publik();

        return $publik !== null && $publik !== $this->dashboard();
    }

    public function adalahHostPublik(string $host): bool
    {
        $publik = $this->publik();

        return $publik !== null && $this->tanpaWww($host) === $this->tanpaWww($publik);
    }

    /** Bentuk kanonik host publik; bentuk lain dialihkan 301 ke sini. */
    public function publikKanonik(): ?string
    {
        $publik = $this->publik();
        if ($publik === null) {
            return null;
        }

        $tanpaWww = $this->tanpaWww($publik);

        return config('amanpoll.domain.kanonik_pakai_www') === true ? 'www.'.$tanpaWww : $tanpaWww;
    }

    public function urlKanonik(string $path = '/'): ?string
    {
        $host = $this->publikKanonik();
        if ($host === null) {
            return null;
        }

        $skema = (string) config('amanpoll.domain.skema_kanonik', 'https');

        return $skema.'://'.$host.'/'.ltrim($path, '/');
    }

    /**
     * Bentuk host publik yang bukan kanonik — apex bila kanoniknya www, dan
     * sebaliknya. Bentuk ini tetap harus dilayani supaya dapat dialihkan 301;
     * host yang tidak punya rute sama sekali menjawab 404, bukan pengalihan.
     */
    public function publikNonKanonik(): ?string
    {
        $kanonik = $this->publikKanonik();
        if ($kanonik === null) {
            return null;
        }

        return str_starts_with($kanonik, 'www.') ? substr($kanonik, 4) : 'www.'.$kanonik;
    }

    /** Domain induk untuk cookie yang harus bertahan lintas subdomain. */
    public function cookieInduk(): ?string
    {
        return $this->bersihkan(config('amanpoll.domain.cookie_induk'));
    }

    private function tanpaWww(string $host): string
    {
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    private function bersihkan(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $host = strtolower(trim($nilai));

        return $host === '' || $host === 'null' ? null : $host;
    }
}
