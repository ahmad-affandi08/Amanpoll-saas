<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarTeknisi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tab Aset dan Riwayat aset teknisi (DESIGN §36.6 layar 14–15).
 *
 * Daftar aset tunduk pada `AsetPolicy` dan lingkup unit pengguna seperti
 * dasbor; tanpa `Aset.Lihat` layar menampilkan keadaan tanpa izin.
 */
final class LapanganTeknisiAsetController extends Controller
{
    /** Hasil pencarian aset yang dibawa ke layar. */
    private const BATAS_HASIL = 30;

    public function __construct(private readonly PenyusunLayarTeknisi $penyusun) {}

    public function index(Request $request): Response
    {
        $pengguna = $request->user('web');
        $kata = trim((string) $request->query('cari', ''));
        $bolehLihat = Gate::forUser($pengguna)->allows('viewAny', Aset::class);

        if (! $bolehLihat) {
            return Inertia::render('Lapangan/Teknisi/Aset', [
                'aset' => [], 'cari' => $kata, 'asetTerpilih' => null, 'bolehLihat' => false,
            ]);
        }

        $kueri = Aset::query()->with(['kategoriAset:Id,Nama', 'lokasi:Id,Nama,IndukId', 'lokasi.induk:Id,Nama']);

        if ($kata !== '') {
            $pola = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $kata).'%';
            $kueri->where(fn ($dalam) => $dalam->where('Nama', 'like', $pola)->orWhere('KodeAset', 'like', $pola));
        } else {
            // Tanpa kata kunci: aset di tiket yang sedang ditugaskan kepadanya.
            $kueri->whereIn('Id', PerintahKerjaAset::query()
                ->select('AsetId')
                ->whereIn('PerintahKerjaId', $this->penyusun->kueriDitugaskan($pengguna)->select('Id')));
        }

        $terpilihId = trim((string) $request->query('aset', ''));
        $terpilih = $terpilihId === '' ? null : Aset::query()->find($terpilihId);

        return Inertia::render('Lapangan/Teknisi/Aset', [
            'aset' => array_values($kueri->orderBy('Nama')->orderBy('Id')->limit(self::BATAS_HASIL)->get()
                ->map(fn (Aset $aset): array => $this->penyusun->ringkasAset($aset))
                ->all()),
            'cari' => $kata,
            'asetTerpilih' => $terpilih === null || Gate::forUser($pengguna)->denies('view', $terpilih)
                ? null
                : $this->penyusun->detailAset($terpilih, $pengguna),
            'bolehLihat' => true,
        ]);
    }

    public function riwayat(Request $request, Aset $aset): Response
    {
        $this->authorize('view', $aset);
        $aset->loadMissing(['kategoriAset:Id,Nama', 'lokasi:Id,Nama,IndukId', 'lokasi.induk:Id,Nama']);

        return Inertia::render('Lapangan/Teknisi/RiwayatAset', [
            'aset' => $this->penyusun->ringkasAset($aset),
            ...$this->penyusun->riwayatAset($aset),
        ]);
    }
}
