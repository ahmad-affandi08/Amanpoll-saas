import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Search, ShoppingCart } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { EmptyState } from '@/components/shared/EmptyState';
import { Pagination, navigasiHalaman } from '@/components/shared/Pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Paginasi } from '@/types/global';
import type {
  PermintaanPembelian,
  PrioritasPermintaanPembelian,
  StatusPermintaanPembelian,
} from '@/features/PermintaanPembelian/types';
import { formatUang } from '@/lib/uang';
import { rutePermintaanPembelian } from '@/features/PermintaanPembelian/api';
import { PageHeader } from '@/components/shared/PageHeader';

interface UnitRingkas {
  Id: string;
  Nama: string;
}
interface RencanaRingkas {
  Id: string;
  Nomor: string;
  Nama: string;
  PosAnggaranId: string | null;
}
interface PosRingkas {
  Id: string;
  Kode: string;
  Nama: string;
}
interface Props {
  permintaan: Paginasi<PermintaanPembelian>;
  unitOrganisasi: UnitRingkas[];
  rencana: RencanaRingkas[];
  posAnggaran: PosRingkas[];
  filter: { cari?: string; status?: StatusPermintaanPembelian };
}

const TANPA = '__tanpa__';
const SEMUA = '__semua__';
const STATUS: StatusPermintaanPembelian[] = ['Draft', 'MenungguPersetujuan', 'Disetujui', 'Ditolak'];
const PRIORITAS: PrioritasPermintaanPembelian[] = ['Rendah', 'Normal', 'Tinggi', 'Mendesak'];
const VARIAN_STATUS = {
  Draft: 'netral',
  MenungguPersetujuan: 'perhatian',
  Disetujui: 'sukses',
  Ditolak: 'bahaya',
} as const;

