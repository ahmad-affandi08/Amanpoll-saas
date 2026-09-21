import { z } from 'zod';

export const schemaRencanaPengadaan = z.object({
  Nama: z.string().trim().min(1).max(200),
  Tahun: z.coerce.number().int().min(2000).max(2100),
  PosAnggaranId: z.string().length(26).nullable(),
  UsulanAsetIds: z.array(z.string().length(26)),
});
