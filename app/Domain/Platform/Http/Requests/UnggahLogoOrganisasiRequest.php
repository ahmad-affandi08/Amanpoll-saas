<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UnggahLogoOrganisasiRequest extends FormRequest
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
            'Logo' => ['required', 'image', 'max:2048'],
        ];
    }
}
