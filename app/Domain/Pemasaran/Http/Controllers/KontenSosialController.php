<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\PenerbitKontenSosial;
use App\Domain\Pemasaran\Application\Services\PenjadwalKontenSosial;
use App\Domain\Pemasaran\Application\Services\PenyusunTautanDistribusi;
use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusJadwalSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusKontenSosial;
use App\Domain\Pemasaran\Http\Requests\SimpanDistribusiSosialRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanKontenSosialRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DistribusiKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\JadwalKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenSosial;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Konsol konten sosial, distribusi, dan jadwalnya (MARKETING.md 18). */
final class KontenSosialController extends Controller
{
    public function __construct(
        private readonly PenjadwalKontenSosial $penjadwal,
        private readonly PenerbitKontenSosial $penerbit,
        private readonly PenyusunTautanDistribusi $penyusun,
        private readonly LayananAudit $audit,
    ) {}

    public function index(): Response
    {
        $konten = KontenSosial::query()
            ->with(['distribusi', 'kampanye:Id,Kode,Nama', 'halaman:Id,Slug'])
            ->orderByDesc('DibuatPada')
            ->get();

        // Jadwal menunggu dibaca sekali sebagai peta, bukan satu kueri per distribusi.
        $menunggu = JadwalKontenSosial::query()
            ->where('Status', StatusJadwalSosial::Menunggu->value)
            ->orderBy('JadwalPada')
            ->get()
            ->keyBy('DistribusiKontenSosialId');

        return Inertia::render('Pemasaran/Sosial', [
            'konten' => $konten->map(fn (KontenSosial $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Judul' => $satu->Judul,
                'Ringkasan' => $satu->Ringkasan,
                'MediaUrl' => $satu->MediaUrl,
                'HalamanId' => $satu->HalamanId,
                'KampanyeId' => $satu->KampanyeId,
                'KampanyeKode' => $satu->kampanye?->Kode,
                'HalamanSlug' => $satu->halaman?->Slug,
                'Distribusi' => $satu->distribusi
                    ->map(fn (DistribusiKontenSosial $baris): array => [
                        'Id' => $baris->Id,
                        'Channel' => $baris->Channel->value,
                        'Caption' => $baris->Caption,
                        'MediaUrl' => $baris->MediaUrl,
                        'Cta' => $baris->Cta,
                        'TautanTujuan' => $baris->TautanTujuan,
                        'UtmSource' => $baris->UtmSource,
                        'UtmMedium' => $baris->UtmMedium,
                        'UtmTerm' => $baris->UtmTerm,
                        'UtmContent' => $baris->UtmContent,
                        'Status' => $baris->Status->value,
                        'TujuanStatus' => array_map(
                            fn (StatusKontenSosial $tujuan): string => $tujuan->value,
                            $baris->Status->tujuanSah(),
                        ),
                        'TautanBerUtm' => $this->penyusun->untuk($baris),
                        'UrlTerbit' => $baris->UrlTerbit,
                        'Galat' => $baris->Galat,
                        'Percobaan' => $baris->Percobaan,
                        'TerbitPada' => $baris->TerbitPada?->toIso8601String(),
                        'JadwalPada' => $menunggu->get($baris->Id)?->JadwalPada?->toIso8601String(),
                    ])->all(),
            ])->all(),
            'pilihan' => [
                'Channel' => array_column(ChannelSosial::cases(), 'value'),
                'ChannelWajibMedia' => array_values(array_map(
                    fn (ChannelSosial $satu): string => $satu->value,
                    array_filter(ChannelSosial::cases(), fn (ChannelSosial $satu): bool => $satu->wajibMedia()),
                )),
                'Kampanye' => Kampanye::query()->orderBy('Kode')->pluck('Kode', 'Id')->all(),
                'Halaman' => HalamanPemasaran::query()->orderBy('Slug')->pluck('Slug', 'Id')->all(),
            ],
        ]);
    }

    public function store(SimpanKontenSosialRequest $request): RedirectResponse
    {
        $konten = KontenSosial::create($request->validated());

        $this->audit->catat('KontenSosial.Dibuat', 'KontenSosial', $konten->Id, dataSesudah: [
            'Kode' => $konten->Kode,
        ]);

        return back()->with('sukses', 'Konten sosial dibuat.');
    }

