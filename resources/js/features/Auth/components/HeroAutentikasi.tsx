import { MapPin, Wrench } from 'lucide-react';
import { Ikon3D } from '@/components/shared/Ikon3D';
import { isiAutentikasi, jenisTempat, tiketContoh } from '@/features/Auth/isi';
import { cn } from '@/lib/utils';

/**
 * Latar hero halaman autentikasi (DESIGN.md 37): gradien biru, cincin samar, dan jalur
 * putus-putus. Menempel di atas halaman; tingginya mengikuti `--tinggi-hero` di desktop.
 * Sudut bawah melengkung: kedua sisi di HP, hanya kanan (140px) di desktop.
 */
export function LatarHero() {
  return (
    <div
      aria-hidden="true"
      className="gradien-hero-autentikasi absolute inset-x-0 top-0 -z-10 h-[310px] overflow-hidden rounded-b-[36px] sm:h-[392px] lg:h-(--tinggi-hero) lg:rounded-b-none lg:rounded-br-[140px]"
    >
      {/* HP: satu cincin di kanan atas dan bulatan oranye lembut di kiri bawah. */}
      <span className="absolute -top-[70px] -right-[90px] size-[260px] rounded-full border-[36px] border-white/5 lg:hidden" />
      <span className="absolute top-[200px] -left-[70px] size-[180px] rounded-full bg-lapangan-oranye-600/15 blur-[2px] lg:hidden" />

      {/* Desktop: cincin besar di kanan atas, cincin kecil di tengah, jalur putus-putus. */}
      <span className="absolute -top-[200px] -right-[120px] hidden size-[520px] rounded-full border-[44px] border-white/5 lg:block" />
      <span className="absolute top-[calc(var(--tinggi-hero)-200px)] left-[calc(50%-200px)] hidden size-[300px] rounded-full border-[30px] border-white/[0.04] lg:block" />
      <svg
        className="absolute top-[calc(var(--tinggi-hero)-560px)] left-[calc(50%-720px)] hidden lg:block"
        width="1440"
        height="560"
        viewBox="0 0 1440 560"
        fill="none"
      >
        <path
          d="M560 520 C 640 440, 700 330, 820 300 S 980 250, 1060 120"
          stroke="white"
          strokeOpacity="0.16"
          strokeWidth="2"
          strokeDasharray="2 8"
          strokeLinecap="round"
        />
      </svg>
    </div>
  );
}

/** Teknisi 3D di kanan hero HP dan tablet; bagian bawahnya tertutup kartu formulir. */
export function TeknisiHeroRingkas() {
  return (
    <Ikon3D
      nama="man_mechanic"
      ukuran={150}
      segera
      className="absolute top-[98px] -right-1 drop-shadow-[0_14px_16px_rgb(11_34_57_/_0.22)] max-[379px]:top-[116px] max-[379px]:size-[124px]! sm:top-[110px] sm:right-0 sm:size-[200px]! lg:hidden"
    />
  );
}

function ChipTiket({
  warna,
  ikon: Ikon,
  children,
}: {
  warna: 'oranye' | 'biru';
  ikon: typeof Wrench;
  children: string;
}) {
  return (
    <span
      className={cn(
        'inline-flex h-[26px] shrink-0 items-center gap-[5px] rounded-full px-2.5 text-xs font-bold whitespace-nowrap',
        warna === 'oranye'
          ? 'bg-lapangan-oranye-50 text-lapangan-oranye-teks'
          : 'bg-lapangan-biru-50 text-lapangan-biru-600',
      )}
    >
      <Ikon className="size-3.5 stroke-[2.4]" aria-hidden="true" />
      {children}
    </span>
  );
}

/**
 * Tiket kerja bergaya karcis (DESIGN.md 36.3) dengan data rekaan berlabel "Contoh".
 * Lekukan sobekan diisi warna latar halaman karena posisinya selalu di bawah batas hero.
 */
