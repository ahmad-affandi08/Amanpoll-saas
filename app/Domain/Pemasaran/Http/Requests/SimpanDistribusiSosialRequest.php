<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DistribusiKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenSosial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanDistribusiSosialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $konten = $this->route('konten');
        $distribusi = $this->route('distribusi');

        $unik = Rule::unique('DistribusiKontenSosial', 'Channel')
            ->where('KontenSosialId', $konten instanceof KontenSosial ? $konten->getKey() : null);

        if ($distribusi instanceof DistribusiKontenSosial) {
            $unik = $unik->ignore($distribusi->getKey(), 'Id');
        }

        return [
            'Channel' => ['required', Rule::enum(ChannelSosial::class), $unik],
            'Caption' => ['required', 'string', 'max:5000'],
            'MediaUrl' => ['nullable', 'url', 'max:500'],
            'Cta' => ['nullable', 'string', 'max:190'],
            'TautanTujuan' => ['nullable', 'url', 'max:500'],
            'UtmSource' => ['nullable', 'string', 'max:100'],
            'UtmMedium' => ['nullable', 'string', 'max:100'],
            'UtmTerm' => ['nullable', 'string', 'max:100'],
            'UtmContent' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Channel bergambar tanpa media akan ditolak penyedianya; lebih baik ketahuan di formulir.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $channel = ChannelSosial::tryFrom((string) $this->input('Channel'));
            $media = (string) $this->input('MediaUrl', '');

            if ($channel?->wajibMedia() === true && $media === '') {
                $validator->errors()->add('MediaUrl', "Channel {$channel->value} menuntut media.");
            }
        }];
    }
}
