<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Core\Konfigurasi\LayananKonfigurasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\SimpanKonfigurasiOrganisasi;
use App\Domain\Platform\Http\Requests\SimpanKonfigurasiOrganisasiRequest;
use App\Domain\Platform\Http\Resources\KonfigurasiOrganisasiResource;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class KonfigurasiOrganisasiController extends Controller
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananKonfigurasi $layananKonfigurasi,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', KonfigurasiOrganisasi::class);

        return Inertia::render('KonfigurasiOrganisasi/Index', [
            'konfigurasi' => KonfigurasiOrganisasiResource::collection(
                $this->layananKonfigurasi->semua($this->konteks->wajibId()),
            ),
        ]);
    }

    public function update(SimpanKonfigurasiOrganisasiRequest $request, string $kunci, SimpanKonfigurasiOrganisasi $aksi): RedirectResponse
    {
        $this->authorize('update', KonfigurasiOrganisasi::class);

        $aksi->jalankan($this->konteks->wajibId(), $kunci, $request->validated()['Nilai']);

        return back()->with('sukses', 'Konfigurasi berhasil diperbarui.');
    }
}
