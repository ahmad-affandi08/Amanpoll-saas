<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

/**
 * Daftar `NAMA_IKON_3D` di `components/shared/Ikon3D.tsx` dan berkas PNG di
 * `public/images/3d` harus cocok (DESIGN.md 36.5 dan 37).
 *
 * TypeScript hanya memeriksa nama ikon terhadap daftar itu, bukan terhadap
 * berkasnya. Nama yang ditambah tanpa menyalin PNG-nya lolos `tsc` dan build,
 * lalu tampil sebagai gambar rusak di halaman login atau Mode Lapangan. Berkas
 * yang tersisa tanpa nama berarti aset pihak ketiga yang ikut terbit padahal
 * tidak dipakai.
 */
final class Ikon3DTersediaTest extends TestCase
{
    public function test_setiap_nama_ikon_3d_punya_berkas_png(): void
    {
        $hilang = array_values(array_filter(
            $this->namaTerdaftar(),
            fn (string $nama): bool => ! is_file(public_path("images/3d/{$nama}.png")),
        ));

        $this->assertSame([], $hilang, 'Ikon 3D terdaftar tanpa berkas di public/images/3d: '.implode(', ', $hilang));
    }

    public function test_setiap_berkas_png_ikon_3d_terdaftar_dan_berlisensi(): void
    {
        $this->assertFileExists(public_path('images/3d/LICENSE'));

        $berkas = array_map(
            fn (string $jalur): string => basename($jalur, '.png'),
            glob(public_path('images/3d/*.png')) ?: [],
        );
        $this->assertNotEmpty($berkas);

        $yatim = array_values(array_diff($berkas, $this->namaTerdaftar()));

        $this->assertSame([], $yatim, 'Berkas ikon 3D tanpa nama di NAMA_IKON_3D: '.implode(', ', $yatim));
    }

    /** @return list<string> */
    private function namaTerdaftar(): array
    {
        $sumber = (string) file_get_contents(resource_path('js/components/shared/Ikon3D.tsx'));

        if (preg_match('/NAMA_IKON_3D\s*=\s*\[(.*?)\]\s*as const/s', $sumber, $daftar) !== 1) {
            $this->fail('NAMA_IKON_3D tidak ditemukan di components/shared/Ikon3D.tsx.');
        }
        preg_match_all("/'([a-z0-9_]+)'/", $daftar[1], $nama);

        // Pola yang tidak pernah cocok akan membandingkan dua daftar kosong.
        $this->assertNotEmpty($nama[1], 'Daftar NAMA_IKON_3D tidak terbaca; pola pemeriksaannya rusak.');

        return $nama[1];
    }
}
