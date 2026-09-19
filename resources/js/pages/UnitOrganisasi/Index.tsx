import { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';

interface Props {
  unitOrganisasi: UnitOrganisasi[];
  status: string;
}

const TANPA_INDUK = '__tanpa_induk__';

function urutkanSebagaiPohon(unit: UnitOrganisasi[]): Array<UnitOrganisasi & { kedalaman: number }> {
  const anakDari = new Map<string | null, UnitOrganisasi[]>();
  unit.forEach((u) => {
    const daftar = anakDari.get(u.IndukId) ?? [];
    daftar.push(u);
    anakDari.set(u.IndukId, daftar);
  });

  const hasil: Array<UnitOrganisasi & { kedalaman: number }> = [];
  const telusuri = (indukId: string | null, kedalaman: number) => {
    for (const u of anakDari.get(indukId) ?? []) {
      hasil.push({ ...u, kedalaman });
      telusuri(u.Id, kedalaman + 1);
    }
  };
  telusuri(null, 0);

  return hasil;
}

function DialogFormUnit({ unit, semuaUnit }: { unit: UnitOrganisasi | null; semuaUnit: UnitOrganisasi[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm(unit
    ? { Kode: unit.Kode, Nama: unit.Nama, Jenis: unit.Jenis, Status: unit.Status, IndukId: unit.IndukId ?? TANPA_INDUK }
    : { Kode: '', Nama: '', Jenis: 'Unit', Status: 'Aktif' as const, IndukId: TANPA_INDUK });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form.data, IndukId: form.data.IndukId === TANPA_INDUK ? null : form.data.IndukId };
    const opsi = { onSuccess: () => { setBuka(false); form.reset(); } };
    if (unit) {
      router.put(`/platform/unit-organisasi/${unit.Id}`, payload, opsi);
    } else {
      router.post('/platform/unit-organisasi', payload, opsi);
    }
  };

  const pilihanInduk = semuaUnit.filter((u) => u.Id !== unit?.Id);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={unit ? 'outline' : 'default'} size={unit ? 'sm' : 'default'}>{unit ? 'Ubah' : 'Tambah Unit'}</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>{unit ? 'Ubah Unit Organisasi' : 'Tambah Unit Organisasi'}</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Kode</Label>
              <Input value={form.data.Kode} onChange={(e) => form.setData('Kode', e.target.value)} className="font-mono" />
              {form.errors.Kode && <p className="text-sm text-destructive">{form.errors.Kode}</p>}
            </div>
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Jenis</Label>
              <Input value={form.data.Jenis} onChange={(e) => form.setData('Jenis', e.target.value)} placeholder="Divisi, Departemen, dst." />
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v as 'Aktif' | 'Nonaktif')}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="Aktif">Aktif</SelectItem>
                  <SelectItem value="Nonaktif">Nonaktif</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-2">
            <Label>Induk</Label>
            <Select value={form.data.IndukId} onValueChange={(v) => form.setData('IndukId', v)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA_INDUK}>Tanpa induk</SelectItem>
                {pilihanInduk.map((u) => <SelectItem key={u.Id} value={u.Id}>{u.Nama}</SelectItem>)}
              </SelectContent>
            </Select>
            {form.errors.IndukId && <p className="text-sm text-destructive">{form.errors.IndukId}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Simpan</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function UnitOrganisasiIndex({ unitOrganisasi, status }: Props) {
  const pohon = useMemo(() => urutkanSebagaiPohon(unitOrganisasi), [unitOrganisasi]);

  const filterStatus = (nilai: string) => {
    router.get('/platform/unit-organisasi', nilai === '__semua__' ? {} : { status: nilai }, { preserveState: true });
  };

  const hapus = (unit: UnitOrganisasi) => {
    if (!confirm(`Hapus unit "${unit.Nama}"?`)) return;
    router.delete(`/platform/unit-organisasi/${unit.Id}`, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Unit Organisasi" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Unit Organisasi</h1>
          <p className="text-sm text-muted-foreground">Kelola struktur divisi dan hierarki organisasi.</p>
        </div>
        <DialogFormUnit unit={null} semuaUnit={unitOrganisasi} />
      </div>

      <div className="mb-4">
        <Select value={status || '__semua__'} onValueChange={filterStatus}>
          <SelectTrigger className="w-48"><SelectValue placeholder="Semua status" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="__semua__">Semua Status</SelectItem>
            <SelectItem value="Aktif">Aktif</SelectItem>
            <SelectItem value="Nonaktif">Nonaktif</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nama</TableHead>
              <TableHead>Kode</TableHead>
              <TableHead>Jenis</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {pohon.map((unit) => (
              <TableRow key={unit.Id}>
                <TableCell style={{ paddingLeft: `${unit.kedalaman * 1.5 + 1}rem` }} className="font-medium text-foreground">
                  {unit.kedalaman > 0 && <span className="mr-1 text-muted-foreground">└</span>}
                  {unit.Nama}
                </TableCell>
                <TableCell className="font-mono text-sm">{unit.Kode}</TableCell>
                <TableCell>{unit.Jenis}</TableCell>
                <TableCell><Badge variant={unit.Status === 'Aktif' ? 'default' : 'outline'}>{unit.Status}</Badge></TableCell>
                <TableCell className="flex justify-end gap-2">
                  <DialogFormUnit unit={unit} semuaUnit={unitOrganisasi} />
                  <Button variant="ghost" size="sm" onClick={() => hapus(unit)}>Hapus</Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </AppLayout>
  );
}
