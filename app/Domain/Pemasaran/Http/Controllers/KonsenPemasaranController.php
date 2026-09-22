<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Actions\CatatPermintaanData;
use App\Domain\Pemasaran\Application\Actions\ProsesPermintaanData;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\JenisPermintaanData;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DaftarSupresi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KonsenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PermintaanDataProspek;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

/** Consent, daftar supresi, dan permintaan data di konsol platform (MARKETING.md 27). */
final class KonsenPemasaranController extends Controller
{
    public function __construct(
        private readonly LayananKonsen $konsen,
        private readonly CatatPermintaanData $catatPermintaan,
        private readonly ProsesPermintaanData $prosesPermintaan,
    ) {}

    public function index(Request $request): Response
    {
        $cari = mb_strtolower(trim((string) $request->query('cari', '')));

        return Inertia::render('Pemasaran/Email/Konsen', [
            'cari' => $cari,
            'supresi' => DaftarSupresi::query()
                ->when($cari !== '', fn ($kueri) => $kueri->where('Email', 'like', "%{$cari}%"))
                ->orderByDesc('DitambahkanPada')
                ->limit(200)
                ->get()
                ->map(fn (DaftarSupresi $satu): array => [
                    'Id' => $satu->Id,
                    'Email' => $satu->Email,
                    'Alasan' => $satu->Alasan->value,
                    'Catatan' => $satu->Catatan,
                    'DitambahkanPada' => $satu->DitambahkanPada->toIso8601String(),
                ])->all(),
            'riwayat' => $cari === '' ? [] : KonsenPemasaran::query()
                ->where('Email', 'like', "%{$cari}%")
                ->orderByDesc('DicatatPada')
                ->limit(100)
                ->get()
                ->map(fn (KonsenPemasaran $satu): array => [
                    'Id' => $satu->Id,
                    'Email' => $satu->Email,
                    'Diberikan' => $satu->Diberikan,
                    'Sumber' => $satu->Sumber->value,
                    'VersiKebijakan' => $satu->VersiKebijakan,
                    'DicatatPada' => $satu->DicatatPada->toIso8601String(),
                ])->all(),
            'permintaan' => PermintaanDataProspek::query()
                ->orderByRaw('DiprosesPada is null desc')
                ->orderByDesc('DimintaPada')
                ->limit(100)
                ->get()
                ->map(fn (PermintaanDataProspek $satu): array => [
                    'Id' => $satu->Id,
                    'Email' => $satu->Email,
                    'Jenis' => $satu->Jenis->value,
                    'Catatan' => $satu->Catatan,
                    'DimintaPada' => $satu->DimintaPada->toIso8601String(),
                    'DiprosesPada' => $satu->DiprosesPada?->toIso8601String(),
                ])->all(),
            'pilihan' => [
                'Alasan' => array_column(AlasanSupresi::cases(), 'value'),
                'Jenis' => array_column(JenisPermintaanData::cases(), 'value'),
            ],
        ]);
    }

    public function supresi(Request $request): RedirectResponse
    {
        /** @var array{Email: string, Alasan: string, Catatan?: string|null} $sah */
        $sah = $request->validate([
            'Email' => ['required', 'email', 'max:190'],
            'Alasan' => ['required', new Enum(AlasanSupresi::class)],
            'Catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $this->konsen->supresi(
            $sah['Email'],
            AlasanSupresi::from($sah['Alasan']),
            $sah['Catatan'] ?? null,
        );

        return back()->with('sukses', 'Alamat berhasil dimasukkan ke daftar supresi.');
    }

    public function catatPermintaan(Request $request): RedirectResponse
    {
        /** @var array{Email: string, Jenis: string, Catatan?: string|null} $sah */
        $sah = $request->validate([
            'Email' => ['required', 'email', 'max:190'],
            'Jenis' => ['required', new Enum(JenisPermintaanData::class)],
            'Catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $this->catatPermintaan->jalankan(
            $sah['Email'],
            JenisPermintaanData::from($sah['Jenis']),
            catatan: $sah['Catatan'] ?? null,
        );

        return back()->with('sukses', 'Permintaan dicatat dan alamatnya langsung disupresi.');
    }

    public function prosesPermintaan(PermintaanDataProspek $permintaan): RedirectResponse
    {
        $this->prosesPermintaan->jalankan($permintaan);

        return back()->with('sukses', 'Permintaan data berhasil diproses.');
    }
}
