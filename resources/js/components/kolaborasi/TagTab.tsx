import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { http } from '@/lib/http';
import type { EntitasTag, Tag } from '@/features/Kolaborasi/types';
import { ruteKolaborasi } from '@/features/Kolaborasi/api';

interface Props {
  jenisEntitas: string;
  entitasId: string;
}

export function TagTab({ jenisEntitas, entitasId }: Props) {
  const [semuaTag, setSemuaTag] = useState<Tag[]>([]);
  const [entitasTag, setEntitasTag] = useState<EntitasTag[]>([]);
  const [tagDipilih, setTagDipilih] = useState('');
  const [memuat, setMemuat] = useState(true);

  const muat = () => {
    setMemuat(true);
    Promise.all([
      http.get(ruteKolaborasi.tag),
      http.get(ruteKolaborasi.entitasTag, { params: { jenisEntitas, entitasId } }),
    ])
      .then(([resTag, resEntitasTag]) => {
        setSemuaTag(resTag.data.data ?? resTag.data);
        setEntitasTag(resEntitasTag.data.data ?? resEntitasTag.data);
      })
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [jenisEntitas, entitasId]);

  const tambahkan = () => {
    if (!tagDipilih) return;
    router.post(
      ruteKolaborasi.entitasTag,
      { TagId: tagDipilih, JenisEntitas: jenisEntitas, EntitasId: entitasId },
      {
        preserveScroll: true,
        onSuccess: () => {
          setTagDipilih('');
          muat();
        },
      },
    );
  };

  const lepaskan = (item: EntitasTag) => {
    router.delete(ruteKolaborasi.entitasTagDetail(item.Id), { preserveScroll: true, onSuccess: muat });
  };

  const tagBelumDipakai = semuaTag.filter((t) => !entitasTag.some((et) => et.TagId === t.Id));

  return (
    <div className="space-y-3">
      <div className="flex gap-2">
        <Select value={tagDipilih} onValueChange={setTagDipilih}>
          <SelectTrigger className="flex-1">
            <SelectValue placeholder="Pilih tag..." />
          </SelectTrigger>
          <SelectContent>
            {tagBelumDipakai.map((t) => (
              <SelectItem key={t.Id} value={t.Id}>
                {t.Nama}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Button type="button" onClick={tambahkan} disabled={!tagDipilih}>
          Tambah
        </Button>
      </div>

      {memuat && <p className="text-sm text-muted-foreground">Memuat tag...</p>}
      {!memuat && entitasTag.length === 0 && <p className="text-sm text-muted-foreground">Belum ada tag.</p>}

      <div className="flex flex-wrap gap-2">
        {entitasTag.map((item) => (
          <Badge key={item.Id} variant="outline" className="gap-1.5 pr-1">
            {item.Tag?.Nama ?? '(tag tidak dikenal)'}
            <button
              type="button"
              onClick={() => lepaskan(item)}
              className="ml-1 rounded-sm hover:bg-accent"
              aria-label="Lepas tag"
            >
              &times;
            </button>
          </Badge>
        ))}
      </div>
    </div>
  );
}
