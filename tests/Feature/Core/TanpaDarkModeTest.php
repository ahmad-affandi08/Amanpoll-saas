<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Amanpoll tidak punya dark mode (PRD 14 dan kriteria penerimaan 14).
 *
 * Aturan itu mudah dilanggar tanpa disadari: varian `dark:` bawaan Tailwind v4
 * menyala lewat `prefers-color-scheme`, jadi satu kelas yang terbawa komponen
 * pihak ketiga langsung berlaku di perangkat bertema gelap -- di atas palet
 * terang yang tidak punya pasangan gelapnya. Komentar di CSS tidak menahan apa
 * pun; yang menahan hanya penimpaan variannya.
 */
final class TanpaDarkModeTest extends TestCase
{
    public function test_varian_dark_diarahkan_ke_selektor_yang_tidak_pernah_ada(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/@custom-variant\s+dark\s*\(/',
            $css,
            'Tanpa penimpaan @custom-variant, `dark:` kembali menyala lewat prefers-color-scheme.',
        );
        $this->assertStringNotContainsString(
            '@custom-variant dark (&:where(.dark',
            $css,
            'Varian dark tidak boleh diarahkan ke kelas yang benar-benar dipakai.',
        );
    }

    /** Kode kita sendiri tidak boleh menulis utilitas dark:; komponen vendor dibiarkan tetapi tidak pernah cocok. */
    public function test_tidak_ada_utilitas_dark_di_kode_sendiri(): void
    {
        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (! in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                continue;
            }

            $jalur = str_replace(resource_path('js').'/', '', $berkas->getPathname());

            if (str_starts_with($jalur, 'components/ui/')) {
                continue;
            }

            if (str_contains((string) File::get($berkas->getPathname()), 'dark:')) {
                $pelanggar[] = $jalur;
            }
        }

        $this->assertSame([], $pelanggar, 'Utilitas dark: ditemukan di: '.implode(', ', $pelanggar));
    }
}
