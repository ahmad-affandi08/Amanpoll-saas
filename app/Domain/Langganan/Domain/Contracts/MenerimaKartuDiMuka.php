<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Contracts;

/**
 * Penyedia yang dapat menyimpan metode pembayaran sebelum ada tagihan.
 *
 * Dipisah dari PenyediaPembayaran karena tidak semua penyedia bisa: transfer
 * manual tidak punya kartu untuk disimpan. Kebijakan "kartu diperlukan" saat
 * mendaftar trial hanya dapat ditegakkan oleh penyedia yang mengimplementasikan
 * antarmuka ini.
 */
interface MenerimaKartuDiMuka
{
    /** Memeriksa token metode pembayaran yang dikirim peramban. */
    public function metodePembayaranSah(string $token): bool;
}
