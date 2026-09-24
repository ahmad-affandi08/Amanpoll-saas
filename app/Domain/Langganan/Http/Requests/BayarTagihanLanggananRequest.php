<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Requests;

use App\Domain\Langganan\Application\Services\RegistriPenyediaPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Tenant hanya boleh memilih penyedia yang dinyalakan di konsol platform (PRD 8.23). */
final class BayarTagihanLanggananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bayar', Langganan::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $aktif = array_keys(app(RegistriPenyediaPembayaran::class)->aktif());

        return [
            'Penyedia' => ['nullable', 'string', Rule::in($aktif)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'Penyedia.in' => 'Metode pembayaran itu tidak tersedia.',
        ];
    }

    public function kodePenyedia(): ?string
    {
        $kode = $this->validated('Penyedia');

        return is_string($kode) && $kode !== '' ? $kode : null;
    }
}
