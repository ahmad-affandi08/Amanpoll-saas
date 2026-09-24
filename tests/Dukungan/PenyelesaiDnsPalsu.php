<?php

declare(strict_types=1);

namespace Tests\Dukungan;

use App\Shared\Infrastructure\Keamanan\PenyelesaiDns;

/**
 * Resolver DNS untuk test; dipasang untuk setiap test oleh `Tests\TestCase`.
 *
 * Host yang tidak dipetakan dianggap publik (ALAMAT_PUBLIK), sehingga test
 * lama dengan domain `.test` tetap berjalan tanpa pernah menyentuh DNS nyata.
 */
final class PenyelesaiDnsPalsu implements PenyelesaiDns
{
    public const ALAMAT_PUBLIK = '93.184.215.14';

    /** @var array<string, list<string>> */
    private array $peta = [];

    /** @var list<string> */
    public array $ditanyakan = [];

    /** @param  list<string>  $alamat  daftar kosong berarti host tidak ditemukan */
    public function petakan(string $host, array $alamat): self
    {
        $this->peta[strtolower($host)] = $alamat;

        return $this;
    }

    /** @return list<string> */
    public function alamat(string $host): array
    {
        $this->ditanyakan[] = $host;

        return $this->peta[strtolower($host)] ?? [self::ALAMAT_PUBLIK];
    }
}
