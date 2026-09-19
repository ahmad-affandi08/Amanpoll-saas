import { FormEvent, useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { apiKolaborasi } from '@/features/Kolaborasi/api';
import type { LampiranEntitas } from '@/features/Kolaborasi/types';

interface Props {
  jenisEntitas: string;
  entitasId: string;
}

function formatUkuran(byte: number | null): string {
  if (byte === null) return '';
  if (byte < 1024) return `${byte} B`;
  if (byte < 1024 * 1024) return `${(byte / 1024).toFixed(1)} KB`;
  return `${(byte / (1024 * 1024)).toFixed(1)} MB`;
}

export function LampiranTab({ jenisEntitas, entitasId }: Props) {
  const [lampiran, setLampiran] = useState<LampiranEntitas[]>([]);
  const [memuat, setMemuat] = useState(true);
  const [mengunggah, setMengunggah] = useState(false);
  const [file, setFile] = useState<File | null>(null);

  const muat = () => {
    setMemuat(true);
    apiKolaborasi
      .get('/kolaborasi/lampiran', { params: { jenisEntitas, entitasId } })
      .then((res) => setLampiran(res.data.data ?? res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [jenisEntitas, entitasId]);

  const unggah = (e: FormEvent) => {
    e.preventDefault();
    if (!file) return;
    setMengunggah(true);
    router.post('/kolaborasi/berkas', { Berkas: file, JenisEntitas: jenisEntitas, EntitasId: entitasId }, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => { setFile(null); muat(); },
      onFinish: () => setMengunggah(false),
    });
  };

  const hapus = (item: LampiranEntitas) => {
    if (!confirm(`Hapus berkas "${item.Berkas?.NamaAsli}"?`)) return;
    router.delete(`/kolaborasi/berkas/${item.BerkasId}`, { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-3">
      <form onSubmit={unggah} className="flex gap-2">
        <Input type="file" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="flex-1" />
        <Button type="submit" disabled={!file || mengunggah}>Unggah</Button>
      </form>

      {memuat && <p className="text-sm text-muted-foreground">Memuat lampiran...</p>}
      {!memuat && lampiran.length === 0 && <p className="text-sm text-muted-foreground">Belum ada berkas terlampir.</p>}

      <div className="space-y-2">
        {lampiran.map((item) => (
          <div key={item.Id} className="flex items-center justify-between rounded-md border border-border px-3 py-2">
            <div className="min-w-0">
              <a href={`/kolaborasi/berkas/${item.BerkasId}/unduh`} className="truncate text-sm font-medium text-foreground hover:underline">
                {item.Berkas?.NamaAsli ?? '(berkas tidak dikenal)'}
              </a>
              <div className="text-xs text-muted-foreground">{formatUkuran(item.Berkas?.UkuranByte ?? null)}</div>
            </div>
            <Button variant="ghost" size="sm" onClick={() => hapus(item)}>Hapus</Button>
          </div>
        ))}
      </div>
    </div>
  );
}
