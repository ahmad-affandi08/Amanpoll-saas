<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\ModelAttribution;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKonfigurasiPemasaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Kunci' => ['required', 'string', Rule::in(KatalogKonfigurasiPemasaran::kunci())],
            // Bentuk nilainya berbeda per kunci; yang punya daftar tertutup dijaga di bawah.
            'Nilai' => ['required', ...$this->aturanNilai()],
        ];
    }

    /**
     * Setelan berdaftar tertutup tidak boleh diisi teks bebas: nilai asing akan
     * diam-diam jatuh ke bawaannya saat dibaca, dan angka dashboard berubah
     * tanpa ada yang tahu sebabnya.
     *
     * @return list<mixed>
     */
    private function aturanNilai(): array
    {
        $kunci = $this->input('Kunci');

        return match ($kunci) {
            KatalogKonfigurasiPemasaran::ATTRIBUTION_MODEL => [Rule::enum(ModelAttribution::class)],
            KatalogKonfigurasiPemasaran::ATTRIBUTION_PARUH_HARI => ['integer', 'min:1', 'max:365'],
            KatalogKonfigurasiPemasaran::ATTRIBUTION_JENDELA_HARI => ['integer', 'min:1', 'max:3650'],
            default => [],
        };
    }
}
