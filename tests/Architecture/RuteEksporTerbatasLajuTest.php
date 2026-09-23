<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Rute;
use Tests\TestCase;

/**
 * Setiap rute ekspor harus dibatasi lajunya.
 *
 * Ekspor membaca seluruh daftar, bukan satu halaman: tanpa batas laju ia
 * menjadi cara termurah membebani basis data sekaligus menyedot seluruh isi
 * tenant satu berkas demi satu berkas. Penjaga ini ada karena rutenya
 * bertambah satu per satu, dan yang terlupakan tidak akan terlihat dari
 * halaman mana pun.
 */
class RuteEksporTerbatasLajuTest extends TestCase
{
    /** Ekspor laporan KPI memakai antrean, batasnya dipasang di rute POST-nya. */
    private const DIKECUALIKAN = ['pelaporan.ekspor.unduh'];

    public function test_setiap_rute_ekspor_memakai_throttle(): void
    {
        $tanpaBatas = [];
        $diperiksa = 0;

        foreach (Rute::getRoutes() as $rute) {
            /** @var Route $rute */
            $nama = (string) $rute->getName();

            if (! str_contains($nama, 'ekspor') || in_array($nama, self::DIKECUALIKAN, true)) {
                continue;
            }

            $diperiksa++;

            $adaThrottle = false;
            foreach ($rute->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && str_starts_with($middleware, 'throttle:')) {
                    $adaThrottle = true;
                    break;
                }
            }

            if (! $adaThrottle) {
                $tanpaBatas[] = $nama;
            }
        }

        // Tanpa ini, daftar kosong hanya membuktikan tidak ada rute yang terbaca.
        $this->assertGreaterThan(5, $diperiksa, 'Rute ekspor tidak terbaca; penjaga ini jadi hampa.');

        $this->assertSame(
            [],
            $tanpaBatas,
            'Rute ekspor ini belum dibatasi lajunya: '.implode(', ', $tanpaBatas),
        );
    }
}
