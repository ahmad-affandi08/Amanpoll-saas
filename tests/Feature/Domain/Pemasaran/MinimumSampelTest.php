<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PenghitungHasilEksperimen;
use App\Domain\Pemasaran\Application\Services\PenilaiEksperimen;
use App\Domain\Pemasaran\Domain\Enums\MetrikEksperimen;
use App\Domain\Pemasaran\Domain\Enums\StatusEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HasilEksperimen;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Pemenang tidak dapat dinyatakan sebelum sampel minimum tercapai (Gate 38.06). */
final class MinimumSampelTest extends KasusEksperimen
{
    /** Inti Gate 38.06: selisih besar sekalipun tidak melahirkan pemenang di bawah ambang. */
    public function test_selisih_besar_tidak_melahirkan_pemenang_di_bawah_ambang(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 100);

        // Sepuluh peserta tiap varian, dan varian B menang telak: sembilan klik lawan satu.
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 10), 1);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 10), 9);

        $penilaian = app(PenilaiEksperimen::class)->nilai($eksperimen);

        $this->assertFalse($penilaian->bolehDinyatakan);
        $this->assertNull($penilaian->pemenang);
        $this->assertSame(PenilaiEksperimen::BELUM_CUKUP_SAMPEL, $penilaian->alasan);
    }

    /** Tombol nyatakan pemenang pun tidak dapat melewati ambangnya. */
    public function test_menyatakan_pemenang_di_bawah_ambang_ditolak(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 100);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 10), 1);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 10), 9);

        try {
            app(PenilaiEksperimen::class)->nyatakanPemenang($eksperimen);
            $this->fail('Pemenang seharusnya belum dapat dinyatakan.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertSame(PenilaiEksperimen::BELUM_CUKUP_SAMPEL, $galat->getMessage());
            $this->assertNull($eksperimen->fresh()?->PemenangVarianId);
        }
    }

    /** Satu varian cukup sampel tetapi yang lain belum: tetap tidak ada pemenang. */
    public function test_satu_varian_cukup_sampel_belum_cukup(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 20);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 25), 5);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 5), 5);

        $penilaian = app(PenilaiEksperimen::class)->nilai($eksperimen);

        $this->assertFalse($penilaian->bolehDinyatakan);
        $this->assertSame(PenilaiEksperimen::BELUM_CUKUP_SAMPEL, $penilaian->alasan);
    }

    /** Begitu keduanya cukup, pemenangnya dinyatakan beserta alasannya. */
    public function test_pemenang_dinyatakan_setelah_ambang_tercapai(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 20);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 25), 5);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 25), 20);

        $pemenang = app(PenilaiEksperimen::class)->nyatakanPemenang($eksperimen);
        $segar = $eksperimen->fresh();

        $this->assertSame('B', $pemenang->Kode);
        $this->assertSame($pemenang->Id, $segar?->PemenangVarianId);
        $this->assertSame(StatusEksperimen::Selesai, $segar?->Status);
        $this->assertNotNull($segar?->DiputuskanPada);
    }

    /** Metrik yang sama persis bukan kemenangan, walau sampelnya sudah cukup. */
    public function test_metrik_yang_seri_tidak_melahirkan_pemenang(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 10);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 20), 10);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 20), 10);

        $penilaian = app(PenilaiEksperimen::class)->nilai($eksperimen);

        $this->assertFalse($penilaian->bolehDinyatakan);
        $this->assertSame(PenilaiEksperimen::TIDAK_ADA_SELISIH, $penilaian->alasan);
    }

    public function test_eksperimen_satu_varian_tidak_dapat_menang(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 1);
        $this->varian($eksperimen, 'B')->delete();
        $this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 10);

        $penilaian = app(PenilaiEksperimen::class)->nilai($eksperimen->fresh());

        $this->assertFalse($penilaian->bolehDinyatakan);
    }

    /** Ambang dibaca dari eksperimennya, bukan angka tetap di kode. */
    public function test_ambang_dibaca_dari_eksperimennya(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 5);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 6), 1);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 6), 5);

        $this->assertTrue(app(PenilaiEksperimen::class)->nilai($eksperimen)->bolehDinyatakan);

        $eksperimen->MinimumSampel = 50;
        $eksperimen->save();

        $this->assertFalse(app(PenilaiEksperimen::class)->nilai($eksperimen->fresh())->bolehDinyatakan);
    }

    /** Hasil menyimpan pembilang dan penyebutnya, sehingga rasionya dapat ditelusuri. */
    public function test_hasil_menyimpan_pembilang_dan_penyebutnya(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 5);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 8), 2);

        app(PenghitungHasilEksperimen::class)->hitung($eksperimen);

        $baris = HasilEksperimen::query()
            ->where('VarianEksperimenId', $this->varian($eksperimen, 'B')->Id)
            ->where('Metrik', MetrikEksperimen::Ctr->value)
            ->firstOrFail();

        $this->assertSame(8, $baris->Penyebut);
        $this->assertSame(2, $baris->Pembilang);
        $this->assertSame('0.2500', $baris->Rasio);
    }

    /** Menghitung ulang memperbarui barisnya, bukan menumpuk baris kedua. */
    public function test_perhitungan_ulang_tidak_menggandakan_barisnya(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 5);
        $this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 4);

        $penghitung = app(PenghitungHasilEksperimen::class);
        $penghitung->hitung($eksperimen);
        $pertama = HasilEksperimen::query()->count();
        $penghitung->hitung($eksperimen);

        $this->assertSame($pertama, HasilEksperimen::query()->count());
        $this->assertSame(count(MetrikEksperimen::cases()) * 2, $pertama);
    }

    /** Varian tanpa peserta berpenyebut nol, bukan rasio yang dikarang. */
    public function test_varian_tanpa_peserta_berasio_nol(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 5);

        app(PenghitungHasilEksperimen::class)->hitung($eksperimen);

        $baris = HasilEksperimen::query()
            ->where('VarianEksperimenId', $this->varian($eksperimen, 'A')->Id)
            ->where('Metrik', MetrikEksperimen::Ctr->value)
            ->firstOrFail();

        $this->assertSame(0, $baris->Penyebut);
        $this->assertSame('0.0000', $baris->Rasio);
    }

    /** Metrik utama yang dipilihlah yang menentukan pemenangnya, bukan metrik lain. */
    public function test_metrik_utama_yang_menentukan_pemenangnya(): void
    {
        $eksperimen = $this->buatEksperimen(minimumSampel: 5, metrik: MetrikEksperimen::KonversiFormulir);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'A'), 6), 6);
        $this->catatKlik($this->daftarkanKeVarian($eksperimen, $this->varian($eksperimen, 'B'), 6), 0);

        $penilaian = app(PenilaiEksperimen::class)->nilai($eksperimen);

        // CTR-nya berbeda jauh, tetapi metrik utamanya konversi formulir yang sama-sama nol.
        $this->assertFalse($penilaian->bolehDinyatakan);
        $this->assertSame(PenilaiEksperimen::TIDAK_ADA_SELISIH, $penilaian->alasan);
    }
}