function DialogBuatPermintaan({
  unitOrganisasi,
  rencana,
  posAnggaran,
}: Omit<Props, 'permintaan' | 'filter'>) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    UnitOrganisasiId: TANPA,
    RencanaPengadaanId: TANPA,
    PosAnggaranId: '',
    TanggalPermintaan: new Date().toISOString().slice(0, 10),
    TanggalDibutuhkan: '',
    Prioritas: 'Normal',
    Alasan: '',
  });

  function pilihRencana(id: string): void {
    form.setData('RencanaPengadaanId', id);
    const dipilih = rencana.find((item) => item.Id === id);
    if (dipilih?.PosAnggaranId) {
      form.setData('PosAnggaranId', dipilih.PosAnggaranId);
    }
  }

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      UnitOrganisasiId: data.UnitOrganisasiId === TANPA ? null : data.UnitOrganisasiId,
      RencanaPengadaanId: data.RencanaPengadaanId === TANPA ? null : data.RencanaPengadaanId,
      TanggalDibutuhkan: data.TanggalDibutuhkan || null,
      Alasan: data.Alasan || null,
    }));
    form.post(rutePermintaanPembelian.index, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Buat Permintaan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Permintaan Pembelian</DialogTitle>
          <DialogDescription>
            Simpan header sebagai draft; item dan total ditambahkan pada halaman detail.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="TanggalPermintaan">Tanggal Permintaan</Label>
              <Input
                id="TanggalPermintaan"
                type="date"
                value={form.data.TanggalPermintaan}
                onChange={(event) => form.setData('TanggalPermintaan', event.target.value)}
              />
              {form.errors.TanggalPermintaan && (
                <p className="text-sm text-destructive">{form.errors.TanggalPermintaan}</p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="TanggalDibutuhkan">Dibutuhkan</Label>
              <Input
                id="TanggalDibutuhkan"
                type="date"
                value={form.data.TanggalDibutuhkan}
                onChange={(event) => form.setData('TanggalDibutuhkan', event.target.value)}
              />
              {form.errors.TanggalDibutuhkan && (
                <p className="text-sm text-destructive">{form.errors.TanggalDibutuhkan}</p>
              )}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Pos Anggaran</Label>
            <Select
              value={form.data.PosAnggaranId}
              onValueChange={(value) => form.setData('PosAnggaranId', value)}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Pilih pos anggaran aktif" />
              </SelectTrigger>
              <SelectContent>
                {posAnggaran.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.Kode} — {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {form.errors.PosAnggaranId && (
              <p className="text-sm text-destructive">{form.errors.PosAnggaranId}</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label>Rencana Pengadaan</Label>
            <Select value={form.data.RencanaPengadaanId} onValueChange={pilihRencana}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={TANPA}>Tanpa rencana</SelectItem>
                {rencana.map((item) => (
                  <SelectItem key={item.Id} value={item.Id}>
                    {item.Nomor} — {item.Nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Unit Organisasi</Label>
              <Select
                value={form.data.UnitOrganisasiId}
                onValueChange={(value) => form.setData('UnitOrganisasiId', value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={TANPA}>Tanpa unit</SelectItem>
                  {unitOrganisasi.map((item) => (
                    <SelectItem key={item.Id} value={item.Id}>
                      {item.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Prioritas</Label>
              <Select value={form.data.Prioritas} onValueChange={(value) => form.setData('Prioritas', value)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {PRIORITAS.map((item) => (
                    <SelectItem key={item} value={item}>
                      {item}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="Alasan">Alasan</Label>
            <Input
              id="Alasan"
              value={form.data.Alasan}
              onChange={(event) => form.setData('Alasan', event.target.value)}
            />
            {form.errors.Alasan && <p className="text-sm text-destructive">{form.errors.Alasan}</p>}
          </div>
          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan Draft
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default function PermintaanPembelianIndex({
  permintaan,
  unitOrganisasi,
  rencana,
  posAnggaran,
  filter,
}: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState<string>(filter.status ?? SEMUA);

  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      rutePermintaanPembelian.index,
      { cari, status: status === SEMUA ? '' : status },
      { preserveState: true, replace: true },
    );
  }

  return (
    <AppLayout>
      <Head title="Permintaan Pembelian" />
      <div className="space-y-6">
        <PageHeader
          judul="Permintaan Pembelian"
          deskripsi="Draft kebutuhan, validasi sisa anggaran, dan pengajuan persetujuan."
          aksi={
            <>
              <DialogBuatPermintaan
                unitOrganisasi={unitOrganisasi}
                rencana={rencana}
                posAnggaran={posAnggaran}
              />
            </>
          }
        />

        <form onSubmit={terapkanFilter} className="grid gap-3 sm:grid-cols-[1fr_12rem_auto]">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              aria-label="Cari nomor permintaan"
              placeholder="Cari nomor permintaan"
              className="pl-9"
              value={cari}
              onChange={(event) => setCari(event.target.value)}
            />
          </div>
          <Select value={status} onValueChange={setStatus}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua status</SelectItem>
              {STATUS.map((item) => (
                <SelectItem key={item} value={item}>
                  {item}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Button type="submit" variant="outline">
            Terapkan
          </Button>
        </form>

        {permintaan.data.length === 0 ? (
          <EmptyState
            ilustrasi="/assets/3d/berkas-dokumen.webp"
            judul="Belum ada permintaan pembelian."
            deskripsi="Buat permintaan untuk memulai proses pengadaan."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Nomor</th>
                    <th className="px-4 py-3">Pos Anggaran / Unit</th>
                    <th className="px-4 py-3">Prioritas</th>
                    <th className="px-4 py-3 text-right">Total Estimasi</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {permintaan.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        <Link
                          className="font-mono font-medium hover:text-primary"
                          href={rutePermintaanPembelian.detail(item.Id)}
                        >
                          {item.Nomor}
                        </Link>
                        <p className="text-xs text-muted-foreground">{item.JumlahItem ?? 0} item</p>
                      </td>
                      <td className="px-4 py-3">
                        {item.NamaPosAnggaran ?? 'Pos belum dipilih'}
                        <p className="text-xs text-muted-foreground">
                          {item.NamaUnitOrganisasi ?? 'Tanpa unit'}
                        </p>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant="secondary">{item.Prioritas}</Badge>
                      </td>
                      <td className="px-4 py-3 text-right font-mono font-semibold">
                        {formatUang(item.TotalEstimasi)}
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {permintaan.data.map((item) => (
                <Link
                  key={item.Id}
                  href={rutePermintaanPembelian.detail(item.Id)}
                  className="flex min-h-24 items-center gap-3 p-4"
                >
                  <ShoppingCart className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-mono font-medium">{item.Nomor}</p>
                    <p className="text-xs text-muted-foreground">
                      {item.JumlahItem ?? 0} item · {item.NamaPosAnggaran ?? 'Pos belum dipilih'}
                    </p>
                    <p className="mt-1 font-mono text-sm font-semibold">{formatUang(item.TotalEstimasi)}</p>
                  </div>
                  <Badge variant={VARIAN_STATUS[item.Status]}>{item.Status}</Badge>
                </Link>
              ))}
            </div>
            <Pagination
              meta={permintaan.meta}
              onNavigasi={(halaman) =>
                navigasiHalaman(halaman, { cari, status: status === SEMUA ? '' : status })
              }
            />
          </div>
        )}
      </div>
    </AppLayout>
  );
}
