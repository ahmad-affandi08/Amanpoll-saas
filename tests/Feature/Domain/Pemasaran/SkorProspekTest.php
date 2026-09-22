<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Application\Services\LayananAturanSkorProspek;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PenghitungSkorProspek;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanSkorProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SkorProspek;
use App\Domain\Pemasaran\Jobs\HitungSkorProspek;
use Illuminate\Support\Str;

/** Skor prospek (MARKETING.md 5.4, 36). */
final class SkorProspekTest extends KasusProspek
{
    public function test_skor_dihitung_dari_peristiwa_pengunjungnya(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();

        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::DEMO_DIMULAI);

        $total = app(PenghitungSkorProspek::class)->hitungUlang($prospek);

        // HargaDilihat 5 + DemoDimulai 8 + FormulirDikirim 10, ditambah AktifTigaHari 10.
        $this->assertSame(33, $total);
        $this->assertSame(33, (int) Prospek::query()->whereKey($prospek->Id)->value('Skor'));
    }

    public function test_satu_peristiwa_hanya_dihitung_sekali(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();

        foreach (range(1, 5) as $ke) {
            $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);
        }

        app(PenghitungSkorProspek::class)->hitungUlang($prospek);

        $this->assertSame(
            1,
            SkorProspek::query()
                ->where('ProspekId', $prospek->Id)
                ->where('Peristiwa', KatalogPeristiwaPemasaran::HARGA_DILIHAT)
                ->count(),
        );
    }

    public function test_bobot_dibaca_dari_tabel_aturan_bukan_dari_kode(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        // Hanya menyisakan satu aturan supaya angkanya persis terbaca.
        AturanSkorProspek::query()
            ->where('Peristiwa', '!=', KatalogPeristiwaPemasaran::HARGA_DILIHAT)
            ->update(['Aktif' => false]);
        AturanSkorProspek::query()
            ->where('Peristiwa', KatalogPeristiwaPemasaran::HARGA_DILIHAT)
            ->update(['Bobot' => 99]);
        app(LayananAturanSkorProspek::class)->buangCache();

        $this->assertSame(99, app(PenghitungSkorProspek::class)->hitungUlang($prospek));
    }

    public function test_perhitungan_ulang_tidak_menumpuk(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        $penghitung = app(PenghitungSkorProspek::class);
        $pertama = $penghitung->hitungUlang($prospek);
        $kedua = $penghitung->hitungUlang($prospek);

        $this->assertSame($pertama, $kedua);
    }

    public function test_rincian_skor_menjelaskan_asal_angkanya(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        app(PenghitungSkorProspek::class)->hitungUlang($prospek);

        $rincian = SkorProspek::query()->where('ProspekId', $prospek->Id)->pluck('Bobot', 'Peristiwa');

        $this->assertSame(5, (int) $rincian[KatalogPeristiwaPemasaran::HARGA_DILIHAT]);
    }

    public function test_prospek_yang_lama_tidak_aktif_mendapat_bobot_negatif(): void
    {
        [$prospek] = $this->buatProspek();
        $prospek->AktivitasTerakhirPada = now()->subDays(30);
        $prospek->save();

        app(PenghitungSkorProspek::class)->hitungUlang($prospek);

        $this->assertSame(
            -20,
            (int) SkorProspek::query()
                ->where('ProspekId', $prospek->Id)
                ->where('Peristiwa', 'TidakAktifEmpatBelasHari')
                ->value('Bobot'),
        );
    }

    public function test_ambang_qualified_dibaca_dari_konfigurasi(): void
    {
        [$prospek] = $this->buatProspek();
        $prospek->Skor = 41;

        $penghitung = app(PenghitungSkorProspek::class);
        $this->assertTrue($penghitung->qualified($prospek));

        app(LayananKonfigurasiPemasaran::class)->simpan(
            KatalogKonfigurasiPemasaran::SKOR_AMBANG_QUALIFIED,
            100,
        );

        $this->assertFalse($penghitung->qualified($prospek));
    }

    public function test_pekerjaan_menghitung_skor_prospek_yang_ditunjuk(): void
    {
        [$prospek, $pengenal] = $this->buatProspek();
        $this->peristiwa($pengenal, KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        (new HitungSkorProspek($prospek->Id))->handle(app(PenghitungSkorProspek::class));

        $this->assertGreaterThan(0, (int) Prospek::query()->whereKey($prospek->Id)->value('Skor'));
    }

    public function test_pekerjaan_tidak_gagal_untuk_prospek_yang_sudah_terhapus(): void
    {
        (new HitungSkorProspek((string) Str::ulid()))->handle(app(PenghitungSkorProspek::class));

        $this->assertSame(0, SkorProspek::query()->count());
    }

    /** @return array{0: Prospek, 1: string} */
    private function buatProspek(): array
    {
        $pengenal = (string) Str::ulid();

        $prospek = app(CatatProspek::class)->jalankan(
            ['Nama' => 'Budi', 'Email' => 'budi@contoh.test'],
            SumberProspek::Website,
            $pengenal,
        );

        return [$prospek, $pengenal];
    }

    private function peristiwa(string $pengenal, string $jenis): void
    {
        app(PerekamEventPemasaran::class)->catat($jenis, pengenalPengunjung: $pengenal);
    }
}
