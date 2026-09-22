<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\PencariRedirectPemasaran;
use App\Domain\Pemasaran\Domain\Enums\KodeRedirect;
use App\Domain\Pemasaran\Http\Requests\SimpanRedirectPemasaranRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RedirectPemasaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Peta redirect situs publik di konsol platform (MARKETING.md 9). */
final class RedirectPemasaranController extends Controller
{
    public function __construct(
        private readonly PencariRedirectPemasaran $pencari,
        private readonly LayananAudit $audit,
    ) {}

    public function index(): Response
    {
        $redirect = RedirectPemasaran::query()
            ->orderBy('Dari')
            ->get();

        return Inertia::render('Pemasaran/Redirect/Index', [
            'redirect' => $redirect->map(fn (RedirectPemasaran $satu): array => [
                'Id' => $satu->Id,
                'Dari' => $satu->Dari,
                'Ke' => $satu->Ke,
                'Kode' => $satu->Kode->value,
                'Aktif' => $satu->Aktif,
                'Catatan' => $satu->Catatan,
                'JumlahDipakai' => $satu->JumlahDipakai,
                'TerakhirDipakaiPada' => $satu->TerakhirDipakaiPada?->toIso8601String(),
            ])->all(),
            'pilihan' => ['Kode' => array_column(KodeRedirect::cases(), 'value')],
        ]);
    }

    public function store(SimpanRedirectPemasaranRequest $request): RedirectResponse
    {
        $redirect = RedirectPemasaran::create($this->atribut($request->validated()));
        $this->pencari->buangCache();

        $this->audit->catat('RedirectPemasaran.Dibuat', 'RedirectPemasaran', $redirect->Id, dataSesudah: [
            'Dari' => $redirect->Dari,
            'Ke' => $redirect->Ke,
            'Kode' => $redirect->Kode->value,
        ]);

        return back()->with('sukses', 'Redirect berhasil dibuat.');
    }

    public function update(
        SimpanRedirectPemasaranRequest $request,
        RedirectPemasaran $redirect,
    ): RedirectResponse {
        $sebelum = ['Dari' => $redirect->Dari, 'Ke' => $redirect->Ke, 'Kode' => $redirect->Kode->value];

        $redirect->update($this->atribut($request->validated()));
        $this->pencari->buangCache();

        $this->audit->catat(
            'RedirectPemasaran.Diubah',
            'RedirectPemasaran',
            $redirect->Id,
            dataSebelum: $sebelum,
            dataSesudah: ['Dari' => $redirect->Dari, 'Ke' => $redirect->Ke, 'Kode' => $redirect->Kode->value],
        );

        return back()->with('sukses', 'Redirect berhasil diperbarui.');
    }

    public function destroy(RedirectPemasaran $redirect): RedirectResponse
    {
        $sebelum = ['Dari' => $redirect->Dari, 'Ke' => $redirect->Ke, 'Kode' => $redirect->Kode->value];

        $redirect->delete();
        $this->pencari->buangCache();

        $this->audit->catat(
            'RedirectPemasaran.Dihapus',
            'RedirectPemasaran',
            $redirect->Id,
            dataSebelum: $sebelum,
        );

        return back()->with('sukses', 'Redirect berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function atribut(array $data): array
    {
        $kode = KodeRedirect::from((string) $data['Kode']);

        return [
            'Dari' => $data['Dari'],
            // Tujuan dibuang untuk 410, apa pun yang terkirim.
            'Ke' => $kode->butuhTujuan() ? $data['Ke'] : null,
            'Kode' => $kode,
            'Aktif' => (bool) ($data['Aktif'] ?? true),
            'Catatan' => $data['Catatan'] ?? null,
        ];
    }
}
