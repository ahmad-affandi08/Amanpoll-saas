<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Keamanan;

use Illuminate\Container\Attributes\Bind;

/**
 * Menerjemahkan nama host menjadi alamat IP untuk `PenjagaUrlKeluar`.
 *
 * Dijadikan antarmuka supaya test dapat memalsukannya: test tidak pernah boleh
 * bergantung pada DNS sungguhan.
 */
#[Bind(PenyelesaiDnsSistem::class)]
interface PenyelesaiDns
{
    /**
     * Seluruh alamat A dan AAAA milik host; daftar kosong bila host tidak ditemukan.
     *
     * @return list<string>
     */
    public function alamat(string $host): array;
}
