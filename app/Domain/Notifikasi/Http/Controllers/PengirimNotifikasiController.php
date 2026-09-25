<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Controllers;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Notifikasi\Application\Actions\KirimEmailUjiPenyedia;
use App\Domain\Notifikasi\Application\Services\PemilihPengirimNotifikasi;
use App\Domain\Notifikasi\Application\Services\PenghitungKuotaWhatsApp;
use App\Domain\Notifikasi\Application\Services\TujuanWhatsAppNotifikasi;
use App\Domain\Notifikasi\Domain\Contracts\DapatMengirimNotifikasiWhatsApp;
use App\Domain\Notifikasi\Domain\Contracts\PenyediaEmail;
use App\Domain\Notifikasi\Http\Requests\SimpanPengirimNotifikasiRequest;
use App\Domain\Platform\Application\Actions\CatatKesehatanPenyediaOrganisasi;
use App\Domain\Platform\Application\Actions\HapusPenyediaLayananOrganisasi;
use App\Domain\Platform\Application\Actions\SimpanPenyediaLayananOrganisasi;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Application\Services\PembacaKredensialPenyedia;
use App\Domain\Platform\Application\Services\PenyajiPenyediaLayanan;
use App\Domain\Platform\Domain\Contracts\DapatDiujiKoneksi;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananOrganisasi;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use App\Shared\Domain\ValueObjects\NomorWhatsApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Halaman "Email & WhatsApp": pengantar notifikasi milik organisasi (PRD 8.23).
 *
 * Tanpa pengaturan di sini, notifikasi berangkat lewat email dan nomor Amanpoll; WhatsApp
 * lewat nomor Amanpoll memakai kuota paket. Nilai isian rahasia tidak pernah dikirim ke
 * peramban.
 */
