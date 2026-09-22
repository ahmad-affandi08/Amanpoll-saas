import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ClipboardPlus, Plus, Search } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
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
import { Textarea } from '@/components/ui/textarea';
import type { Paginasi } from '@/types/global';
import type { PrioritasUsulanAset, StatusUsulanAset, UsulanAset } from '@/features/UsulanAset/types';
import { formatUang } from '@/lib/uang';
import { ruteUsulanAset } from '@/features/UsulanAset/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';

interface Referensi {
  Id: string;
  Nama: string;
}
interface Props {
  usulan: Paginasi<UsulanAset>;
  unitOrganisasi: Referensi[];
  kategoriAset: Referensi[];
  modelAset: Referensi[];
  filter: { cari?: string; status?: StatusUsulanAset; prioritas?: PrioritasUsulanAset };
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const STATUS: StatusUsulanAset[] = ['Draft', 'Diajukan', 'MenungguPersetujuan', 'Disetujui', 'Ditolak'];
const PRIORITAS: PrioritasUsulanAset[] = ['Rendah', 'Normal', 'Tinggi', 'Kritis'];
const VARIAN_STATUS = {
  Draft: 'netral',
  Diajukan: 'info',
  MenungguPersetujuan: 'perhatian',
  Disetujui: 'sukses',
  Ditolak: 'bahaya',
} as const;
const VARIAN_PRIORITAS = { Rendah: 'netral', Normal: 'info', Tinggi: 'perhatian', Kritis: 'bahaya' } as const;

function DialogBuatUsulan({
  unitOrganisasi,
  kategoriAset,
  modelAset,
  wajib,
}: Pick<Props, 'unitOrganisasi' | 'kategoriAset' | 'modelAset'> & { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    UnitOrganisasiId: '',
    KategoriAsetId: TANPA_PILIHAN,
    ModelAsetId: TANPA_PILIHAN,
    NamaKebutuhan: '',
    Jumlah: '1',
    EstimasiHargaSatuan: '',
    Alasan: '',
    JenisKebutuhan: '',
    TahunKebutuhan: new Date().getFullYear().toString(),
    Prioritas: 'Normal' as PrioritasUsulanAset,
  });