function TiketContoh({ className }: { className?: string }) {
  return (
    <div className={cn('rounded-[22px] bg-white text-lapangan-teks shadow-lapangan-tiket', className)}>
      <div className="px-5 pt-[18px] pb-3">
        <div className="mb-3 flex items-center justify-between gap-2.5">
          <span className="text-[12.5px] font-bold tracking-[0.01em] text-lapangan-teks-3">
            {tiketContoh.nomor}
            <span className="font-semibold"> · {isiAutentikasi.catatanContoh}</span>
          </span>
          <ChipTiket warna="oranye" ikon={Wrench}>
            {tiketContoh.status}
          </ChipTiket>
        </div>
        <p className="mb-3 text-[17px] leading-snug font-extrabold tracking-[-0.01em]">{tiketContoh.judul}</p>
        <div className="grid grid-cols-[auto_1fr_auto] items-center gap-3">
          <div>
            <strong className="block text-2xl leading-[1.1] font-extrabold tracking-[-0.02em] tabular-nums">
              {tiketContoh.dilaporkan}
            </strong>
            <small className="mt-0.5 block text-xs font-semibold text-lapangan-teks-3">Dilaporkan</small>
          </div>
          <div className="relative flex h-8 items-center justify-center">
            <span className="absolute inset-x-0 top-1/2 border-t-2 border-dotted border-lapangan-teks-3/40" />
            <span className="relative flex flex-col items-center bg-white px-2 text-xs font-bold text-lapangan-teks-3">
              <Ikon3D nama="stopwatch" ukuran={26} className="drop-shadow-none" />
              {tiketContoh.sisa}
            </span>
          </div>
          <div className="text-right">
            <strong className="block text-2xl leading-[1.1] font-extrabold tracking-[-0.02em] tabular-nums">
              {tiketContoh.targetSla}
            </strong>
            <small className="mt-0.5 block text-xs font-semibold text-lapangan-teks-3">Target SLA</small>
          </div>
        </div>
      </div>
      <div className="relative h-[18px] before:absolute before:top-0 before:-left-[9px] before:size-[18px] before:rounded-full before:bg-lapangan-latar after:absolute after:top-0 after:-right-[9px] after:size-[18px] after:rounded-full after:bg-lapangan-latar">
        <i className="absolute inset-x-[18px] top-2 border-t-2 border-dashed border-lapangan-garis" />
      </div>
      <div className="flex items-center gap-2.5 px-5 pt-3 pb-4">
        <span className="gradien-avatar-lapangan flex size-[34px] shrink-0 items-center justify-center rounded-full text-[13px] font-extrabold text-lapangan-navy-900">
          {tiketContoh.inisial}
        </span>
        <div className="min-w-0 flex-1 text-[13px] leading-[1.35] text-lapangan-teks-2">
          <b className="block text-sm font-bold text-lapangan-teks">{tiketContoh.teknisi}</b>
          {tiketContoh.keterangan}
        </div>
        <ChipTiket warna="biru" ikon={MapPin}>
          {tiketContoh.posisi}
        </ChipTiket>
      </div>
    </div>
  );
}

/**
 * Ilustrasi desktop yang menembus batas hero: teknisi 3D di balik tiket contoh, kilau, serta
 * kotak perkakas dan palu-kunci di tepi hero. Kotaknya menempel di dasar area hero kiri,
 * jadi tiket selalu menimpa batas hero berapa pun tinggi hero. Kotak perkakas diperkecil,
 * lalu disembunyikan, saat kolom kiri menyempit (kueri kontainer).
 */
export function IlustrasiHero() {
  return (
    <div
      aria-hidden="true"
      className="@container absolute inset-x-0 bottom-0 hidden h-[373px] max-w-[734px] lg:block"
    >
      <Ikon3D
        nama="man_mechanic"
        ukuran={230}
        segera
        className="absolute top-0 left-[222px] drop-shadow-[0_14px_16px_rgb(11_34_57_/_0.22)]"
      />
      <Ikon3D
        nama="sparkles"
        ukuran={44}
        segera
        className="absolute top-[18px] left-[444px] drop-shadow-[0_14px_16px_rgb(11_34_57_/_0.22)]"
      />
      <span className="absolute top-[294px] right-[54px] hidden h-[26px] w-[170px] rounded-[50%] bg-[radial-gradient(ellipse,rgb(11_34_57_/_0.28),rgb(11_34_57_/_0)_70%)] @min-[640px]:block" />
      <Ikon3D
        nama="toolbox"
        ukuran={150}
        segera
        className="absolute top-[170px] right-[68px] hidden drop-shadow-[0_14px_16px_rgb(11_34_57_/_0.22)] @min-[640px]:block @max-[700px]:right-[56px] @max-[700px]:size-[128px]!"
      />
      <Ikon3D
        nama="hammer_and_wrench"
        ukuran={110}
        segera
        className="absolute top-[120px] right-0 hidden -rotate-[14deg] drop-shadow-[0_14px_16px_rgb(11_34_57_/_0.22)] @min-[640px]:block @max-[700px]:size-[96px]!"
      />
      <TiketContoh className="absolute top-[146px] left-0 w-[min(450px,100%)] @max-[700px]:w-[420px]" />
    </div>
  );
}

/** Deretan jenis tempat berikon 3D. Tersembunyi di HP agar formulir tetap di layar pertama. */
export function DeretanJenisTempat({ className }: { className?: string }) {
  return (
    <section aria-labelledby="judul-jenis-tempat" className={className}>
      <h2 id="judul-jenis-tempat" className="mb-3.5 text-[15px] font-bold text-lapangan-teks-2">
        {isiAutentikasi.judulJenisTempat}
      </h2>
      <ul className="grid grid-cols-6 gap-2.5">
        {jenisTempat.map((tempat) => (
          <li
            key={tempat.label}
            className="flex flex-col items-center gap-2 text-center text-[13px] font-semibold text-lapangan-teks"
          >
            <span className="flex size-16 items-center justify-center rounded-[20px] bg-white shadow-lapangan-kartu">
              <Ikon3D nama={tempat.ikon} ukuran={44} className="drop-shadow-none" />
            </span>
            {tempat.label}
          </li>
        ))}
      </ul>
    </section>
  );
}
