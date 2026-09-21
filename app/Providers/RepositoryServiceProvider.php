<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\IntegrasiAudit\Domain\Repositories\CatatanAksesRepository;
use App\Domain\IntegrasiAudit\Domain\Repositories\CatatanAuditRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories\EloquentCatatanAksesRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories\EloquentCatatanAuditRepository;
use App\Domain\Kolaborasi\Domain\Repositories\BerkasRepository;
use App\Domain\Kolaborasi\Domain\Repositories\DefinisiKolomKustomRepository;
use App\Domain\Kolaborasi\Domain\Repositories\EntitasTagRepository;
use App\Domain\Kolaborasi\Domain\Repositories\KomentarEntitasRepository;
use App\Domain\Kolaborasi\Domain\Repositories\LampiranEntitasRepository;
use App\Domain\Kolaborasi\Domain\Repositories\NilaiKolomKustomRepository;
use App\Domain\Kolaborasi\Domain\Repositories\TagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentBerkasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentDefinisiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentEntitasTagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentKomentarEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentLampiranEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentNilaiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentTagRepository;
use App\Domain\Notifikasi\Domain\Repositories\NotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Repositories\EloquentNotifikasiRepository;
use App\Domain\Platform\Domain\Repositories\PenggunaRepository;
use App\Domain\Platform\Domain\Repositories\PeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentPenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentPeranRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        PenggunaRepository::class => EloquentPenggunaRepository::class,
        PeranRepository::class => EloquentPeranRepository::class,
        BerkasRepository::class => EloquentBerkasRepository::class,
        LampiranEntitasRepository::class => EloquentLampiranEntitasRepository::class,
        TagRepository::class => EloquentTagRepository::class,
        EntitasTagRepository::class => EloquentEntitasTagRepository::class,
        DefinisiKolomKustomRepository::class => EloquentDefinisiKolomKustomRepository::class,
        NilaiKolomKustomRepository::class => EloquentNilaiKolomKustomRepository::class,
        KomentarEntitasRepository::class => EloquentKomentarEntitasRepository::class,
        NotifikasiRepository::class => EloquentNotifikasiRepository::class,
        CatatanAuditRepository::class => EloquentCatatanAuditRepository::class,
        CatatanAksesRepository::class => EloquentCatatanAksesRepository::class,
    ];
}
