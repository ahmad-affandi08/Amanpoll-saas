<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use App\Shared\Infrastructure\Qr\PembuatQrAset;
use Illuminate\Foundation\Http\FormRequest;

final class PindaiPengambilanAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'Kode' => ['required', 'string', 'max:'.PembuatQrAset::MAKS_PANJANG_KODE],
        ];
    }
}
