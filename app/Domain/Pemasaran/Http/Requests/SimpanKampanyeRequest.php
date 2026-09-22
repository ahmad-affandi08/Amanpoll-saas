<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Domain\Enums\ObjectiveKampanye;
use App\Domain\Pemasaran\Domain\Enums\StatusKampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanKampanyeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->kampanye()?->getKey();

        return [
            // Kode inilah yang dipakai sebagai utm_campaign.
            'Kode' => ['nullable', 'string', 'max:100', Rule::unique('Kampanye', 'Kode')->ignore($id, 'Id')],
            'Nama' => ['required', 'string', 'max:180'],
            'Objective' => ['required', Rule::enum(ObjectiveKampanye::class)],
            'Status' => ['required', Rule::enum(StatusKampanye::class)],
            'Budget' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'Audience' => ['nullable', 'string', 'max:500'],
            'Offer' => ['nullable', 'string', 'max:300'],
            'HalamanId' => ['nullable', 'string', Rule::exists('HalamanPemasaran', 'Id')],
            'FormulirId' => ['nullable', 'string', Rule::exists('FormulirPemasaran', 'Id')],
            'UtmSource' => ['nullable', 'string', 'max:100'],
            'UtmMedium' => ['nullable', 'string', 'max:100'],
            'UtmTerm' => ['nullable', 'string', 'max:100'],
            'UtmContent' => ['nullable', 'string', 'max:100'],
            'MulaiPada' => ['nullable', 'date'],
            'SelesaiPada' => ['nullable', 'date', 'after_or_equal:MulaiPada'],
            'Channel' => ['nullable', 'array'],
            'Channel.*' => [Rule::enum(ChannelKampanye::class), 'distinct'],
            'Catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Transisi status diperiksa di sini agar salah pilih kembali sebagai galat field,
     * bukan halaman galat. Penjaga yang sebenarnya tetap ada di SimpanKampanye.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $tujuan = StatusKampanye::tryFrom((string) $this->input('Status'));
            $kampanye = $this->kampanye();

            if ($tujuan === null) {
                return;
            }

            if ($kampanye === null) {
                if ($tujuan !== StatusKampanye::Draf) {
                    $validator->errors()->add('Status', 'Kampanye baru selalu lahir sebagai draf.');
                }

                return;
            }

            $asal = $kampanye->Status;

            if ($asal === $tujuan || $asal->bolehPindahKe($tujuan)) {
                return;
            }

            $sah = implode(', ', array_map(
                fn (StatusKampanye $satu): string => $satu->value,
                $asal->tujuanSah(),
            ));

            $validator->errors()->add(
                'Status',
                "Kampanye berstatus {$asal->value} hanya dapat berpindah ke {$sah}.",
            );
        }];
    }

    private function kampanye(): ?Kampanye
    {
        $kampanye = $this->route('kampanye');

        return $kampanye instanceof Kampanye ? $kampanye : null;
    }
}
