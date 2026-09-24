<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Core\Peristiwa\LayananKotakKeluar;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusKotakKeluarPeristiwa;
use App\Shared\Infrastructure\Persistence\KunciBarisAntrean;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pengambilan batch outbox memakai FOR UPDATE SKIP LOCKED bila server mendukungnya
 * (audit produksi #9), supaya worker kedua melewati baris yang sedang dipegang.
 *
 * Test dua koneksi butuh baris yang benar-benar ter-commit, jadi kelas ini tidak
 * memakai DatabaseTransactions dan membersihkan barisnya sendiri.
 */
final class KunciBarisAntreanTest extends TestCase
{
    private string $penanda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->penanda = 'Uji.SkipLocked.'.Str::lower((string) Str::ulid());
        config(['database.connections.koneksi_kedua' => config('database.connections.'.config('database.default'))]);
    }

    protected function tearDown(): void
    {
        DB::connection('koneksi_kedua')->disconnect();
        DB::table('KotakKeluarPeristiwa')->where('NamaPeristiwa', $this->penanda)->delete();

        parent::tearDown();
    }

    /** @return array<string, array{string, bool, bool}> */
    public static function versiServer(): array
    {
        return [
            'MariaDB 10.5' => ['10.5.23-MariaDB', true, false],
            'MariaDB 10.6' => ['10.6.0-MariaDB', true, true],
            'MariaDB 10.11' => ['10.11.14-MariaDB-0ubuntu0.24.04.1', true, true],
            'MariaDB dengan awalan klien lama' => ['5.5.5-10.11.6-MariaDB', false, true],
            'MariaDB lama dengan awalan klien lama' => ['5.5.5-10.4.32-MariaDB', false, false],
            'MySQL 5.7' => ['5.7.44', false, false],
            'MySQL 8.0.0' => ['8.0.0', false, false],
            'MySQL 8.0.1' => ['8.0.1', false, true],
            'MySQL 8.4' => ['8.4.3', false, true],
            'Versi tak terbaca' => ['tidak-dikenal', false, false],
        ];
    }

    #[DataProvider('versiServer')]
    public function test_aturan_versi_skip_locked(string $versi, bool $mariaDb, bool $diharapkan): void
    {
        $this->assertSame($diharapkan, KunciBarisAntrean::versiMendukung($versi, $mariaDb));
    }

    public function test_outbox_mengirim_klausa_skip_locked_di_server_ini(): void
    {
        $this->assertTrue(
            KunciBarisAntrean::mendukungSkipLocked(DB::connection()),
            'Server dev/test (MariaDB 10.11) seharusnya mendukung SKIP LOCKED.',
        );
        $this->semaiPeristiwa(1);

        DB::enableQueryLog();
        app(LayananKotakKeluar::class)->ambilUntukDiproses(10);
        $kueri = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        DB::disableQueryLog();

        $this->assertStringContainsString('for update skip locked', strtolower($kueri));
    }

    public function test_worker_kedua_melewati_baris_yang_sedang_dikunci(): void
    {
        $this->semaiPeristiwa(4);

        DB::beginTransaction();

        try {
            $milikPertama = $this->ambilBatch(DB::connection()->getName(), 2);

            // Tanpa SKIP LOCKED koneksi kedua menunggu kunci; batas 1 detik membuatnya gagal cepat.
            DB::connection('koneksi_kedua')->statement('SET SESSION innodb_lock_wait_timeout = 1');
            $milikKedua = $this->ambilBatch('koneksi_kedua', 10);
        } finally {
            DB::rollBack();
        }

        $this->assertCount(2, $milikPertama);
        $this->assertCount(2, $milikKedua);
        $this->assertSame([], array_values(array_intersect($milikPertama, $milikKedua)));
    }

    /** @return list<string> */
    private function ambilBatch(string $koneksi, int $batas): array
    {
        $db = DB::connection($koneksi);

        return array_values($db->table('KotakKeluarPeristiwa')
            ->where('NamaPeristiwa', $this->penanda)
            ->where('Status', StatusKotakKeluarPeristiwa::Menunggu->value)
            ->orderBy('TersediaPada')
            ->limit($batas)
            ->lock(KunciBarisAntrean::klausa($db))
            ->pluck('Id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all());
    }

    private function semaiPeristiwa(int $jumlah): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            DB::table('KotakKeluarPeristiwa')->insert([
                'Id' => (string) Str::ulid(),
                'NamaPeristiwa' => $this->penanda,
                'MuatanData' => '{}',
                'Status' => StatusKotakKeluarPeristiwa::Menunggu->value,
                'Percobaan' => 0,
                'TersediaPada' => now()->subMinutes(10 - $i),
                'DibuatPada' => now(),
            ]);
        }
    }
}
