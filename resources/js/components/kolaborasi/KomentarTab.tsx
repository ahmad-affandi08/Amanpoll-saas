import { FormEvent, useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { apiKolaborasi } from '@/features/Kolaborasi/api';
import type { KomentarEntitas } from '@/features/Kolaborasi/types';
import type { PageProps } from '@/types/global';

interface Props {
  jenisEntitas: string;
  entitasId: string;
}

export function KomentarTab({ jenisEntitas, entitasId }: Props) {
  const { auth } = usePage<PageProps>().props;
  const [komentar, setKomentar] = useState<KomentarEntitas[]>([]);
  const [memuat, setMemuat] = useState(true);
  const [isiBaru, setIsiBaru] = useState('');
  const [mengedit, setMengedit] = useState<{ id: string; isi: string } | null>(null);

  const muat = () => {
    setMemuat(true);
    apiKolaborasi
      .get('/kolaborasi/komentar', { params: { jenisEntitas, entitasId } })
      .then((res) => setKomentar(res.data.data ?? res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [jenisEntitas, entitasId]);

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    if (!isiBaru.trim()) return;
    router.post('/kolaborasi/komentar', { JenisEntitas: jenisEntitas, EntitasId: entitasId, Isi: isiBaru }, {
      preserveScroll: true,
      onSuccess: () => { setIsiBaru(''); muat(); },
    });
  };

  const simpanEdit = () => {
    if (!mengedit) return;
    router.put(`/kolaborasi/komentar/${mengedit.id}`, { Isi: mengedit.isi }, {
      preserveScroll: true,
      onSuccess: () => { setMengedit(null); muat(); },
    });
  };

  const hapus = (item: KomentarEntitas) => {
    if (!confirm('Hapus komentar ini?')) return;
    router.delete(`/kolaborasi/komentar/${item.Id}`, { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-3">
      <form onSubmit={kirim} className="space-y-2">
        <Textarea value={isiBaru} onChange={(e) => setIsiBaru(e.target.value)} placeholder="Tulis komentar..." rows={2} />
        <div className="flex justify-end"><Button type="submit" size="sm" disabled={!isiBaru.trim()}>Kirim</Button></div>
      </form>

      {memuat && <p className="text-sm text-muted-foreground">Memuat komentar...</p>}
      {!memuat && komentar.length === 0 && <p className="text-sm text-muted-foreground">Belum ada komentar.</p>}

      <div className="space-y-3">
        {komentar.map((item) => {
          const penulis = item.DibuatOleh === auth.pengguna?.Id;
          return (
            <div key={item.Id} className="rounded-md border border-border p-3">
              <div className="mb-1 flex items-center justify-between">
                <span className="text-sm font-medium text-foreground">{item.NamaPembuat ?? 'Pengguna'}</span>
                <span className="text-xs text-muted-foreground">{new Date(item.DibuatPada).toLocaleString('id-ID')}</span>
              </div>
              {mengedit?.id === item.Id ? (
                <div className="space-y-2">
                  <Textarea value={mengedit.isi} onChange={(e) => setMengedit({ id: item.Id, isi: e.target.value })} rows={2} />
                  <div className="flex justify-end gap-2">
                    <Button size="sm" variant="outline" onClick={() => setMengedit(null)}>Batal</Button>
                    <Button size="sm" onClick={simpanEdit}>Simpan</Button>
                  </div>
                </div>
              ) : (
                <>
                  <p className="whitespace-pre-wrap text-sm text-foreground">{item.Isi}</p>
                  <div className="mt-1 flex justify-end gap-3">
                    {penulis && (
                      <button type="button" className="text-xs text-muted-foreground hover:underline" onClick={() => setMengedit({ id: item.Id, isi: item.Isi })}>
                        Ubah
                      </button>
                    )}
                    <button type="button" className="text-xs text-muted-foreground hover:underline" onClick={() => hapus(item)}>
                      Hapus
                    </button>
                  </div>
                </>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
