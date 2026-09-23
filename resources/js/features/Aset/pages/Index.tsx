import { Dispatch, FormEvent, SetStateAction, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ListFilter } from 'lucide-react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetFooter } from '@/components/ui/sheet';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { formatUang } from '@/lib/uang';
import type { Paginasi } from '@/types/global';
import type { Aset, FilterAset } from '@/features/Aset/types';
import type { KategoriAset } from '@/features/Aset/types';
import { VARIAN_BADGE_STATUS_ASET } from '@/features/Aset/status';
import type { Lokasi } from '@/features/Lokasi/types';
import { ruteAset } from '@/features/Aset/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { PemilihNomenklatur } from '@/features/Aset/components/PemilihNomenklatur';
import { opsiDari, TANPA_PILIHAN } from '@/lib/pilihan';
import { InputUang } from '@/components/shared/InputUang';

interface Props {
  aset: Paginasi<Aset>;
  filter: FilterAset;
  /** Batas sekali cetak, datang dari CetakLabelAsetRequest supaya tidak pernah berbeda. */
  maksLabel: number;
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
  kategoriAset: KategoriAset[];
  lokasi: Lokasi[];
}

const SEMUA = '__semua__';

function badgeStatus(status: Aset['Status']) {
  return <Badge variant={VARIAN_BADGE_STATUS_ASET[status]}>{status}</Badge>;
}

function MedanFilterAset({
  form,
  setForm,
  kategoriAset,
  lokasi,
}: {
  form: FilterAset;
  setForm: Dispatch<SetStateAction<FilterAset>>;
  kategoriAset: KategoriAset[];
  lokasi: Lokasi[];
}) {
  return (
    <>
      <div className="space-y-1.5">
        <Label>Cari</Label>
        <Input
          value={form.cari ?? ''}
          onChange={(e) => setForm((f) => ({ ...f, cari: e.target.value || undefined }))}
          placeholder="Nama, kode, atau nomor seri..."
        />
      </div>
      <div className="space-y-1.5">
        <Label>Kategori</Label>
        <Combobox
          nilai={form.kategoriAsetId ?? SEMUA}
          onPilih={(v) => setForm((f) => ({ ...f, kategoriAsetId: v === SEMUA ? undefined : v }))}
          opsi={[{ nilai: SEMUA, label: 'Semua' }, ...opsiDari(kategoriAset, (k) => k.Nama)]}
          placeholder="Semua"
        />
      </div>
      <div className="space-y-1.5">
        <Label>Lokasi</Label>
        <Combobox
          nilai={form.lokasiId ?? SEMUA}
          onPilih={(v) => setForm((f) => ({ ...f, lokasiId: v === SEMUA ? undefined : v }))}
          opsi={[{ nilai: SEMUA, label: 'Semua' }, ...opsiDari(lokasi, (l) => l.Nama)]}
          placeholder="Semua"
        />
      </div>
      <div className="space-y-1.5">
        <Label>Status</Label>
        <Combobox
          nilai={form.status ?? SEMUA}
          onPilih={(v) => setForm((f) => ({ ...f, status: v === SEMUA ? undefined : v }))}
          opsi={[
            { nilai: SEMUA, label: 'Semua' },
            ...['Aktif', 'Nonaktif', 'Dipinjam', 'Rusak', 'Diarsipkan'].map((s) => ({ nilai: s, label: s })),
          ]}
          placeholder="Semua"
        />
      </div>
    </>
  );
}

function jumlahFilterAktif(filter: FilterAset): number {
  return [filter.cari, filter.kategoriAsetId, filter.lokasiId, filter.status].filter(Boolean).length;
}

