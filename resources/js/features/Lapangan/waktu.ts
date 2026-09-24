/** Format waktu Mode Lapangan: jam bertitik ("09.41"), relatif ("2 mnt lalu"), dan kelompok hari. */

function keTanggal(nilai: Date | string): Date {
  return nilai instanceof Date ? nilai : new Date(nilai);
}

/** "09.41" (jam 24 dengan titik, seperti di papan acuan). */
export function jamPendek(nilai: Date | string | null | undefined): string {
  if (!nilai) return '—';
  const tanggal = keTanggal(nilai);
  if (Number.isNaN(tanggal.getTime())) return '—';
  const jam = String(tanggal.getHours()).padStart(2, '0');
  const menit = String(tanggal.getMinutes()).padStart(2, '0');
  return `${jam}.${menit}`;
}

/** "24 Sep" atau "24 Sep 2025" bila tahunnya berbeda. */
export function tanggalPendek(nilai: Date | string | null | undefined): string {
  if (!nilai) return '—';
  const tanggal = keTanggal(nilai);
  if (Number.isNaN(tanggal.getTime())) return '—';
  const tahunIni = new Date().getFullYear() === tanggal.getFullYear();
  return tanggal.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    ...(tahunIni ? {} : { year: 'numeric' }),
  });
}

/** "baru saja", "2 mnt lalu", "3 jam lalu", "kemarin", lalu tanggal pendek. */
export function waktuRelatif(nilai: Date | string | null | undefined, sekarang: Date = new Date()): string {
  if (!nilai) return '—';
  const tanggal = keTanggal(nilai);
  const selisihMenit = Math.floor((sekarang.getTime() - tanggal.getTime()) / 60000);
  if (Number.isNaN(selisihMenit)) return '—';
  if (selisihMenit < 1) return 'baru saja';
  if (selisihMenit < 60) return `${selisihMenit} mnt lalu`;
  const selisihJam = Math.floor(selisihMenit / 60);
  if (selisihJam < 24 && tanggal.getDate() === sekarang.getDate()) return `${selisihJam} jam lalu`;
  if (kelompokHari(tanggal, sekarang) === 'Kemarin') return 'kemarin';
  return tanggalPendek(tanggal);
}

/** "Hari ini", "Kemarin", atau tanggal pendek; dipakai untuk mengelompokkan daftar. */
export function kelompokHari(nilai: Date | string, sekarang: Date = new Date()): string {
  const tanggal = keTanggal(nilai);
  const awal = (t: Date) => new Date(t.getFullYear(), t.getMonth(), t.getDate()).getTime();
  const selisihHari = Math.round((awal(sekarang) - awal(tanggal)) / 86400000);
  if (selisihHari === 0) return 'Hari ini';
  if (selisihHari === 1) return 'Kemarin';
  return tanggalPendek(tanggal);
}

/** "Selamat pagi" / "siang" / "sore" / "malam" menurut jam perangkat. */
export function salamWaktu(sekarang: Date = new Date()): string {
  const jam = sekarang.getHours();
  if (jam >= 4 && jam < 11) return 'Selamat pagi';
  if (jam >= 11 && jam < 15) return 'Selamat siang';
  if (jam >= 15 && jam < 18) return 'Selamat sore';
  return 'Selamat malam';
}

/** Dua huruf inisial untuk avatar: "Budi Santoso" → "BS". */
export function inisialNama(nama: string | null | undefined): string {
  if (!nama) return 'AP';
  const bagian = nama.trim().split(/\s+/);
  if (bagian.length === 1) return bagian[0].substring(0, 2).toUpperCase();
  return (bagian[0][0] + bagian[bagian.length - 1][0]).toUpperCase();
}
