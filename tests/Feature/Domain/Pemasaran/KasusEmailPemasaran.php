<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaEmailPemasaran;
use App\Domain\Pemasaran\Domain\Enums\JenisTemplateEmail;
use App\Domain\Pemasaran\Domain\Enums\SumberKonsen;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahSequenceEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Dukungan\PenyediaEmailPalsu;

/** Dasar test email pemasaran: penyedia palsu, template, dan sequence siap pakai. */
abstract class KasusEmailPemasaran extends KasusProspek
{
    protected PenyediaEmailPalsu $penyedia;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');

        $this->penyedia = new PenyediaEmailPalsu;
        $this->app->instance(PenyediaEmailPemasaran::class, $this->penyedia);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    protected function buatProspek(string $email = 'budi@pabrik.test', bool $denganKonsen = true): Prospek
    {
        $prospek = app(CatatProspek::class)->jalankan(
            ['Nama' => 'Budi', 'Email' => $email],
            SumberProspek::Website,
            (string) Str::ulid(),
        );

        if ($denganKonsen) {
            app(LayananKonsen::class)->catat($email, true, SumberKonsen::Formulir, $prospek);
        }

        return $prospek;
    }

    protected function buatTemplate(string $kode = 'welcome', string $subjek = 'Halo {{Nama}}'): TemplateEmailPemasaran
    {
        return TemplateEmailPemasaran::create([
            'Kode' => $kode,
            'Nama' => 'Template '.$kode,
            'Jenis' => JenisTemplateEmail::Trial->value,
            'Subjek' => $subjek,
            'IsiHtml' => '<p>Halo {{Nama}}, sisa trial {{HariTrialTersisa}} hari.</p>',
            'Aktif' => true,
        ]);
    }

    /** @param list<int> $hari */
    protected function buatSequence(array $hari = [0, 1, 3], bool $aktif = true): SequenceEmailPemasaran
    {
        $sequence = SequenceEmailPemasaran::create([
            'Kode' => 'trial-'.Str::random(5),
            'Nama' => 'Sequence Trial',
            'Aktif' => $aktif,
        ]);

        foreach ($hari as $urutan => $hariKe) {
            LangkahSequenceEmail::create([
                'SequenceEmailPemasaranId' => $sequence->Id,
                'TemplateEmailPemasaranId' => $this->buatTemplate('tpl-'.Str::random(5))->Id,
                'Urutan' => $urutan,
                'HariKe' => $hariKe,
                'Aktif' => true,
            ]);
        }

        return $sequence->fresh(['langkah']) ?? $sequence;
    }
}
