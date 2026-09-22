<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class SimpanBerkasLeadMagnetRequest extends FormRequest
{
    /** Lead magnet adalah dokumen dan lembar kerja, bukan berkas yang dapat dieksekusi. */
    private const MIME_DIIZINKAN = 'pdf,doc,docx,xls,xlsx,csv,ppt,pptx,zip,png,jpg,jpeg';

    private const UKURAN_MAKS_KB = 20480;

    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Berkas' => ['required', 'file', 'mimes:'.self::MIME_DIIZINKAN, 'max:'.self::UKURAN_MAKS_KB],
        ];
    }

    public function berkas(): UploadedFile
    {
        $berkas = $this->file('Berkas');

        // Validasi sudah memastikan satu berkas tunggal; pemeriksaan ini menjaga tipe statisnya.
        return $berkas instanceof UploadedFile
            ? $berkas
            : throw new \RuntimeException('Berkas unggahan tidak terbaca.');
    }
}
