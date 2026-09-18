import { FormEvent, useEffect, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger,
} from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import type { PageProps, Paginasi } from '@/types/global';
import type { KunciApi } from '@/features/KunciApi/types';
import type { KatalogIzin } from '@/features/PeranIzin/types';

interface Props {
  kunciApi: Paginasi<KunciApi>;
}

function DialogTampilkanToken({ token, onTutup }: { token: string; onTutup: () => void }) {
  const [disalin, setDisalin] = useState(false);

  const salin = () => {
    navigator.clipboard.writeText(token).then(() => setDisalin(true));
  };

  return (
    <Dialog open onOpenChange={onTutup}>
      <DialogContent>
        <DialogHeader><DialogTitle>Kunci API Berhasil Dibuat</DialogTitle></DialogHeader>
        <Alert>
          <AlertTitle>Simpan token ini sekarang</AlertTitle>
          <AlertDescription>Token hanya ditampilkan sekali dan tidak dapat dilihat kembali.</AlertDescription>
        </Alert>
        <div className="rounded-md border border-border bg-muted p-3 font-mono text-sm break-all">{token}</div>
        <DialogFooter>
          <Button variant="outline" onClick={salin}>{disalin ? 'Tersalin' : 'Salin Token'}</Button>
          <Button onClick={onTutup}>Selesai</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function DialogBuatKunci() {
  const [buka, setBuka] = useState(false);
  const [katalog, setKatalog] = useState<KatalogIzin | null>(null);
  const form = useForm<{ Nama: string; Cakupan: string[]; KadaluarsaPada: string; AlamatIpDiizinkan: string }>({
    Nama: '', Cakupan: [], KadaluarsaPada: '', AlamatIpDiizinkan: '',
  });

  useEffect(() => {
    if (buka && !katalog) {
      fetch('/platform/izin', { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((json) => setKatalog(json.data));
    }
  }, [buka, katalog]);

  const toggleCakupan = (kode: string) => {
    form.setData('Cakupan', form.data.Cakupan.includes(kode)
      ? form.data.Cakupan.filter((k) => k !== kode)
      : [...form.data.Cakupan, kode]);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post('/platform/kunci-api', {
      Nama: form.data.Nama,
      Cakupan: form.data.Cakupan.length ? form.data.Cakupan : undefined,
      KadaluarsaPada: form.data.KadaluarsaPada || undefined,
      AlamatIpDiizinkan: form.data.AlamatIpDiizinkan
        ? form.data.AlamatIpDiizinkan.split(',').map((s) => s.trim()).filter(Boolean)
        : undefined,
    }, {
      onSuccess: () => { setBuka(false); form.reset(); },
      onError: (errors) => Object.entries(errors).forEach(([k, v]) => form.setError(k as never, v as string)),
    });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Kunci API</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[80vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader><DialogTitle>Buat Kunci API</DialogTitle></DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <Label>Nama</Label>
            <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
            {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
          </div>
          <div className="space-y-2">
            <Label>Kadaluarsa (opsional)</Label>
            <Input type="date" value={form.data.KadaluarsaPada} onChange={(e) => form.setData('KadaluarsaPada', e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Alamat IP Diizinkan (opsional, pisahkan dengan koma)</Label>
            <Input value={form.data.AlamatIpDiizinkan} onChange={(e) => form.setData('AlamatIpDiizinkan', e.target.value)} placeholder="203.0.113.1, 203.0.113.2" />
          </div>
          <div className="space-y-2">
            <Label>Cakupan (opsional, kosongkan untuk akses penuh)</Label>
            {!katalog && <p className="text-sm text-muted-foreground">Memuat katalog izin...</p>}
            {katalog && Object.entries(katalog).map(([modul, daftar]) => (
              <div key={modul} className="mb-3">
                <h4 className="mb-1 text-xs font-semibold uppercase text-muted-foreground">{modul}</h4>
                {daftar.map((izin) => (
                  <label key={izin.Id} className="flex items-center gap-2 text-sm">
                    <Checkbox checked={form.data.Cakupan.includes(izin.Kode)} onCheckedChange={() => toggleCakupan(izin.Kode)} />
                    {izin.Nama}
                  </label>
                ))}
              </div>
            ))}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>Buat</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function KunciApiIndex({ kunciApi }: Props) {
  const { flash } = usePage<PageProps>().props;
  const [tokenTampil, setTokenTampil] = useState<string | null>(null);

  useEffect(() => {
    if (flash.tokenKunciApi) setTokenTampil(flash.tokenKunciApi);
  }, [flash.tokenKunciApi]);

  const cabut = (item: KunciApi) => {
    if (!confirm(`Cabut kunci API "${item.Nama}"?`)) return;
    router.delete(`/platform/kunci-api/${item.Id}`, { preserveScroll: true });
  };

  return (
    <AppLayout>
      <Head title="Kunci API" />
      {tokenTampil && <DialogTampilkanToken token={tokenTampil} onTutup={() => setTokenTampil(null)} />}
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">Kunci API</h1>
          <p className="text-sm text-muted-foreground">Kelola akses integrasi eksternal ke Amanpoll.</p>
        </div>
        <DialogBuatKunci />
      </div>

      <div className="rounded-lg border border-border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nama</TableHead>
              <TableHead>Awalan</TableHead>
              <TableHead>Cakupan</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Terakhir Dipakai</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {kunciApi.data.map((item) => (
              <TableRow key={item.Id}>
                <TableCell className="font-medium text-foreground">{item.Nama}</TableCell>
                <TableCell className="font-mono text-sm">{item.AwalanKunci}</TableCell>
                <TableCell>
                  <div className="flex flex-wrap gap-1">
                    {(item.Cakupan ?? []).length === 0
                      ? <span className="text-sm text-muted-foreground">Akses Penuh</span>
                      : item.Cakupan?.map((c) => <Badge key={c} variant="secondary">{c}</Badge>)}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant={item.Status === 'Aktif' ? 'default' : 'outline'}>{item.Status}</Badge>
                </TableCell>
                <TableCell className="text-sm text-muted-foreground">
                  {item.TerakhirDipakaiPada ? new Date(item.TerakhirDipakaiPada).toLocaleString('id-ID') : 'Belum pernah'}
                </TableCell>
                <TableCell className="text-right">
                  {item.Status === 'Aktif' && (
                    <Button variant="ghost" size="sm" onClick={() => cabut(item)}>Cabut</Button>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
        <Pagination meta={kunciApi.meta} onNavigasi={(h) => navigasiHalaman(h)} />
      </div>
    </AppLayout>
  );
}
