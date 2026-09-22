<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Application\Actions\PindahkanTahapProspek;
use App\Domain\Pemasaran\Application\Services\PenghitungSkorProspek;
use App\Domain\Pemasaran\Application\Services\PenyusunTimelineProspek;
use App\Domain\Pemasaran\Domain\Enums\JenisAktivitasProspek;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Http\Requests\PindahkanTahapProspekRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanAktivitasProspekRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanProspekRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AktivitasProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRM prospek (MARKETING.md 6, 7).
 */
final class ProspekController extends Controller
{
    public function __construct(
        private readonly PenghitungSkorProspek $skor,
        private readonly PenyusunTimelineProspek $timeline,
        private readonly LayananAudit $audit,
    ) {}

    public function index(Request $request): Response
    {
        $filter = $request->validate([
            'tahap' => ['nullable', 'string', 'max:50'],
            'cari' => ['nullable', 'string', 'max:190'],
        ]);

        $prospek = Prospek::query()
            ->with(['tahap', 'organisasiProspek', 'kampanye'])
            ->when(
                $filter['tahap'] ?? null,
                fn ($q, string $kode) => $q->whereHas('tahap', fn ($t) => $t->where('Kode', $kode)),
            )
            ->when($filter['cari'] ?? null, fn ($q, string $cari) => $q->where(
                fn ($w) => $w->where('Nama', 'like', "%{$cari}%")->orWhere('Email', 'like', "%{$cari}%"),
            ))
            ->orderByDesc('Skor')
            ->orderByDesc('DibuatPada')
            ->limit(200)
            ->get();

        return Inertia::render('Pemasaran/Prospek/Index', [
            'prospek' => $prospek->map(fn (Prospek $satu): array => $this->ringkas($satu))->all(),
            'tahap' => $this->daftarTahap(),
            'filter' => $filter,
        ]);
    }

    public function show(Prospek $prospek): Response
    {
        $prospek->load(['tahap', 'organisasiProspek', 'kampanye', 'kontak', 'tag', 'rincianSkor']);

        return Inertia::render('Pemasaran/Prospek/Show', [
            'prospek' => [
                ...$this->ringkas($prospek),
                'Telepon' => $prospek->Telepon,
                'WhatsApp' => $prospek->WhatsApp,
                'Jabatan' => $prospek->Jabatan,
                'Catatan' => $prospek->Catatan,
                'Tag' => $prospek->tag->pluck('Nama')->all(),
                'RincianSkor' => $prospek->rincianSkor
                    ->map(fn ($satu): array => ['Peristiwa' => $satu->Peristiwa, 'Bobot' => $satu->Bobot])
                    ->all(),
                'Qualified' => $this->skor->qualified($prospek),
            ],
            'timeline' => $this->timeline->untuk($prospek),
            'tahap' => $this->daftarTahap(),
            'jenisAktivitas' => array_column(JenisAktivitasProspek::cases(), 'value'),
        ]);
    }

    public function store(SimpanProspekRequest $request, CatatProspek $aksi): RedirectResponse
    {
        $prospek = $aksi->jalankan($request->validated(), SumberProspek::Manual);

        $this->audit->catat('Prospek.Dibuat', 'Prospek', $prospek->Id, dataSesudah: [
            'Nama' => $prospek->Nama,
            'Email' => $prospek->Email,
        ]);

        return back()->with('sukses', 'Prospek berhasil dicatat.');
    }

    public function pindahkanTahap(
        PindahkanTahapProspekRequest $request,
        Prospek $prospek,
        PindahkanTahapProspek $aksi,
    ): RedirectResponse {
        $data = $request->validated();
        $tujuan = TahapPipeline::query()->where('Kode', $data['Kode'])->firstOrFail();
        $sebelum = $prospek->tahap?->Kode;

        $aksi->jalankan($prospek, $tujuan, $data['Alasan'] ?? null);

        $this->audit->catat(
            'Prospek.TahapDipindah',
            'Prospek',
            $prospek->Id,
            dataSebelum: ['Tahap' => $sebelum],
            dataSesudah: ['Tahap' => $tujuan->Kode],
        );

        return back()->with('sukses', 'Tahap prospek berhasil diperbarui.');
    }

    public function catatAktivitas(SimpanAktivitasProspekRequest $request, Prospek $prospek): RedirectResponse
    {
        $data = $request->validated();

        AktivitasProspek::create([
            'ProspekId' => $prospek->Id,
            'AktorPlatformId' => Auth::guard('platform')->id(),
            'Jenis' => $data['Jenis'],
            'Judul' => $data['Judul'],
            'Isi' => $data['Isi'] ?? null,
            'TerjadiPada' => now(),
        ]);

        $prospek->AktivitasTerakhirPada = CarbonImmutable::now();
        $prospek->save();

        return back()->with('sukses', 'Aktivitas berhasil dicatat.');
    }

    /** @return array<string, mixed> */
    private function ringkas(Prospek $prospek): array
    {
        return [
            'Id' => $prospek->Id,
            'Nama' => $prospek->Nama,
            'Email' => $prospek->Email,
            'Perusahaan' => $prospek->organisasiProspek?->Nama,
            'Industri' => $prospek->organisasiProspek?->Industri,
            'Sumber' => $prospek->Sumber,
            'Kampanye' => $prospek->kampanye?->Nama,
            'Tahap' => $prospek->tahap?->Nama,
            'KodeTahap' => $prospek->tahap?->Kode,
            'Skor' => $prospek->Skor,
            'AktivitasTerakhirPada' => $prospek->AktivitasTerakhirPada?->toIso8601String(),
            'DibuatPada' => $prospek->DibuatPada->toIso8601String(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function daftarTahap(): array
    {
        return array_values(TahapPipeline::query()
            ->orderBy('Urutan')
            ->get()
            ->map(fn (TahapPipeline $satu): array => [
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'TahapAkhir' => $satu->TahapAkhir,
            ])
            ->all());
    }
}
