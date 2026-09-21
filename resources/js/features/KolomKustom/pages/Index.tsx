import { FormEvent, useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { http } from '@/lib/http';
import type { DefinisiKolomKustom, TipeDataKolomKustom } from '@/features/Kolaborasi/types';
import { ruteKolomKustom } from '@/features/KolomKustom/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';

interface Props {
  jenisEntitasTersedia: string[];
}

const TIPE_DATA: TipeDataKolomKustom[] = ['Teks', 'Angka', 'Tanggal', 'Boolean', 'Pilihan', 'PilihanGanda'];

function DialogFormDefinisi({
  jenisEntitas,
  definisi,
  onSelesai,
}: {
  jenisEntitas: string;
  definisi: DefinisiKolomKustom | null;
  onSelesai: () => void;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    JenisEntitas: jenisEntitas,
    Kode: definisi?.Kode ?? '',
    Label: definisi?.Label ?? '',
    TipeData: definisi?.TipeData ?? ('Teks' as TipeDataKolomKustom),
    Wajib: definisi?.Wajib ?? false,
    Pilihan: (definisi?.Pilihan ?? []).join(', '),
    Urutan: definisi?.Urutan ?? 0,
  });

  const perluPilihan = form.data.TipeData === 'Pilihan' || form.data.TipeData === 'PilihanGanda';

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      Pilihan: perluPilihan
        ? form.data.Pilihan.split(',')
            .map((s) => s.trim())
            .filter(Boolean)
        : null,
    };
    const opsi = {
      onSuccess: () => {
        setBuka(false);
        onSelesai();
      },
    };
    if (definisi) {
      router.put(ruteKolomKustom.detail(definisi.Id), payload, opsi);
    } else {
      router.post(ruteKolomKustom.index, payload, opsi);
    }
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={definisi ? 'outline' : 'default'} size={definisi ? 'sm' : 'default'}>
          {definisi ? 'Ubah' : 'Tambah Kolom'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{definisi ? 'Ubah Kolom Kustom' : 'Tambah Kolom Kustom'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kode</Label>
              <Input
                value={form.data.Kode}
                onChange={(e) => form.setData('Kode', e.target.value)}
                className="font-mono"
                disabled={!!definisi}
              />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-2">
              <Label>Label</Label>
              <Input value={form.data.Label} onChange={(e) => form.setData('Label', e.target.value)} />
              {form.errors.Label && <p className="text-sm text-destructive">{form.errors.Label}</p>}
            </div>
          </div>
          <div className="space-y-2">
            <Label>Tipe Data</Label>
            <Select
              value={form.data.TipeData}
              onValueChange={(v) => form.setData('TipeData', v as TipeDataKolomKustom)}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {TIPE_DATA.map((t) => (
                  <SelectItem key={t} value={t}>
                    {t}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          {perluPilihan && (
            <div className="space-y-2">
              <Label>Opsi (pisahkan dengan koma)</Label>
              <Input
                value={form.data.Pilihan}
                onChange={(e) => form.setData('Pilihan', e.target.value)}
                placeholder="Baik, Rusak, Perlu Servis"
              />
              {form.errors.Pilihan && <p className="text-sm text-destructive">{form.errors.Pilihan}</p>}
            </div>
          )}
          <label className="flex items-center gap-2 text-sm">
            <Checkbox checked={form.data.Wajib} onCheckedChange={(v) => form.setData('Wajib', Boolean(v))} />
            Wajib diisi
          </label>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function KolomKustomIndex({ jenisEntitasTersedia }: Props) {
  const konfirmasi = useKonfirmasi();
  const [jenisEntitas, setJenisEntitas] = useState(jenisEntitasTersedia[0] ?? '');
  const [definisi, setDefinisi] = useState<DefinisiKolomKustom[]>([]);
  const [memuat, setMemuat] = useState(true);

  const muat = () => {
    if (!jenisEntitas) return;
    setMemuat(true);
    http
      .get(ruteKolomKustom.index, { params: { jenisEntitas } })
      .then((res) => setDefinisi(res.data.data ?? res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [jenisEntitas]);

  const hapus = async (item: DefinisiKolomKustom) => {
    if (
      !(await konfirmasi({
        judul: `Hapus kolom kustom "${item.Label}"?`,
        deskripsi: 'Seluruh nilai yang sudah diisi pada kolom ini ikut terhapus.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteKolomKustom.detail(item.Id), { preserveScroll: true, onSuccess: muat });
  };

  return (
    <AppLayout>
      <Head title="Kolom Kustom" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Kolom Kustom</h1>
          <p className="text-sm text-muted-foreground">
            Tambahkan field tambahan khusus organisasi Anda untuk setiap jenis data.
          </p>
        </div>
        {jenisEntitas && <DialogFormDefinisi jenisEntitas={jenisEntitas} definisi={null} onSelesai={muat} />}
      </div>

      <div className="mb-4 w-64 space-y-2">
        <Label>Jenis Entitas</Label>
        <Select value={jenisEntitas} onValueChange={setJenisEntitas}>
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {jenisEntitasTersedia.map((j) => (
              <SelectItem key={j} value={j}>
                {j}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Label</TableHead>
              <TableHead>Kode</TableHead>
              <TableHead>Tipe Data</TableHead>
              <TableHead>Wajib</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {!memuat && definisi.length === 0 && (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground">
                  Belum ada kolom kustom.
                </TableCell>
              </TableRow>
            )}
            {definisi.map((item) => (
              <TableRow key={item.Id}>
                <TableCell className="font-medium text-foreground">{item.Label}</TableCell>
                <TableCell className="font-mono text-xs text-muted-foreground">{item.Kode}</TableCell>
                <TableCell>
                  <Badge variant="outline">{item.TipeData}</Badge>
                </TableCell>
                <TableCell>{item.Wajib ? 'Ya' : '-'}</TableCell>
                <TableCell className="flex justify-end gap-2">
                  <DialogFormDefinisi jenisEntitas={jenisEntitas} definisi={item} onSelesai={muat} />
                  <Button variant="ghost" size="sm" onClick={() => hapus(item)}>
                    Hapus
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </AppLayout>
  );
}
