import { Link } from '@inertiajs/react';
import { ArrowRight, Clock } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { ruteAuth } from '@/features/Auth/api';
import { isiPanelAutentikasi, manfaatProduk, tiketContoh } from '@/features/Auth/isi';
import { cn } from '@/lib/utils';

interface AjakanProps {
  /** Durasi trial dari server; ajakan hanya tampil bila lebih dari nol. */
  durasiTrialHari?: number | null;
}

/** Durasi trial yang layak ditawarkan, atau null bila ajakan tidak ditampilkan. */
function durasiTawaran(durasiTrialHari?: number | null): number | null {
  return durasiTrialHari !== undefined && durasiTrialHari !== null && durasiTrialHari > 0
    ? durasiTrialHari
    : null;
}

/** Tombol terang di atas panel gelap; bukan amber, karena amber hanya untuk peringatan (DESIGN.md 4.2). */
const TOMBOL_AJAKAN =
  'inline-flex min-h-11 items-center justify-center gap-2 rounded-[7px] bg-card px-4 text-sm font-semibold text-teknisi-900 transition-colors hover:bg-teknisi-50 focus-visible:ring-2 focus-visible:ring-teknisi-300 focus-visible:ring-offset-2 focus-visible:ring-offset-teknisi-900 focus-visible:outline-none';

function PitaBaru() {
  return (
    <p className="inline-flex max-w-full items-center gap-2 rounded-md border border-white/15 bg-white/10 px-2.5 py-1.5 text-sm text-teknisi-100">
      <span className="shrink-0 rounded-[5px] bg-teknisi-100 px-1.5 py-px text-xs font-semibold text-teknisi-900">
        {isiPanelAutentikasi.pitaBaru.label}
      </span>
      <span>{isiPanelAutentikasi.pitaBaru.teks}</span>
    </p>
  );
}

/**
 * Pratinjau produk dari elemen UI yang sama dengan dasbor (kartu angka, badge
 * status). Datanya rekaan dan diberi catatan "Contoh tampilan", bukan
 * tangkapan layar pelanggan.
 */
