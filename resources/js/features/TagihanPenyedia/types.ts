export type StatusTagihanPenyedia = 'BelumDibayar' | 'DibayarSebagian' | 'Dibayar';

export interface PembayaranPenyedia {
  Id: string;
  TagihanPenyediaId: string;
  NomorPembayaran: string;
  TanggalBayar: string;
  Jumlah: string;
  Metode: string | null;
  Referensi: string | null;
  NamaPembuat?: string | null;
  DibuatPada: string;
}

export interface TagihanPenyedia {
  Id: string;
  PenyediaId: string;
  NamaPenyedia?: string | null;
  PesananPembelianId: string | null;
  NomorPesananPembelian?: string | null;
  NomorTagihan: string;
  TanggalTagihan: string;
  JatuhTempo: string | null;
  Subtotal: string;
  Pajak: string;
  Total: string;
  Sisa: string;
  Status: StatusTagihanPenyedia;
  JumlahPembayaran?: number;
  Pembayaran?: PembayaranPenyedia[];
  DibuatPada: string;
}
