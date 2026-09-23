import { type FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Search, ShoppingCart } from 'lucide-react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
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
import type { Paginasi } from '@/types/global';
import type {
  PermintaanPembelian,
  PrioritasPermintaanPembelian,
  StatusPermintaanPembelian,
} from '@/features/PermintaanPembelian/types';
import { formatUang } from '@/lib/uang';
import { rutePermintaanPembelian } from '@/features/PermintaanPembelian/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { tanggalHariIni } from '@/lib/waktu';

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
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

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
  wajib,
}: Omit<Props, 'permintaan' | 'filter' | 'wajib'> & { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    UnitOrganisasiId: TANPA_PILIHAN,
    RencanaPengadaanId: TANPA_PILIHAN,
    PosAnggaranId: '',
    TanggalPermintaan: tanggalHariIni(),
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
      UnitOrganisasiId: data.UnitOrganisasiId === TANPA_PILIHAN ? null : data.UnitOrganisasiId,
      RencanaPengadaanId: data.RencanaPengadaanId === TANPA_PILIHAN ? null : data.RencanaPengadaanId,
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
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="TanggalPermintaan" htmlFor="TanggalPermintaan">
                  Tanggal Permintaan
                </Label>
                <DatePicker
                  value={form.data.TanggalPermintaan}
                  onChange={(nilai) => form.setData('TanggalPermintaan', nilai)}
                  id="TanggalPermintaan"
                />
                {form.errors.TanggalPermintaan && (
                  <p className="text-sm text-destructive">{form.errors.TanggalPermintaan}</p>
                )}
              </div>
              <div className="space-y-1.5">
                <Label nama="TanggalDibutuhkan" htmlFor="TanggalDibutuhkan">
                  Dibutuhkan
                </Label>
                <DatePicker
                  value={form.data.TanggalDibutuhkan}
                  onChange={(nilai) => form.setData('TanggalDibutuhkan', nilai)}
                  id="TanggalDibutuhkan"
                />
                {form.errors.TanggalDibutuhkan && (
                  <p className="text-sm text-destructive">{form.errors.TanggalDibutuhkan}</p>
                )}
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="PosAnggaranId">Pos Anggaran</Label>
              <Combobox
                nilai={form.data.PosAnggaranId}
                onPilih={(value) => form.setData('PosAnggaranId', value)}
                opsi={opsiDari(posAnggaran, (item) => `${item.Kode} — ${item.Nama}`)}
                placeholder="Pilih pos anggaran aktif"
              />
              {form.errors.PosAnggaranId && (
                <p className="text-sm text-destructive">{form.errors.PosAnggaranId}</p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label nama="RencanaPengadaanId">Rencana Pengadaan</Label>
              <Combobox
                nilai={form.data.RencanaPengadaanId}
                onPilih={pilihRencana}
                opsi={[
                  opsiKosong('Tanpa rencana'),
                  ...opsiDari(rencana, (item) => `${item.Nomor} — ${item.Nama}`),
                ]}
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label nama="UnitOrganisasiId">Unit Organisasi</Label>
                <Combobox
                  nilai={form.data.UnitOrganisasiId}
                  onPilih={(value) => form.setData('UnitOrganisasiId', value)}
                  opsi={[opsiKosong('Tanpa unit'), ...opsiDari(unitOrganisasi, (item) => item.Nama)]}
                />
              </div>
              <div className="space-y-1.5">
                <Label nama="Prioritas">Prioritas</Label>
                <Select
                  value={form.data.Prioritas}
                  onValueChange={(value) => form.setData('Prioritas', value)}
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
              <Label nama="Alasan" htmlFor="Alasan">
                Alasan
              </Label>
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
        </AturanWajibProvider>
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
  wajib,
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
    <KerangkaAplikasi>
      <Head title="Permintaan Pembelian" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Permintaan Pembelian"
          deskripsi="Draft kebutuhan, validasi sisa anggaran, dan pengajuan persetujuan."
          aksi={
            <>
              <TombolEkspor url={rutePermintaanPembelian.ekspor} filter={filter as Record<string, string>} />
              <DialogBuatPermintaan
                unitOrganisasi={unitOrganisasi}
                rencana={rencana}
                posAnggaran={posAnggaran}
                wajib={wajib.permintaan}
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
          <KeadaanKosong
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
            <KontrolPaginasi
              meta={permintaan.meta}
              onNavigasi={(halaman) =>
                navigasiHalaman(halaman, { cari, status: status === SEMUA ? '' : status })
              }
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
