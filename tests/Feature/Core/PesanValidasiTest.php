<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/** Pesan validasi bawaan terbaca manusia, bukan kunci `validation.*`. */
final class PesanValidasiTest extends TestCase
{
    public function test_pesan_bawaan_berbahasa_indonesia_dengan_nama_isian_sebagai_kata(): void
    {
        $galat = Validator::make(
            ['JumlahAset' => 'banyak'],
            ['JumlahAset' => ['required', 'integer'], 'KataSandi' => ['required']],
        )->errors();

        $this->assertSame('Jumlah aset harus berupa bilangan bulat.', $galat->first('JumlahAset'));
        $this->assertSame('Kata sandi wajib diisi.', $galat->first('KataSandi'));
    }

    public function test_isian_baris_disebut_dengan_nomor_barisnya(): void
    {
        $galat = Validator::make(
            ['Detail' => [['HargaSatuan' => 1000], ['HargaSatuan' => '']]],
            ['Detail.*.HargaSatuan' => ['required']],
        )->errors();

        $this->assertSame('Harga satuan baris 2 wajib diisi.', $galat->first('Detail.1.HargaSatuan'));
    }

    public function test_label_isian_dari_pemanggil_tetap_didahulukan(): void
    {
        $galat = Validator::make(
            ['Detail' => [['HargaSatuan' => '']]],
            ['Detail.*.HargaSatuan' => ['required']],
            [],
            ['Detail.*.HargaSatuan' => 'harga per unit'],
        )->errors();

        $this->assertSame('Harga per unit wajib diisi.', $galat->first('Detail.0.HargaSatuan'));
    }
}
