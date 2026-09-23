<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\KalkulatorKeandalanPublik;
use App\Domain\Pemasaran\Application\Services\PenautHostPengunjung;
use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use App\Domain\Pemasaran\Domain\Enums\ToolPublik;
use App\Domain\Pemasaran\Http\Requests\BuatQrAsetRequest;
use App\Domain\Pemasaran\Http\Requests\HitungKeandalanPublikRequest;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Qr\PembuatQrAset;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Tools publik: kalkulator keandalan dan generator QR aset (MARKETING.md 10). */
final class ToolsPublikController extends Controller
{
    public function __construct(
        private readonly PetaHost $host,
        private readonly PenautHostPengunjung $penaut,
        private readonly PerangkapSpam $perangkap,
    ) {}

    public function kalkulator(Request $request, ToolPublik $tool): Response
    {
        return Inertia::render('Publik/Kalkulator', [
            'wajib' => ['kalkulator' => AturanWajib::untuk(HitungKeandalanPublikRequest::class)],
            ...$this->propsBersama($request),
            'tool' => $this->ringkasTool($tool),
            // Hasil hitung dikirim `hitung()` lewat flash sesi; tanpa ini halaman tidak pernah menampilkannya.
            'hasil' => $request->session()->get('hasil'),
        ]);
    }

    /** Dihitung di server supaya rumusnya satu dengan KPI di dalam aplikasi. */
    public function hitung(
        HitungKeandalanPublikRequest $request,
        KalkulatorKeandalanPublik $kalkulator,
    ): RedirectResponse {
        if ($this->perangkap->terperangkap($request->validated())) {
            return back();
        }

        // Formulir mengirim angka sebagai teks; aturan `integer` menerimanya tanpa mengubah tipenya.
        $hasil = $kalkulator->hitung(
            $request->integer('JumlahAset'),
            $request->integer('HariRentang'),
            $request->integer('JumlahKegagalan'),
            $request->integer('MenitDowntime'),
        );

        return back()->with('hasil', $hasil->keArray());
    }

    public function qr(Request $request): Response
    {
        return Inertia::render('Publik/QrAset', [
            ...$this->propsBersama($request),
            'tool' => $this->ringkasTool(ToolPublik::QrAset),
            'qr' => $request->session()->get('qr'),
            'batas' => [
                'MaksKode' => BuatQrAsetRequest::MAKS_KODE,
                'MaksPanjangKode' => PembuatQrAset::MAKS_PANJANG_KODE,
            ],
        ]);
    }

    public function buatQr(BuatQrAsetRequest $request, PembuatQrAset $pembuat): RedirectResponse
    {
        if ($this->perangkap->terperangkap($request->validated())) {
            return back();
        }

        /** @var array{Kode: list<string>} $sah */
        $sah = $request->validated();

        return back()->with('qr', $pembuat->untuk($sah['Kode'], BuatQrAsetRequest::MAKS_KODE));
    }

    /** @return array<string, mixed> */
    private function ringkasTool(ToolPublik $tool): array
    {
        return [
            'Kode' => $tool->value,
            'Judul' => $tool->judul(),
            'Jalur' => $tool->jalur(),
            'MetrikSorotan' => $tool->metrikSorotan(),
            'FieldPerangkap' => PerangkapSpam::FIELD,
        ];
    }

    /** @return array<string, mixed> */
    private function propsBersama(Request $request): array
    {
        $pengenal = $request->attributes->get('pengenalPengunjung');
        $pengenal = is_string($pengenal) ? $pengenal : null;

        return [
            'kanonik' => $this->host->urlKanonik($request->path()),
            'urlMasuk' => $this->penaut->tautan(route('login'), $pengenal),
            'urlDaftar' => $this->penaut->tautan(route('daftar'), $pengenal),
        ];
    }
}
