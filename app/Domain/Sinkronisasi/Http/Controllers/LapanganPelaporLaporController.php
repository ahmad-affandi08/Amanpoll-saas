<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Core\Izin\PemeriksaIzin;
use App\Core\Izin\ScopeLingkup;
use App\Domain\Aset\Application\Services\PencariAsetLewatKode;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kolaborasi\Application\Actions\LampirkanBerkas;
use App\Domain\Kolaborasi\Application\Actions\UnggahBerkas;
use App\Domain\Pemeliharaan\Http\Requests\SimpanKeluhanRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Application\Services\LayananLaporanLapangan;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarPelapor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lapor kerusakan dalam tiga langkah (DESIGN §36.7 layar 04–07): pilih alat,
 * apa masalahnya, tinjau & kirim.
 *
 * `?aset=<AsetId>` adalah kontrak tetap untuk hasil pindai QR pengguna mode
 * Pelapor (lihat `AsetPindaiController`); `?kode=` dipakai pemindai di langkah 1.
 * Keduanya hanya mengisi aset yang terlihat di lingkup pelapor dan hanya bila ia
 * boleh melihat aset (`Aset.Lihat`). Aset di luar itu tidak diisi, sehingga
 * keberadaannya tidak terbuka.
 */
final class LapanganPelaporLaporController extends Controller
{
    /** Foto yang boleh disertakan (papan acuan: "2 dari 4"). */
    public const MAKS_FOTO = 4;

    public function create(
        Request $request,
        PenyusunLayarPelapor $penyusun,
        PemeriksaIzin $izin,
        PencariAsetLewatKode $pencariAset,
    ): Response {
        $this->authorize('create', Keluhan::class);

        $pengguna = $request->user('web');
        $bolehLihatAset = $izin->boleh($pengguna->Id, 'Aset.Lihat');
        $masukan = $request->validate([
            'aset' => ['nullable', 'string', 'max:26'],
            'kode' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:26'],
            'kategori' => ['nullable', 'string', 'max:26'],
        ]);

        $asetTerpilih = null;
        if ($bolehLihatAset && filled($masukan['aset'] ?? null)) {
            $asetTerpilih = Aset::query()->whereKey($masukan['aset'])->first();
        } elseif ($bolehLihatAset && filled($masukan['kode'] ?? null)) {
            $asetTerpilih = $pencariAset->cari((string) $masukan['kode']);
        }
        $asetTerpilih?->load(['kategoriAset', 'lokasi' => fn ($q) => $q->with(['induk' => fn ($induk) => $induk->withoutGlobalScope(ScopeLingkup::class)])]);

        $lokasi = $penyusun->cariLokasi($asetTerpilih?->LokasiId)
            ?? $penyusun->cariLokasi($masukan['lokasi'] ?? null)
            ?? $penyusun->lokasiSaya($pengguna);

        return Inertia::render('Lapangan/Pelapor/Lapor', [
            'bolehLihatAset' => $bolehLihatAset,
            'lokasi' => $penyusun->ringkasLokasi($lokasi),
            'pilihanLokasi' => $penyusun->pilihanLokasi(),
            'aset' => $bolehLihatAset && $lokasi !== null ? $penyusun->asetDiLokasi($lokasi, $pengguna) : [],
            'asetTerpilih' => $asetTerpilih === null ? null : $this->ringkasAsetTerpilih($asetTerpilih, $penyusun, $pengguna),
            'asetTidakDitemukan' => $asetTerpilih === null && (filled($masukan['aset'] ?? null) || filled($masukan['kode'] ?? null)),
            'kategori' => KategoriKeluhan::query()
                ->where('Aktif', true)
                ->orderBy('Nama')
                ->orderBy('Id')
                ->get(['Id', 'Nama', 'AsetWajib'])
                ->map(fn (KategoriKeluhan $kategori): array => [
                    'Id' => $kategori->Id,
                    'Nama' => $kategori->Nama,
                    'AsetWajib' => $kategori->AsetWajib,
                ])
                ->values()
                ->all(),
            'kategoriAwal' => $masukan['kategori'] ?? null,
            'kontak' => ['Nama' => $pengguna->Nama, 'Telepon' => $pengguna->Telepon],
        ]);
    }

    public function store(
        Request $request,
        LayananLaporanLapangan $layanan,
        UnggahBerkas $unggahBerkas,
        LampirkanBerkas $lampirkanBerkas,
    ): RedirectResponse {
        $this->authorize('create', Keluhan::class);

        $pengguna = $request->user('web');
        $data = $request->validate([
            ...LayananLaporanLapangan::aturan((new SimpanKeluhanRequest)->rules()),
            'Foto' => ['sometimes', 'array', 'max:'.self::MAKS_FOTO],
            'Foto.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $pelanggaran = $layanan->pelanggaran($data);
        if ($pelanggaran !== []) {
            throw ValidationException::withMessages($pelanggaran);
        }

        $foto = $data['Foto'] ?? [];
        unset($data['Foto']);
        $hasil = $layanan->laporkan($data, $pengguna);

        if ($hasil['baru']) {
            $this->lampirkanFoto($foto, $hasil['keluhan'], $pengguna, $unggahBerkas, $lampirkanBerkas);
        }

        return redirect()->route('lapangan.pelapor.laporan.terkirim', $hasil['keluhan']);
    }

    /** @return array<string, mixed> */
    private function ringkasAsetTerpilih(Aset $aset, PenyusunLayarPelapor $penyusun, Pengguna $pengguna): array
    {
        $terbuka = $penyusun->laporanTerbukaUntukAset([$aset->Id], $pengguna);

        return $penyusun->ringkasAset($aset, $terbuka[$aset->Id] ?? []);
    }

    /** @param array<int, mixed> $foto */
    private function lampirkanFoto(
        array $foto,
        Keluhan $keluhan,
        Pengguna $pengguna,
        UnggahBerkas $unggahBerkas,
        LampirkanBerkas $lampirkanBerkas,
    ): void {
        foreach ($foto as $satu) {
            if (! $satu instanceof UploadedFile) {
                continue;
            }

            $berkas = $unggahBerkas->jalankan($satu, $pengguna->Id);
            $lampirkanBerkas->jalankan('Keluhan', $keluhan->Id, $berkas->Id, 'Bukti', null, $pengguna->Id);
        }
    }
}
