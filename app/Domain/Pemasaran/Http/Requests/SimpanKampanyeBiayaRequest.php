<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanKampanyeBiayaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Channel' => ['required', Rule::enum(ChannelKampanye::class)],
            'Tanggal' => ['required', 'date'],
            'Jumlah' => ['required', 'numeric', 'min:0', 'max:99999999999.99'],
            'Catatan' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * Biaya di channel yang tidak dijalankan kampanye ini akan membuat CAC channel itu
     * punya penyebut nol selamanya, jadi ditolak sejak di formulir.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $kampanye = $this->route('kampanye');
            $channel = (string) $this->input('Channel');

            if (! $kampanye instanceof Kampanye || $channel === '') {
                return;
            }

            $dijalankan = $kampanye->channel()->where('Channel', $channel)->exists();

            if (! $dijalankan) {
                $validator->errors()->add(
                    'Channel',
                    "Kampanye ini tidak berjalan di channel {$channel}; tambahkan channelnya lebih dulu.",
                );
            }
        }];
    }
}
