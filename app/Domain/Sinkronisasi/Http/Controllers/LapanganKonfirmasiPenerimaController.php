<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Core\Izin\PemeriksaLingkupBaris;
use App\Core\Izin\ScopeLingkup;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Application\Actions\KonfirmasiPenerimaLewatPindai;
use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\HasilKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\MetodeKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Http\Requests\KonfirmasiPenerimaRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KonfirmasiPenerimaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Application\Services\TautanKonfirmasiPenerima;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman konfirmasi penerima hasil pindai QR (PRD 8.22, cara 2).
 *
 * Terbuka bagi siapa pun yang masuk ke organisasinya -- pengguna lapangan murni
 * (jalurnya di bawah `/lapangan`, termasuk `JALUR_BEBAS`) maupun pengguna dasbor --
 * tanpa pagar mode Mode Lapangan. Yang menjaga: tanda tangan tautan (masa berlaku,
 * satu perintah kerja), tenancy pada pencarian tiket, dan
 * `PerintahKerjaPolicy::konfirmasiLewatPindai`. Tamu dialihkan ke login lalu kembali
 * ke tautan yang sama lewat `redirect()->intended()`.
 *
 * Tiket dicari tanpa `ScopeLingkup` supaya penerima di luar lingkup mendapat pesan
 * yang jelas, bukan 404; policy tetap menolaknya dengan `PemeriksaLingkupBaris`.
 */
final class LapanganKonfirmasiPenerimaController extends Controller
{
    public function __construct(
        private readonly TautanKonfirmasiPenerima $tautan,
        private readonly AturanKonfirmasiPenerima $aturan,
    ) {}

    public function show(Request $request, string $perintahKerja): Response
    {
        $tiket = $this->cari($perintahKerja);
        $pengguna = $request->user('web');
        [$keadaan, $pesan, $status] = $this->keadaan($request, $tiket, $pengguna);
        $konfirmasi = $this->aturan->berlaku($tiket);

        return Inertia::render('Lapangan/KonfirmasiPenerima', [
            'keadaan' => $keadaan,
            'pesan' => $pesan,
            // Tautan palsu/kedaluwarsa dan penerima yang tidak berhak tidak melihat apa pun tentang tiketnya.
            'pekerjaan' => in_array($keadaan, ['Siap', 'SudahDikonfirmasi', 'TidakMenunggu'], true) ? $this->ringkas($tiket) : null,
            'konfirmasi' => $konfirmasi === null || $keadaan !== 'SudahDikonfirmasi' ? null : [
                'NamaPenerima' => $konfirmasi->NamaPenerima,
                'DikonfirmasiPada' => $konfirmasi->DikonfirmasiPada->toIso8601String(),
                'OlehSaya' => $konfirmasi->PenggunaId === $pengguna->Id,
            ],
            'urlKirim' => $request->getRequestUri(),
        ])->toResponse($request)->setStatusCode($status);
    }

    public function store(KonfirmasiPenerimaRequest $request, string $perintahKerja, KonfirmasiPenerimaLewatPindai $aksi): RedirectResponse
    {
        $tiket = $this->cari($perintahKerja);
        $pengguna = $request->user('web');
        [$keadaan, $pesan] = $this->keadaan($request, $tiket, $pengguna);

        abort_if(in_array($keadaan, ['TautanTidakSah', 'Kedaluwarsa', 'TanpaAkses'], true), 403, $pesan ?? '');

        $hasil = HasilKonfirmasiPenerima::from($request->string('Hasil')->toString());
        $gambar = $request->file('TandaTangan');
        try {
            $aksi->jalankan($tiket, $pengguna, $hasil, $request->validated('Alasan'), $gambar instanceof UploadedFile ? $gambar : null);
        } catch (AturanBisnisDilanggar $e) {
            // Keadaan tiket berubah sejak halaman dibuka (mis. penerima lain lebih dulu).
            return back()->withErrors(['Konfirmasi' => $e->getMessage()]);
        }

        return redirect()->route('lapangan.konfirmasi-penerima.hasil', ['perintahKerja' => $tiket->Id]);
    }

    /** Layar sesudah menjawab; hanya bagi penerima yang baru saja mengonfirmasi tiket ini. */
    public function hasil(Request $request, string $perintahKerja): Response
    {
        $tiket = $this->cari($perintahKerja);
        $pengguna = $request->user('web');
        $jawaban = KonfirmasiPenerimaPerintahKerja::query()
            ->where('PerintahKerjaId', $tiket->Id)
            ->where('PenggunaId', $pengguna->Id)
            ->where('Metode', MetodeKonfirmasiPenerima::PindaiQr->value)
            ->latest('DikonfirmasiPada')
            ->orderByDesc('Id')
            ->first();

        abort_if($jawaban === null, 404);

        return Inertia::render('Lapangan/KonfirmasiPenerimaHasil', [
            'pekerjaan' => $this->ringkas($tiket),
            'hasil' => $jawaban->Hasil,
            'dikonfirmasiPada' => $jawaban->DikonfirmasiPada->toIso8601String(),
        ])->toResponse($request);
    }

