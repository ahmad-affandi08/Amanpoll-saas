<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Core\Audit\LayananCatatanAkses;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\PencariAkunMasuk;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Masuk dengan email dan kata sandi, tanpa kode organisasi (PRD 8.1).
 *
 * Satu akun cocok langsung masuk. Beberapa akun cocok (email yang sama di
 * beberapa organisasi) dibawa ke layar Pilih organisasi; daftar Id akun yang
 * kata sandinya terbukti cocok disimpan sementara di sesi, dan hanya akun di
 * daftar itu yang diterima saat memilih.
 */
final class LoginController extends Controller
{
    private const MAKS_PERCOBAAN = 5;

    private const DURASI_KUNCI_DETIK = 60;

    /** Kunci sesi daftar akun yang menunggu dipilih. */
    public const SESI_PILIHAN = 'masuk.pilihanOrganisasi';

    public const KEDALUWARSA_PILIHAN_MENIT = 5;

    private const PESAN_GAGAL = 'Email atau kata sandi tidak sesuai.';

    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly LayananCatatanAkses $layananCatatanAkses,
        private readonly PenentuModeLapangan $penentuModeLapangan,
        private readonly PencariAkunMasuk $pencariAkun,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'Email' => ['required', 'email'],
            'KataSandi' => ['required', 'string'],
            'IngatSaya' => ['sometimes', 'boolean'],
        ]);

        $kunciBatas = $this->kunciBatasPercobaan($request, $data['Email']);

        if (RateLimiter::tooManyAttempts($kunciBatas, self::MAKS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunciBatas);
            throw ValidationException::withMessages([
                'Email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
            ]);
        }

        $kandidat = $this->pencariAkun->akunAktif($data['Email']);
        $cocok = $this->pencariAkun->cocokkanKataSandi($kandidat, $data['KataSandi']);
        $ingatSaya = $request->boolean('IngatSaya');

        if ($cocok->isEmpty()) {
            RateLimiter::hit($kunciBatas, self::DURASI_KUNCI_DETIK);
            $this->catatGagal($kandidat);
            throw ValidationException::withMessages(['Email' => self::PESAN_GAGAL]);
        }

        RateLimiter::clear($kunciBatas);

        if ($cocok->count() === 1) {
            return $this->masukkan($request, $cocok->sole(), $ingatSaya);
        }

        // Sesi diganti begitu kata sandi terbukti, supaya Id sesi yang mungkin
        // ditanam orang lain tidak dapat dipakai memilih lebih dulu.
        $request->session()->regenerate();
        $request->session()->put(self::SESI_PILIHAN, [
            'PenggunaId' => $cocok->pluck('Id')->map(fn (mixed $id): string => (string) $id)->values()->all(),
            'IngatSaya' => $ingatSaya,
            'KedaluwarsaPada' => now()->addMinutes(self::KEDALUWARSA_PILIHAN_MENIT)->getTimestamp(),
        ]);

        return redirect()->route('login.organisasi');
    }

    public function pilihOrganisasi(Request $request): Response|RedirectResponse
    {
        $daftarId = $this->daftarIdPilihan($request);

        if ($daftarId === null) {
            return $this->kembaliKeLogin($request);
        }

        $akun = $this->pencariAkun->akunAktifDenganId($daftarId);

        if ($akun->isEmpty()) {
            return $this->kembaliKeLogin($request);
        }

        return Inertia::render('Auth/PilihOrganisasi', [
            'pilihan' => $akun
                ->map(fn (Pengguna $pengguna): array => [
                    'PenggunaId' => (string) $pengguna->Id,
                    'NamaOrganisasi' => (string) $pengguna->organisasi?->Nama,
                ])
                ->sortBy('NamaOrganisasi', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
        ]);
    }

    public function masukKeOrganisasi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'PenggunaId' => ['required', 'string'],
        ]);

        $daftarId = $this->daftarIdPilihan($request);

        if ($daftarId === null) {
            return $this->kembaliKeLogin($request);
        }

        $pengguna = in_array($data['PenggunaId'], $daftarId, true)
            ? $this->pencariAkun->akunAktifDenganId([$data['PenggunaId']])->first()
            : null;

        if ($pengguna === null) {
            throw ValidationException::withMessages([
                'PenggunaId' => 'Organisasi yang dipilih tidak tersedia.',
            ]);
        }

        $ingatSaya = (bool) $request->session()->get(self::SESI_PILIHAN.'.IngatSaya', false);
        $request->session()->forget(self::SESI_PILIHAN);

        return $this->masukkan($request, $pengguna, $ingatSaya);
    }

    public function batalPilihOrganisasi(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESI_PILIHAN);

        return redirect()->route('login');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function masukkan(Request $request, Pengguna $pengguna, bool $ingatSaya): RedirectResponse
    {
        $organisasiId = (string) $pengguna->OrganisasiId;
        $this->konteks->tetapkan($organisasiId);

        Auth::guard('web')->login($pengguna, $ingatSaya);
        $request->session()->regenerate();
        DB::table('Pengguna')->where('Id', $pengguna->Id)->update(['TerakhirMasukPada' => now()]);
        $this->layananCatatanAkses->catat('Login', $organisasiId, (string) $pengguna->Id, true);

        return redirect()->intended($this->tujuanBawaan($pengguna));
    }

    /**
     * Percobaan gagal dicatat di organisasi tiap akun yang email-nya cocok, supaya
     * admin tenant melihat percobaan terhadap penggunanya. Email yang tidak
     * terdaftar dicatat tanpa organisasi.
     *
     * @param  Collection<int, Pengguna>  $kandidat
     */
    private function catatGagal(Collection $kandidat): void
    {
        if ($kandidat->isEmpty()) {
            $this->layananCatatanAkses->catat('Login', null, null, false, 'Email tidak terdaftar pada akun aktif.');

            return;
        }

        foreach ($kandidat as $pengguna) {
            $this->layananCatatanAkses->catat('Login', (string) $pengguna->OrganisasiId, null, false, 'Kata sandi salah.');
        }
    }

    /**
     * Daftar Id akun yang menunggu dipilih, atau null bila tidak ada atau sudah
     * kedaluwarsa.
     *
     * @return list<string>|null
     */
    private function daftarIdPilihan(Request $request): ?array
    {
        $pilihan = $request->session()->get(self::SESI_PILIHAN);

        if (! is_array($pilihan) || ! is_array($pilihan['PenggunaId'] ?? null)) {
            return null;
        }

        if ((int) ($pilihan['KedaluwarsaPada'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        return array_values(array_filter($pilihan['PenggunaId'], is_string(...)));
    }

    /** Daftar dibuang; pesan hanya muncul bila memang ada daftar yang tak lagi berlaku. */
    private function kembaliKeLogin(Request $request): RedirectResponse
    {
        $adaPilihan = $request->session()->has(self::SESI_PILIHAN);
        $request->session()->forget(self::SESI_PILIHAN);

        $pengalihan = redirect()->route('login');

        return $adaPilihan
            ? $pengalihan->withErrors(['Email' => 'Pilihan organisasi sudah tidak berlaku. Silakan masuk lagi.'])
            : $pengalihan;
    }

    /**
     * Tujuan sesudah login bila tidak ada URL yang sedang dituju.
     *
     * Pengguna lapangan murni langsung ke Mode Lapangan (PRD 8.20). URL tujuan
     * dari `intended()` tetap dihormati; bila isinya halaman dasbor,
     * `ArahkanPenggunaLapangan` yang membawanya ke Mode Lapangan.
     */
    private function tujuanBawaan(Pengguna $pengguna): string
    {
        return $this->penentuModeLapangan->lapanganMurni($pengguna)
            ? route('lapangan.beranda')
            : route('dashboard');
    }

    /** Kunci per email dan IP; tidak lagi memuat kode organisasi. */
    private function kunciBatasPercobaan(Request $request, string $email): string
    {
        return 'masuk|'.Str::lower($email).'|'.$request->ip();
    }
}
