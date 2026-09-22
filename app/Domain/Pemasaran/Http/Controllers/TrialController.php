<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Actions\PerpanjangTrial;
use App\Domain\Pemasaran\Application\Actions\PindahkanStatusTrial;
use App\Domain\Pemasaran\Application\Services\PembacaKonfigurasiTrial;
use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Http\Requests\PerpanjangTrialRequest;
use App\Domain\Pemasaran\Http\Requests\UbahStatusTrialRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\DaftarTersaring;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Pemantauan trial dan aktivasinya di konsol platform (MARKETING.md 12). */
final class TrialController extends Controller
{
    public function __construct(private readonly PembacaKonfigurasiTrial $konfigurasi) {}

    public function index(Request $request): Response
    {
        $daftar = DaftarTersaring::untuk($request, Trial::query()->with(['organisasi', 'prospek', 'butir']))
            ->urut(['Status', 'MulaiPada', 'BerakhirPada', 'DibuatPada'], bawaan: 'DibuatPada', arahBawaan: 'desc')
            ->faset(['Status']);

        return Inertia::render('Pemasaran/Trial/Index', [
            'wajib' => ['perpanjang' => AturanWajib::untuk(PerpanjangTrialRequest::class), 'status' => AturanWajib::untuk(UbahStatusTrialRequest::class)],
            'trial' => $daftar->halamanTerpeta(fn (Trial $satu): array => $this->ringkas($satu)),
            'filter' => $daftar->filterBerlaku(),
            'konfigurasi' => $this->konfigurasi->berlaku()->keArray(),
            'pilihan' => [
                'Status' => array_column(StatusTrial::cases(), 'value'),
                'Butir' => array_map(
                    fn (ButirAktivasi $butir): array => ['Kode' => $butir->value, 'Label' => $butir->label()],
                    ButirAktivasi::cases(),
                ),
                'ButirWajib' => array_map(
                    fn (ButirAktivasi $butir): string => $butir->value,
                    ButirAktivasi::wajibUntukAktivasi(),
                ),
            ],
        ]);
    }

    public function perpanjang(
        PerpanjangTrialRequest $request,
        Trial $trial,
        PerpanjangTrial $aksi,
    ): RedirectResponse {
        $data = $request->validated();
        $aksi->jalankan($trial, (int) $data['Hari'], $data['Alasan'] ?? null);

        return back()->with('sukses', 'Trial berhasil diperpanjang.');
    }

    public function ubahStatus(
        UbahStatusTrialRequest $request,
        Trial $trial,
        PindahkanStatusTrial $aksi,
    ): RedirectResponse {
        $data = $request->validated();
        $aksi->jalankan($trial, StatusTrial::from($data['Status']), $data['Alasan'] ?? null);

        return back()->with('sukses', 'Status trial berhasil diperbarui.');
    }

    /** @return array<string, mixed> */
    private function ringkas(Trial $trial): array
    {
        return [
            'Id' => $trial->Id,
            'Organisasi' => $trial->organisasi?->Nama,
            'OrganisasiId' => $trial->OrganisasiId,
            'Prospek' => $trial->prospek?->Nama,
            'ProspekId' => $trial->ProspekId,
            'Status' => $trial->Status->value,
            'MulaiPada' => $trial->MulaiPada->toIso8601String(),
            'BerakhirPada' => $trial->BerakhirPada->toIso8601String(),
            'TeraktivasiPada' => $trial->TeraktivasiPada?->toIso8601String(),
            'KonversiPada' => $trial->KonversiPada?->toIso8601String(),
            'HariPerpanjangan' => $trial->HariPerpanjangan,
            'ButirSelesai' => $trial->butirSelesai(),
            'StatusBerikutnya' => array_map(
                fn (StatusTrial $status): string => $status->value,
                $trial->Status->tujuanYangDiizinkan(),
            ),
        ];
    }
}
