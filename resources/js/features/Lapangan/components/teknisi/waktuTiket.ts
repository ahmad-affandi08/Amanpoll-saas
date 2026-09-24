import type { TitikRute } from '@/features/Lapangan/components/RuteJam';
import type { NamaIkon3D } from '@/components/shared/Ikon3D';
import type { TiketTeknisi } from '@/features/Lapangan/types';
import { jamPendek, tanggalPendek } from '@/features/Lapangan/waktu';

/** Status yang berarti teknisi sudah menyelesaikan bagiannya. */
export const STATUS_SELESAI_TEKNISI = ['MenungguVerifikasi', 'Selesai', 'Ditutup'];

const MENIT = 60_000;

function awalHari(tanggal: Date): number {
  return new Date(tanggal.getFullYear(), tanggal.getMonth(), tanggal.getDate()).getTime();
}

/** Tanggal yang sama dengan `sekarang` menurut jam perangkat. */
export function hariIni(nilai: string | null | undefined, sekarang: Date = new Date()): boolean {
  if (!nilai) return false;
  const tanggal = new Date(nilai);
  return !Number.isNaN(tanggal.getTime()) && awalHari(tanggal) === awalHari(sekarang);
}

/** "sisa 1j 49m", "sisa 25 mnt", "lewat 3j", "lewat 2 hari". */
export function labelSisa(batas: string, sekarang: Date = new Date()): { teks: string; lewat: boolean } {
  const selisih = new Date(batas).getTime() - sekarang.getTime();
  const lewat = selisih < 0;
  const menit = Math.floor(Math.abs(selisih) / MENIT);
  const jam = Math.floor(menit / 60);
  const hari = Math.floor(jam / 24);

  let teks: string;
  if (hari >= 1) {
    teks = `${hari} hari`;
  } else if (jam >= 1) {
    teks = lewat ? `${jam}j` : `${jam}j ${menit % 60}m`;
  } else {
    teks = `${Math.max(menit, 1)} mnt`;
  }

  return { teks: lewat ? `lewat ${teks}` : `sisa ${teks}`, lewat };
}

/** Tiket melewati batas penyelesaiannya dan belum diselesaikan teknisi. */
export function terlambat(tiket: TiketTeknisi, sekarang: Date = new Date()): boolean {
  return (
    tiket.BatasPada !== null &&
    !STATUS_SELESAI_TEKNISI.includes(tiket.Status) &&
    new Date(tiket.BatasPada).getTime() < sekarang.getTime()
  );
}

/** "2 hari" dari tiket yang terlambat, untuk chip "Terlambat 2 hari". */
export function lamaTerlambat(tiket: TiketTeknisi, sekarang: Date = new Date()): string | null {
  if (!terlambat(tiket, sekarang) || !tiket.BatasPada) return null;
  return labelSisa(tiket.BatasPada, sekarang).teks.replace('lewat ', '');
}

function labelDenganTanggal(label: string, nilai: string, sekarang: Date): string {
  return hariIni(nilai, sekarang) ? label : `${label}, ${tanggalPendek(nilai)}`;
}

export interface RuteTiket {
  kiri: TitikRute;
  kanan: TitikRute;
  ikon: NamaIkon3D;
  label?: string;
  labelMerah: boolean;
}

/**
 * Rute jam tiket (DESIGN §36.3): tiket dari laporan "Dilaporkan → Target SLA" dengan sisa
 * waktu; pekerjaan terjadwal "Mulai → Target" dengan ikon kalender; yang terlambat merah.
 */
export function ruteTiket(tiket: TiketTeknisi, sekarang: Date = new Date()): RuteTiket | null {
  const terjadwal = !tiket.DariKeluhan && tiket.DijadwalkanMulaiPada !== null;
  const awal = terjadwal ? tiket.DijadwalkanMulaiPada : tiket.DilaporkanPada;
  const batas = tiket.BatasPada;

  if (!awal || !batas) {
    return null;
  }

  const sisa = STATUS_SELESAI_TEKNISI.includes(tiket.Status) ? null : labelSisa(batas, sekarang);
  const lewat = sisa?.lewat ?? false;

  return {
    kiri: {
      jam: jamPendek(awal),
      label: terjadwal || lewat ? labelDenganTanggal('Mulai', awal, sekarang) : 'Dilaporkan',
    },
    kanan: {
      jam: jamPendek(batas),
      label: lewat ? labelDenganTanggal('Target', batas, sekarang) : terjadwal ? 'Target' : 'Target SLA',
      merah: lewat,
    },
    ikon: lewat ? 'hourglass_done' : terjadwal ? 'spiral_calendar' : 'stopwatch',
    label: sisa?.teks,
    labelMerah: lewat,
  };
}

/** "42 mnt" atau "1j 5m" dari jumlah menit. */
export function durasiPendek(menit: number): string {
  if (menit < 60) return `${Math.max(0, Math.round(menit))} mnt`;
  const jam = Math.floor(menit / 60);
  const sisa = Math.round(menit % 60);
  return sisa === 0 ? `${jam} jam` : `${jam}j ${sisa}m`;
}

/** Pewaktu "12:36" atau "1:02:05" dari jumlah detik. */
export function pewaktu(detik: number): string {
  const aman = Math.max(0, Math.floor(detik));
  const jam = Math.floor(aman / 3600);
  const menit = Math.floor((aman % 3600) / 60);
  const sisaDetik = aman % 60;
  const dua = (n: number) => String(n).padStart(2, '0');
  return jam > 0 ? `${jam}:${dua(menit)}:${dua(sisaDetik)}` : `${dua(menit)}:${dua(sisaDetik)}`;
}

/** Nama pendek aset atau judul untuk halte jadwal ("Lift 3", "AC Lt. 12"). */
export function labelHalte(tiket: TiketTeknisi): string {
  return tiket.Aset?.Nama ?? tiket.Judul;
}

/** "Menara A · Shaft Timur". */
export function teksLokasi(lokasi: { Nama: string; Induk: string | null } | null | undefined): string | null {
  if (!lokasi) return null;
  return lokasi.Induk ? `${lokasi.Induk} · ${lokasi.Nama}` : lokasi.Nama;
}
