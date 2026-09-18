<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Infrastructure\Persistence\TransaksiDatabaseLaravel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class TransaksiDatabaseLaravelTest extends TestCase
{
    use RefreshDatabase;

    private function baris(): array
    {
        return [
            'queue' => 'default',
            'payload' => 'uji-coba',
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ];
    }

    public function test_perubahan_dibatalkan_saat_callback_melempar_exception(): void
    {
        $transaksi = new TransaksiDatabaseLaravel();

        try {
            $transaksi->jalankan(function (): void {
                DB::table('AntrianPekerjaan')->insert($this->baris());

                throw new RuntimeException('gagal di tengah transaksi');
            });
            $this->fail('Exception seharusnya diteruskan ke pemanggil.');
        } catch (RuntimeException $e) {
            $this->assertSame('gagal di tengah transaksi', $e->getMessage());
        }

        $this->assertSame(0, DB::table('AntrianPekerjaan')->count());
    }

    public function test_perubahan_tersimpan_saat_callback_berhasil(): void
    {
        $transaksi = new TransaksiDatabaseLaravel();

        $transaksi->jalankan(function (): void {
            DB::table('AntrianPekerjaan')->insert($this->baris());
        });

        $this->assertSame(1, DB::table('AntrianPekerjaan')->count());
    }
}
