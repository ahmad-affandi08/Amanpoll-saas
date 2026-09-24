import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DatePicker } from '@/components/ui/date-picker';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import type { PageProps } from '@/types/global';
import type { KunciApi } from '@/features/KunciApi/types';
import type { KatalogIzin } from '@/features/PeranIzin/types';
import { ruteKunciApi } from '@/features/KunciApi/api';
import { http } from '@/lib/http';
import { rutePeranIzin } from '@/features/PeranIzin/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import type { Paginasi } from '@/types/global';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Props {
  kunciApi: Paginasi<KunciApi>;
  filter: FilterDaftar;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

function DialogTampilkanToken({ token, onTutup }: { token: string; onTutup: () => void }) {
  const [disalin, setDisalin] = useState(false);

  const salin = () => {
    navigator.clipboard.writeText(token).then(() => setDisalin(true));
  };

  return (
    <Dialog open onOpenChange={onTutup}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Kunci API Berhasil Dibuat</DialogTitle>
        </DialogHeader>
        <Alert variant="perhatian">
          <AlertTitle>Simpan token ini sekarang</AlertTitle>
          <AlertDescription>Token hanya ditampilkan sekali dan tidak dapat dilihat kembali.</AlertDescription>
        </Alert>
        <div className="rounded-md border border-border bg-muted p-3 font-mono text-sm break-all">
          {token}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={salin}>
            {disalin ? 'Tersalin' : 'Salin Token'}
          </Button>
          <Button onClick={onTutup}>Selesai</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function DialogBuatKunci({ wajib }: { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const [katalog, setKatalog] = useState<KatalogIzin | null>(null);
  const form = useForm<{
    Nama: string;
    Cakupan: string[];
    KadaluarsaPada: string;
    AlamatIpDiizinkan: string;
  }>({
    Nama: '',
    Cakupan: [],
    KadaluarsaPada: '',
    AlamatIpDiizinkan: '',
  });

  useEffect(() => {
    if (buka && !katalog) {
      http.get(rutePeranIzin.daftarIzin).then((res) => setKatalog(res.data.data));
    }
  }, [buka, katalog]);

  const toggleCakupan = (kode: string) => {
    form.setData(
      'Cakupan',
      form.data.Cakupan.includes(kode)
        ? form.data.Cakupan.filter((k) => k !== kode)
        : [...form.data.Cakupan, kode],
    );
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      ruteKunciApi.index,
      {
        Nama: form.data.Nama,
        Cakupan: form.data.Cakupan.length ? form.data.Cakupan : undefined,
        KadaluarsaPada: form.data.KadaluarsaPada || undefined,
        AlamatIpDiizinkan: form.data.AlamatIpDiizinkan
          ? form.data.AlamatIpDiizinkan.split(',')
              .map((s) => s.trim())
              .filter(Boolean)
          : undefined,
      },
      {
        onSuccess: () => {
          setBuka(false);
          form.reset();
        },
        onError: (errors) =>
          Object.entries(errors).forEach(([k, v]) => form.setError(k as never, v as string)),
      },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Kunci API</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[80vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Buat Kunci API</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
              <Label nama="Nama">Nama</Label>
              <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
              {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
            </div>
            <div className="space-y-2">
              <Label nama="KadaluarsaPada">Kadaluarsa (opsional)</Label>
              <DatePicker
                value={form.data.KadaluarsaPada}
                onChange={(val) => form.setData('KadaluarsaPada', val)}
                placeholder="Pilih tanggal kadaluarsa"
              />
            </div>
            <div className="space-y-2">
              <Label nama="AlamatIpDiizinkan">Alamat IP Diizinkan (opsional, pisahkan dengan koma)</Label>
              <Input
                value={form.data.AlamatIpDiizinkan}
                onChange={(e) => form.setData('AlamatIpDiizinkan', e.target.value)}
                placeholder="203.0.113.1, 203.0.113.2"
              />
            </div>
            <div className="space-y-2">
              <Label>Cakupan (opsional, kosongkan untuk akses penuh)</Label>
              {!katalog && <p className="text-sm text-muted-foreground">Memuat katalog izin...</p>}
              {katalog &&
                Object.entries(katalog).map(([modul, daftar]) => (
                  <div key={modul} className="mb-3">
                    <h4 className="mb-1 text-xs font-medium text-grafit-500">{modul}</h4>
                    {daftar.map((izin) => (
                      <label key={izin.Id} className="flex items-center gap-2 text-sm">
                        <Checkbox
                          checked={form.data.Cakupan.includes(izin.Kode)}
                          onCheckedChange={() => toggleCakupan(izin.Kode)}
                        />
                        {izin.Nama}
                      </label>
                    ))}
                  </div>
                ))}
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Buat
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function KunciApiIndex({ kunciApi, filter, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  const { flash } = usePage<PageProps>().props;
  const [tokenTampil, setTokenTampil] = useState<string | null>(null);

  useEffect(() => {
    if (flash.tokenKunciApi) setTokenTampil(flash.tokenKunciApi);
  }, [flash.tokenKunciApi]);

  const cabut = async (item: KunciApi) => {
    if (
      !(await konfirmasi({
        judul: `Cabut kunci API "${item.Nama}"?`,
        deskripsi: 'Integrasi yang memakai kunci ini langsung kehilangan akses.',
        ragam: 'bahaya',
        ilustrasi: '/assets/3d/peringatan.webp',
      }))
    )
      return;
    router.delete(ruteKunciApi.detail(item.Id), { preserveScroll: true });
  };

  const columns = useMemo<ColumnDef<KunciApi>[]>(
    () => [
      {
        accessorKey: 'Nama',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
        cell: ({ row }) => <span className="font-medium text-foreground">{row.original.Nama}</span>,
        meta: { label: 'Nama' },
      },
      {
        accessorKey: 'AwalanKunci',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Awalan" />,
        cell: ({ row }) => <span className="font-mono text-sm">{row.original.AwalanKunci}</span>,
        meta: { label: 'Awalan' },
      },
      {
        id: 'Cakupan',
        header: 'Cakupan',
        accessorFn: (row) => (row.Cakupan ?? []).join(', '),
        cell: ({ row }) => (
          <div className="flex flex-wrap gap-1">
            {(row.original.Cakupan ?? []).length === 0 ? (
              <span className="text-sm text-muted-foreground">Akses Penuh</span>
            ) : (
              row.original.Cakupan?.map((c) => (
                <Badge key={c} variant="secondary">
                  {c}
                </Badge>
              ))
            )}
          </div>
        ),
        enableSorting: false,
        meta: { label: 'Cakupan' },
      },
      {
        accessorKey: 'Status',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        cell: ({ row }) => (
          <Badge variant={row.original.Status === 'Aktif' ? 'default' : 'outline'}>
            {row.original.Status}
          </Badge>
        ),
        filterFn: (row, id, value: string[]) => value.includes(row.getValue(id)),
        meta: { label: 'Status' },
      },
      {
        accessorKey: 'TerakhirDipakaiPada',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Terakhir Dipakai" />,
        cell: ({ row }) => (
          <span className="text-sm text-muted-foreground">
            {row.original.TerakhirDipakaiPada
              ? new Date(row.original.TerakhirDipakaiPada).toLocaleString('id-ID')
              : 'Belum pernah'}
          </span>
        ),
        meta: { label: 'Terakhir Dipakai' },
      },
      {
        id: 'aksi',
        header: 'Aksi',
        cell: ({ row }) => (
          <div className="text-right">
            {row.original.Status === 'Aktif' && (
              <Button variant="ghost" size="sm" onClick={() => cabut(row.original)}>
                Cabut
              </Button>
            )}
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { label: 'Aksi' },
      },
    ],
    [wajib],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Kunci API" />
      {tokenTampil && <DialogTampilkanToken token={tokenTampil} onTutup={() => setTokenTampil(null)} />}
      <KepalaHalaman
        judul="Kunci API"
        deskripsi="Kelola akses integrasi eksternal ke Amanpoll."
        aksi={
          <>
            <DialogBuatKunci wajib={wajib.kunciApi} />
          </>
        }
        className="mb-5"
      />

      <DataTable
        columns={columns}
        data={kunciApi.data}
        server={{ meta: kunciApi.meta, filter }}
        ekspor="/platform/kunci-api/ekspor"
        pencarianPlaceholder="Cari nama atau awalan kunci..."
        facetedFilters={[
          {
            columnId: 'Status',
            title: 'Status',
            options: [
              { label: 'Aktif', value: 'Aktif' },
              { label: 'Dicabut', value: 'Dicabut' },
            ],
          },
        ]}
        pesanKosong={adaPenyaringAktif(filter) ? 'Tidak ada kunci yang cocok.' : 'Belum ada kunci API.'}
        ilustrasiKosong="/assets/3d/integrasi.webp"
      />
    </KerangkaAplikasi>
  );
}
