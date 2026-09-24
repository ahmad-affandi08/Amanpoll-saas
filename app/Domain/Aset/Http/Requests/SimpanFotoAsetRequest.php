<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Shared\Domain\Exceptions\AksesDitolak;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

/**
 * Unggah foto galeri aset (PRD 8.4 "Foto Aset"), satu atau beberapa sekaligus.
 * Hanya format gambar yang dapat dibaca peramban dan server; peramban sudah
 * mengecilkannya lebih dulu (`pampatkanGambar`).
 */
final class SimpanFotoAsetRequest extends FormRequest
{
    public const UKURAN_MAKS_KB = 10240;

    /**
     * Izin diperiksa sebelum validasi, supaya pengguna tanpa hak tidak
     * mendapat pesan batas galeri (atau isi galeri) sebelum ditolak.
     */
    public function authorize(): bool
    {
        $aset = $this->route('aset');

        return $aset instanceof Aset && $this->user()?->can('tambahFoto', $aset) === true;
    }

    /**
     * Pesan yang dapat dipahami teknisi yang fotonya terkirim dari antrean HP
     * sesudah penugasannya berakhir (PRD 8.4 "Foto Aset").
     */
    protected function failedAuthorization(): void
    {
        throw new AksesDitolak('Anda tidak berhak menambah foto aset ini. Teknisi hanya dapat menambah foto selama ditugaskan pada perintah kerja aktif untuk aset itu.');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'Foto' => ['required', 'array', 'min:1', 'max:'.GaleriFotoAset::MAKS_FOTO],
            'Foto.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.self::UKURAN_MAKS_KB],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'Foto.max' => 'Paling banyak '.GaleriFotoAset::MAKS_FOTO.' foto sekali unggah.',
            'Foto.*.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'Foto.*.max' => 'Ukuran satu foto paling besar 10 MB.',
        ];
    }

    /**
     * Batas galeri diperiksa di sini supaya formulir menampilkan pesannya di
     * isian; Action memeriksanya ulang di bawah kunci baris aset.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $aset = $this->route('aset');

                if (! $aset instanceof Aset || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $baru = count(array_filter((array) $this->file('Foto', []), fn (mixed $satu): bool => $satu instanceof UploadedFile));
                $sekarang = app(GaleriFotoAset::class)->jumlah($aset);

                if ($sekarang + $baru > GaleriFotoAset::MAKS_FOTO) {
                    $sisa = max(0, GaleriFotoAset::MAKS_FOTO - $sekarang);
                    $validator->errors()->add('Foto', $sisa === 0
                        ? 'Galeri aset ini sudah berisi '.GaleriFotoAset::MAKS_FOTO.' foto, batas paling banyak. Hapus foto lama lebih dulu.'
                        : 'Galeri aset paling banyak '.GaleriFotoAset::MAKS_FOTO." foto; tersisa tempat untuk {$sisa} foto lagi.");
                }
            },
        ];
    }

    /** @return list<UploadedFile> */
    public function foto(): array
    {
        return array_values(array_filter((array) $this->file('Foto', []), fn (mixed $satu): bool => $satu instanceof UploadedFile));
    }
}
