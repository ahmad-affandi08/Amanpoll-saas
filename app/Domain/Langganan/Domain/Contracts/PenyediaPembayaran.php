<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Contracts;

use App\Domain\Langganan\Domain\ValueObjects\InstruksiPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PesananPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use Illuminate\Http\Request;

/** Abstraksi penyedia pembayaran (22.06, PRD 8.19, 8.23). */
interface PenyediaPembayaran
{
    /** Kode stabil yang disimpan pada PembayaranLangganan.PenyediaPembayaran. */
    public function kode(): string;

    public function nama(): string;

    /**
     * Menyiapkan pembayaran untuk sebuah tagihan: membuka sesi bayar di gateway
     * (URL pengalihan) atau menyusun rincian transfer untuk penyedia manual.
     */
    public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran;

    /**
     * Memeriksa keaslian webhook. Permintaan utuh diteruskan karena sebagian
     * penyedia menandatangani badan mentah (Stripe, Tripay, DOKU) atau mengirim
     * form-urlencoded (Duitku, iPaymu), bukan JSON.
     */
    public function webhookSah(Request $permintaan): bool;

    /** Dipanggil hanya setelah webhookSah() menerima permintaannya. */
    public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran;
}
