<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Template `.env` produksi Niagahoster (FASE 45).
 *
 * Operator menyalin template ini apa adanya. Isian yang salah di sini tidak
 * menimbulkan galat saat deploy, hanya sesi yang putus antar-subdomain, cookie
 * tanpa Secure, atau email yang melewati penyedia pilihan konsol platform.
 */
final class TemplateEnvProduksiTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function nilaiWajib(): array
    {
        return [
            'lingkungan produksi' => ['APP_ENV', 'production'],
            'debug mati' => ['APP_DEBUG', 'false'],
            'sesi lintas subdomain' => ['SESSION_DOMAIN', '.amanpoll.id'],
            'cookie hanya lewat https' => ['SESSION_SECURE_COOKIE', 'true'],
            'email lewat penyedia konsol platform' => ['MAIL_MAILER', 'amanpoll'],
            'antrean database' => ['QUEUE_CONNECTION', 'database'],
            'salinan cadangan luar server' => ['AMANPOLL_CADANGAN_DISK_LUAR', 'cadangan_luar'],
        ];
    }

    #[DataProvider('nilaiWajib')]
    public function test_template_produksi_memuat_nilai_wajib(string $kunci, string $nilai): void
    {
        $this->assertSame($nilai, $this->baca('deploy/niagahoster/.env.production.example')[$kunci] ?? null);
    }

    /** Isian SMTP yang aktif menyesatkan: MAIL_MAILER=amanpoll tidak memakainya. */
    public function test_template_produksi_tidak_mengaktifkan_isian_smtp(): void
    {
        $env = $this->baca('deploy/niagahoster/.env.production.example');

        $this->assertArrayNotHasKey('MAIL_HOST', $env);
        $this->assertArrayNotHasKey('MAIL_ENCRYPTION', $env);
    }

    /** Variabel baru FASE 45 dikenal di kedua template, bukan hanya di salah satunya. */
    public function test_kedua_template_memuat_variabel_antrean_dan_cadangan(): void
    {
        $kunci = [
            'DB_QUEUE_RETRY_AFTER',
            'DB_QUEUE_PANJANG_RETRY_AFTER',
            'AMANPOLL_CADANGAN_DISK_LUAR',
            'AMANPOLL_CADANGAN_RETENSI_LOKAL_HARI',
            'AMANPOLL_CADANGAN_RETENSI_LUAR_HARI',
            'AMANPOLL_CADANGAN_LUAR_BUCKET',
            'AMANPOLL_CADANGAN_LUAR_ENDPOINT',
        ];

        foreach (['.env.example', 'deploy/niagahoster/.env.production.example'] as $template) {
            $this->assertSame([], array_values(array_diff($kunci, array_keys($this->baca($template)))), $template);
        }
    }

    /** @return array<string, string|null> */
    private function baca(string $jalur): array
    {
        return Dotenv::parse((string) file_get_contents(base_path($jalur)));
    }
}
