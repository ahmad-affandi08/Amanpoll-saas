<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Enums\StatusEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PartisipasiEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VarianEksperimen;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Str;

/** Pengunjung yang sama selalu melihat varian yang sama (Gate 38.06). */
final class EksperimenPenetapanTest extends KasusEksperimen
{
    /** Inti Gate 38.06: penetapan bertahan berapa kali pun ditanyakan. */
    public function test_pengunjung_yang_sama_selalu_mendapat_varian_yang_sama(): void
    {
        $eksperimen = $this->buatEksperimen();
        $pengenal = (string) Str::ulid();
        $penetap = $this->penetap();

        $pertama = $penetap->untuk($eksperimen, $pengenal);
        $this->assertNotNull($pertama);

        foreach (range(1, 10) as $ke) {
            $this->assertSame($pertama->Id, $penetap->untuk($eksperimen, $pengenal)?->Id);
        }

        $this->assertSame(1, PartisipasiEksperimen::query()->count());
    }

    /** Penetapan tetap bertahan walau bobot variannya diubah di tengah jalan. */
    public function test_varian_tidak_berpindah_walau_bobotnya_diubah(): void
    {
        $eksperimen = $this->buatEksperimen();
        $pengenal = (string) Str::ulid();
        $penetap = $this->penetap();

        $pertama = $penetap->untuk($eksperimen, $pengenal);
        $this->assertNotNull($pertama);

        VarianEksperimen::query()
            ->where('EksperimenPemasaranId', $eksperimen->Id)
            ->where('Id', '!=', $pertama->Id)
            ->update(['Bobot' => 1000]);

        $this->assertSame($pertama->Id, $penetap->untuk($eksperimen->fresh(), $pengenal)?->Id);
    }

    /** Eksperimen yang dijeda tetap menjawab peserta lama, tetapi tidak menerima yang baru. */
    public function test_eksperimen_dijeda_menjawab_peserta_lama_tanpa_menerima_yang_baru(): void
    {
        $eksperimen = $this->buatEksperimen();
        $lama = (string) Str::ulid();
        $penetap = $this->penetap();

        $varian = $penetap->untuk($eksperimen, $lama);
        $this->assertNotNull($varian);

        $eksperimen->Status = StatusEksperimen::Dijeda;
        $eksperimen->save();

        $this->assertSame($varian->Id, $penetap->untuk($eksperimen, $lama)?->Id);
        $this->assertNull($penetap->untuk($eksperimen, (string) Str::ulid()));
        $this->assertSame(1, PartisipasiEksperimen::query()->count());
    }

    public function test_eksperimen_draf_belum_menetapkan_siapa_pun(): void
    {
        $eksperimen = $this->buatEksperimen(status: StatusEksperimen::Draf);

        $this->assertNull($this->penetap()->untuk($eksperimen, (string) Str::ulid()));
        $this->assertSame(0, PartisipasiEksperimen::query()->count());
    }

    /** Pemilihan pertama deterministik: kesimpulan yang sama tanpa menyentuh basis data. */
    public function test_pemilihan_pertama_deterministik(): void
    {
        $eksperimen = $this->buatEksperimen();
        $pengenal = (string) Str::ulid();
        $penetap = $this->penetap();

        $pilihan = $penetap->pilih($eksperimen, $pengenal);

        foreach (range(1, 5) as $ke) {
            $this->assertSame($pilihan->Id, $penetap->pilih($eksperimen, $pengenal)->Id);
        }

        $this->assertSame(0, PartisipasiEksperimen::query()->count());
    }

    /** Dua varian berbobot sama harus benar-benar terpakai keduanya, bukan selalu yang pertama. */
    public function test_kedua_varian_benar_benar_terpakai(): void
    {
        $eksperimen = $this->buatEksperimen();
        $penetap = $this->penetap();

        foreach (range(1, 60) as $ke) {
            $penetap->untuk($eksperimen, (string) Str::ulid());
        }

        $sebaran = PartisipasiEksperimen::query()
            ->selectRaw('VarianEksperimenId, COUNT(*) as Jumlah')
            ->groupBy('VarianEksperimenId')
            ->pluck('Jumlah')
            ->all();

        $this->assertCount(2, $sebaran);
        $this->assertGreaterThan(5, min($sebaran));
    }

    /** Varian berbobot nol tidak pernah ditetapkan kepada siapa pun. */
    public function test_varian_berbobot_nol_tidak_pernah_ditetapkan(): void
    {
        $eksperimen = $this->buatEksperimen();
        $dimatikan = $this->varian($eksperimen, 'B');
        $dimatikan->Bobot = 0;
        $dimatikan->save();

        $penetap = $this->penetap();

        foreach (range(1, 30) as $ke) {
            $penetap->untuk($eksperimen->fresh(), (string) Str::ulid());
        }

        $this->assertSame(0, PartisipasiEksperimen::query()
            ->where('VarianEksperimenId', $dimatikan->Id)->count());
        $this->assertSame(30, PartisipasiEksperimen::query()->count());
    }

    public function test_seluruh_varian_berbobot_nol_ditolak(): void
    {
        $eksperimen = $this->buatEksperimen();
        VarianEksperimen::query()->where('EksperimenPemasaranId', $eksperimen->Id)->update(['Bobot' => 0]);

        $this->expectException(AturanBisnisDilanggar::class);

        $this->penetap()->untuk($eksperimen->fresh(), (string) Str::ulid());
    }

    public function test_eksperimen_tanpa_varian_ditolak(): void
    {
        $eksperimen = $this->buatEksperimen();
        VarianEksperimen::query()->where('EksperimenPemasaranId', $eksperimen->Id)->delete();

        $this->expectException(AturanBisnisDilanggar::class);

        $this->penetap()->untuk($eksperimen->fresh(), (string) Str::ulid());
    }

    /** Dua eksperimen berbeda menetapkan pengunjung yang sama secara mandiri. */
    public function test_penetapan_terpisah_antar_eksperimen(): void
    {
        $pertama = $this->buatEksperimen();
        $pengenal = (string) Str::ulid();

        $kedua = $this->buatEksperimen(kode: 'cta-q1');

        $penetap = $this->penetap();
        $penetap->untuk($pertama, $pengenal);
        $penetap->untuk($kedua, $pengenal);

        $this->assertSame(2, PartisipasiEksperimen::query()
            ->where('PengenalPengunjung', $pengenal)->count());
    }

    /** Kode eksperimen ikut dihash; tanpa itu pengunjung jatuh ke varian pertama di semua eksperimen. */
    public function test_penetapan_tidak_berkorelasi_antar_eksperimen(): void
    {
        $pertama = $this->buatEksperimen();
        $kedua = $this->buatEksperimen(kode: 'cta-q1');
        $penetap = $this->penetap();

        $berbeda = 0;

        foreach (range(1, 40) as $ke) {
            $pengenal = (string) Str::ulid();

            if ($penetap->pilih($pertama, $pengenal)->Kode !== $penetap->pilih($kedua, $pengenal)->Kode) {
                $berbeda++;
            }
        }

        $this->assertGreaterThan(5, $berbeda, 'Kedua eksperimen selalu menetapkan varian yang sama.');
    }
}
