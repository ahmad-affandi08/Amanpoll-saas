<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Controllers;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Pelaporan\Application\Services\LayananEksporLaporan;
use App\Domain\Pelaporan\Application\Services\LayananMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Http\Requests\MintaEksporLaporanRequest;
use App\Domain\Pelaporan\Jobs\BuatEksporLaporan;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Permintaan dan pengunduhan ekspor laporan (21.05). */
final class EksporLaporanController extends Controller
{
    public function store(
        MintaEksporLaporanRequest $request,
        LayananMetrik $layananMetrik,
    ): RedirectResponse {
        $pengguna = $request->user('web');
        $data = $request->validated();

        // Kunci disaring di sini juga, bukan hanya di job.
        $diizinkan = $layananMetrik->saringYangDiizinkan(array_values($data['KunciKpi']), $pengguna);
        if (count($diizinkan) !== count($data['KunciKpi'])) {
            throw new AksesDitolak('Ada KPI pada permintaan ekspor yang tidak boleh Anda lihat.');
        }
        if ($diizinkan === []) {
            throw new AturanBisnisDilanggar('Pilih minimal satu KPI untuk diekspor.');
        }

        BuatEksporLaporan::dispatch(
            $pengguna->Id,
            $diizinkan,
            FilterMetrik::dariArray($data['Filter'] ?? [])->keArray(),
            $data['Format'],
            $data['Judul'],
        );

        return back()->with(
            'sukses',
            'Ekspor sedang diproses. Anda akan menerima notifikasi begitu berkasnya siap.',
        );
    }

    /** Unduhan diotorisasi per berkas, bukan hanya per rute. */
    public function unduh(Request $request, Berkas $berkas): StreamedResponse
    {
        $this->authorize('view', $berkas);

        $jenis = (string) ($berkas->DataTambahan['Jenis'] ?? '');
        if ($jenis !== LayananEksporLaporan::JENIS_BERKAS) {
            throw new AksesDitolak('Berkas ini bukan hasil ekspor laporan.');
        }

        if ($berkas->DiunggahOleh !== $request->user('web')->Id) {
            throw new AksesDitolak('Ekspor hanya dapat diunduh oleh pemesannya.');
        }

        return Storage::disk($berkas->MediaPenyimpanan)->download($berkas->LokasiPenyimpanan, $berkas->NamaAsli);
    }
}
