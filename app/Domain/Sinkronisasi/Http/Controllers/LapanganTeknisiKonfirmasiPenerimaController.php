<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Pemeliharaan\Application\Actions\KonfirmasiPenerimaDiPerangkat;
use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Http\Requests\KonfirmasiPenerimaPerangkatRequest;
use App\Domain\Pemeliharaan\Http\Resources\KonfirmasiPenerimaResource;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Sinkronisasi\Application\Services\TautanKonfirmasiPenerima;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

/**
 * Konfirmasi penerima dari HP teknisi (PRD 8.22): QR bertoken untuk dipindai penerima
 * (cara 2), status konfirmasi untuk layar yang menunggu, dan tanda tangan penerima
 * tanpa akun (cara 3). Semua JSON lewat `@/lib/http`; aturannya milik Pemeliharaan.
 */
final class LapanganTeknisiKonfirmasiPenerimaController extends Controller
{
    public function __construct(private readonly AturanKonfirmasiPenerima $aturan) {}

    /** QR baru (atau diperbarui) untuk tiket yang menunggu konfirmasi. Butuh sinyal. */
    public function qr(PerintahKerja $perintahKerja, TautanKonfirmasiPenerima $tautan): JsonResponse
    {
        $this->authorize('operate', $perintahKerja);

        if ($perintahKerja->Status !== StatusPerintahKerja::MenungguVerifikasi->value) {
            throw new AturanBisnisDilanggar('QR konfirmasi baru bisa ditampilkan setelah laporan selesai terkirim.');
        }
        if ($this->aturan->berlaku($perintahKerja) !== null) {
            throw new AturanBisnisDilanggar('Pekerjaan ini sudah dikonfirmasi penerima.');
        }

        return response()->json($tautan->buat($perintahKerja));
    }

    /** Dibaca berkala layar QR supaya berganti sendiri begitu penerima menjawab. */
    public function status(PerintahKerja $perintahKerja): JsonResponse
    {
        $this->authorize('view', $perintahKerja);
        $konfirmasi = $this->aturan->berlaku($perintahKerja);
        $terakhir = $perintahKerja->konfirmasiPenerima()->latest('DikonfirmasiPada')->orderByDesc('Id')->first();

        return response()->json([
            'Status' => $perintahKerja->Status,
            'Konfirmasi' => $konfirmasi === null ? null : KonfirmasiPenerimaResource::ringkas($konfirmasi),
            'Terakhir' => $terakhir === null ? null : KonfirmasiPenerimaResource::ringkas($terakhir),
        ]);
    }

    /** Cara 3: tanda tangan di HP teknisi; diunggah langsung atau dari draf offline. */
    public function tandaTangan(KonfirmasiPenerimaPerangkatRequest $request, PerintahKerja $perintahKerja, KonfirmasiPenerimaDiPerangkat $aksi): JsonResponse
    {
        $this->authorize('operate', $perintahKerja);
        $gambar = $request->file('TandaTangan');
        $jabatan = $request->validated('JabatanPenerima');
        $kunci = $request->validated('KunciPerangkat');

        $konfirmasi = $aksi->jalankan(
            $perintahKerja,
            $request->user('web')->Id,
            $request->string('NamaPenerima')->trim()->toString(),
            is_string($jabatan) ? $jabatan : null,
            $gambar instanceof UploadedFile ? $gambar : throw new AturanBisnisDilanggar('Tanda tangan penerima wajib digambar.'),
            is_string($kunci) ? $kunci : null,
        );

        return response()->json(['Konfirmasi' => KonfirmasiPenerimaResource::ringkas($konfirmasi)], $konfirmasi->wasRecentlyCreated ? 201 : 200);
    }
}
