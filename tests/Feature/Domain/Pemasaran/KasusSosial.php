<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaSosial;
use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DistribusiKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenSosial;
use Tests\Dukungan\PenyediaSosialPalsu;

/** Dasar test sosial: penyedia palsu, satu kampanye, dan satu konten utama. */
abstract class KasusSosial extends KasusPemasaran
{
    protected PenyediaSosialPalsu $penyedia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->penyedia = new PenyediaSosialPalsu;
        $this->app->instance(PenyediaSosial::class, $this->penyedia);
    }

    protected function buatKampanye(string $kode = 'promo-q1'): Kampanye
    {
        return Kampanye::create([
            'Kode' => $kode,
            'Nama' => 'Promo Kuartal 1',
            'Objective' => 'Lead',
            'Status' => 'Draf',
        ]);
    }

    protected function buatKonten(?Kampanye $kampanye = null, string $kode = 'artikel-audit'): KontenSosial
    {
        return KontenSosial::create([
            'Kode' => $kode,
            'Judul' => 'Lima tanda audit mutu Anda tertinggal',
            'Ringkasan' => 'Ringkasan artikel.',
            'KampanyeId' => $kampanye?->Id,
        ]);
    }

    protected function buatDistribusi(
        KontenSosial $konten,
        ChannelSosial $channel = ChannelSosial::LinkedIn,
        ?string $mediaUrl = null,
        ?string $tautan = 'https://amanpoll.test/artikel/audit',
    ): DistribusiKontenSosial {
        return DistribusiKontenSosial::create([
            'KontenSosialId' => $konten->Id,
            'Channel' => $channel,
            'Caption' => "Caption untuk {$channel->value}.",
            'MediaUrl' => $mediaUrl ?? ($channel->wajibMedia() ? 'https://contoh.test/gambar.jpg' : null),
            'TautanTujuan' => $tautan,
            'Status' => StatusKontenSosial::Terjadwal,
        ]);
    }
}