    private function cari(string $id): PerintahKerja
    {
        return PerintahKerja::query()->withoutGlobalScope(ScopeLingkup::class)->findOrFail($id);
    }

    /**
     * Keadaan layar dan kode HTTP-nya. Urutan pemeriksaan penting: tautan palsu atau
     * kedaluwarsa ditolak sebelum apa pun tentang tiketnya ditampilkan.
     *
     * @return array{0: string, 1: string|null, 2: int}
     */
    private function keadaan(Request $request, PerintahKerja $tiket, Pengguna $pengguna): array
    {
        if (! $this->tautan->sah($request)) {
            return ['TautanTidakSah', 'Tautan konfirmasi ini tidak sah. Minta teknisi menampilkan QR lagi.', 403];
        }
        if ($this->tautan->kedaluwarsa($request)) {
            return ['Kedaluwarsa', 'QR ini sudah kedaluwarsa. Minta teknisi memperbarui QR di HP-nya.', 403];
        }
        if (! Gate::forUser($pengguna)->allows('konfirmasiLewatPindai', $tiket)) {
            return ['TanpaAkses', $this->alasanDitolak($tiket, $pengguna), 403];
        }
        if ($this->aturan->berlaku($tiket) !== null) {
            return ['SudahDikonfirmasi', null, 200];
        }
        if ($tiket->Status !== StatusPerintahKerja::MenungguVerifikasi->value) {
            return ['TidakMenunggu', 'Pekerjaan ini tidak sedang menunggu konfirmasi penerima.', 200];
        }

        return ['Siap', null, 200];
    }

    private function alasanDitolak(PerintahKerja $tiket, Pengguna $pengguna): string
    {
        if ($this->aturan->ditugaskan($tiket, $pengguna->Id)) {
            return 'Kamu teknisi yang mengerjakan tiket ini. Minta penerima memindai QR dari HP-nya sendiri.';
        }
        if (! app(PemeriksaLingkupBaris::class)->mencakup($pengguna->Id, $tiket)) {
            return 'Pekerjaan ini di luar lingkup akunmu, jadi kamu tidak dapat mengonfirmasinya.';
        }

        return 'Akunmu tidak dapat mengonfirmasi pekerjaan ini.';
    }

    /** @return array<string, mixed> */
    private function ringkas(PerintahKerja $tiket): array
    {
        $tiket->loadMissing(['aset.kategoriAset:Id,Nama', 'lokasi:Id,Nama', 'penugasan.pengguna:Id,Nama,Jabatan']);
        $aset = $tiket->aset->first(fn (Aset $satu): bool => (bool) $satu->getAttribute('pivot')?->Utama) ?? $tiket->aset->first();
        $teknisi = $tiket->penugasan
            ->filter(fn (PenugasanPerintahKerja $satu): bool => in_array($satu->Status, [
                StatusPenugasanPerintahKerja::Ditugaskan->value,
                StatusPenugasanPerintahKerja::Diterima->value,
                StatusPenugasanPerintahKerja::Selesai->value,
            ], true))
            ->map(fn (PenugasanPerintahKerja $satu): ?string => $satu->pengguna?->Nama)
            ->filter()
            ->values()
            ->all();
        $diserahkanPada = RiwayatStatusPerintahKerja::query()
            ->where('PerintahKerjaId', $tiket->Id)
            ->where('StatusSesudah', StatusPerintahKerja::MenungguVerifikasi->value)
            ->max('DiubahPada');

        return [
            'Id' => $tiket->Id,
            'Nomor' => $tiket->Nomor,
            'Judul' => $tiket->Judul,
            'Status' => $tiket->Status,
            'RingkasanPenyelesaian' => $tiket->RingkasanPenyelesaian,
            'Aset' => $aset === null ? null : ['Nama' => $aset->Nama, 'KodeAset' => $aset->KodeAset, 'Kategori' => $aset->kategoriAset?->Nama],
            'Lokasi' => $tiket->lokasi?->Nama,
            'Teknisi' => $teknisi,
            'DimulaiPada' => $tiket->DimulaiPada?->toIso8601String(),
            'DiserahkanPada' => is_string($diserahkanPada) ? CarbonImmutable::parse($diserahkanPada)->toIso8601String() : null,
        ];
    }
}