function DialogTambahAset({
  kategoriAset,
  lokasi,
  wajib,
}: {
  kategoriAset: KategoriAset[];
  lokasi: Lokasi[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    KategoriAsetId: kategoriAset[0]?.Id ?? '',
    LokasiId: SEMUA,
    KodeAset: '',
    Nama: '',
    NomorSeri: '',
    HargaPerolehan: '',
    AlkesAspakId: TANPA_PILIHAN,
    Status: 'Aktif',
    Kondisi: 'Baik',
    TingkatKritis: 'Normal',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      LokasiId: form.data.LokasiId === SEMUA ? null : form.data.LokasiId,
      AlkesAspakId: form.data.AlkesAspakId === TANPA_PILIHAN ? null : form.data.AlkesAspakId,
    };
    router.post(ruteAset.index, payload, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Daftarkan Aset</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Daftarkan Aset</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <BidangKode
                nilai={form.data.KodeAset}
                onUbah={(nilai) => form.setData('KodeAset', nilai)}
                galat={form.errors.KodeAset}
                label="Kode Aset"
                id="KodeAset"
              />
              <div className="space-y-2">
                <Label nama="Nama">Nama</Label>
                <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="KategoriAsetId">Kategori</Label>
                <Combobox
                  nilai={form.data.KategoriAsetId}
                  onPilih={(v) => form.setData('KategoriAsetId', v)}
                  opsi={opsiDari(kategoriAset, (k) => k.Nama)}
                />
                {form.errors.KategoriAsetId && (
                  <p className="text-sm text-destructive">{form.errors.KategoriAsetId}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label nama="LokasiId">Lokasi Awal</Label>
                <Combobox
                  nilai={form.data.LokasiId}
                  onPilih={(v) => form.setData('LokasiId', v)}
                  opsi={[{ nilai: SEMUA, label: 'Belum ditentukan' }, ...opsiDari(lokasi, (l) => l.Nama)]}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label nama="AlkesAspakId">Nomenklatur Alkes (ASPAK)</Label>
              <PemilihNomenklatur
                nilai={form.data.AlkesAspakId}
                onPilih={(v) => form.setData('AlkesAspakId', v)}
              />
              {form.errors.AlkesAspakId && (
                <p className="text-sm text-destructive">{form.errors.AlkesAspakId}</p>
              )}
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label nama="NomorSeri">Nomor Seri</Label>
                <Input
                  value={form.data.NomorSeri}
                  onChange={(e) => form.setData('NomorSeri', e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label nama="HargaPerolehan">Harga Perolehan</Label>
                <InputUang
                  value={form.data.HargaPerolehan}
                  onChange={(nilai) => form.setData('HargaPerolehan', nilai)}
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Simpan
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function AsetIndex({ aset, filter, maksLabel, wajib, kategoriAset, lokasi }: Props) {
  const [form, setForm] = useState<FilterAset>(filter);
  const [sheetFilterBuka, setSheetFilterBuka] = useState(false);
  // Paginasi memakai preserveState, jadi pilihan bertahan saat berpindah halaman.
  const [terpilih, setTerpilih] = useState<string[]>([]);

  const pilih = (id: string, aktif: boolean) => {
    setTerpilih((kini) => (aktif ? [...kini, id] : kini.filter((satu) => satu !== id)));
  };

  const idHalamanIni = aset.data.map((satu) => satu.Id);
  const semuaHalamanIniTerpilih =
    idHalamanIni.length > 0 && idHalamanIni.every((id) => terpilih.includes(id));

  const pilihHalamanIni = (aktif: boolean) => {
    setTerpilih((kini) =>
      aktif
        ? [...kini, ...idHalamanIni.filter((id) => !kini.includes(id))]
        : kini.filter((id) => !idHalamanIni.includes(id)),
    );
  };

  const terapkanFilter = (e?: FormEvent) => {
    e?.preventDefault();
    setSheetFilterBuka(false);
    router.get(ruteAset.index, { ...form }, { preserveState: true, preserveScroll: true });
  };

  const resetFilter = () => {
    setForm({});
    setSheetFilterBuka(false);
    router.get(ruteAset.index, {}, { preserveState: true, preserveScroll: true });
  };

  const jumlahAktif = jumlahFilterAktif(filter);

  return (
    <KerangkaAplikasi>
      <Head title="Aset" />
      <div className="space-y-4">
        <KepalaHalaman
          judul="Aset"
          deskripsi="Daftar induk aset organisasi -- identitas, lokasi, dan status."
          aksi={
            <>
              <TombolEkspor url={ruteAset.ekspor} filter={filter as Record<string, string>} />
              <DialogTambahAset kategoriAset={kategoriAset} lokasi={lokasi} wajib={wajib.aset} />
            </>
          }
        />

        {/* Mobile: filter di Sheet (DESIGN.md 13.8/33), bukan grid yang dipaksakan */}
        <div className="md:hidden">
          <Button variant="outline" onClick={() => setSheetFilterBuka(true)} className="relative">
            <ListFilter size={16} strokeWidth={1.75} />
            Filter
            {jumlahAktif > 0 && (
              <Badge variant="solid" className="ml-1 h-4 min-w-4 justify-center px-1 py-0 text-[10px]">
                {jumlahAktif}
              </Badge>
            )}
          </Button>
          <Sheet open={sheetFilterBuka} onOpenChange={setSheetFilterBuka}>
            <SheetContent side="bottom" className="max-h-[85vh] overflow-y-auto">
              <SheetHeader>
                <SheetTitle>Filter Aset</SheetTitle>
              </SheetHeader>
              <form onSubmit={terapkanFilter} className="space-y-4 px-4">
                <MedanFilterAset form={form} setForm={setForm} kategoriAset={kategoriAset} lokasi={lokasi} />
              </form>
              <SheetFooter className="flex-row">
                <Button type="button" variant="outline" className="flex-1" onClick={resetFilter}>
                  Reset
                </Button>
                <Button type="button" className="flex-1" onClick={terapkanFilter}>
                  Terapkan
                </Button>
              </SheetFooter>
            </SheetContent>
          </Sheet>
        </div>

        {/* Desktop/tablet: filter inline */}
        <form
          onSubmit={terapkanFilter}
          className="hidden grid-cols-2 gap-4 rounded-[9px] border border-border bg-card p-4 md:grid lg:grid-cols-5 [&>:first-child]:lg:col-span-2"
        >
          <MedanFilterAset form={form} setForm={setForm} kategoriAset={kategoriAset} lokasi={lokasi} />
          <div className="flex items-end gap-2 lg:col-span-5">
            <Button type="submit">Terapkan</Button>
            <Button type="button" variant="outline" onClick={resetFilter}>
              Reset
            </Button>
          </div>
        </form>

        {aset.data.length === 0 && (
          <div className="rounded-[9px] border border-border bg-card">
            <KeadaanKosong
              ilustrasi="/assets/3d/aset-qr.webp"
              judul="Belum ada aset."
              deskripsi="Aset yang terdaftar akan muncul di sini lengkap dengan lokasi dan status."
            />
          </div>
        )}

        {/* Mobile: card list (DESIGN.md 20/33 -- tabel lebar tidak dipaksakan ke layar sempit) */}
        {aset.data.length > 0 && (
          <div className="space-y-2 md:hidden">
            {aset.data.map((a) => (
              <button
                key={a.Id}
                type="button"
                onClick={() => router.visit(ruteAset.detail(a.Id))}
                className="block w-full rounded-[9px] border border-border bg-card p-4 text-left"
              >
                <div className="flex items-start justify-between gap-2">
                  <span className="font-mono text-xs text-muted-foreground">{a.KodeAset}</span>
                  {badgeStatus(a.Status)}
                </div>
                <div className="mt-0.5 font-medium text-foreground">{a.Nama}</div>
                <div className="text-sm text-muted-foreground">
                  {a.NamaLokasi ?? 'Lokasi belum diatur'} · {a.NamaKategoriAset ?? '—'}
                </div>
                <div className="mt-1 text-sm text-muted-foreground">Kondisi: {a.Kondisi}</div>
              </button>
            ))}
            <KontrolPaginasi
              meta={aset.meta}
              onNavigasi={(halaman) => navigasiHalaman(halaman, form as Record<string, string>)}
            />
          </div>
        )}

        {terpilih.length > 0 && (
          <div className="hidden flex-wrap items-center justify-between gap-3 rounded-[9px] border border-border bg-muted/40 px-4 py-3 md:flex">
            <p className="text-sm text-foreground">
              {terpilih.length} aset dipilih
              {terpilih.length > maksLabel && (
                <span className="text-destructive">
                  {' '}
                  — sekali cetak paling banyak {maksLabel}, kurangi dulu pilihannya.
                </span>
              )}
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setTerpilih([])}>
                Bersihkan pilihan
              </Button>
              <Button size="sm" disabled={terpilih.length > maksLabel} asChild={terpilih.length <= maksLabel}>
                {terpilih.length <= maksLabel ? (
                  <Link href={ruteAset.label(terpilih)}>Cetak Label</Link>
                ) : (
                  <span>Cetak Label</span>
                )}
              </Button>
            </div>
          </div>
        )}

        {/* Desktop/tablet: table */}
        {aset.data.length > 0 && (
          <div className="hidden rounded-[9px] border border-border bg-card md:block">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-10">
                    <Checkbox
                      checked={semuaHalamanIniTerpilih}
                      onCheckedChange={(nilai) => pilihHalamanIni(nilai === true)}
                      aria-label="Pilih semua aset di halaman ini"
                    />
                  </TableHead>
                  <TableHead>Nama</TableHead>
                  <TableHead>Kategori</TableHead>
                  <TableHead>Lokasi</TableHead>
                  <TableHead>Harga Perolehan</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {aset.data.map((a) => (
                  <TableRow
                    key={a.Id}
                    className="cursor-pointer"
                    onClick={() => router.visit(ruteAset.detail(a.Id))}
                  >
                    {/* Klik baris membuka detail, jadi kotak centangnya tidak boleh ikut memicunya. */}
                    <TableCell onClick={(e) => e.stopPropagation()}>
                      <Checkbox
                        checked={terpilih.includes(a.Id)}
                        onCheckedChange={(nilai) => pilih(a.Id, nilai === true)}
                        aria-label={`Pilih ${a.Nama}`}
                      />
                    </TableCell>
                    <TableCell>
                      <div className="font-medium text-foreground">{a.Nama}</div>
                      <div className="font-mono text-xs text-muted-foreground">{a.KodeAset}</div>
                    </TableCell>
                    <TableCell>{a.NamaKategoriAset ?? '—'}</TableCell>
                    <TableCell>{a.NamaLokasi ?? '—'}</TableCell>
                    <TableCell>{a.HargaPerolehan ? formatUang(a.HargaPerolehan, a.MataUang) : '—'}</TableCell>
                    <TableCell>{badgeStatus(a.Status)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            <KontrolPaginasi
              meta={aset.meta}
              onNavigasi={(halaman) => navigasiHalaman(halaman, form as Record<string, string>)}
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
