<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use Illuminate\Foundation\Http\FormRequest;

final class HitungKeandalanPublikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'JumlahAset' => ['required', 'integer', 'min:0', 'max:1000000'],
            'HariRentang' => ['required', 'integer', 'min:0', 'max:3650'],
            'JumlahKegagalan' => ['required', 'integer', 'min:0', 'max:1000000'],
            'MenitDowntime' => ['required', 'integer', 'min:0', 'max:100000000'],
            PerangkapSpam::FIELD => ['nullable', 'string', 'max:190'],
        ];
    }
}
