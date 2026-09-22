<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Actions\SimpanKampanye;
use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenKampanye;
use App\Domain\Pemasaran\Domain\Enums\MetrikTargetKampanye;
use App\Domain\Pemasaran\Domain\Enums\ObjectiveKampanye;
use App\Domain\Pemasaran\Domain\Enums\StatusKampanye;
use App\Domain\Pemasaran\Http\Requests\SimpanKampanyeRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeBiaya;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeKonten;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeTarget;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\UtmPemasaran;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Kampanye pemasaran (MARKETING.md 13). */
final class KampanyeController extends Controller
{
    public function __construct(
        private readonly SimpanKampanye $simpanKampanye,
        private readonly LayananAudit $audit,
    ) {}

    public function index(Request $request): Response
    {
        $daftar = DaftarTersaring::untuk($request, Kampanye::query()->with('channel')->withCount('channel'))
            ->cari(['Kode', 'Nama'])
            ->urut(['Nama', 'Kode', 'Status', 'MulaiPada', 'DibuatPada'], bawaan: 'DibuatPada', arahBawaan: 'desc')
            ->faset(['Status', 'Objective']);

        $halaman = $daftar->halaman();
        $idHalaman = $halaman->getCollection()->pluck('Id');

        // Agregat dibatasi pada baris yang benar-benar tampil, bukan seluruh kampanye.
        $kunjungan = UtmPemasaran::query()
            ->whereIn('KampanyeId', $idHalaman)
            ->selectRaw('KampanyeId, COUNT(*) as Jumlah')
            ->groupBy('KampanyeId')
            ->pluck('Jumlah', 'KampanyeId');

        $biaya = KampanyeBiaya::query()
            ->whereIn('KampanyeId', $idHalaman)
            ->selectRaw('KampanyeId, SUM(Jumlah) as Total')
            ->groupBy('KampanyeId')
            ->pluck('Total', 'KampanyeId');

        return Inertia::render('Pemasaran/Kampanye', [
            'kampanye' => DaftarTersaring::paginasi($halaman, fn (Kampanye $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Objective' => $satu->Objective->value,
                'Status' => $satu->Status->value,
                'MulaiPada' => $satu->MulaiPada?->toDateString(),
                'SelesaiPada' => $satu->SelesaiPada?->toDateString(),
                'Channel' => $satu->channel->pluck('Channel')->all(),
                'JumlahKunjungan' => (int) ($kunjungan[$satu->Id] ?? 0),
                'TotalBiaya' => round((float) ($biaya[$satu->Id] ?? 0), 2),
                'Budget' => $satu->Budget === null ? null : (float) $satu->Budget,
                'Audience' => $satu->Audience,
                'Offer' => $satu->Offer,
                'HalamanId' => $satu->HalamanId,
                'FormulirId' => $satu->FormulirId,
                'UtmSource' => $satu->UtmSource,
                'UtmMedium' => $satu->UtmMedium,
                'UtmTerm' => $satu->UtmTerm,
                'UtmContent' => $satu->UtmContent,
                'Catatan' => $satu->Catatan,
            ]),
            'filter' => $daftar->filterBerlaku(),
            'pilihan' => $this->pilihan(),
        ]);
    }

