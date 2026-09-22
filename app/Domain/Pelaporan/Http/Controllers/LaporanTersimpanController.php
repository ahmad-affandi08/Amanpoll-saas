<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Controllers;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Pelaporan\Application\Actions\KelolaLaporanTersimpan;
use App\Domain\Pelaporan\Application\Services\LayananEksporLaporan;
use App\Domain\Pelaporan\Application\Services\LayananMetrik;
use App\Domain\Pelaporan\Domain\Enums\FormatEkspor;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Http\Requests\SimpanLaporanTersimpanRequest;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\LaporanTersimpan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/** Laporan tersimpan (21.03) beserta pemicu ekspornya (21.05). */
final class LaporanTersimpanController extends Controller
{
    public function index(Request $request, LayananMetrik $layananMetrik): Response
    {
        $this->authorize('viewAny', LaporanTersimpan::class);

        $pengguna = $request->user('web');
        $filter = FilterMetrik::dariArray($request->all());

        $laporan = LaporanTersimpan::query()
            ->with('pemilik:Id,Nama')
            ->where(fn ($query) => $query->where('PemilikId', $pengguna->Id)->orWhere('Pribadi', false))
            ->orderBy('Nama')
            ->limit(BatasDaftar::MAKS)
            ->get()
            ->filter(fn (LaporanTersimpan $satu): bool => $request->user('web')->can('view', $satu))
            ->values();

        $dibuka = $this->laporanDibuka($request, $laporan);

        return Inertia::render('Laporan/Index', [
            'laporan' => $laporan->map(fn (LaporanTersimpan $satu): array => $this->ringkas($satu, $pengguna->Id))->all(),
            'dibuka' => $dibuka === null ? null : $this->ringkas($dibuka, $pengguna->Id),
            'metrik' => $dibuka === null
                ? []
                : $layananMetrik->hitungBanyak(
                    array_values(array_map('strval', (array) ($dibuka->Konfigurasi['KunciKpi'] ?? []))),
                    $filter,
                    $pengguna,
                ),
            'filter' => $filter->keArray(),
            'katalogKpi' => $layananMetrik->katalogUntuk($pengguna),
            'formatEkspor' => array_map(
                fn (FormatEkspor $format): array => ['Nilai' => $format->value, 'Label' => $format->label()],
                FormatEkspor::cases(),
            ),
            'pilihanUnit' => UnitOrganisasi::query()->orderBy('Nama')->get(['Id', 'Nama'])->all(),
            'pilihanLokasi' => Lokasi::query()->orderBy('Nama')->get(['Id', 'Nama'])->all(),
            'eksporTerakhir' => $this->eksporTerakhir($pengguna->Id),
        ]);
    }

    public function store(SimpanLaporanTersimpanRequest $request, KelolaLaporanTersimpan $aksi): RedirectResponse
    {
        $this->authorize('create', LaporanTersimpan::class);
        $laporan = $aksi->simpan($request->user('web'), $request->validated());

        return redirect()
            ->route('pelaporan.laporan.index', ['laporan' => $laporan->Id])
            ->with('sukses', 'Laporan tersimpan dibuat.');
    }

    public function update(
        SimpanLaporanTersimpanRequest $request,
        LaporanTersimpan $laporanTersimpan,
        KelolaLaporanTersimpan $aksi,
    ): RedirectResponse {
        $this->authorize('update', $laporanTersimpan);
        $aksi->simpan($request->user('web'), $request->validated(), $laporanTersimpan);

        return back()->with('sukses', 'Laporan tersimpan diperbarui.');
    }

    public function destroy(LaporanTersimpan $laporanTersimpan, KelolaLaporanTersimpan $aksi): RedirectResponse
    {
        $this->authorize('delete', $laporanTersimpan);
        $aksi->hapus($laporanTersimpan);

        return redirect()->route('pelaporan.laporan.index')->with('sukses', 'Laporan tersimpan dihapus.');
    }

    /** @return array<string, mixed> */
    private function ringkas(LaporanTersimpan $laporan, string $penggunaId): array
    {
        return [
            'Id' => $laporan->Id,
            'Nama' => $laporan->Nama,
            'Jenis' => $laporan->Jenis,
            'Pribadi' => $laporan->Pribadi,
            'Milik' => $laporan->PemilikId === $penggunaId,
            'NamaPemilik' => $laporan->namaPemilik(),
            'KunciKpi' => array_values(array_map('strval', (array) ($laporan->Konfigurasi['KunciKpi'] ?? []))),
            'Filter' => (array) ($laporan->Konfigurasi['Filter'] ?? []),
            'DiperbaruiPada' => $laporan->DiperbaruiPada->toIso8601String(),
        ];
    }

    /** @param Collection<int, LaporanTersimpan> $laporan */
    private function laporanDibuka(Request $request, $laporan): ?LaporanTersimpan
    {
        $diminta = $request->query('laporan');

        return is_string($diminta) && $diminta !== ''
            ? $laporan->firstWhere('Id', $diminta)
            : null;
    }

    /**
     * Riwayat ekspor milik pengguna ini saja. Berkas ekspor berisi angka yang
     * sudah disaring menurut izin pemesannya, jadi tidak boleh muncul di daftar
     * orang lain meski berada dalam satu organisasi.
     *
     * @return array<int, array<string, mixed>>
     */
    private function eksporTerakhir(string $penggunaId): array
    {
        return Berkas::query()
            ->where('DiunggahOleh', $penggunaId)
            ->where('DataTambahan->Jenis', LayananEksporLaporan::JENIS_BERKAS)
            ->orderByDesc('DibuatPada')
            ->limit(10)
            ->get()
            ->map(fn (Berkas $berkas): array => [
                'Id' => $berkas->Id,
                'NamaAsli' => $berkas->NamaAsli,
                'Judul' => (string) ($berkas->DataTambahan['Judul'] ?? $berkas->NamaAsli),
                'Format' => (string) ($berkas->DataTambahan['Format'] ?? ''),
                'JumlahBaris' => (int) ($berkas->DataTambahan['JumlahBaris'] ?? 0),
                'UkuranByte' => $berkas->UkuranByte,
                'DibuatPada' => $berkas->DibuatPada->toIso8601String(),
            ])
            ->all();
    }
}
