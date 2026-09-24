import type { FungsiAntrikan } from '@/hooks/use-sinkronisasi-offline';
import type { MutasiOffline, OperasiOffline } from '@/features/Sinkronisasi/types';
import type { TiketTeknisi } from '@/features/Lapangan/types';

/** Mutasi yang sudah diantrikan perangkat tetapi belum diterapkan server. */
const STATUS_TERTUNDA: MutasiOffline['Status'][] = ['Menunggu', 'Diproses'];

/** Status tempat teknisi boleh (kembali) mulai mengerjakan. */
export const STATUS_BISA_MULAI = ['Diterima', 'Dijeda', 'MenungguSukuCadang', 'MenungguPenyedia'];

/** Status tempat alur pengerjaan berjalan. */
export const STATUS_SEDANG_DIKERJAKAN = ['Dikerjakan'];

export interface KeadaanLokalTiket {
  Status: string;
  /** Versi yang akan dimiliki tiket di server setelah semua mutasi tertunda diterapkan. */
  Versi: number;
  PerluRespons: boolean;
  /** Teknisi meminta tiket ini dialihkan (penugasan ditolak) dan belum terkirim/terproses. */
  Dialihkan: boolean;
  /** Jumlah mutasi tiket ini yang masih menunggu dikirim. */
  Tertunda: number;
  Konflik: MutasiOffline[];
  Gagal: MutasiOffline[];
}

type TiketDasar = Pick<TiketTeknisi, 'Id' | 'Status' | 'Versi' | 'PerluRespons'>;

/**
 * Status tiket menurut perangkat: status server ditambah mutasi yang masih di antrean
 * (FASE 20). Dipakai supaya "Terima & Mulai" atau "Selesai" yang dibuat tanpa sinyal
 * langsung tampil, dan supaya mutasi berikutnya membawa versi yang benar.
 */
export function keadaanLokal(tiket: TiketDasar, antrian: MutasiOffline[]): KeadaanLokalTiket {
  const milik = antrian
    .filter((mutasi) => mutasi.EntitasId === tiket.Id)
    .sort((a, b) => a.DibuatPada.localeCompare(b.DibuatPada));

  const keadaan: KeadaanLokalTiket = {
    Status: tiket.Status,
    Versi: tiket.Versi,
    PerluRespons: tiket.PerluRespons,
    Dialihkan: false,
    Tertunda: 0,
    Konflik: milik.filter((mutasi) => mutasi.Status === 'Konflik'),
    Gagal: milik.filter((mutasi) => mutasi.Status === 'Gagal'),
  };

  for (const mutasi of milik) {
    if (!STATUS_TERTUNDA.includes(mutasi.Status)) continue;
    keadaan.Tertunda++;

    if (mutasi.Operasi === 'PerintahKerja.ResponsPenugasan') {
      keadaan.PerluRespons = false;
      if (mutasi.MuatanData.Respons === 'Tolak') {
        keadaan.Dialihkan = true;
      } else if (keadaan.Status === 'Ditugaskan') {
        keadaan.Status = 'Diterima';
        keadaan.Versi = (mutasi.VersiKlien ?? keadaan.Versi) + 1;
      }
    }

    if (mutasi.Operasi === 'PerintahKerja.UbahStatus' && typeof mutasi.MuatanData.Status === 'string') {
      keadaan.Status = mutasi.MuatanData.Status;
      keadaan.Versi = (mutasi.VersiKlien ?? keadaan.Versi) + 1;
    }
  }

  return keadaan;
}

/**
 * Mutasi "selesai, menunggu verifikasi" tiket ini yang ditolak server (mis. tanda tangan penerima
 * wajib tetapi lampirannya belum ada). Layar kerja membukanya kembali di Ringkasan supaya bisa
 * diperbaiki dan dikirim ulang, alih-alih diam-diam menampilkan "Pekerjaan selesai".
 */
export function penyelesaianDitolak(tiketId: string, antrian: MutasiOffline[]): MutasiOffline[] {
  return antrian.filter(
    (mutasi) =>
      mutasi.EntitasId === tiketId &&
      mutasi.Status === 'Gagal' &&
      mutasi.Operasi === 'PerintahKerja.UbahStatus' &&
      mutasi.MuatanData.Status === 'MenungguVerifikasi',
  );
}

/** Terapkan keadaan lokal ke objek tiket untuk ditampilkan. */
export function tiketLokal<T extends TiketDasar>(tiket: T, antrian: MutasiOffline[]): T {
  const keadaan = keadaanLokal(tiket, antrian);
  return { ...tiket, Status: keadaan.Status, Versi: keadaan.Versi, PerluRespons: keadaan.PerluRespons };
}

