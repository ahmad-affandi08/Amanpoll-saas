<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use App\Shared\Infrastructure\Qr\PembuatQrAset;
use Illuminate\Foundation\Http\FormRequest;

final class BuatQrAsetRequest extends FormRequest
{
    /** Satu lembar label sekali cetak; batasnya menjaga endpoint anonim ini tetap murah. */
    public const MAKS_KODE = 50;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Kode' => ['required', 'array', 'min:1', 'max:'.self::MAKS_KODE],
            'Kode.*' => ['required', 'string', 'max:'.PembuatQrAset::MAKS_PANJANG_KODE],
            PerangkapSpam::FIELD => ['nullable', 'string', 'max:190'],
        ];
    }
}
