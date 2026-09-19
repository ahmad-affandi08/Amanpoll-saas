<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Konfigurasi\DefinisiKonfigurasi;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SimpanKonfigurasiOrganisasiRequest extends FormRequest
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
        $definisi = DefinisiKonfigurasi::cari((string) $this->route('kunci'));
        if ($definisi === null) {
            throw new NotFoundHttpException("Kunci konfigurasi tidak dikenal.");
        }

        $aturanTipe = match ($definisi['Tipe']) {
            'boolean' => ['boolean'],
            'integer' => ['integer'],
            default => ['string', 'max:2000'],
        };

        return [
            'Nilai' => array_merge(['required'], $aturanTipe),
        ];
    }
}
