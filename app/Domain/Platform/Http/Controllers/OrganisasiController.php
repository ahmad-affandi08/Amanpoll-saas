<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\UbahProfilOrganisasi;
use App\Domain\Platform\Application\Actions\UnggahLogoOrganisasi;
use App\Domain\Platform\Http\Requests\SimpanOrganisasiRequest;
use App\Domain\Platform\Http\Requests\UnggahLogoOrganisasiRequest;
use App\Domain\Platform\Http\Resources\OrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class OrganisasiController extends Controller
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    public function edit(): Response
    {
        $organisasi = $this->organisasiSaatIni();
        $this->authorize('view', $organisasi);

        return Inertia::render('Organisasi/Index', [
            'wajib' => ['organisasi' => AturanWajib::untuk(SimpanOrganisasiRequest::class)],
            'organisasi' => new OrganisasiResource($organisasi),
        ]);
    }

    public function update(SimpanOrganisasiRequest $request, UbahProfilOrganisasi $aksi): RedirectResponse
    {
        $organisasi = $this->organisasiSaatIni();
        $this->authorize('update', $organisasi);

        $aksi->jalankan($organisasi, $request->validated());

        return back()->with('sukses', 'Profil organisasi berhasil diperbarui.');
    }

    public function unggahLogo(UnggahLogoOrganisasiRequest $request, UnggahLogoOrganisasi $aksi): RedirectResponse
    {
        $organisasi = $this->organisasiSaatIni();
        $this->authorize('update', $organisasi);

        $aksi->jalankan($organisasi, $request->file('Logo'));

        return back()->with('sukses', 'Logo organisasi berhasil diperbarui.');
    }

    private function organisasiSaatIni(): Organisasi
    {
        return Organisasi::query()->findOrFail($this->konteks->wajibId());
    }
}
