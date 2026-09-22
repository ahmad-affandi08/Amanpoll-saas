<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PembagiBobotAttribution;
use App\Domain\Pemasaran\Domain\Enums\ModelAttribution;
use App\Domain\Pemasaran\Domain\ValueObjects\BobotSentuhan;
use App\Domain\Pemasaran\Domain\ValueObjects\Sentuhan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/** Jumlah bobot seluruh sentuhan satu konversi selalu tepat satu (Gate 38.10). */
final class BobotSentuhanTest extends TestCase
{
    private CarbonImmutable $konversi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->konversi = CarbonImmutable::parse('2026-06-30 12:00:00');
    }

    private function pembagi(): PembagiBobotAttribution
    {
        return app(PembagiBobotAttribution::class);
    }

    /** @return list<Sentuhan> */
    private function sentuhan(int $jumlah, int $jarakHari = 3): array
    {
        $hasil = [];

        foreach (range(1, $jumlah) as $ke) {
            $hasil[] = new Sentuhan(
                sumber: 'sumber-'.$ke,
                medium: 'cpc',
                kampanye: null,
                kampanyeId: null,
                pada: $this->konversi->subDays(($jumlah - $ke + 1) * $jarakHari),
            );
        }

        return $hasil;
    }

    /**
     * Inti Gate 38.10. Diuji pada tiap model dan tiap panjang perjalanan,
     * termasuk yang pembagiannya tidak bulat seperti tiga atau tujuh sentuhan.
     */
    public function test_jumlah_bobot_selalu_tepat_satu(): void
    {
        foreach (ModelAttribution::cases() as $model) {
            foreach ([1, 2, 3, 4, 5, 7, 11, 13, 50] as $jumlah) {
                $bobot = $this->pembagi()->bagi($this->sentuhan($jumlah), $model, $this->konversi, 7);

                $this->assertCount($jumlah, $bobot);
                $this->assertSame(
                    BobotSentuhan::SKALA,
                    array_sum(array_map(fn (BobotSentuhan $satu): int => $satu->bagian, $bobot)),
                    "Model {$model->value} dengan {$jumlah} sentuhan tidak berjumlah tepat satu.",
                );
            }
        }
    }

    /** Uang yang dibagikan tidak boleh melebihi uang yang benar-benar masuk. */
    public function test_porsi_uang_berjumlah_utuh(): void
    {
        foreach (ModelAttribution::cases() as $model) {
            $bobot = $this->pembagi()->bagi($this->sentuhan(7), $model, $this->konversi, 7);

            $total = array_sum(array_map(fn (BobotSentuhan $satu): float => $satu->porsiDari(1_000_000.0), $bobot));

            $this->assertEqualsWithDelta(1_000_000.0, $total, 0.000001, "Model {$model->value} tidak utuh.");
        }
    }

    public function test_tanpa_sentuhan_tidak_ada_bobot(): void
    {
        foreach (ModelAttribution::cases() as $model) {
            $this->assertSame([], $this->pembagi()->bagi([], $model, $this->konversi, 7));
        }
    }

    public function test_satu_sentuhan_mendapat_seluruhnya(): void
    {
        foreach (ModelAttribution::cases() as $model) {
            $bobot = $this->pembagi()->bagi($this->sentuhan(1), $model, $this->konversi, 7);

            $this->assertSame(BobotSentuhan::SKALA, $bobot[0]->bagian);
            $this->assertSame(1.0, $bobot[0]->pecahan());
        }
    }

    public function test_model_pertama_memberi_seluruhnya_kepada_yang_paling_awal(): void
    {
        $bobot = $this->pembagi()->bagi($this->sentuhan(4), ModelAttribution::Pertama, $this->konversi, 7);

        $this->assertSame(BobotSentuhan::SKALA, $bobot[0]->bagian);
        $this->assertSame(0, $bobot[1]->bagian);
        $this->assertSame(0, $bobot[3]->bagian);
    }

    public function test_model_terakhir_memberi_seluruhnya_kepada_yang_paling_akhir(): void
    {
        $bobot = $this->pembagi()->bagi($this->sentuhan(4), ModelAttribution::Terakhir, $this->konversi, 7);

        $this->assertSame(0, $bobot[0]->bagian);
        $this->assertSame(BobotSentuhan::SKALA, $bobot[3]->bagian);
    }

    public function test_model_linear_membagi_rata(): void
    {
        $bobot = $this->pembagi()->bagi($this->sentuhan(4), ModelAttribution::Linear, $this->konversi, 7);

        foreach ($bobot as $satu) {
            $this->assertSame(250_000, $satu->bagian);
        }
    }

    /** Pembagian yang tidak bulat tetap berjumlah satu; sisanya diberikan, bukan dibuang. */
    public function test_pembagian_tidak_bulat_tetap_utuh(): void
    {
        $bobot = $this->pembagi()->bagi($this->sentuhan(3), ModelAttribution::Linear, $this->konversi, 7);

        $bagian = array_map(fn (BobotSentuhan $satu): int => $satu->bagian, $bobot);

        $this->assertSame(BobotSentuhan::SKALA, array_sum($bagian));
        $this->assertSame([333_334, 333_333, 333_333], $bagian);
    }

    public function test_berbasis_posisi_memberi_empat_puluh_persen_di_kedua_ujung(): void
    {
        $bobot = $this->pembagi()->bagi($this->sentuhan(4), ModelAttribution::PositionBased, $this->konversi, 7);

        $this->assertSame(400_000, $bobot[0]->bagian);
        $this->assertSame(100_000, $bobot[1]->bagian);
        $this->assertSame(100_000, $bobot[2]->bagian);
        $this->assertSame(400_000, $bobot[3]->bagian);
    }

    /** Dengan dua sentuhan tidak ada bagian tengah, jadi keduanya berbagi rata. */
    public function test_berbasis_posisi_dengan_dua_sentuhan_membagi_rata(): void
    {
        $bobot = $this->pembagi()->bagi($this->sentuhan(2), ModelAttribution::PositionBased, $this->konversi, 7);

        $this->assertSame(500_000, $bobot[0]->bagian);
        $this->assertSame(500_000, $bobot[1]->bagian);
    }

    /** Sentuhan yang lebih dekat ke konversi mendapat porsi lebih besar. */
    public function test_peluruhan_menaik_ke_arah_konversi(): void
    {
        $bobot = $this->pembagi()->bagi($this->sentuhan(4), ModelAttribution::TimeDecay, $this->konversi, 7);

        $this->assertLessThan($bobot[1]->bagian, $bobot[0]->bagian);
        $this->assertLessThan($bobot[2]->bagian, $bobot[1]->bagian);
        $this->assertLessThan($bobot[3]->bagian, $bobot[2]->bagian);
    }

    /** Paruh waktu berarti sentuhan satu paruh lebih tua bernilai separuhnya. */
    public function test_peluruhan_menghormati_paruh_waktunya(): void
    {
        $sentuhan = [
            new Sentuhan('lama', 'cpc', null, null, $this->konversi->subDays(7)),
            new Sentuhan('baru', 'cpc', null, null, $this->konversi),
        ];

        $bobot = $this->pembagi()->bagi($sentuhan, ModelAttribution::TimeDecay, $this->konversi, 7);

        $this->assertEqualsWithDelta(2.0, $bobot[1]->bagian / $bobot[0]->bagian, 0.001);
    }

    public function test_paruh_waktu_lebih_panjang_meratakan_bobot(): void
    {
        $sentuhan = $this->sentuhan(4);

        $tajam = $this->pembagi()->bagi($sentuhan, ModelAttribution::TimeDecay, $this->konversi, 1);
        $landai = $this->pembagi()->bagi($sentuhan, ModelAttribution::TimeDecay, $this->konversi, 365);

        $this->assertGreaterThan($landai[3]->bagian, $tajam[3]->bagian);
        $this->assertLessThan($landai[0]->bagian, $tajam[0]->bagian);
    }

    public function test_paruh_waktu_nol_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        $this->pembagi()->bagi($this->sentuhan(2), ModelAttribution::TimeDecay, $this->konversi, 0);
    }

    /** Sentuhan tepat pada detik konversi tetap dapat dibagi, bukan membagi nol. */
    public function test_seluruh_sentuhan_pada_waktu_konversi_tetap_terbagi(): void
    {
        $sentuhan = [
            new Sentuhan('a', 'cpc', null, null, $this->konversi),
            new Sentuhan('b', 'cpc', null, null, $this->konversi),
        ];

        $bobot = $this->pembagi()->bagi($sentuhan, ModelAttribution::TimeDecay, $this->konversi, 7);

        $this->assertSame(500_000, $bobot[0]->bagian);
        $this->assertSame(500_000, $bobot[1]->bagian);
    }

    /** Bobot menempel pada sentuhannya, bukan pada posisinya saja. */
    public function test_bobot_membawa_sentuhannya(): void
    {
        $bobot = $this->pembagi()->bagi($this->sentuhan(3), ModelAttribution::Linear, $this->konversi, 7);

        $this->assertSame('sumber-1', $bobot[0]->sentuhan->sumber);
        $this->assertSame('sumber-3', $bobot[2]->sentuhan->sumber);
    }
}
