<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Actions\ImporAset;
use App\Domain\Aset\Application\Services\PemeriksaImporAset;
use App\Domain\Aset\Application\Services\PenyusunTemplatImporAset;
use App\Domain\Aset\Http\Requests\ImporAsetRequest;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\FormatEkspor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Impor aset dari CSV atau XLSX (PRD 8.4 "Impor Aset").
 *
 * Dua langkah tanpa penyimpanan sementara: pratinjau memeriksa berkas dan
 * melaporkan galat per baris, konfirmasi menerima berkas yang sama lagi,
 * memeriksanya ulang seluruhnya, lalu membuat semua aset atau tidak satu pun.
 * Izinnya sama dengan mendaftarkan satu aset lewat formulir.
 */
final class ImporAsetController extends Controller
{
    /** Galat yang dikirim dalam satu balasan; selebihnya lewat berkas daftar galat. */
    public const MAKS_GALAT_DITAMPILKAN = 200;

    /** Contoh baris yang akan dibuat, untuk diperiksa sekilas di pratinjau. */
    public const JUMLAH_CONTOH = 5;

    public function templat(Request $request, PenyusunTemplatImporAset $penyusun): BinaryFileResponse
    {
        $this->authorize('create', Aset::class);

        $format = $request->validate(['format' => ['nullable', 'string', 'in:csv,xlsx,Csv,Xlsx']])['format'] ?? 'xlsx';
        $format = strtolower((string) $format) === 'csv' ? FormatEkspor::Csv : FormatEkspor::Xlsx;

        $jalur = $this->berkasSementara();
        $format === FormatEkspor::Csv ? $penyusun->csv($jalur) : $penyusun->xlsx($jalur);

        return response()
            ->download($jalur, 'templat-impor-aset.'.$format->ekstensi(), ['Content-Type' => $format->jenisMime()])
            ->deleteFileAfterSend();
    }

    public function pratinjau(ImporAsetRequest $request, PemeriksaImporAset $pemeriksa): JsonResponse
    {
        $this->authorize('create', Aset::class);

        $hasil = $this->periksa($request, $pemeriksa);

        return response()->json([
            ...$this->ringkasan($request, $hasil),
            'contoh' => array_map(static fn (array $satu): array => [
                'baris' => $satu['baris'],
                'KodeAset' => $satu['data']['KodeAset'] ?? null,
                'Nama' => $satu['data']['Nama'] ?? '',
                ...$satu['tampilan'],
            ], array_slice($hasil['sah'], 0, self::JUMLAH_CONTOH)),
        ]);
    }

    public function simpan(ImporAsetRequest $request, PemeriksaImporAset $pemeriksa, ImporAset $aksi): JsonResponse
    {
        $this->authorize('create', Aset::class);

        $hasil = $this->periksa($request, $pemeriksa);

        if ($hasil['galat'] !== []) {
            return response()->json([
                'message' => sprintf(
                    'Impor dibatalkan: %d dari %d baris masih bergalat. Tidak ada aset yang dibuat.',
                    $hasil['jumlahBaris'] - count($hasil['sah']),
                    $hasil['jumlahBaris'],
                ),
                ...$this->ringkasan($request, $hasil),
            ], 422);
        }

        try {
            $asetId = $aksi->jalankan(
                array_map(static fn (array $satu): array => $satu['data'], $hasil['sah']),
                $request->namaBerkas(),
                $request->user('web')->Id,
            );
        } catch (UniqueConstraintViolationException) {
            // Kode yang sama baru saja dipakai orang lain di antara pemeriksaan dan penyimpanan.
            throw ValidationException::withMessages([
                'Berkas' => 'Sebagian kode aset baru saja dipakai aset lain. Tidak ada aset yang dibuat; unggah ulang berkasnya untuk melihat baris mana.',
            ]);
        }

        return response()->json([
            'jumlah' => count($asetId),
            'asetId' => $asetId,
        ], 201);
    }

    /** Seluruh galat sebagai CSV, untuk berkas yang galatnya terlalu banyak dibaca di layar. */
    public function galat(ImporAsetRequest $request, PemeriksaImporAset $pemeriksa, PenyusunTemplatImporAset $penyusun): BinaryFileResponse
    {
        $this->authorize('create', Aset::class);

        $hasil = $this->periksa($request, $pemeriksa);

        $jalur = $this->berkasSementara();
        $penyusun->galatCsv($jalur, $hasil['galat']);

        return response()
            ->download($jalur, 'galat-impor-aset.csv', ['Content-Type' => FormatEkspor::Csv->jenisMime()])
            ->deleteFileAfterSend();
    }

    /**
     * @return array{
     *     galatBerkas: list<string>,
     *     jumlahBaris: int,
     *     galat: list<array{baris: int, kolom: string, nilai: string, pesan: string}>,
     *     sah: list<array{baris: int, data: array<string, mixed>, tampilan: array{Kategori: string|null, Lokasi: string|null, UnitPengelola: string|null}}>
     * }
     *
     * @throws ValidationException bila berkasnya ditolak utuh (tak terbaca, kepala salah, terlalu banyak baris)
     */
    private function periksa(ImporAsetRequest $request, PemeriksaImporAset $pemeriksa): array
    {
        $hasil = $pemeriksa->periksa($request->jalurBerkas(), $request->formatBerkas(), $request->user('web')->Id);

        if ($hasil['galatBerkas'] !== []) {
            throw ValidationException::withMessages(['Berkas' => $hasil['galatBerkas']]);
        }

        return $hasil;
    }

    /**
     * @param  array{jumlahBaris: int, galat: list<array{baris: int, kolom: string, nilai: string, pesan: string}>, sah: list<mixed>}  $hasil
     * @return array<string, mixed>
     */
    private function ringkasan(ImporAsetRequest $request, array $hasil): array
    {
        return [
            'namaBerkas' => $request->namaBerkas(),
            'jumlahBaris' => $hasil['jumlahBaris'],
            'jumlahSah' => count($hasil['sah']),
            'jumlahGalat' => count($hasil['galat']),
            'galat' => array_slice($hasil['galat'], 0, self::MAKS_GALAT_DITAMPILKAN),
        ];
    }

    private function berkasSementara(): string
    {
        $jalur = tempnam(sys_get_temp_dir(), 'impor-aset');

        if ($jalur === false) {
            throw new RuntimeException('Tidak dapat membuat berkas sementara.');
        }

        return $jalur;
    }
}
