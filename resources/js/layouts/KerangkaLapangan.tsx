/**
 * KerangkaLapangan — kerangka layar Mode Lapangan (DESIGN.md 36.2, PRD 8.20).
 *
 * Satu kolom bergaya aplikasi HP: tanpa sidebar dan breadcrumb, dipusatkan dengan lebar
 * maksimum 480px di layar lebar, dengan padding area aman (`env(safe-area-inset-*)`).
 * Memasang `PenyediaSinkronisasiOffline` seperti KerangkaAplikasi, jadi offline tetap berjalan.
 *
 * PENTING: `useSinkronisasiOffline` (dan `useKeluarLapangan`) hanya boleh dipanggil oleh
 * komponen ANAK kerangka ini, bukan di badan halaman yang merender `<KerangkaLapangan>`:
 *
 *   export default function Beranda(props) {
 *     return <KerangkaLapangan judulHalaman="Beranda"><IsiBeranda {...props} /></KerangkaLapangan>;
 *   }
 *
 * Props bersama semua varian:
 * - `judulHalaman: string` — judul tab peramban (`<Head title>`).
 * - `varian?: 'hero' | 'appbar' | 'polos'` — bawaan `hero`.
 * - `navBawah?: boolean` — navigasi bawah lima slot. Bawaan: tampil untuk `hero`/`polos`, tidak
 *   untuk `appbar` (layar alur), dan tidak bila ada `bilahAksi`.
 * - `navAktif?: KunciNavLapangan` — tab aktif; bawaan ditebak dari URL.
 * - `mode?: ModeLapangan` — menimpa isi nav; bawaan prop bersama `lapangan.mode`, lalu URL
 *   (`/lapangan/pelapor…` → Pelapor), lalu Teknisi.
 * - `bilahAksi?: ReactNode` — bilah aksi putih menempel di bawah (radius atas 24px), untuk
 *   tombol utama layar alur. Isinya flex dengan jarak 10px, mis. dua `<TombolLapangan>`.
 * - `latar?: 'latar' | 'putih'` — warna layar; `putih` untuk layar sukses. Bawaan `latar`.
 * - `children` — isi layar, disusun kolom berjarak 16px dengan padding samping 16px.
 *   Anak pertama boleh `<KartuApung>` / `<Tiket apung>` untuk menimpa hero atau appbar `panjang`.
 * - `classNameIsi?: string` — kelas tambahan untuk wadah isi.
 *
 * Varian `hero` (beranda, akun):
 * - `sapaan?: ReactNode` — baris kecil di atas judul. Bawaan "Selamat pagi," dst.
 * - `judul?: ReactNode` — baris besar. Bawaan nama pengguna.
 * - `subjudul?: ReactNode` — baris kecil di bawah judul (mis. lokasi dengan ikon pin).
 * - `avatar?: boolean` — avatar inisial/foto di kiri. Bawaan `true`.
 * - `lonceng?: boolean` — tombol notifikasi bulat (ke `lapangan.notifikasi`), bertitik oranye
 *   bila ada yang belum dibaca. Bawaan `true`.
 * - `jumlahBelumDibaca?: number` — bila diberikan, kerangka tidak menanyakan jumlahnya ke server.
 * - `statusSinkron?: boolean` — chip status sinkronisasi di bawah sapaan. Bawaan `true`.
 * - `chip?: ReactNode` — chip tambahan setelah chip sinkron (mis. `<ChipStatus warna="putih">`).
 * - `isiHero?: ReactNode` — mengganti baris sapaan bawaan seluruhnya (mis. profil besar di Akun).
 * - `apung?: boolean` — sisakan ruang bawah hero untuk kartu apung. Bawaan `true`.
 *
 * Varian `appbar` (detail, formulir, langkah kerja):
 * - `judul: ReactNode`, `subjudul?: ReactNode`.
 * - `kembali?: string | (() => void) | false` — tombol kembali bulat: URL, fungsi, atau `false`
 *   untuk menyembunyikan. Bawaan: kembali di riwayat, atau ke beranda bila tidak ada riwayat.
 * - `ikonKembali?: 'panah' | 'tutup'` — panah kiri (bawaan) atau X (keluar dari alur).
 * - `aksiKanan?: ReactNode` — isi kanan (mis. `<TombolAppbar>` atau pewaktu).
 * - `langkah?: ReactNode` — slot selebar appbar di bawah judul, mis. `<PerhentianAppbar>` atau
 *   `<PerhentianLangkah>` (dibungkus kartu putih oleh pemanggil).
 * - `panjang?: boolean` — tambah ruang bawah supaya anak pertama dapat mengapung (`-mt-14`).
 *
 * Varian `polos` (layar sukses, kamera): tanpa kepala; hanya padding area aman.
 */
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  ArrowLeft,
  Bell,
  Box,
  CircleUserRound,
  ClipboardList,
  House,
  Plus,
  ScanLine,
  Ticket,
  X,
  type LucideIcon,
} from 'lucide-react';
import { useEffect, useState, type PropsWithChildren, type ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { http } from '@/lib/http';
import { PenyediaSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { ruteNotifikasi } from '@/features/Notifikasi/api';
import { ruteLapangan } from '@/features/Lapangan/api';
import { ChipSinkron } from '@/features/Lapangan/components/ChipSinkron';
import type { KunciNavLapangan, ModeLapangan, PropsLapangan } from '@/features/Lapangan/types';
import { inisialNama, salamWaktu } from '@/features/Lapangan/waktu';

interface PropsDasar {
  judulHalaman: string;
  navBawah?: boolean;
  navAktif?: KunciNavLapangan;
  mode?: ModeLapangan;
  bilahAksi?: ReactNode;
  latar?: 'latar' | 'putih';
  classNameIsi?: string;
}

interface PropsHero extends PropsDasar {
  varian?: 'hero';
  sapaan?: ReactNode;
  judul?: ReactNode;
  subjudul?: ReactNode;
  avatar?: boolean;
  lonceng?: boolean;
  jumlahBelumDibaca?: number;
  statusSinkron?: boolean;
  chip?: ReactNode;
  isiHero?: ReactNode;
  apung?: boolean;
}

interface PropsAppbar extends PropsDasar {
  varian: 'appbar';
  judul: ReactNode;
  subjudul?: ReactNode;
  kembali?: string | (() => void) | false;
  ikonKembali?: 'panah' | 'tutup';
  aksiKanan?: ReactNode;
  langkah?: ReactNode;
  panjang?: boolean;
}

interface PropsPolos extends PropsDasar {
  varian: 'polos';
}

export type PropsKerangkaLapangan = PropsWithChildren<PropsHero | PropsAppbar | PropsPolos>;

interface ItemNav {
  kunci: KunciNavLapangan;
  label: string;
  ikon: LucideIcon;
  href: string;
  tengah?: boolean;
}

const NAV: Record<ModeLapangan, ItemNav[]> = {
  Teknisi: [
    { kunci: 'beranda', label: 'Beranda', ikon: House, href: ruteLapangan.teknisi.beranda },
    { kunci: 'tugas', label: 'Tugas', ikon: Ticket, href: ruteLapangan.teknisi.tugas },
    { kunci: 'pindai', label: 'Pindai', ikon: ScanLine, href: ruteLapangan.teknisi.pindai, tengah: true },
    { kunci: 'aset', label: 'Aset', ikon: Box, href: ruteLapangan.teknisi.aset },
    { kunci: 'akun', label: 'Akun', ikon: CircleUserRound, href: ruteLapangan.akun },
  ],
  Pelapor: [
    { kunci: 'beranda', label: 'Beranda', ikon: House, href: ruteLapangan.pelapor.beranda },
    { kunci: 'laporan', label: 'Laporan', ikon: ClipboardList, href: ruteLapangan.pelapor.laporan },
    { kunci: 'lapor', label: 'Lapor', ikon: Plus, href: ruteLapangan.pelapor.lapor, tengah: true },
    { kunci: 'aset', label: 'Aset', ikon: Box, href: ruteLapangan.pelapor.aset },
    { kunci: 'akun', label: 'Akun', ikon: CircleUserRound, href: ruteLapangan.akun },
  ],
};

function tebakNavAktif(item: ItemNav[], path: string): KunciNavLapangan | undefined {
  const cocok = item
    .filter((satu) =>
      satu.kunci === 'beranda'
        ? path === satu.href || path === ruteLapangan.beranda
        : path === satu.href || path.startsWith(`${satu.href}/`),
    )
    .sort((a, b) => b.href.length - a.href.length);
  return cocok[0]?.kunci;
}

/** Hiasan lingkaran samar hero/appbar (papan acuan `.hero::before/::after`). */
function HiasanGradien({ ringkas = false }: { ringkas?: boolean }) {
  return (
    <>
      <span
        aria-hidden
        className={cn(
          'pointer-events-none absolute rounded-full border-white/5',
          ringkas
            ? '-top-[70px] -right-[70px] size-[200px] border-[28px]'
            : '-top-[60px] -right-[90px] size-[260px] border-[36px]',
        )}
      />
      {!ringkas && (
        <span
          aria-hidden
          className="pointer-events-none absolute -bottom-[90px] -left-[70px] size-[180px] rounded-full bg-lapangan-oranye-600/12 blur-[2px]"
        />
      )}
    </>
  );
}

function Avatar({
  nama,
  url,
  besar = false,
}: {
  nama: string | undefined;
  url?: string | null;
  besar?: boolean;
}) {
  const kelas = cn(
    'flex shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white/60 font-bold text-lapangan-navy-900 gradien-avatar-lapangan',
    besar ? 'size-[62px] text-xl' : 'size-11 text-[15px]',
  );

  if (url) {
    return <img src={url} alt="" className={cn(kelas, 'object-cover')} />;
  }

  return (
    <span aria-hidden className={kelas}>
      {inisialNama(nama)}
    </span>
  );
}

export { Avatar as AvatarLapangan };

/** Jumlah notifikasi belum dibaca untuk titik oranye di tombol lonceng. */
function useJumlahBelumDibaca(aktif: boolean, dariProp: number | undefined): number {
  const [jumlah, setJumlah] = useState(0);

  useEffect(() => {
    if (!aktif || dariProp !== undefined || !navigator.onLine) return;
    let batal = false;
    http
      .get<{ jumlahBelumDibaca: number }>(ruteNotifikasi.ringkasan)
      .then(({ data }) => {
        if (!batal) setJumlah(data.jumlahBelumDibaca);
      })
      .catch(() => undefined);
    return () => {
      batal = true;
    };
  }, [aktif, dariProp]);

  return dariProp ?? jumlah;
}

const KELAS_TOMBOL_BULAT =
  'relative flex size-[42px] shrink-0 items-center justify-center rounded-full bg-white/15 text-white transition-colors hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white';

/** Tombol bulat putih transparan untuk `aksiKanan` appbar; `teks` membuatnya berbentuk pil. */
export function TombolAppbar({
  label,
  ikon: Ikon,
  teks,
  onClick,
  href,
}: {
  label: string;
  ikon?: LucideIcon;
  teks?: string;
  onClick?: () => void;
  href?: string;
}) {
  const kelas = cn(KELAS_TOMBOL_BULAT, teks && 'w-auto gap-1.5 px-3.5 text-[13px] font-bold');
  const isi = (
    <>
      {Ikon && <Ikon aria-hidden className={teks ? 'size-4' : 'size-5'} />}
      {teks}
    </>
  );

  if (href) {
    return (
      <Link href={href} aria-label={teks ? undefined : label} className={kelas}>
        {isi}
      </Link>
    );
  }

  return (
    <button type="button" onClick={onClick} aria-label={teks ? undefined : label} className={kelas}>
      {isi}
    </button>
  );
}

function KepalaHero(props: PropsHero) {
  const { auth } = usePage<PropsLapangan>().props;
  const {
    sapaan = `${salamWaktu()},`,
    judul = auth.pengguna?.Nama ?? 'Pengguna',
    subjudul,
    avatar = true,
    lonceng = true,
    jumlahBelumDibaca,
    statusSinkron = true,
    chip,
    isiHero,
    apung = true,
  } = props;
  const belumDibaca = useJumlahBelumDibaca(lonceng, jumlahBelumDibaca);

  return (
    <header
      className={cn(
        'gradien-hero-lapangan relative overflow-hidden px-5 pt-[calc(env(safe-area-inset-top)+20px)] text-white',
        apung ? 'pb-[76px]' : 'pb-6',
      )}
    >
      <HiasanGradien />
      <div className="relative z-10">
        {isiHero ?? (
          <div className="flex items-center gap-3">
            {avatar && <Avatar nama={auth.pengguna?.Nama} url={auth.pengguna?.AvatarUrl} />}
            <div className="min-w-0 flex-1">
              {sapaan && <p className="text-[13px] font-medium text-white/75">{sapaan}</p>}
              <h1 className="truncate text-[19px] leading-tight font-bold tracking-[-0.01em]">{judul}</h1>
              {subjudul && <p className="mt-px text-[13px] font-medium text-white/75">{subjudul}</p>}
            </div>
            {lonceng && (
              <Link
                href={ruteLapangan.notifikasi}
                aria-label={belumDibaca > 0 ? `Notifikasi, ${belumDibaca} belum dibaca` : 'Notifikasi'}
                className={KELAS_TOMBOL_BULAT}
              >
                <Bell aria-hidden className="size-5" />
                {belumDibaca > 0 && (
                  <span
                    aria-hidden
                    className="absolute top-[7px] right-2 size-2.5 rounded-full border-2 border-lapangan-navy-800 bg-lapangan-oranye-600"
                  />
                )}
              </Link>
            )}
          </div>
        )}
        {(statusSinkron || chip) && (
          <div className="mt-3.5 flex flex-wrap gap-2">
            {statusSinkron && <ChipSinkron />}
            {chip}
          </div>
        )}
      </div>
    </header>
  );
}

function KepalaAppbar({
  judul,
  subjudul,
  kembali,
  ikonKembali = 'panah',
  aksiKanan,
  langkah,
  panjang,
}: PropsAppbar) {
  const IkonKembali = ikonKembali === 'tutup' ? X : ArrowLeft;
  const labelKembali = ikonKembali === 'tutup' ? 'Tutup' : 'Kembali';

  const kembaliBawaan = () => {
    if (window.history.length > 1) {
      window.history.back();
    } else {
      router.visit(ruteLapangan.beranda);
    }
  };

  return (
    <header
      className={cn(
        'gradien-hero-lapangan relative flex shrink-0 flex-wrap items-center gap-x-2.5 gap-y-[18px] overflow-hidden px-4 pt-[calc(env(safe-area-inset-top)+16px)] text-white',
        panjang ? 'pb-16' : 'pb-4',
        langkah && !panjang && 'pb-5',
      )}
    >
      <HiasanGradien ringkas />
      {kembali !== false &&
        (typeof kembali === 'string' ? (
          <Link href={kembali} aria-label={labelKembali} className={cn(KELAS_TOMBOL_BULAT, 'z-10 size-10')}>
            <IkonKembali aria-hidden className="size-5" />
          </Link>
        ) : (
          <button
            type="button"
            onClick={kembali ?? kembaliBawaan}
            aria-label={labelKembali}
            className={cn(KELAS_TOMBOL_BULAT, 'z-10 size-10')}
          >
            <IkonKembali aria-hidden className="size-5" />
          </button>
        ))}
      <div className="relative z-10 min-w-0 flex-1">
        <h1 className="truncate text-lg leading-tight font-bold">{judul}</h1>
        {subjudul && <p className="truncate text-[13px] text-white/75">{subjudul}</p>}
      </div>
      {aksiKanan && <div className="relative z-10 flex shrink-0 items-center gap-2">{aksiKanan}</div>}
      {langkah && <div className="relative z-10 basis-full">{langkah}</div>}
    </header>
  );
}

function NavBawah({ item, aktif }: { item: ItemNav[]; aktif: KunciNavLapangan | undefined }) {
  return (
    <nav
      aria-label="Navigasi utama"
      className="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[480px] rounded-t-[26px] bg-white px-1.5 pt-2.5 pb-[calc(env(safe-area-inset-bottom)+10px)] shadow-[0_-6px_24px_rgb(15_42_68_/_0.08)]"
    >
      <ul className="grid grid-cols-5">
        {item.map((satu) => {
          const terpilih = satu.kunci === aktif;
          const Ikon = satu.ikon;

          if (satu.tengah) {
            return (
              <li key={satu.kunci} className="flex justify-center">
                <Link
                  href={satu.href}
                  aria-current={terpilih ? 'page' : undefined}
                  className="-mt-[30px] flex flex-col items-center gap-1 rounded-2xl text-xs font-bold text-lapangan-navy-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
                >
                  <span className="gradien-fab-lapangan flex size-[62px] items-center justify-center rounded-full border-[5px] border-white text-white shadow-lapangan-fab">
                    <Ikon aria-hidden className="size-7" strokeWidth={2.2} />
                  </span>
                  {satu.label}
                </Link>
              </li>
            );
          }

          return (
            <li key={satu.kunci} className="flex justify-center">
              <Link
                href={satu.href}
                aria-current={terpilih ? 'page' : undefined}
                className={cn(
                  'flex min-h-11 w-full flex-col items-center gap-1 rounded-xl text-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
                  terpilih ? 'font-bold text-lapangan-navy-800' : 'font-semibold text-lapangan-teks-3',
                )}
              >
                <Ikon aria-hidden className={cn('size-6', terpilih && 'text-lapangan-oranye-600')} />
                {satu.label}
              </Link>
            </li>
          );
        })}
      </ul>
    </nav>
  );
}

function KerangkaDalam(props: PropsKerangkaLapangan) {
  const page = usePage<PropsLapangan>();
  const path = page.url.split('?')[0];
  const varian = props.varian ?? 'hero';
  const { judulHalaman, bilahAksi, latar = 'latar', classNameIsi, children } = props;

  const mode: ModeLapangan =
    props.mode ??
    page.props.lapangan?.mode ??
    (path.startsWith(ruteLapangan.pelapor.beranda) ? 'Pelapor' : 'Teknisi');
  const itemNav = NAV[mode];
  const tampilNav = props.navBawah ?? (varian !== 'appbar' && !bilahAksi);
  const navAktif = props.navAktif ?? tebakNavAktif(itemNav, path);

  const isiMengapung =
    props.varian === undefined || props.varian === 'hero'
      ? props.apung !== false
      : props.varian === 'appbar' && props.panjang === true;

  return (
    <div className="min-h-dvh bg-lapangan-garis text-[15px] leading-[1.45] text-lapangan-teks antialiased">
      <Head title={judulHalaman} />
      <div
        className={cn(
          'relative mx-auto flex min-h-dvh w-full max-w-[480px] flex-col min-[481px]:shadow-[0_0_40px_rgb(11_34_57_/_0.12)]',
          latar === 'putih' ? 'bg-white' : 'bg-lapangan-latar',
        )}
      >
        {props.varian === 'appbar' ? (
          <KepalaAppbar {...props} />
        ) : props.varian === 'polos' ? null : (
          <KepalaHero {...props} />
        )}

        <main
          className={cn(
            'flex flex-1 flex-col gap-4 px-4',
            varian === 'polos' ? 'pt-[calc(env(safe-area-inset-top)+24px)]' : isiMengapung ? 'pt-0' : 'pt-4',
            tampilNav
              ? 'pb-[calc(env(safe-area-inset-bottom)+104px)]'
              : bilahAksi
                ? 'pb-[calc(env(safe-area-inset-bottom)+112px)]'
                : 'pb-[calc(env(safe-area-inset-bottom)+24px)]',
            classNameIsi,
          )}
        >
          {children}
        </main>

        {bilahAksi && (
          <div
            className={cn(
              'fixed inset-x-0 z-30 mx-auto flex w-full max-w-[480px] gap-2.5 rounded-t-3xl bg-white px-4 pt-3.5 shadow-lapangan-bilah',
              tampilNav
                ? 'bottom-[calc(env(safe-area-inset-bottom)+78px)] pb-3.5'
                : 'bottom-0 pb-[calc(env(safe-area-inset-bottom)+14px)]',
            )}
          >
            {bilahAksi}
          </div>
        )}

        {tampilNav && <NavBawah item={itemNav} aktif={navAktif} />}
      </div>
    </div>
  );
}

/** Pembungkus luar hanya memasang penyedia sinkronisasi offline; kerangkanya sendiri ada di dalam. */
export default function KerangkaLapangan(props: PropsKerangkaLapangan) {
  return (
    <PenyediaSinkronisasiOffline>
      <KerangkaDalam {...props} />
    </PenyediaSinkronisasiOffline>
  );
}
