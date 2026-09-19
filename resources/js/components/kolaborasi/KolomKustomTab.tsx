import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { apiKolaborasi } from '@/features/Kolaborasi/api';
import type { DefinisiKolomKustom, NilaiKolomKustom } from '@/features/Kolaborasi/types';

interface Props {
  jenisEntitas: string;
  entitasId: string;
}

type NilaiKolom = string | number | boolean | string[] | null;

function KolomInput({ definisi, nilai, onChange }: { definisi: DefinisiKolomKustom; nilai: NilaiKolom; onChange: (v: NilaiKolom) => void }) {
  switch (definisi.TipeData) {
    case 'Angka':
      return <Input type="number" value={nilai === null || nilai === undefined ? '' : String(nilai)} onChange={(e) => onChange(e.target.value === '' ? null : Number(e.target.value))} />;
    case 'Tanggal':
      return <Input type="date" value={typeof nilai === 'string' ? nilai : ''} onChange={(e) => onChange(e.target.value || null)} />;
    case 'Boolean':
      return (
        <label className="flex items-center gap-2">
          <Checkbox checked={Boolean(nilai)} onCheckedChange={(v) => onChange(Boolean(v))} />
          <span className="text-sm text-muted-foreground">Ya</span>
        </label>
      );
    case 'Pilihan':
      return (
        <Select value={typeof nilai === 'string' ? nilai : ''} onValueChange={(v) => onChange(v)}>
          <SelectTrigger><SelectValue placeholder="Pilih..." /></SelectTrigger>
          <SelectContent>
            {(definisi.Pilihan ?? []).map((opsi) => <SelectItem key={opsi} value={opsi}>{opsi}</SelectItem>)}
          </SelectContent>
        </Select>
      );
    case 'PilihanGanda': {
      const nilaiArray = Array.isArray(nilai) ? nilai : [];
      const toggle = (opsi: string, dicentang: boolean) => {
        onChange(dicentang ? [...nilaiArray, opsi] : nilaiArray.filter((o) => o !== opsi));
      };
      return (
        <div className="space-y-1.5">
          {(definisi.Pilihan ?? []).map((opsi) => (
            <label key={opsi} className="flex items-center gap-2 text-sm">
              <Checkbox checked={nilaiArray.includes(opsi)} onCheckedChange={(v) => toggle(opsi, Boolean(v))} />
              {opsi}
            </label>
          ))}
        </div>
      );
    }
    default:
      return <Input value={typeof nilai === 'string' ? nilai : ''} onChange={(e) => onChange(e.target.value || null)} />;
  }
}

export function KolomKustomTab({ jenisEntitas, entitasId }: Props) {
  const [definisi, setDefinisi] = useState<DefinisiKolomKustom[]>([]);
  const [nilai, setNilai] = useState<Record<string, NilaiKolom>>({});
  const [memuat, setMemuat] = useState(true);
  const [menyimpan, setMenyimpan] = useState<string | null>(null);

  useEffect(() => {
    setMemuat(true);
    Promise.all([
      apiKolaborasi.get('/kolaborasi/definisi-kolom-kustom', { params: { jenisEntitas } }),
      apiKolaborasi.get('/kolaborasi/nilai-kolom-kustom', { params: { jenisEntitas, entitasId } }),
    ])
      .then(([resDefinisi, resNilai]) => {
        const daftarDefinisi: DefinisiKolomKustom[] = resDefinisi.data.data ?? resDefinisi.data;
        const daftarNilai: NilaiKolomKustom[] = resNilai.data.data ?? resNilai.data;
        setDefinisi(daftarDefinisi.filter((d) => d.Aktif));
        setNilai(Object.fromEntries(daftarNilai.map((n) => [n.DefinisiKolomKustomId, n.Nilai as NilaiKolom])));
      })
      .finally(() => setMemuat(false));
  }, [jenisEntitas, entitasId]);

  const simpan = (def: DefinisiKolomKustom) => {
    setMenyimpan(def.Id);
    router.post('/kolaborasi/nilai-kolom-kustom', {
      DefinisiKolomKustomId: def.Id,
      JenisEntitas: jenisEntitas,
      EntitasId: entitasId,
      Nilai: nilai[def.Id] ?? null,
    }, {
      preserveScroll: true,
      onFinish: () => setMenyimpan(null),
    });
  };

  if (memuat) return <p className="text-sm text-muted-foreground">Memuat kolom kustom...</p>;
  if (definisi.length === 0) return <p className="text-sm text-muted-foreground">Belum ada kolom kustom untuk {jenisEntitas}.</p>;

  return (
    <div className="space-y-4">
      {definisi.map((def) => (
        <div key={def.Id} className="space-y-1.5">
          <Label>{def.Label}{def.Wajib && <span className="text-destructive"> *</span>}</Label>
          <div className="flex items-start gap-2">
            <div className="flex-1"><KolomInput definisi={def} nilai={nilai[def.Id] ?? null} onChange={(v) => setNilai((s) => ({ ...s, [def.Id]: v }))} /></div>
            <Button type="button" size="sm" variant="outline" onClick={() => simpan(def)} disabled={menyimpan === def.Id}>Simpan</Button>
          </div>
        </div>
      ))}
    </div>
  );
}
