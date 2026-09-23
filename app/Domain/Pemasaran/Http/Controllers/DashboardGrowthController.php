<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\PenghitungCacKampanye;
use App\Domain\Pemasaran\Application\Services\PenghitungKpiPemasaran;
use App\Domain\Pemasaran\Application\Services\PenghitungRevenueAttribution;
use App\Domain\Pemasaran\Application\Services\PenyusunFunnelGrowth;
use App\Domain\Pemasaran\Domain\Enums\ModelAttribution;
use App\Domain\Pemasaran\Domain\Enums\TahapFunnelGrowth;
use App\Domain\Pemasaran\Domain\KatalogAlertPemasaran;
use App\Domain\Pemasaran\Domain\KatalogKpiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\DefinisiKpiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AlertPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\MetrikKampanye;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Dashboard growth: satu layar yang menjawab channel, campaign, dan halaman mana yang bekerja (Gate 37). */
final class DashboardGrowthController extends Controller
{
    public function __construct(
        private readonly PenyusunFunnelGrowth $funnel,
        private readonly PenghitungKpiPemasaran $kpi,
        private readonly PenghitungCacKampanye $cac,
        private readonly PenghitungRevenueAttribution $revenue,
    ) {}

    public function index(Request $request): Response
    {
        /** @var array<string, mixed> $kueri */
        $kueri = $request->query();
        $filter = FilterGrowth::dariKueri($kueri);

        $funnel = $this->funnel->hitung($filter);

        return Inertia::render('Pemasaran/Growth/Dashboard', [
            'filter' => $filter->keArray(),
            'funnel' => $this->funnelUntukLayar($funnel),
            'kpi' => $this->kpiUntukLayar($filter, $funnel),
            'revenuePerChannel' => $this->kpi->revenuePerChannel($filter),
            'attribution' => $this->attribution($filter),
            'cacPerChannel' => $this->cac->perChannel($filter),
            'cacTakTerpecah' => $this->cac->takTerpecah($filter),
            'kampanye' => $this->kampanye($filter),
            'halaman' => $this->halaman($filter),
            'alert' => $this->alert(),
            'pilihan' => $this->pilihan(),
        ]);
    }

    /**
     * Model yang sedang dipakai beserta pembandingnya, supaya pembaca tahu
     * angka revenue di layar ini dihitung menurut model yang mana.
     *
     * @return array<string, mixed>
     */
    private function attribution(FilterGrowth $filter): array
    {
        $aktif = $this->revenue->modelAktif();

        return [
            'Model' => $aktif->value,
            'Label' => $aktif->label(),
            'Keterangan' => $aktif->keterangan(),
            'ParuhHari' => $this->revenue->paruhHari(),
            'Perbandingan' => array_map(
                fn (ModelAttribution $satu): array => [
                    'Model' => $satu->value,
                    'Label' => $satu->label(),
                    'PerChannel' => $this->revenue->perChannel($filter, $satu),
                ],
                ModelAttribution::cases(),
            ),
        ];
    }

    public function selesaikanAlert(AlertPemasaran $alert): RedirectResponse
    {
        $alert->DiselesaikanPada = CarbonImmutable::now();
        $alert->save();

        return back()->with('sukses', 'Alert ditandai selesai.');
    }

    /**
     * @param  array<string, int>  $funnel
     * @return list<array<string, mixed>>
     */
    private function funnelUntukLayar(array $funnel): array
    {
        $hasil = [];
        $sebelumnya = null;

        foreach (TahapFunnelGrowth::cases() as $tahap) {
            $jumlah = $funnel[$tahap->value] ?? 0;

            $hasil[] = [
                'Tahap' => $tahap->value,
                'Jumlah' => $jumlah,
                'Sumber' => $tahap->sumber(),
                'PersenDariSebelumnya' => $sebelumnya === null || $sebelumnya === 0
                    ? null
                    : round($jumlah / $sebelumnya * 100, 1),
            ];

            $sebelumnya = $jumlah;
        }

        return $hasil;
    }

    /**
     * @param  array<string, int>  $funnel
     * @return list<array<string, mixed>>
     */
    private function kpiUntukLayar(FilterGrowth $filter, array $funnel): array
    {
        $nilai = $this->kpi->hitung($filter, $funnel);

        return array_values(array_map(
            fn (DefinisiKpiPemasaran $definisi): array => [
                ...$definisi->keArray(),
                'Nilai' => $definisi->tersedia() ? ($nilai[$definisi->kunci] ?? 0.0) : null,
            ],
            KatalogKpiPemasaran::semua(),
        ));
    }