export interface PermintaanMutasiTeknisi {
  Operasi: OperasiOffline;
  EntitasId: string;
  VersiKlien: number | null;
  MuatanData: Record<string, unknown>;
  Label: string;
}

/**
 * Mutasi "Terima & Mulai" (layar 06) atau "Lanjutkan" sesudah jeda, dengan versi
 * berantai: terima menaikkan versi bila tiket masih Ditugaskan, lalu mulai memakai
 * versi hasilnya. Semua dihitung sebelum ada yang dikirim.
 */
export function rencanaMulai(
  tiket: Pick<TiketTeknisi, 'Id' | 'Nomor'>,
  keadaan: KeadaanLokalTiket,
): PermintaanMutasiTeknisi[] {
  const rencana: PermintaanMutasiTeknisi[] = [];
  let status = keadaan.Status;
  let versi = keadaan.Versi;

  if (keadaan.PerluRespons) {
    rencana.push({
      Operasi: 'PerintahKerja.ResponsPenugasan',
      EntitasId: tiket.Id,
      VersiKlien: versi,
      MuatanData: { Respons: 'Terima', Catatan: null },
      Label: `${tiket.Nomor}: tiket diterima`,
    });
    if (status === 'Ditugaskan') {
      status = 'Diterima';
      versi++;
    }
  }

  if (STATUS_BISA_MULAI.includes(status)) {
    rencana.push({
      Operasi: 'PerintahKerja.UbahStatus',
      EntitasId: tiket.Id,
      VersiKlien: versi,
      MuatanData: { Status: 'Dikerjakan', Catatan: 'Mulai dikerjakan dari Mode Lapangan.', Ringkasan: null },
      Label: `${tiket.Nomor}: mulai dikerjakan`,
    });
  }

  return rencana;
}

/** Perubahan status tunggal dengan versi dari keadaan lokal. */
export function rencanaUbahStatus(
  tiket: Pick<TiketTeknisi, 'Id' | 'Nomor'>,
  keadaan: KeadaanLokalTiket,
  status: string,
  label: string,
  muatan: { Catatan?: string | null; Ringkasan?: string | null } = {},
): PermintaanMutasiTeknisi {
  return {
    Operasi: 'PerintahKerja.UbahStatus',
    EntitasId: tiket.Id,
    VersiKlien: keadaan.Versi,
    MuatanData: { Status: status, Catatan: muatan.Catatan ?? null, Ringkasan: muatan.Ringkasan ?? null },
    Label: `${tiket.Nomor}: ${label}`,
  };
}

/** Sesi waktu kerja yang sudah selesai (offline mencatat sesi utuh, bukan sesi terbuka). */
export function rencanaWaktuKerja(
  tiket: Pick<TiketTeknisi, 'Id' | 'Nomor'>,
  mulaiPada: string,
  selesaiPada: Date,
): PermintaanMutasiTeknisi | null {
  // Server menolak sesi yang berakhir "di masa depan"; beri sela kecil untuk jam perangkat.
  const akhir = new Date(selesaiPada.getTime() - 2000);
  if (akhir.getTime() - new Date(mulaiPada).getTime() < 1000) return null;

  return {
    Operasi: 'PerintahKerja.CatatWaktuKerja',
    EntitasId: tiket.Id,
    VersiKlien: null,
    MuatanData: {
      MulaiPada: new Date(mulaiPada).toISOString(),
      SelesaiPada: akhir.toISOString(),
      Catatan: null,
    },
    Label: `${tiket.Nomor}: sesi waktu kerja`,
  };
}

/** Mengantrikan mutasi satu per satu sesuai urutannya (antrean FASE 20 memproses berurutan). */
export async function antrikanBerurutan(
  antrikan: FungsiAntrikan,
  rencana: PermintaanMutasiTeknisi[],
): Promise<void> {
  for (const mutasi of rencana) {
    await antrikan(mutasi);
  }
}

/** Detik sesi waktu kerja tiket ini yang sudah diantrikan tetapi belum diterima server. */
export function detikSesiTertunda(tiketId: string, antrian: MutasiOffline[]): number {
  return antrian
    .filter(
      (mutasi) =>
        mutasi.EntitasId === tiketId &&
        mutasi.Operasi === 'PerintahKerja.CatatWaktuKerja' &&
        STATUS_TERTUNDA.includes(mutasi.Status),
    )
    .reduce((jumlah, mutasi) => {
      const mulai = new Date(String(mutasi.MuatanData.MulaiPada)).getTime();
      const selesai = new Date(String(mutasi.MuatanData.SelesaiPada)).getTime();
      return Number.isNaN(mulai) || Number.isNaN(selesai)
        ? jumlah
        : jumlah + Math.max(0, (selesai - mulai) / 1000);
    }, 0);
}