    public function show(Kampanye $kampanye): Response
    {
        $kampanye->load(['channel', 'biaya', 'target', 'konten']);

        $realisasi = $this->realisasi($kampanye);

        return Inertia::render('Pemasaran/KampanyeDetail', [
            'kampanye' => [
                'Id' => $kampanye->Id,
                'Kode' => $kampanye->Kode,
                'Nama' => $kampanye->Nama,
                'Objective' => $kampanye->Objective->value,
                'Status' => $kampanye->Status->value,
                'Budget' => $kampanye->Budget === null ? null : (float) $kampanye->Budget,
                'Audience' => $kampanye->Audience,
                'Offer' => $kampanye->Offer,
                'HalamanId' => $kampanye->HalamanId,
                'FormulirId' => $kampanye->FormulirId,
                'UtmSource' => $kampanye->UtmSource,
                'UtmMedium' => $kampanye->UtmMedium,
                'UtmTerm' => $kampanye->UtmTerm,
                'UtmContent' => $kampanye->UtmContent,
                'MulaiPada' => $kampanye->MulaiPada?->toDateString(),
                'SelesaiPada' => $kampanye->SelesaiPada?->toDateString(),
                'Catatan' => $kampanye->Catatan,
                'Channel' => $kampanye->channel->pluck('Channel')->all(),
                'TujuanStatus' => array_map(
                    fn (StatusKampanye $satu): string => $satu->value,
                    $kampanye->Status->tujuanSah(),
                ),
            ],
            'biaya' => $kampanye->biaya
                ->sortByDesc(fn (KampanyeBiaya $satu): string => $satu->Tanggal->toDateString())
                ->values()
                ->map(fn (KampanyeBiaya $satu): array => [
                    'Id' => $satu->Id,
                    'Channel' => $satu->Channel->value,
                    'Tanggal' => $satu->Tanggal->toDateString(),
                    'Jumlah' => (float) $satu->Jumlah,
                    'Catatan' => $satu->Catatan,
                ])->all(),
            'target' => $kampanye->target->map(fn (KampanyeTarget $satu): array => [
                'Id' => $satu->Id,
                'Metrik' => $satu->Metrik->value,
                'Nilai' => (float) $satu->Nilai,
                'SatuanUang' => $satu->Metrik->satuanUang(),
                'Realisasi' => $realisasi[$satu->Metrik->kolomMetrik()] ?? 0.0,
            ])->all(),
            'konten' => $kampanye->konten
                ->sortBy(fn (KampanyeKonten $satu): int => $satu->Urutan)
                ->values()
                ->map(fn (KampanyeKonten $satu): array => [
                    'Id' => $satu->Id,
                    'Jenis' => $satu->Jenis->value,
                    'Judul' => $satu->Judul,
                    'Tautan' => $satu->Tautan,
                    'Catatan' => $satu->Catatan,
                    'Urutan' => $satu->Urutan,
                ])->all(),
            'pilihan' => $this->pilihan(),
        ]);
    }

    public function store(SimpanKampanyeRequest $request): RedirectResponse
    {
        $kampanye = $this->simpanKampanye->jalankan(null, $request->validated());

        $this->audit->catat('Kampanye.Dibuat', 'Kampanye', $kampanye->Id, dataSesudah: [
            'Kode' => $kampanye->Kode,
            'Nama' => $kampanye->Nama,
        ]);

        return back()->with('sukses', 'Kampanye berhasil dibuat.');
    }

    public function update(SimpanKampanyeRequest $request, Kampanye $kampanye): RedirectResponse
    {
        $sebelum = ['Nama' => $kampanye->Nama, 'Status' => $kampanye->Status->value];
        $kampanye = $this->simpanKampanye->jalankan($kampanye, $request->validated());

        $this->audit->catat(
            'Kampanye.Diubah',
            'Kampanye',
            $kampanye->Id,
            dataSebelum: $sebelum,
            dataSesudah: ['Nama' => $kampanye->Nama, 'Status' => $kampanye->Status->value],
        );

        return back()->with('sukses', 'Kampanye berhasil diperbarui.');
    }

    /**
     * Realisasi tiap metrik target dibaca dari metrik harian yang sudah dihitung, bukan dari tabel mentah.
     *
     * @return array<string, float>
     */
    private function realisasi(Kampanye $kampanye): array
    {
        $baris = (array) DB::table('MetrikKampanye')
            ->where('KampanyeId', $kampanye->Id)
            ->selectRaw(
                'sum(Visitor) as Visitor, sum(`Lead`) as `Lead`, sum(Trial) as Trial, '
                .'sum(Teraktivasi) as Teraktivasi, sum(Bayar) as Bayar, sum(Revenue) as Revenue',
            )
            ->first();

        $hasil = [];

        foreach (MetrikTargetKampanye::cases() as $satu) {
            $kolom = $satu->kolomMetrik();
            $hasil[$kolom] = round((float) ($baris[$kolom] ?? 0), 2);
        }

        return $hasil;
    }

    /** @return array<string, array<array-key, string>> */
    private function pilihan(): array
    {
        return [
            'Status' => array_column(StatusKampanye::cases(), 'value'),
            'Objective' => array_column(ObjectiveKampanye::cases(), 'value'),
            'Channel' => array_column(ChannelKampanye::cases(), 'value'),
            'Metrik' => array_column(MetrikTargetKampanye::cases(), 'value'),
            'JenisKonten' => array_column(JenisKontenKampanye::cases(), 'value'),
            'Halaman' => HalamanPemasaran::query()->orderBy('Slug')->pluck('Slug', 'Id')->all(),
            'Formulir' => FormulirPemasaran::query()->orderBy('Kode')->pluck('Kode', 'Id')->all(),
        ];
    }
}
