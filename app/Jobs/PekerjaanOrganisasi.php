<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * handle() sengaja final: subclass tidak bisa lupa menetapkan konteks
 * organisasi untuk job queue database. Implementasikan jalankan().
 */
abstract class PekerjaanOrganisasi implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $OrganisasiId) {}

    final public function handle(): void
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->OrganisasiId);
        try {
            $this->jalankan();
        } finally {
            $konteks->bersihkan();
        }
    }

    abstract protected function jalankan(): void;
}
