import { z } from 'zod';

export const schemaAnggaran = z.object({
  Kode: z.string().trim().min(1).max(80),
  Nama: z.string().trim().min(1).max(180),
  Tahun: z.coerce.number().int().min(2000).max(2100),
  MataUang: z.string().length(3),
  Jumlah: z.coerce.number().positive(),
});

export const schemaTransaksiAnggaran = z.object({
  Jenis: z.enum(['Komitmen', 'Realisasi', 'PelepasanKomitmen', 'Penyesuaian']),
  Jumlah: z.coerce.number().refine((nilai) => nilai !== 0),
  Tanggal: z.iso.date(),
  Keterangan: z.string().max(2000).nullable(),
});
