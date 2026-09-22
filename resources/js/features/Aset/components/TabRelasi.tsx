import { FormEvent, useEffect, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { http } from '@/lib/http';
import type { Aset, RelasiAset } from '@/features/Aset/types';
import { ruteAset } from '@/features/Aset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

export function TabRelasi({ aset }: { aset: Aset }) {
  const konfirmasi = useKonfirmasi();
  const [sebagaiInduk, setSebagaiInduk] = useState<RelasiAset[]>([]);
  const [sebagaiAnak, setSebagaiAnak] = useState<RelasiAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({ AsetAnakId: '', JenisRelasi: 'Komponen' as 'Komponen' | 'Terkait' });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.relasi(aset.Id))
      .then((res) => {
        setSebagaiInduk(res.data.sebagaiInduk);
        setSebagaiAnak(res.data.sebagaiAnak);
      })
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteAset.relasi(aset.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        muat();
      },
    });
  };

  const hapus = async (relasi: RelasiAset) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus relasi ini?',
        deskripsi: 'Hubungan antar aset dilepas; kedua aset tetap tersimpan.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteAset.relasiDetail(relasi.Id), { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-4">
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && (
        <>
          <div>
            <h4 className="mb-2 text-sm font-medium text-foreground">
              Komponen / Aset Terkait (sebagai induk)
            </h4>
            {sebagaiInduk.length === 0 && <p className="text-sm text-muted-foreground">Tidak ada.</p>}
            <div className="space-y-2">
              {sebagaiInduk.map((r) => (
                <div
                  key={r.Id}
                  className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
                >
                  <div>
                    <span className="font-medium text-foreground">{r.NamaAsetAnak}</span>{' '}
                    <Badge variant="secondary">{r.JenisRelasi}</Badge>
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => hapus(r)}>
                    Hapus
                  </Button>
                </div>
              ))}
            </div>
          </div>
          <div>
            <h4 className="mb-2 text-sm font-medium text-foreground">
              Bagian dari (sebagai komponen aset lain)
            </h4>
            {sebagaiAnak.length === 0 && <p className="text-sm text-muted-foreground">Tidak ada.</p>}
            <div className="space-y-2">
              {sebagaiAnak.map((r) => (
                <div key={r.Id} className="rounded-md border border-border px-3 py-2 text-sm">
                  <span className="font-medium text-foreground">{r.NamaAsetInduk}</span>{' '}
                  <Badge variant="secondary">{r.JenisRelasi}</Badge>
                </div>
              ))}
            </div>
          </div>
        </>
      )}
      <form onSubmit={submit} className="flex flex-wrap items-end gap-2 border-t border-border pt-4">
        <div className="space-y-1">
          <Label className="text-xs">ID Aset Terkait</Label>
          <Input
            value={form.data.AsetAnakId}
            onChange={(e) => form.setData('AsetAnakId', e.target.value)}
            placeholder="Id aset lain"
            className="w-56 font-mono text-xs"
          />
        </div>
        <div className="space-y-1">
          <Label className="text-xs">Jenis</Label>
          <Select
            value={form.data.JenisRelasi}
            onValueChange={(v) => form.setData('JenisRelasi', v as 'Komponen' | 'Terkait')}
          >
            <SelectTrigger className="w-40">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Komponen">Komponen</SelectItem>
              <SelectItem value="Terkait">Terkait</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <Button type="submit" disabled={form.processing}>
          Tambah Relasi
        </Button>
      </form>
    </div>
  );
}
