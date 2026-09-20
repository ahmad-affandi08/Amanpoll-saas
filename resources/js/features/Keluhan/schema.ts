import { z } from 'zod';

export const schemaKeluhan = z.object({
  KategoriKeluhanId: z.string().min(1),
  LokasiId: z.string().min(1),
  Judul: z.string().min(1).max(220),
  Deskripsi: z.string().min(1).max(10000),
});