final class PengirimNotifikasiController extends Controller
{
    private const PESAN_BELUM_TERSIMPAN = 'Simpan kredensialnya dulu sebelum diuji.';

    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly KatalogPenyediaLayanan $katalog,
        private readonly PenyajiPenyediaLayanan $penyaji,
    ) {}

    public function index(
        Request $request,
        PemilihPengirimNotifikasi $pemilih,
        PenghitungKuotaWhatsApp $kuota,
        PembacaKredensialPenyedia $pembaca,
        TujuanWhatsAppNotifikasi $tujuanWhatsApp,
    ): Response {
        $this->authorize('viewAny', PenyediaLayananOrganisasi::class);

        $organisasiId = $this->konteks->wajibId();
        $tersimpan = PenyediaLayananOrganisasi::query()->get()
            ->keyBy(fn (PenyediaLayananOrganisasi $baris): string => $baris->Kategori->value.'|'.$baris->Kode);
        $organisasi = Organisasi::query()->whereKey($organisasiId)->first(['Id', 'Nama', 'Email']);
        $pengguna = $this->pengguna($request);
        $nomorSaya = $tujuanWhatsApp->nomorUntuk($pengguna->Id);

        $kategori = array_map(
            fn (KategoriPenyediaLayanan $kategori): array => [
                'Kode' => $kategori->value,
                'Label' => $kategori->label(),
                'Penyedia' => array_map(
                    fn (DeskripsiPenyediaLayanan $penyedia): array => $this->ringkas(
                        $penyedia,
                        $tersimpan->get($kategori->value.'|'.$penyedia->kode()),
                    ),
                    $this->katalog->menurutKategori($kategori),
                ),
            ],
            // Urutan sama dengan ringkasan "Yang dipakai sekarang": email dulu.
            [KategoriPenyediaLayanan::Email, KategoriPenyediaLayanan::WhatsApp],
        );

        return Inertia::render('PengirimNotifikasi/Index', [
            'bolehPenyediaSendiri' => $pemilih->bolehPenyediaSendiri($organisasiId),
            'kategori' => $kategori,
            'kuotaWhatsApp' => $kuota->untuk($organisasiId)->keArray(),
            'platform' => [
                'EmailAktif' => $pembaca->kodeUtama(KategoriPenyediaLayanan::Email) !== null,
                'WhatsAppAktif' => $tujuanWhatsApp->penyediaAktif(),
            ],
            'organisasi' => [
                'Nama' => (string) $organisasi?->Nama,
                'Email' => $organisasi?->Email === null ? null : (string) $organisasi->Email,
            ],
            'penerimaUji' => [
                'Email' => (string) $pengguna->Email,
                'WhatsApp' => $nomorSaya === null ? null : NomorWhatsApp::samarkan($nomorSaya),
            ],
        ]);
    }

    public function simpan(
        SimpanPengirimNotifikasiRequest $request,
        string $kategori,
        string $kode,
        SimpanPenyediaLayananOrganisasi $aksi,
    ): RedirectResponse {
        $this->authorize('update', PenyediaLayananOrganisasi::class);

        try {
            $aksi->jalankan(
                $this->konteks->wajibId(),
                $this->kategori($kategori),
                $kode,
                $request->validated(),
                $this->pengguna($request)->Id,
            );
        } catch (AturanBisnisDilanggar $e) {
            throw ValidationException::withMessages(['Kredensial' => $e->getMessage()]);
        }

        return back()->with('sukses', 'Pengaturan pengirim disimpan.');
    }

    public function hapus(string $kategori, string $kode, HapusPenyediaLayananOrganisasi $aksi): RedirectResponse
    {
        $this->authorize('update', PenyediaLayananOrganisasi::class);

        $aksi->jalankan($this->konteks->wajibId(), $this->kategori($kategori), $kode);

        return back()->with('sukses', 'Kredensial dihapus. Notifikasi kembali lewat pengirim Amanpoll.');
    }

    /** Mencoba kredensial yang tersimpan, termasuk milik penyedia yang belum diaktifkan. */
    public function uji(string $kategori, string $kode, CatatKesehatanPenyediaOrganisasi $kesehatan): JsonResponse
    {
        $this->authorize('update', PenyediaLayananOrganisasi::class);

        $tersimpan = $this->penyediaTersimpan($kategori, $kode);

        if ($tersimpan === null) {
            return $this->jawab(false, self::PESAN_BELUM_TERSIMPAN);
        }

        [$penyedia, $baris] = $tersimpan;

        if (! $penyedia instanceof DapatDiujiKoneksi) {
            return $this->jawab(false, 'Penyedia ini tidak menyediakan uji koneksi.');
        }

        return $this->dengan($penyedia, $baris, $kesehatan, function () use ($penyedia, $baris): array {
            $hasil = $penyedia->ujiKoneksi($baris->keKredensial());

            return [$hasil->berhasil, $hasil->pesan];
        });
    }

    /** Email uji ke alamat pengguna ini, atau WhatsApp uji ke nomornya. */
    public function kirimUji(
        Request $request,
        string $kategori,
        string $kode,
        KirimEmailUjiPenyedia $kirimEmail,
        TujuanWhatsAppNotifikasi $tujuanWhatsApp,
        CatatKesehatanPenyediaOrganisasi $kesehatan,
    ): JsonResponse {
        $this->authorize('update', PenyediaLayananOrganisasi::class);

        $tersimpan = $this->penyediaTersimpan($kategori, $kode);

        if ($tersimpan === null) {
            return $this->jawab(false, self::PESAN_BELUM_TERSIMPAN);
        }

        [$penyedia, $baris] = $tersimpan;
        $pengguna = $this->pengguna($request);

        if ($penyedia instanceof PenyediaEmail) {
            $tujuan = trim((string) $pengguna->Email);

            return $this->dengan($penyedia, $baris, $kesehatan, function () use ($kirimEmail, $penyedia, $baris, $tujuan): array {
                $hasil = $kirimEmail->jalankan($penyedia, $baris->keKredensial(), $tujuan);

                return [$hasil->berhasil, $hasil->pesan];
            });
        }

        if (! $penyedia instanceof DapatMengirimNotifikasiWhatsApp) {
            return $this->jawab(false, 'Penyedia ini tidak dapat mengirim pesan uji.');
        }

        $nomor = $tujuanWhatsApp->nomorUntuk($pengguna->Id);

        if ($nomor === null) {
            return $this->jawab(false, 'Isi nomor telepon di profil Anda dulu untuk menerima WhatsApp uji.');
        }

        return $this->dengan($penyedia, $baris, $kesehatan, function () use ($penyedia, $baris, $nomor): array {
            try {
                $penyedia->kirimNotifikasiDengan(
                    $baris->keKredensial(),
                    $nomor,
                    'WhatsApp uji Amanpoll',
                    'Pesan ini memastikan nomor WhatsApp organisasi Anda dapat mengirim notifikasi ke staf.',
                );
            } catch (AturanBisnisDilanggar $galat) {
                throw new AturanBisnisDilanggar(str_replace($nomor, NomorWhatsApp::samarkan($nomor), $galat->getMessage()), previous: $galat);
            }

            return [true, 'WhatsApp uji dikirim ke '.NomorWhatsApp::samarkan($nomor).'. Periksa ponsel Anda.'];
        });
    }

    /** @return array<string, mixed> */
    private function ringkas(DeskripsiPenyediaLayanan $penyedia, ?PenyediaLayananOrganisasi $baris): array
    {
        return [
            ...$this->penyaji->ringkas($penyedia, $baris?->nilaiKredensial() ?? [], organisasi: true),
            'Aktif' => (bool) ($baris->Aktif ?? false),
            'Utama' => false,
            'ModeUji' => (bool) ($baris->ModeUji ?? false),
            'DiperbaruiPada' => $baris?->DiperbaruiPada?->toIso8601String(),
            'Bermasalah' => (bool) $baris?->sedangBermasalah(),
            'GalatTerakhir' => $baris?->GalatTerakhir,
            'TerakhirBerhasilPada' => $baris?->TerakhirBerhasilPada?->toIso8601String(),
            'TerakhirGagalPada' => $baris?->TerakhirGagalPada?->toIso8601String(),
        ];
    }

    /** @return array{0: DeskripsiPenyediaLayanan, 1: PenyediaLayananOrganisasi}|null */
    private function penyediaTersimpan(string $kategori, string $kode): ?array
    {
        $jenis = $this->kategori($kategori);
        $penyedia = $this->katalog->untuk($jenis, $kode) ?? throw new DataTidakDitemukan("Penyedia {$kode} tidak dikenal.");

        $baris = PenyediaLayananOrganisasi::query()->where('Kategori', $jenis->value)->where('Kode', $kode)->first();

        return $baris === null || $baris->nilaiKredensial() === [] ? null : [$penyedia, $baris];
    }

    /**
     * Menjalankan satu uji dan menerjemahkan hasil atau galatnya menjadi jawaban JSON.
     * Keberhasilan dicatat, sehingga peringatan "bermasalah" hilang setelah penyedianya diperbaiki.
     *
     * @param  callable(): array{0: bool, 1: string}  $uji
     */
    private function dengan(
        DeskripsiPenyediaLayanan $penyedia,
        PenyediaLayananOrganisasi $baris,
        CatatKesehatanPenyediaOrganisasi $kesehatan,
        callable $uji,
    ): JsonResponse {
        try {
            [$berhasil, $pesan] = $uji();
        } catch (AturanBisnisDilanggar $e) {
            return $this->jawab(false, $baris->keKredensial()->sensor($e->getMessage(), $penyedia->isian()));
        } catch (Throwable $e) {
            // Pesan mentah bisa memuat URL berisi kredensial; yang dicatat hanya jenis galatnya.
            Log::warning('Uji penyedia organisasi gagal.', ['Kode' => $penyedia->kode(), 'Galat' => $e::class]);

            return $this->jawab(false, 'Penyedia tidak dapat dihubungi. Coba lagi nanti.');
        }

        if ($berhasil) {
            $kesehatan->berhasil((string) $baris->Id);
        }

        return $this->jawab($berhasil, $pesan);
    }

    private function jawab(bool $berhasil, string $pesan): JsonResponse
    {
        return response()->json(['Berhasil' => $berhasil, 'Pesan' => $pesan], $berhasil ? 200 : 422);
    }

    private function kategori(string $kode): KategoriPenyediaLayanan
    {
        $kategori = KategoriPenyediaLayanan::tryFrom($kode);

        if ($kategori === null || ! $kategori->bolehMilikOrganisasi()) {
            throw new DataTidakDitemukan('Kategori penyedia tidak dikenal.');
        }

        return $kategori;
    }

    private function pengguna(Request $request): Pengguna
    {
        $pengguna = $request->user();

        if (! $pengguna instanceof Pengguna) {
            abort(403);
        }

        return $pengguna;
    }
}