    /**
     * Dibaca dari metrik yang sudah dihitung pekerjaan harian, bukan satu kueri per kampanye.
     *
     * @return list<array<string, mixed>>
     */
    private function kampanye(FilterGrowth $filter): array
    {
        $baris = MetrikKampanye::query()
            ->with('kampanye:Id,Kode,Nama')
            ->whereBetween('Tanggal', [$filter->tanggalDari(), $filter->tanggalSampai()])
            ->whereNotNull('KampanyeId')
            ->when($filter->channel !== null, fn ($kueri) => $kueri->where('Channel', $filter->channel))
            ->get()
            ->groupBy('KampanyeId');

        $hasil = [];

        foreach ($baris as $kampanyeId => $kelompok) {
            $pertama = $kelompok->first();

            $hasil[] = [
                'KampanyeId' => (string) $kampanyeId,
                'Kode' => $pertama?->kampanye->Kode ?? 'Kampanye terhapus',
                'Nama' => $pertama?->kampanye->Nama ?? 'Kampanye terhapus',
                'Visitor' => (int) $kelompok->sum('Visitor'),
                'Lead' => (int) $kelompok->sum('Lead'),
                'Trial' => (int) $kelompok->sum('Trial'),
                'Bayar' => (int) $kelompok->sum('Bayar'),
                'Revenue' => round((float) $kelompok->sum('Revenue'), 2),
                'Biaya' => round((float) $kelompok->sum('Biaya'), 2),
            ];
        }

        usort($hasil, fn (array $a, array $b): int => $b['Revenue'] <=> $a['Revenue']);

        return array_slice($hasil, 0, 20);
    }

    /**
     * Landing page mana yang paling efektif: kunjungan dibanding prospek yang lahir darinya.
     *
     * @return list<array<string, mixed>>
     */
    private function halaman(FilterGrowth $filter): array
    {
        $baris = DB::table('SesiPengunjung')
            ->leftJoin('Prospek', 'Prospek.PengenalPengunjung', '=', 'SesiPengunjung.PengenalPengunjung')
            ->whereBetween('SesiPengunjung.DimulaiPada', [$filter->dari, $filter->sampai])
            ->whereNotNull('SesiPengunjung.LandingUrl')
            ->groupBy('SesiPengunjung.LandingUrl')
            ->selectRaw(
                'SesiPengunjung.LandingUrl as landing, '
                .'count(distinct SesiPengunjung.PengenalPengunjung) as pengunjung, '
                .'count(distinct Prospek.Id) as lead',
            )
            ->orderByDesc('pengunjung')
            ->limit(20)
            ->get();

        return array_values($baris->map(fn (object $satu): array => [
            'Landing' => (string) $satu->landing,
            'Pengunjung' => (int) $satu->pengunjung,
            'Lead' => (int) $satu->lead,
            'Konversi' => (int) $satu->pengunjung === 0
                ? 0.0
                : round((int) $satu->lead / (int) $satu->pengunjung * 100, 1),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function alert(): array
    {
        $alert = AlertPemasaran::query()
            ->whereNull('DiselesaikanPada')
            ->orderByDesc('DibuatPada')
            ->limit(50)
            ->get();

        return array_values($alert->map(fn (AlertPemasaran $satu): array => [
            'Id' => $satu->Id,
            'Kode' => $satu->Kode,
            'Tingkat' => $satu->Tingkat->value,
            'Judul' => $satu->Judul,
            'Isi' => $satu->Isi,
            'DibuatPada' => $satu->DibuatPada->toIso8601String(),
        ])->all());
    }

    /** @return array<string, mixed> */
    private function pilihan(): array
    {
        return [
            'Channel' => $this->nilaiUnik('AttributionPemasaran', 'SumberPertama'),
            'Kampanye' => $this->nilaiUnik('AttributionPemasaran', 'KampanyePertama'),
            'Industri' => $this->nilaiUnik('OrganisasiProspek', 'Industri'),
            'Perangkat' => $this->nilaiUnik('SesiPengunjung', 'Perangkat'),
            'Paket' => $this->nilaiUnik('PaketLangganan', 'Kode'),
            'Referral' => $this->nilaiUnik('ProgramReferral', 'Kode'),
            'AlertBelumTersedia' => array_values(array_filter(
                KatalogAlertPemasaran::kode(),
                fn (string $kode): bool => ! KatalogAlertPemasaran::tersedia($kode),
            )),
        ];
    }

    /** @return list<string> */
    private function nilaiUnik(string $tabel, string $kolom): array
    {
        return array_values(DB::table($tabel)
            ->whereNotNull($kolom)
            ->where($kolom, '!=', '')
            ->distinct()
            ->orderBy($kolom)
            ->limit(100)
            ->pluck($kolom)
            ->map(strval(...))
            ->all());
    }
}
