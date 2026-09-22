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
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RiwayatTahapProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** CRM prospek (MARKETING.md 6, 7). */
final class ProspekController extends Controller
{
    public function __construct(
        private readonly PenghitungSkorProspek $skor,
        private readonly PenyusunTimelineProspek $timeline,
        private readonly LayananAudit $audit,
    ) {}

    public function index(Request $request): Response
    {
        $daftar = DaftarTersaring::untuk($request, Prospek::query()->with(['tahap', 'organisasiProspek', 'kampanye']))
            // Perusahaan adalah nama relasi organisasiProspek, bukan kolom Prospek.
            ->cari(['Nama', 'Email'])
            ->urut(['Nama', 'Sumber', 'Skor'], bawaan: 'Skor', arahBawaan: 'desc')
            // Tahap disaring lewat kodenya, bukan Id-nya, karena itu yang dikenal frontend.
            ->saring('tahap', function (Builder $kueri, string $kode): void {
                $kueri->whereHas('tahap', fn (Builder $tahap) => $tahap->where('Kode', $kode));
            });

        return Inertia::render('Pemasaran/Prospek/Index', [
            'wajib' => ['prospek' => AturanWajib::untuk(SimpanProspekRequest::class)],
            'prospek' => $daftar->halamanTerpeta(fn (Prospek $satu): array => $this->ringkas($satu)),
            'tahap' => $this->daftarTahap(),
            'filter' => $daftar->filterBerlaku(),
        ]);
    }

    public function show(Prospek $prospek): Response
    {
        $prospek->load(['tahap', 'organisasiProspek', 'kampanye', 'kontak', 'tag', 'rincianSkor']);

        return Inertia::render('Pemasaran/Prospek/Show', [
            'wajib' => ['tahap' => AturanWajib::untuk(PindahkanTahapProspekRequest::class), 'aktivitas' => AturanWajib::untuk(SimpanAktivitasProspekRequest::class)],
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
            'riwayatTahap' => $this->riwayatTahap($prospek),
            'tahap' => $this->daftarTahap(),
            'jenisAktivitas' => array_column(JenisAktivitasProspek::cases(), 'value'),
        ]);
    }

    /**
     * Perpindahan tahap prospek beserta lama menetap di masing-masingnya.
     *
     * RiwayatTahapProspek sudah lama diisi PindahkanTahapProspek tetapi tidak
     * pernah dibaca, sehingga pertanyaan pipeline yang paling sering muncul --
     * prospek ini sudah mengendap berapa lama di tahap sekarang -- tidak
     * terjawab. Timeline hanya menyebut perpindahannya, bukan durasinya.
     *
     * @return list<array{Id: string, TahapSebelum: string|null, TahapSesudah: string|null, Alasan: string|null, BerpindahPada: string, LamaHari: int, Berjalan: bool}>
     */
    private function riwayatTahap(Prospek $prospek): array
    {
        $riwayat = $prospek->riwayatTahap()
            ->with(['tahapSebelum', 'tahapSesudah'])
            ->orderBy('BerpindahPada')
            ->get()
            ->values()
            ->all();

        $sekarang = CarbonImmutable::now();
        $baris = [];

        foreach ($riwayat as $urutan => $satu) {
            // Lama satu tahap diukur sampai perpindahan berikutnya; tahap
            // terakhir masih berjalan, jadi diukur sampai sekarang.
            $berikutnya = $riwayat[$urutan + 1] ?? null;
            $akhir = $berikutnya === null ? $sekarang : $berikutnya->BerpindahPada;

            $baris[] = [
                'Id' => $satu->Id,
                'TahapSebelum' => $satu->tahapSebelum?->Nama,
                'TahapSesudah' => $satu->tahapSesudah?->Nama,
                'Alasan' => $satu->Alasan,
                'BerpindahPada' => $satu->BerpindahPada->toIso8601String(),
                'LamaHari' => (int) $satu->BerpindahPada->diffInDays($akhir),
                'Berjalan' => $berikutnya === null,
            ];
        }

        return $baris;
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