function PratinjauProduk() {
  const { pratinjau } = isiPanelAutentikasi;

  return (
    <figure
      className="overflow-hidden rounded-[10px] border border-white/15 bg-card text-foreground shadow-[0_8px_24px_rgb(23_32_39_/_0.10),0_2px_6px_rgb(23_32_39_/_0.06)]"
      aria-label={`${pratinjau.judul}, ${pratinjau.catatan.toLowerCase()}`}
    >
      <div className="flex items-center justify-between gap-3 border-b border-garis-200 bg-permukaan-50 px-4 py-2.5">
        <p className="text-sm font-semibold text-foreground">{pratinjau.judul}</p>
        <figcaption className="text-xs text-muted-foreground">{pratinjau.catatan}</figcaption>
      </div>
      <div className="space-y-3 p-4">
        <dl className="grid grid-cols-3 gap-2">
          {pratinjau.kpi.map((kpi) => (
            <div key={kpi.label} className="min-w-0 rounded-md border border-garis-200 px-3 py-2">
              <dt className="truncate text-xs text-muted-foreground">{kpi.label}</dt>
              <dd className="text-xl font-semibold tabular-nums text-foreground">{kpi.nilai}</dd>
            </div>
          ))}
        </dl>
        <ul className="divide-y divide-garis-200 rounded-md border border-garis-200">
          {tiketContoh.map((tiket, urutan) => (
            <li
              key={tiket.nomor}
              className={cn(
                'flex items-center justify-between gap-3 px-3 py-2.5',
                // Layar pendek (mis. 1280x800): cukup satu tiket supaya panel tidak perlu digulir.
                urutan > 0 && '[@media(max-height:840px)]:hidden',
              )}
            >
              <div className="min-w-0">
                <p className="truncate text-sm font-medium text-foreground">{tiket.judul}</p>
                <p className="text-xs text-muted-foreground">
                  <span className="font-mono">{tiket.nomor}</span> · Unit {tiket.unit}
                </p>
              </div>
              <div className="flex shrink-0 flex-col items-end gap-1">
                <Badge variant={tiket.varian}>{tiket.status}</Badge>
                <span className="inline-flex items-center gap-1 text-xs text-grafit-700">
                  <Clock className="size-3" aria-hidden="true" />
                  {tiket.sla}
                </span>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </figure>
  );
}

function Ajakan({ durasiHari }: { durasiHari: number }) {
  return (
    <Link href={ruteAuth.daftar} className={TOMBOL_AJAKAN}>
      {isiPanelAutentikasi.ajakan.tombol(durasiHari)}
      <ArrowRight className="size-4" aria-hidden="true" />
    </Link>
  );
}

/** Panel kanan desktop (>= 1024px): nilai produk, manfaat, pratinjau, dan ajakan trial. */
export function PanelPemasaran({ durasiTrialHari, className }: AjakanProps & { className?: string }) {
  const durasi = durasiTawaran(durasiTrialHari);

  return (
    <aside
      aria-label="Tentang Amanpoll"
      className={cn(
        'latar-panel-autentikasi text-white lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto',
        className,
      )}
    >
      <div className="mx-auto flex min-h-full w-full max-w-[600px] flex-col justify-center gap-6 px-10 py-8 xl:px-12">
        <div className="space-y-4">
          <PitaBaru />
          <h2 className="text-[30px] leading-[38px] font-bold tracking-tight text-white">
            {isiPanelAutentikasi.judul}
          </h2>
          <p className="text-base text-teknisi-100">{isiPanelAutentikasi.subjudul}</p>
          {durasi !== null ? <Ajakan durasiHari={durasi} /> : null}
        </div>

        <ul className="grid grid-cols-2 gap-x-6 gap-y-5">
          {manfaatProduk.map(({ ikon: Ikon, judul, keterangan }) => (
            <li key={judul} className="flex gap-3">
              <span className="flex size-9 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10">
                <Ikon className="size-[18px] text-teknisi-100" aria-hidden="true" />
              </span>
              <div className="min-w-0">
                <p className="text-sm font-semibold text-white">{judul}</p>
                <p className="mt-0.5 text-sm leading-5 text-teknisi-100">{keterangan}</p>
              </div>
            </li>
          ))}
        </ul>

        <PratinjauProduk />
      </div>
    </aside>
  );
}

/**
 * Versi ringkas untuk HP dan tablet: pita manfaat di bawah formulir, sehingga
 * formulir tetap di layar pertama.
 */
export function PitaManfaat({ durasiTrialHari, className }: AjakanProps & { className?: string }) {
  const durasi = durasiTawaran(durasiTrialHari);

  return (
    <section aria-label="Tentang Amanpoll" className={cn('latar-panel-autentikasi text-white', className)}>
      <div className="mx-auto w-full max-w-2xl space-y-5 px-4 py-8 sm:px-8">
        <div className="space-y-3">
          <PitaBaru />
          <h2 className="text-xl font-semibold text-white">{isiPanelAutentikasi.judul}</h2>
        </div>
        <ul className="grid gap-3 sm:grid-cols-2">
          {manfaatProduk.map(({ ikon: Ikon, judul, ringkas }) => (
            <li key={judul} className="flex items-center gap-3 text-sm font-medium text-white">
              <span className="flex size-8 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10">
                <Ikon className="size-4 text-teknisi-100" aria-hidden="true" />
              </span>
              {ringkas}
            </li>
          ))}
        </ul>
        <div className="hidden pt-1 sm:block">
          <PratinjauProduk />
        </div>
        {durasi !== null ? (
          <Link href={ruteAuth.daftar} className={cn(TOMBOL_AJAKAN, 'w-full sm:w-auto')}>
            {isiPanelAutentikasi.ajakan.tombol(durasi)}
            <ArrowRight className="size-4" aria-hidden="true" />
          </Link>
        ) : null}
      </div>
    </section>
  );
}