  function submit(event: FormEvent): void {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      KategoriAsetId: data.KategoriAsetId === TANPA_PILIHAN ? null : data.KategoriAsetId,
      ModelAsetId: data.ModelAsetId === TANPA_PILIHAN ? null : data.ModelAsetId,
      EstimasiHargaSatuan: data.EstimasiHargaSatuan || null,
      JenisKebutuhan: data.JenisKebutuhan || null,
    }));
    form.post(ruteUsulanAset.index, { onSuccess: () => setBuka(false) });
  }

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button className="min-h-11 sm:min-h-9">
          <Plus /> Buat Usulan
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Buat Usulan Aset</DialogTitle>
          <DialogDescription>
            Simpan kebutuhan sebagai draft. Nomor dokumen dibuat otomatis oleh sistem.
          </DialogDescription>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="UnitOrganisasiId">Unit Organisasi</Label>
                <Select
                  value={form.data.UnitOrganisasiId}
                  onValueChange={(value) => form.setData('UnitOrganisasiId', value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Pilih unit" />
                  </SelectTrigger>
                  <SelectContent>
                    {unitOrganisasi.map((item) => (
                      <SelectItem key={item.Id} value={item.Id}>
                        {item.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {form.errors.UnitOrganisasiId && (
                  <p className="text-sm text-destructive">{form.errors.UnitOrganisasiId}</p>
                )}
              </div>
              <div className="space-y-1.5">
                <Label nama="Prioritas">Prioritas Awal</Label>
                <Select
                  value={form.data.Prioritas}
                  onValueChange={(value) => form.setData('Prioritas', value as PrioritasUsulanAset)}
                >
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
              <Label nama="NamaKebutuhan">Nama Kebutuhan</Label>
              <Input
                value={form.data.NamaKebutuhan}
                onChange={(event) => form.setData('NamaKebutuhan', event.target.value)}
              />
              {form.errors.NamaKebutuhan && (
                <p className="text-sm text-destructive">{form.errors.NamaKebutuhan}</p>
              )}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="KategoriAsetId">Kategori Aset</Label>
                <Select
                  value={form.data.KategoriAsetId}
                  onValueChange={(value) => form.setData('KategoriAsetId', value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={TANPA_PILIHAN}>Belum ditentukan</SelectItem>
                    {kategoriAset.map((item) => (
                      <SelectItem key={item.Id} value={item.Id}>
                        {item.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label nama="ModelAsetId">Model Aset</Label>
                <Select
                  value={form.data.ModelAsetId}
                  onValueChange={(value) => form.setData('ModelAsetId', value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={TANPA_PILIHAN}>Belum ditentukan</SelectItem>
                    {modelAset.map((item) => (
                      <SelectItem key={item.Id} value={item.Id}>
                        {item.Nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-3">
              <div className="space-y-1.5">
                <Label nama="Jumlah">Jumlah</Label>
                <Input
                  type="number"
                  min="0.0001"
                  step="0.0001"
                  value={form.data.Jumlah}
                  onChange={(event) => form.setData('Jumlah', event.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="EstimasiHargaSatuan">Estimasi Harga / Unit</Label>
                <Input
                  type="number"
                  min="0"
                  step="0.01"
                  value={form.data.EstimasiHargaSatuan}
                  onChange={(event) => form.setData('EstimasiHargaSatuan', event.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="TahunKebutuhan">Tahun Kebutuhan</Label>
                <Input
                  type="number"
                  min="2000"
                  max="2100"
                  value={form.data.TahunKebutuhan}
                  onChange={(event) => form.setData('TahunKebutuhan', event.target.value)}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="JenisKebutuhan">Jenis Kebutuhan</Label>
              <Input
                placeholder="Penggantian, penambahan, atau lainnya"
                value={form.data.JenisKebutuhan}
                onChange={(event) => form.setData('JenisKebutuhan', event.target.value)}
              />
            </div>
            <div className="space-y-1.5">
              <Label nama="Alasan">Alasan</Label>
              <Textarea
                rows={4}
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
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function UsulanAsetIndex({
  usulan,
  unitOrganisasi,
  kategoriAset,
  modelAset,
  filter,
  wajib,
}: Props) {
  const [cari, setCari] = useState(filter.cari ?? '');
  const [status, setStatus] = useState(filter.status ?? SEMUA);
  const [prioritas, setPrioritas] = useState(filter.prioritas ?? SEMUA);
  function terapkanFilter(event: FormEvent): void {
    event.preventDefault();
    router.get(
      ruteUsulanAset.index,
      {
        cari: cari || undefined,
        status: status === SEMUA ? undefined : status,
        prioritas: prioritas === SEMUA ? undefined : prioritas,
      },
      { preserveState: true },
    );
  }

  return (
    <KerangkaAplikasi>
      <Head title="Usulan Aset" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Usulan Aset"
          deskripsi="Susun kebutuhan, lakukan penilaian, lalu ajukan persetujuan."
          aksi={
            <>
              <DialogBuatUsulan
                unitOrganisasi={unitOrganisasi}
                kategoriAset={kategoriAset}
                modelAset={modelAset}
                wajib={wajib.usulan}
              />
            </>
          }
        />
        <form
          onSubmit={terapkanFilter}
          className="grid gap-2 rounded-[9px] border border-border bg-card p-3 sm:grid-cols-[1fr_13rem_11rem_auto]"
        >
          <div className="relative">
            <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
            <Input
              className="pl-9"
              aria-label="Cari usulan"
              placeholder="Cari nomor atau kebutuhan..."
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
                  {item === 'MenungguPersetujuan' ? 'Menunggu Persetujuan' : item}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Select value={prioritas} onValueChange={setPrioritas}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua prioritas</SelectItem>
              {PRIORITAS.map((item) => (
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
        {usulan.data.length === 0 ? (
          <KeadaanKosong
            ilustrasi="/assets/3d/dashboard-analitik.webp"
            judul="Belum ada usulan aset."
            deskripsi="Buat usulan pertama untuk memulai proses perencanaan kebutuhan."
          />
        ) : (
          <div className="overflow-hidden rounded-[9px] border border-border bg-card">
            <div className="hidden overflow-x-auto md:block">
              <table className="w-full text-sm">
                <thead className="border-b border-border bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">Usulan</th>
                    <th className="px-4 py-3">Unit</th>
                    <th className="px-4 py-3 text-right">Estimasi</th>
                    <th className="px-4 py-3">Prioritas</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {usulan.data.map((item) => (
                    <tr key={item.Id} className="hover:bg-muted/30">
                      <td className="px-4 py-3">
                        <Link
                          href={ruteUsulanAset.detail(item.Id)}
                          className="font-medium hover:text-primary"
                        >
                          {item.NamaKebutuhan}
                        </Link>
                        <p className="font-mono text-xs text-muted-foreground">
                          {item.Nomor} · {Number(item.Jumlah).toLocaleString('id-ID')} unit
                        </p>
                      </td>
                      <td className="px-4 py-3 text-muted-foreground">{item.NamaUnitOrganisasi}</td>
                      <td className="px-4 py-3 text-right font-mono">
                        {item.EstimasiHargaSatuan
                          ? formatUang(Number(item.Jumlah) * Number(item.EstimasiHargaSatuan))
                          : '—'}
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_PRIORITAS[item.Prioritas]}>{item.Prioritas}</Badge>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={VARIAN_STATUS[item.Status]}>
                          {item.Status === 'MenungguPersetujuan' ? 'Menunggu Persetujuan' : item.Status}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="divide-y divide-border md:hidden">
              {usulan.data.map((item) => (
                <Link
                  key={item.Id}
                  href={ruteUsulanAset.detail(item.Id)}
                  className="flex min-h-24 items-center gap-3 p-4"
                >
                  <ClipboardPlus className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{item.NamaKebutuhan}</p>
                    <p className="font-mono text-xs text-muted-foreground">{item.Nomor}</p>
                    <div className="mt-2 flex gap-2">
                      <Badge variant={VARIAN_PRIORITAS[item.Prioritas]}>{item.Prioritas}</Badge>
                      <Badge variant={VARIAN_STATUS[item.Status]}>
                        {item.Status === 'MenungguPersetujuan' ? 'Menunggu' : item.Status}
                      </Badge>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
            <KontrolPaginasi
              meta={usulan.meta}
              onNavigasi={(page) =>
                navigasiHalaman(page, {
                  cari,
                  status: status === SEMUA ? '' : status,
                  prioritas: prioritas === SEMUA ? '' : prioritas,
                })
              }
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
