<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PemeriksaFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Http\Requests\SimpanKonfigurasiPemasaranRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FiturPlatform;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Pengaturan Growth & Marketing (MARKETING.md 30). */
final class PengaturanPemasaranController extends Controller
{
    public function __construct(
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
        private readonly PemeriksaFiturPlatform $fitur,
        private readonly LayananAudit $audit,
    ) {}

    public function index(PetaHost $host): Response
    {
        return Inertia::render('Pemasaran/Pengaturan', [
            // Host hanya dibaca, tidak pernah diubah lewat form.
            'domain' => [
                'publik' => $host->publik(),
                'dashboard' => $host->dashboard(),
                'partner' => $host->partner(),
                'kanonik' => $host->publikKanonik(),
                'situsPublikAktif' => $host->situsPublikAktif(),
            ],
            'fitur' => $this->ringkasFitur(),
            'konfigurasi' => $this->ringkasKonfigurasi(),
        ]);
    }

    public function ubahFitur(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'Kode' => ['required', 'string', Rule::in(KatalogFiturPlatform::kode())],
            'Aktif' => ['required', 'boolean'],
        ]);

        $baris = FiturPlatform::query()->where('Kode', $data['Kode'])->firstOrFail();
        $sebelum = (bool) $baris->Aktif;

        $baris->Aktif = (bool) $data['Aktif'];
        $baris->save();

        $this->fitur->bersihkanCache();

        $this->audit->catat(
            'FiturPlatform.Diubah',
            'FiturPlatform',
            $baris->Id,
            dataSebelum: ['Aktif' => $sebelum],
            dataSesudah: ['Aktif' => (bool) $baris->Aktif],
        );

        return back()->with('sukses', 'Status modul berhasil diperbarui.');
    }

    public function simpanKonfigurasi(SimpanKonfigurasiPemasaranRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $sebelum = $this->konfigurasi->ambil($data['Kunci']);

        $this->konfigurasi->simpan($data['Kunci'], $data['Nilai']);

        $this->audit->catat(
            'KonfigurasiPemasaran.Diubah',
            'KonfigurasiPemasaran',
            null,
            dataSebelum: ['Kunci' => $data['Kunci'], 'Nilai' => $sebelum],
            dataSesudah: ['Kunci' => $data['Kunci'], 'Nilai' => $data['Nilai']],
        );

        return back()->with('sukses', 'Konfigurasi berhasil disimpan.');
    }

    /** @return list<array<string, mixed>> */
    private function ringkasFitur(): array
    {
        $status = $this->fitur->status();

        return array_map(
            fn (string $kode): array => [
                'Kode' => $kode,
                'Nama' => KatalogFiturPlatform::semua()[$kode]['nama'],
                'Keterangan' => KatalogFiturPlatform::semua()[$kode]['keterangan'],
                'Aktif' => $status[$kode] ?? false,
            ],
            KatalogFiturPlatform::kode(),
        );
    }

    /** @return list<array<string, mixed>> */
    private function ringkasKonfigurasi(): array
    {
        $efektif = $this->konfigurasi->efektif();

        return array_map(
            fn (string $kunci): array => [
                'Kunci' => $kunci,
                'Keterangan' => KatalogKonfigurasiPemasaran::keterangan($kunci),
                'Nilai' => $efektif[$kunci] ?? null,
                'Bawaan' => KatalogKonfigurasiPemasaran::bawaan($kunci),
            ],
            KatalogKonfigurasiPemasaran::kunci(),
        );
    }
}
