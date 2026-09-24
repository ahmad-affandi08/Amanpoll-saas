import type { UrgensiLaporan } from '@/features/Lapangan/types';

interface PadananMasalah {
  pola: RegExp;
  masalah: string[];
}

/**
 * Pilihan cepat "Pilih yang paling mirip" (papan pelapor layar 06), dicocokkan dari
 * nama kategori keluhan milik tenant atau jenis aset. Yang tidak cocok memakai
 * pilihan umum. "Lainnya" selalu ada di ujung.
 */
const PADANAN: PadananMasalah[] = [
  {
    pola: /printer|pencetak/i,
    masalah: ['Kertas macet', 'Tidak bisa mencetak', 'Hasil bergaris', 'Bunyi aneh'],
  },
  {
    pola: /\bit\b|komputer|laptop|jaringan|internet|wifi/i,
    masalah: ['Tidak menyala', 'Lambat sekali', 'Tidak ada internet', 'Layar bermasalah'],
  },
  {
    pola: /\bac\b|pendingin|udara|hvac/i,
    masalah: ['Tidak dingin', 'Bocor air', 'Bunyi aneh', 'Bau tidak sedap'],
  },
  {
    pola: /lift|elevator|eskalator/i,
    masalah: ['Macet', 'Pintu tidak menutup', 'Bunyi aneh', 'Tombol tidak berfungsi'],
  },
  {
    pola: /pompa|\bair\b|pipa|plumbing|sanitasi|toilet/i,
    masalah: ['Bocor', 'Mampet', 'Air tidak keluar', 'Bau tidak sedap'],
  },
  { pola: /lampu|penerangan/i, masalah: ['Mati', 'Berkedip', 'Redup'] },
  {
    pola: /listrik|elektrikal|panel|stop ?kontak/i,
    masalah: ['Mati total', 'Berkedip', 'Bau hangus', 'Stop kontak rusak'],
  },
  {
    pola: /bangunan|gedung|sipil|atap|dinding|lantai|pintu|jendela/i,
    masalah: ['Retak', 'Bocor dari atas', 'Pintu atau kunci rusak', 'Lantai licin'],
  },
  {
    pola: /cctv|kamera|keamanan|security|akses/i,
    masalah: ['Tidak merekam', 'Kamera mati', 'Akses pintu rusak'],
  },
  { pola: /apar|pemadam|hydrant|kebakaran/i, masalah: ['Tekanan kurang', 'Segel rusak', 'Kedaluwarsa'] },
];

const UMUM = ['Tidak berfungsi', 'Rusak fisik', 'Bunyi aneh', 'Bau tidak sedap'];

export const MASALAH_LAINNYA = 'Lainnya';

/** Pilihan masalah untuk kategori (atau jenis aset bila kategorinya umum). */
export function pilihanMasalah(...petunjuk: (string | null | undefined)[]): string[] {
  for (const teks of petunjuk) {
    if (!teks) continue;
    const cocok = PADANAN.find((satu) => satu.pola.test(teks));
    if (cocok) return [...cocok.masalah, MASALAH_LAINNYA];
  }

  return [...UMUM, MASALAH_LAINNYA];
}

/**
 * Judul keluhan dari pilihan pelapor: "Printer Lt. 12 kertas macet", atau "Retak di Menara A · Lt. 12"
 * bila hanya lokasinya yang dilaporkan. Tanpa pilihan
 * cepat, kalimat pertama ceritanya; tanpa keduanya, "Kerusakan di <lokasi>".
 */
export function susunJudul(
  subjek: string,
  masalah: string | null,
  cerita: string,
  lokasiSaja = false,
): string {
  const judul =
    masalah && masalah !== MASALAH_LAINNYA
      ? lokasiSaja
        ? `${masalah} di ${subjek}`
        : `${subjek} ${masalah.charAt(0).toLowerCase()}${masalah.slice(1)}`
      : cerita.trim()
        ? cerita.trim().split(/[.!?\n]/)[0]
        : `Kerusakan di ${subjek}`;

  return judul.length > 200 ? `${judul.slice(0, 199)}…` : judul;
}

/** Deskripsi keluhan: ceritanya, didahului masalah yang dipilih. */
export function susunDeskripsi(masalah: string | null, cerita: string): string {
  const bagian = [masalah && masalah !== MASALAH_LAINNYA ? `${masalah}.` : null, cerita.trim() || null];
  return bagian.filter(Boolean).join(' ') || 'Dilaporkan lewat Mode Lapangan.';
}

export interface PilihanUrgensi {
  kunci: UrgensiLaporan;
  label: string;
  /** Kelas warna titik (token lapangan). */
  titik: string;
}

/** Tingkat urgensi dalam bahasa awam; server memetakannya ke prioritas keluhan. */
export const PILIHAN_URGENSI: PilihanUrgensi[] = [
  { kunci: 'TidakBuruBuru', label: 'Tidak buru-buru', titik: 'bg-lapangan-hijau-700' },
  { kunci: 'MenggangguKerja', label: 'Mengganggu kerja', titik: 'bg-lapangan-kuning-700' },
  { kunci: 'KerjaTerhenti', label: 'Kerja terhenti', titik: 'bg-lapangan-oranye-600' },
  { kunci: 'Berbahaya', label: 'Berbahaya', titik: 'bg-lapangan-merah-700' },
];
