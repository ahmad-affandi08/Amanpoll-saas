import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import { apiPenyedia } from '@/features/Penyedia/api';
import type {
  Penyedia, KategoriPenyedia, KontakPenyedia, PenilaianPenyedia, RekapPenilaianPenyedia,
} from '@/features/Penyedia/types';

interface Props {
  penyedia: Penyedia[];
  kategoriPenyedia: KategoriPenyedia[];
}

function DialogKelolaKategori({ kategoriPenyedia }: { kategoriPenyedia: KategoriPenyedia[] }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({ Kode: '', Nama: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/penyedia/kategori', { onSuccess: () => form.reset(), preserveScroll: true });
  };

  const hapus = (kategori: KategoriPenyedia) => {
    if (!confirm(`Hapus kategori "${kategori.Nama}"?`)) return;
    router.delete(`/penyedia/kategori/${kategori.Id}`, { preserveScroll: true });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">Kelola Kategori</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader><DialogTitle>Kategori Penyedia</DialogTitle></DialogHeader>
        <div className="space-y-2">
          {kategoriPenyedia.map((k) => (
            <div key={k.Id} className="flex items-center justify-between rounded-md border border-border px-3 py-2">
              <div>
                <span className="text-sm font-medium text-foreground">{k.Nama}</span>
                <span className="ml-2 font-mono text-xs text-muted-foreground">{k.Kode}</span>
              </div>
              <Button variant="ghost" size="sm" onClick={() => hapus(k)}>Hapus</Button>
            </div>
          ))}
          {kategoriPenyedia.length === 0 && <p className="text-sm text-muted-foreground">Belum ada kategori.</p>}
        </div>
        <form onSubmit={submit} className="flex gap-2 border-t border-border pt-4">
          <Input placeholder="Kode" value={form.data.Kode} onChange={(e) => form.setData('Kode', e.target.value)} className="w-28 font-mono" />
          <Input placeholder="Nama kategori" value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} className="flex-1" />
          <Button type="submit" disabled={form.processing}>Tambah</Button>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function FormInfoPenyedia({ penyedia, onSukses }: { penyedia: Penyedia | null; onSukses?: () => void }) {
  const form = useForm(penyedia
    ? {
        Kode: penyedia.Kode, Nama: penyedia.Nama, NamaLegal: penyedia.NamaLegal ?? '',
        NomorIdentitasPajak: penyedia.NomorIdentitasPajak ?? '', Email: penyedia.Email ?? '',
        Telepon: penyedia.Telepon ?? '', Website: penyedia.Website ?? '', Alamat: penyedia.Alamat ?? '',
        Kota: penyedia.Kota ?? '', Provinsi: penyedia.Provinsi ?? '', Negara: penyedia.Negara ?? '',
        Status: penyedia.Status,
      }
    : {
        Kode: '', Nama: '', NamaLegal: '', NomorIdentitasPajak: '', Email: '', Telepon: '', Website: '',
        Alamat: '', Kota: '', Provinsi: '', Negara: '', Status: 'Aktif' as const,
      });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const opsi = { onSuccess: () => { if (!penyedia) form.reset(); onSukses?.(); } };
    if (penyedia) {
      router.put(`/penyedia/${penyedia.Id}`, form.data, opsi);
    } else {
      router.post('/penyedia', form.data, opsi);
    }
  };

  return (
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
          <Label>Nama Legal</Label>
          <Input value={form.data.NamaLegal} onChange={(e) => form.setData('NamaLegal', e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label>NPWP</Label>
          <Input value={form.data.NomorIdentitasPajak} onChange={(e) => form.setData('NomorIdentitasPajak', e.target.value)} />
        </div>
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label>Email</Label>
          <Input type="email" value={form.data.Email} onChange={(e) => form.setData('Email', e.target.value)} />
          {form.errors.Email && <p className="text-sm text-destructive">{form.errors.Email}</p>}
        </div>
        <div className="space-y-2">
          <Label>Telepon</Label>
          <Input value={form.data.Telepon} onChange={(e) => form.setData('Telepon', e.target.value)} />
        </div>
      </div>
      <div className="space-y-2">
        <Label>Website</Label>
        <Input value={form.data.Website} onChange={(e) => form.setData('Website', e.target.value)} placeholder="https://" />
        {form.errors.Website && <p className="text-sm text-destructive">{form.errors.Website}</p>}
      </div>
      <div className="space-y-2">
        <Label>Alamat</Label>
        <Textarea value={form.data.Alamat} onChange={(e) => form.setData('Alamat', e.target.value)} rows={2} />
      </div>
      <div className="grid grid-cols-3 gap-4">
        <div className="space-y-2">
          <Label>Kota</Label>
          <Input value={form.data.Kota} onChange={(e) => form.setData('Kota', e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label>Provinsi</Label>
          <Input value={form.data.Provinsi} onChange={(e) => form.setData('Provinsi', e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label>Negara</Label>
          <Input value={form.data.Negara} onChange={(e) => form.setData('Negara', e.target.value)} />
        </div>
      </div>
      <div className="space-y-2">
        <Label>Status</Label>
        <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v as 'Aktif' | 'Nonaktif')}>
          <SelectTrigger className="w-48"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="Aktif">Aktif</SelectItem>
            <SelectItem value="Nonaktif">Nonaktif</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <DialogFooter>
        <Button type="submit" disabled={form.processing}>Simpan</Button>
      </DialogFooter>
    </form>
  );
}

function TabKategori({ penyedia, kategoriPenyedia }: { penyedia: Penyedia; kategoriPenyedia: KategoriPenyedia[] }) {
  const toggle = (kategori: KategoriPenyedia, dicentang: boolean) => {
    if (dicentang) {
      router.post(`/penyedia/${penyedia.Id}/kategori`, { KategoriPenyediaId: kategori.Id }, { preserveScroll: true });
    } else {
      router.delete(`/penyedia/${penyedia.Id}/kategori/${kategori.Id}`, { preserveScroll: true });
    }
  };

  if (kategoriPenyedia.length === 0) {
    return <p className="text-sm text-muted-foreground">Belum ada kategori penyedia. Buat lewat "Kelola Kategori".</p>;
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

function TabKontak({ penyedia }: { penyedia: Penyedia }) {
  const [data, setData] = useState<KontakPenyedia[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({ Nama: '', Jabatan: '', Email: '', Telepon: '', Utama: false });

  const muat = () => {
    setMemuat(true);
    apiPenyedia.get(`/penyedia/${penyedia.Id}/kontak`).then((res) => setData(res.data)).finally(() => setMemuat(false));
  };

  useEffect(muat, [penyedia.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`/penyedia/${penyedia.Id}/kontak`, form.data, { preserveScroll: true, onSuccess: () => { form.reset(); muat(); } });
  };

  const hapus = (kontak: KontakPenyedia) => {
    if (!confirm(`Hapus kontak "${kontak.Nama}"?`)) return;
    router.delete(`/penyedia/kontak/${kontak.Id}`, { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-4">
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && <p className="text-sm text-muted-foreground">Belum ada kontak.</p>}
      <div className="space-y-2">
        {data.map((k) => (
          <div key={k.Id} className="flex items-center justify-between rounded-md border border-border px-3 py-2">
            <div>
              <div className="flex items-center gap-2">
                <span className="text-sm font-medium text-foreground">{k.Nama}</span>
                {k.Utama && <Badge variant="default">Utama</Badge>}
              </div>
              <div className="text-xs text-muted-foreground">{[k.Jabatan, k.Email, k.Telepon].filter(Boolean).join(' · ')}</div>
            </div>
            <Button variant="ghost" size="sm" onClick={() => hapus(k)}>Hapus</Button>
          </div>
        ))}
      </div>
      <form onSubmit={submit} className="space-y-2 border-t border-border pt-4">
        <div className="grid grid-cols-2 gap-2">
          <Input placeholder="Nama" value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
          <Input placeholder="Jabatan" value={form.data.Jabatan} onChange={(e) => form.setData('Jabatan', e.target.value)} />
        </div>
        <div className="grid grid-cols-2 gap-2">
          <Input placeholder="Email" value={form.data.Email} onChange={(e) => form.setData('Email', e.target.value)} />
          <Input placeholder="Telepon" value={form.data.Telepon} onChange={(e) => form.setData('Telepon', e.target.value)} />
        </div>
        <label className="flex items-center gap-2 text-sm">
          <Checkbox checked={form.data.Utama} onCheckedChange={(v) => form.setData('Utama', v === true)} />
          Jadikan kontak utama
        </label>
        <Button type="submit" disabled={form.processing}>Tambah Kontak</Button>
      </form>
    </div>
  );
}

function TabPenilaian({ penyedia }: { penyedia: Penyedia }) {
  const [histori, setHistori] = useState<PenilaianPenyedia[]>([]);
  const [rekap, setRekap] = useState<RekapPenilaianPenyedia | null>(null);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({
    PeriodeMulai: '', PeriodeSelesai: '', SkorKualitas: '', SkorKetepatanWaktu: '', SkorHarga: '', SkorLayanan: '', Catatan: '',
  });

  const muat = () => {
    setMemuat(true);
    apiPenyedia.get(`/penyedia/${penyedia.Id}/penilaian`).then((res) => {
      setHistori(res.data.histori);
      setRekap(res.data.rekap);
    }).finally(() => setMemuat(false));
  };

  useEffect(muat, [penyedia.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(`/penyedia/${penyedia.Id}/penilaian`, form.data, { preserveScroll: true, onSuccess: () => { form.reset(); muat(); } });
  };

  return (
    <div className="space-y-4">
      {rekap && rekap.JumlahPenilaian > 0 && (
        <div className="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
          Skor total rata-rata: <span className="font-semibold text-foreground">{rekap.SkorTotalRataRata}</span>{' '}
          dari {rekap.JumlahPenilaian} penilaian.
        </div>
      )}
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && histori.length === 0 && <p className="text-sm text-muted-foreground">Belum ada penilaian.</p>}
      <div className="space-y-2">
        {histori.map((p) => (
          <div key={p.Id} className="rounded-md border border-border px-3 py-2 text-sm">
            <div className="flex items-center justify-between">
              <span className="font-medium text-foreground">{p.PeriodeMulai} s/d {p.PeriodeSelesai}</span>
              {p.SkorTotal && <Badge variant="default">Total {p.SkorTotal}</Badge>}
            </div>
            <div className="mt-1 text-xs text-muted-foreground">
              Kualitas {p.SkorKualitas ?? '-'} · Ketepatan {p.SkorKetepatanWaktu ?? '-'} · Harga {p.SkorHarga ?? '-'} · Layanan {p.SkorLayanan ?? '-'}
            </div>
            {p.Catatan && <div className="mt-1 text-xs text-muted-foreground">"{p.Catatan}"</div>}
            {p.NamaPenilai && <div className="mt-1 text-xs text-muted-foreground">Dinilai oleh {p.NamaPenilai}</div>}
          </div>
        ))}
      </div>
      <form onSubmit={submit} className="space-y-2 border-t border-border pt-4">
        <div className="grid grid-cols-2 gap-2">
          <div className="space-y-1">
            <Label className="text-xs">Periode Mulai</Label>
            <Input type="date" value={form.data.PeriodeMulai} onChange={(e) => form.setData('PeriodeMulai', e.target.value)} />
          </div>
          <div className="space-y-1">
            <Label className="text-xs">Periode Selesai</Label>
            <Input type="date" value={form.data.PeriodeSelesai} onChange={(e) => form.setData('PeriodeSelesai', e.target.value)} />
          </div>
        </div>
        <div className="grid grid-cols-4 gap-2">
          <Input placeholder="Kualitas" type="number" min={0} max={100} value={form.data.SkorKualitas} onChange={(e) => form.setData('SkorKualitas', e.target.value)} />
          <Input placeholder="Ketepatan" type="number" min={0} max={100} value={form.data.SkorKetepatanWaktu} onChange={(e) => form.setData('SkorKetepatanWaktu', e.target.value)} />
          <Input placeholder="Harga" type="number" min={0} max={100} value={form.data.SkorHarga} onChange={(e) => form.setData('SkorHarga', e.target.value)} />
          <Input placeholder="Layanan" type="number" min={0} max={100} value={form.data.SkorLayanan} onChange={(e) => form.setData('SkorLayanan', e.target.value)} />
        </div>
        <Textarea placeholder="Catatan (opsional)" value={form.data.Catatan} onChange={(e) => form.setData('Catatan', e.target.value)} rows={2} />
        <Button type="submit" disabled={form.processing}>Simpan Penilaian</Button>
      </form>
    </div>
  );
}

function DialogKelolaPenyedia({ penyedia, kategoriPenyedia }: { penyedia: Penyedia; kategoriPenyedia: KategoriPenyedia[] }) {
  const [buka, setBuka] = useState(false);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">Kelola</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader><DialogTitle>{penyedia.Nama}</DialogTitle></DialogHeader>
        <Tabs defaultValue="info">
          <TabsList>
            <TabsTrigger value="info">Info</TabsTrigger>
            <TabsTrigger value="kategori">Kategori</TabsTrigger>
            <TabsTrigger value="kontak">Kontak</TabsTrigger>
            <TabsTrigger value="penilaian">Penilaian</TabsTrigger>
            <TabsTrigger value="kolaborasi">Kolaborasi</TabsTrigger>
          </TabsList>
          <TabsContent value="info"><FormInfoPenyedia penyedia={penyedia} /></TabsContent>
          <TabsContent value="kategori"><TabKategori penyedia={penyedia} kategoriPenyedia={kategoriPenyedia} /></TabsContent>
          <TabsContent value="kontak"><TabKontak penyedia={penyedia} /></TabsContent>
          <TabsContent value="penilaian"><TabPenilaian penyedia={penyedia} /></TabsContent>
          <TabsContent value="kolaborasi"><PanelKolaborasi jenisEntitas="Penyedia" entitasId={penyedia.Id} /></TabsContent>
        </Tabs>
      </DialogContent>
    </Dialog>
  );
}

function DialogTambahPenyedia() {
  const [buka, setBuka] = useState(false);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Penyedia</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader><DialogTitle>Tambah Penyedia</DialogTitle></DialogHeader>
        <FormInfoPenyedia penyedia={null} onSukses={() => setBuka(false)} />
      </DialogContent>
    </Dialog>
  );
}

export default function PenyediaIndex({ penyedia, kategoriPenyedia }: Props) {
  const hapus = (item: Penyedia) => {
    if (!confirm(`Hapus penyedia "${item.Nama}"?`)) return;
    router.delete(`/penyedia/${item.Id}`, { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<Penyedia>[]>(() => [
    {
      id: 'Nama',
      accessorFn: (row) => `${row.Nama} ${row.Kode}`,
      header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
      cell: ({ row }) => (
        <div>
          <div className="font-medium text-foreground">{row.original.Nama}</div>
          <div className="font-mono text-xs text-muted-foreground">{row.original.Kode}</div>
        </div>
      ),
      meta: { label: 'Nama' },
    },
    {
      id: 'NamaKategoriPenyedia',
      accessorFn: (row) => row.NamaKategoriPenyedia,
      header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
      cell: ({ row }) => (
        <div className="flex flex-wrap gap-1">
          {row.original.NamaKategoriPenyedia.length === 0 && '—'}
          {row.original.NamaKategoriPenyedia.map((nama) => <Badge key={nama} variant="secondary">{nama}</Badge>)}
        </div>
      ),
      filterFn: (row, id, value: string[]) => {
        const idsTerpilih = kategoriPenyedia.filter((k) => value.includes(k.Nama)).map((k) => k.Id);
        return row.original.KategoriPenyediaId.some((kid) => idsTerpilih.includes(kid));
      },
      meta: { label: 'Kategori' },
    },
    {
      id: 'Kontak',
      accessorFn: (row) => `${row.Email ?? ''} ${row.Telepon ?? ''}`,
      header: 'Kontak',
      cell: ({ row }) => (
        <div className="text-sm">
          <div>{row.original.Email ?? '—'}</div>
          <div className="text-xs text-muted-foreground">{row.original.Telepon ?? '—'}</div>
        </div>
      ),
      meta: { label: 'Kontak' },
    },
    {
      accessorKey: 'Status',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
      cell: ({ row }) => <Badge variant={row.original.Status === 'Aktif' ? 'default' : 'outline'}>{row.original.Status}</Badge>,
      filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
      meta: { label: 'Status' },
    },
    {
      id: 'aksi',
      header: 'Aksi',
      cell: ({ row }) => (
        <div className="flex justify-end gap-2">
          <DialogKelolaPenyedia penyedia={row.original} kategoriPenyedia={kategoriPenyedia} />
          <Button variant="ghost" size="sm" onClick={() => hapus(row.original)}>Hapus</Button>
        </div>
      ),
      enableSorting: false,
      enableHiding: false,
      meta: { label: 'Aksi' },
    },
  ], [kategoriPenyedia]);

  return (
    <AppLayout>
      <Head title="Penyedia" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Penyedia</h1>
          <p className="text-sm text-muted-foreground">Kelola data vendor/supplier untuk pengadaan, kontrak, dan kalibrasi.</p>
        </div>
        <div className="flex gap-2">
          <DialogKelolaKategori kategoriPenyedia={kategoriPenyedia} />
          <DialogTambahPenyedia />
        </div>
      </div>

      <DataTable
        columns={columns}
        data={penyedia}
        pencarianPlaceholder="Cari nama atau kode penyedia..."
        facetedFilters={[
          { columnId: 'Status', title: 'Status', options: [{ label: 'Aktif', value: 'Aktif' }, { label: 'Nonaktif', value: 'Nonaktif' }] },
          { columnId: 'NamaKategoriPenyedia', title: 'Kategori', options: kategoriPenyedia.map((k) => ({ label: k.Nama, value: k.Nama })) },
        ]}
        pesanKosong="Belum ada penyedia."
        ilustrasiKosong="/assets/3d/penyedia-kontrak.webp"
      />
    </AppLayout>
  );
}
