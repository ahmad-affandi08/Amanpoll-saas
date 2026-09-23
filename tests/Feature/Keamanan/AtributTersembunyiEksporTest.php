<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use RuntimeException;
use Tests\TestCase;

/**
 * `$hidden` pada model tidak menahan apa pun di jalur ekspor.
 *
 * Ia hanya bekerja saat serialisasi, sedangkan KolomEkspor membaca lewat
 * getAttribute() yang tidak pernah menyentuh daftar itu. Sebelum penjaga ini
 * ada, model yang menyembunyikan HashKunci tetap menyerahkannya bulat-bulat
 * ke berkas begitu ada yang menuliskan kolomnya -- dan berkas ekspor beredar
 * di luar aplikasi, tidak dapat ditarik kembali.
 */
class AtributTersembunyiEksporTest extends TestCase
{
    public function test_model_kunci_api_memang_menyembunyikan_hash_kuncinya(): void
    {
        // Pembanding: tanpa ini, test di bawah bisa lulus hanya karena
        // atributnya kebetulan tidak pernah ada di daftar $hidden.
        $this->assertContains('HashKunci', (new KunciApi)->getHidden());
    }

    public function test_kolom_yang_menyebut_atribut_tersembunyi_ditolak(): void
    {
        $kunci = new KunciApi;
        $kunci->setAttribute('HashKunci', 'rahasia-yang-tidak-boleh-keluar');

        $kolom = KolomEkspor::atribut('Hash', 'HashKunci');

        try {
            $kolom->nilai($kunci);
            $this->fail('Kolom yang menyebut atribut $hidden seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('HashKunci', $e->getMessage());
            $this->assertStringNotContainsString(
                'rahasia-yang-tidak-boleh-keluar',
                $e->getMessage(),
                'Pesan galat tidak boleh ikut membocorkan nilainya.',
            );
        }
    }

    public function test_kolom_tanggal_ikut_dijaga(): void
    {
        $kunci = new KunciApi;

        try {
            KolomEkspor::tanggal('Hash', 'HashKunci')->nilai($kunci);
            $this->fail('Kolom tanggal yang menyebut atribut $hidden seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('HashKunci', $e->getMessage());
        }
    }

    /** Pembanding: atribut biasa tetap lewat, supaya penjaganya tidak menolak segalanya. */
    public function test_atribut_biasa_tetap_dapat_diekspor(): void
    {
        $kunci = new KunciApi;
        $kunci->setAttribute('Nama', 'Kunci Integrasi SIMRS');

        $this->assertSame('Kunci Integrasi SIMRS', KolomEkspor::atribut('Nama', 'Nama')->nilai($kunci));
    }
}
