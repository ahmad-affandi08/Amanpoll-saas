import { Alert, AlertDescription } from '@/components/ui/alert';

/**
 * Pesan server tentang rentang yang dipotong atau tanggal yang diabaikan (FASE 45).
 * Tanpa ini, angka satu tahun terakhir terbaca sebagai angka rentang yang diminta.
 */
export function CatatanRentang({ catatan }: { catatan: string[] }) {
  if (catatan.length === 0) {
    return null;
  }

  return (
    <Alert variant="info" role="status">
      <AlertDescription>
        {catatan.length === 1 ? (
          catatan[0]
        ) : (
          <ul className="list-disc space-y-0.5 pl-4">
            {catatan.map((satu) => (
              <li key={satu}>{satu}</li>
            ))}
          </ul>
        )}
      </AlertDescription>
    </Alert>
  );
}
