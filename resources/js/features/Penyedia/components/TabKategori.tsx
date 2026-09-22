import { router } from '@inertiajs/react';
import { Checkbox } from '@/components/ui/checkbox';
import type { Penyedia, KategoriPenyedia } from '@/features/Penyedia/types';
import { rutePenyedia } from '@/features/Penyedia/api';

export function TabKategori({
  penyedia,
  kategoriPenyedia,
}: {
  penyedia: Penyedia;
  kategoriPenyedia: KategoriPenyedia[];
}) {
  const toggle = (kategori: KategoriPenyedia, dicentang: boolean) => {
    if (dicentang) {
      router.post(
        rutePenyedia.kategori2(penyedia.Id),
        { KategoriPenyediaId: kategori.Id },
        { preserveScroll: true },
      );
    } else {
      router.delete(rutePenyedia.kategoriDetail2(penyedia.Id, kategori.Id), { preserveScroll: true });
    }
  };

  if (kategoriPenyedia.length === 0) {
    return (
      <p className="text-sm text-muted-foreground">
        Belum ada kategori penyedia. Buat lewat "Kelola Kategori".
      </p>
    );
  }

  return (
    <div className="space-y-2">
      {kategoriPenyedia.map((k) => (
        <label key={k.Id} className="flex items-center gap-2 rounded-md border border-border px-3 py-2">
          <Checkbox
            checked={penyedia.KategoriPenyediaId.includes(k.Id)}
            onCheckedChange={(v) => toggle(k, v === true)}
          />
          <span className="text-sm text-foreground">{k.Nama}</span>
          <span className="font-mono text-xs text-muted-foreground">{k.Kode}</span>
        </label>
      ))}
    </div>
  );
}
