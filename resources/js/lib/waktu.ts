/**
 * Jembatan antara jam dinding pengguna dan momen UTC yang disimpan server.
 *
 * Input itu berbicara jam dinding tanpa zona ("2026-09-22T08:00"), sedangkan
 * server menyimpan UTC. Dikirim apa adanya, jam 08:00 WIB terbaca 08:00 UTC dan
 * jadwalnya bergeser tujuh jam. Sebaliknya nilai dari server adalah ISO berzona,
 * dan memotongnya (`slice(0, 16)`) menampilkan jam UTC di dalam input.
 *
 * Keduanya memakai zona perangkat, zona yang sama dengan `toLocaleString()` di
 * seluruh tampilan: jam yang diketik sama dengan jam yang kemudian terlihat.
 */

function duaDigit(angka: number): string {
  return String(angka).padStart(2, '0');
}

/** Momen ISO dari server menjadi nilai input datetime-local di zona perangkat. */
export function keMasukanWaktu(iso: string | null | undefined): string {
  if (!iso) {
    return '';
  }

  const waktu = new Date(iso);
  if (Number.isNaN(waktu.getTime())) {
    return '';
  }

  return (
    `${waktu.getFullYear()}-${duaDigit(waktu.getMonth() + 1)}-${duaDigit(waktu.getDate())}` +
    `T${duaDigit(waktu.getHours())}:${duaDigit(waktu.getMinutes())}`
  );
}

/** Nilai input datetime-local (jam dinding perangkat) menjadi momen ISO UTC; kosong menjadi null. */
export function dariMasukanWaktu(nilai: string | null | undefined): string | null {
  if (!nilai) {
    return null;
  }

  // String tanpa zona dibaca sebagai waktu lokal oleh Date (spesifikasi ECMAScript).
  const waktu = new Date(nilai);

  return Number.isNaN(waktu.getTime()) ? null : waktu.toISOString();
}

/**
 * Tanggal kalender (YYYY-MM-DD) tempat sebuah momen jatuh di zona perangkat.
 *
 * Bukan `toISOString().slice(0, 10)`: itu tanggal UTC, yang sebelum pukul
 * 07:00 WIB masih tanggal kemarin. Teks yang sudah berupa tanggal kalender
 * dikembalikan apa adanya, karena ia tidak punya zona untuk digeser.
 */
export function tanggalLokal(nilai: Date | string): string {
  if (typeof nilai === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(nilai)) {
    return nilai;
  }

  const waktu = typeof nilai === 'string' ? new Date(nilai) : nilai;

  return `${waktu.getFullYear()}-${duaDigit(waktu.getMonth() + 1)}-${duaDigit(waktu.getDate())}`;
}

/** Tanggal hari ini di zona perangkat, untuk nilai awal pemilih tanggal. */
export function tanggalHariIni(): string {
  return tanggalLokal(new Date());
}

/** Tanggal kalender YYYY-MM-DD digeser sejumlah hari; murni aritmetika kalender. */
export function tambahHari(tanggal: string, hari: number): string {
  const waktu = new Date(`${tanggal}T00:00:00Z`);
  waktu.setUTCDate(waktu.getUTCDate() + hari);

  return waktu.toISOString().slice(0, 10);
}
