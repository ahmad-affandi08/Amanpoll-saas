<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/** Berhenti langganan satu klik; tanda tangan URL yang jadi buktinya, bukan formulir konfirmasi (MARKETING.md 27). */
final class BerhentiLanggananController extends Controller
{
    public function __construct(private readonly LayananKonsen $konsen) {}

    public function __invoke(PengirimanEmailPemasaran $pengiriman): Response
    {
        $this->konsen->cabut(
            $pengiriman->Email,
            AlasanSupresi::Unsubscribe,
            $pengiriman->prospek,
            'Dari tautan email.',
        );

        $pengiriman->Status = StatusPengirimanEmail::Unsubscribe;
        $pengiriman->DiperbaruiStatusPada = CarbonImmutable::now();
        $pengiriman->save();

        return Inertia::render('Publik/BerhentiLangganan', ['email' => $pengiriman->Email]);
    }
}
