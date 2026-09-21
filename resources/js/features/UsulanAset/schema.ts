import { z } from 'zod';

export const schemaUsulanAset = z.object({
  UnitOrganisasiId: z.string().length(26),
  NamaKebutuhan: z.string().trim().min(1).max(220),
  Jumlah: z.coerce.number().positive(),
  EstimasiHargaSatuan: z.coerce.number().nonnegative().nullable(),
  Alasan: z.string().trim().min(1).max(5000),
  TahunKebutuhan: z.coerce.number().int().min(2000).max(2100).nullable(),
  Prioritas: z.enum(['Rendah', 'Normal', 'Tinggi', 'Kritis']),
});
