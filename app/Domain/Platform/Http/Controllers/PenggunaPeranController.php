<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Core\Izin\LingkupAkses;
use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Application\Actions\CabutPeranDariPengguna;
use App\Domain\Platform\Application\Actions\TetapkanPeranKePengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PenggunaPeranController extends Controller
{
    public function __construct(
        private readonly PemeriksaIzin $pemeriksaIzin,
        private readonly LingkupAkses $lingkupAkses,
    ) {}

    public function store(Request $request, Pengguna $pengguna, TetapkanPeranKePengguna $aksi): RedirectResponse
    {
        $this->pastikanBerizin($request);

        $data = $request->validate([
            'PeranId' => ['required', 'string'],
            'UnitOrganisasiId' => ['nullable', 'string'],
            'LokasiId' => ['nullable', 'string'],
            'KonfirmasiSeluruhOrganisasi' => ['sometimes', 'boolean'],
        ]);

        $peran = Peran::query()->where('Id', $data['PeranId'])->firstOrFail();

        $this->pastikanPelebaranDikonfirmasi($request, $pengguna, $data);

        $aksi->jalankan($pengguna, $peran, $data['UnitOrganisasiId'] ?? null, $data['LokasiId'] ?? null);

        return back()->with('sukses', 'Peran berhasil ditetapkan ke pengguna.');
    }

    public function destroy(Request $request, PenggunaPeran $penggunaPeran, CabutPeranDariPengguna $aksi): RedirectResponse
    {
        $this->pastikanBerizin($request);

        $aksi->jalankan($penggunaPeran);

        return back()->with('sukses', 'Peran berhasil dicabut dari pengguna.');
    }

    /**
     * Penetapan tanpa unit dan ruangan membuka seluruh organisasi (PRD 8.21).
     *
     * Itu sah -- admin memang boleh memberikannya -- jadi tidak diblokir. Tetapi
     * bagi pengguna yang sebelumnya berlingkup, satu penetapan tanpa lingkup
     * menghapus seluruh batasnya tanpa tanda apa pun di layar. Karena itu
     * server meminta konfirmasi eksplisit lebih dulu, bukan hanya peringatan
     * di formulir yang bisa terlewat.
     *
     * @param  array<string, mixed>  $data
     */
    private function pastikanPelebaranDikonfirmasi(Request $request, Pengguna $pengguna, array $data): void
    {
        $tanpaLingkup = blank($data['UnitOrganisasiId'] ?? null) && blank($data['LokasiId'] ?? null);

        if (! $tanpaLingkup
            || $request->boolean('KonfirmasiSeluruhOrganisasi')
            || $this->lingkupAkses->tanpaBatas((string) $pengguna->Id)) {
            return;
        }

        throw ValidationException::withMessages([
            'KonfirmasiSeluruhOrganisasi' => 'Pengguna ini sekarang hanya melihat data unit atau ruangan tertentu. '
                .'Peran tanpa unit dan ruangan akan membuatnya melihat seluruh organisasi. '
                .'Centang konfirmasi bila memang itu yang dimaksud, atau pilih unit atau ruangan.',
        ]);
    }

    private function pastikanBerizin(Request $request): void
    {
        abort_unless(
            $this->pemeriksaIzin->boleh((string) $request->user('web')->Id, 'Pengguna.Kelola'),
            403,
        );
    }
}