    public function update(SimpanKontenSosialRequest $request, KontenSosial $konten): RedirectResponse
    {
        $konten->update($request->validated());

        $this->audit->catat('KontenSosial.Diubah', 'KontenSosial', $konten->Id, dataSesudah: [
            'Kode' => $konten->Kode,
        ]);

        return back()->with('sukses', 'Konten sosial diperbarui.');
    }

    public function simpanDistribusi(
        SimpanDistribusiSosialRequest $request,
        KontenSosial $konten,
    ): RedirectResponse {
        $distribusi = DistribusiKontenSosial::create([
            ...$request->validated(),
            'KontenSosialId' => $konten->Id,
            'Status' => StatusKontenSosial::Draf,
        ]);

        $this->audit->catat('DistribusiSosial.Dibuat', 'DistribusiKontenSosial', $distribusi->Id, dataSesudah: [
            'Channel' => $distribusi->Channel->value,
        ]);

        return back()->with('sukses', "Distribusi {$distribusi->Channel->value} ditambahkan.");
    }

    public function perbaruiDistribusi(
        SimpanDistribusiSosialRequest $request,
        KontenSosial $konten,
        DistribusiKontenSosial $distribusi,
    ): RedirectResponse {
        $this->pastikanMilik($konten, $distribusi);
        $distribusi->update($request->validated());

        return back()->with('sukses', 'Distribusi diperbarui.');
    }

    public function pindahkanStatus(
        Request $request,
        KontenSosial $konten,
        DistribusiKontenSosial $distribusi,
    ): RedirectResponse {
        $this->pastikanMilik($konten, $distribusi);

        /** @var array{Status: string} $sah */
        $sah = $request->validate([
            'Status' => ['required', Rule::enum(StatusKontenSosial::class)],
        ]);

        $this->penjadwal->pindahkan($distribusi, StatusKontenSosial::from($sah['Status']));

        return back()->with('sukses', "Distribusi berpindah ke {$sah['Status']}.");
    }

    public function jadwalkan(
        Request $request,
        KontenSosial $konten,
        DistribusiKontenSosial $distribusi,
    ): RedirectResponse {
        $this->pastikanMilik($konten, $distribusi);

        /** @var array{JadwalPada: string} $sah */
        $sah = $request->validate(['JadwalPada' => ['required', 'date']]);

        $this->penjadwal->jadwalkan($distribusi, CarbonImmutable::parse($sah['JadwalPada']));

        $this->audit->catat('DistribusiSosial.Dijadwalkan', 'DistribusiKontenSosial', $distribusi->Id, dataSesudah: [
            'JadwalPada' => $sah['JadwalPada'],
        ]);

        return back()->with('sukses', 'Distribusi dijadwalkan.');
    }

    public function batalkanJadwal(KontenSosial $konten, DistribusiKontenSosial $distribusi): RedirectResponse
    {
        $this->pastikanMilik($konten, $distribusi);
        $this->penjadwal->batalkan($distribusi);

        return back()->with('sukses', 'Jadwal dibatalkan dan distribusinya kembali ke draf.');
    }

    /** Terbitkan sekarang memakai jalur yang sama dengan job terjadwal, tanpa jalan pintas. */
    public function terbitkanSekarang(KontenSosial $konten, DistribusiKontenSosial $distribusi): RedirectResponse
    {
        $this->pastikanMilik($konten, $distribusi);

        $jadwal = $this->penjadwal->jadwalkan($distribusi, CarbonImmutable::now());
        $status = $this->penerbit->terbitkan($jadwal);

        return back()->with('sukses', "Penerbitan selesai dengan status {$status->value}.");
    }

    private function pastikanMilik(KontenSosial $konten, DistribusiKontenSosial $distribusi): void
    {
        if ($distribusi->KontenSosialId !== $konten->Id) {
            throw new DataTidakDitemukan('Distribusi itu bukan milik konten ini.');
        }
    }
}
