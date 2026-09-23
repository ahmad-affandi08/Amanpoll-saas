import { FormEvent, useMemo, useRef, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Combobox } from '@/components/ui/combobox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { DataTableColumnHeader } from '@/components/data-table/DataTableColumnHeader';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { KartuAngka } from '@/components/shared/riwayat';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { opsiDari, opsiKosong, TANPA_PILIHAN } from '@/lib/pilihan';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { useIzin } from '@/hooks/use-izin';
import type { Paginasi } from '@/types/global';
import { ruteAspak } from '@/features/Aspak/api';
import type { AlkesAspak, PemetaanAspak, RingkasanAspak } from '@/features/Aspak/types';

interface Acuan {
  Id: string;
  Nama: string;
}

interface Props {
  alkes: Paginasi<AlkesAspak>;
  filter: FilterDaftar;
  pemetaan: PemetaanAspak[];
  kategoriAset: Acuan[];
  modelAset: Acuan[];
  ringkasan: RingkasanAspak;
  /** Judul kolom berkas ekspor, menurut profil yang berlaku. */
  kolomEkspor: string[];
  wajib: Record<string, AturanWajib>;
}

function DialogImporKatalog() {
  const [buka, setBuka] = useState(false);
  const [berkas, setBerkas] = useState<File | null>(null);
  const [mengirim, setMengirim] = useState(false);
  const masukan = useRef<HTMLInputElement>(null);

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    if (!berkas) return;

    setMengirim(true);
    router.post(
      ruteAspak.imporKatalog,
      { Berkas: berkas },
      {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          setBuka(false);
          setBerkas(null);
          if (masukan.current) masukan.current.value = '';
        },
        onFinish: () => setMengirim(false),
      },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline">Impor Katalog</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Impor Katalog Alkes ASPAK</DialogTitle>
        </DialogHeader>
        <form onSubmit={kirim} className="space-y-4">
          <div className="space-y-2">
            <Label nama="Berkas" wajib>
              Berkas CSV
            </Label>
            <Input
              ref={masukan}
              type="file"
              accept=".csv,text/csv,text/plain"
              onChange={(e) => setBerkas(e.target.files?.[0] ?? null)}
            />
            <p className="text-sm text-muted-foreground">
              Berisi kolom <span className="font-mono">Kode</span> dan{' '}
              <span className="font-mono">Nama</span>; <span className="font-mono">Kelompok</span> dan{' '}
              <span className="font-mono">Satuan</span> opsional. Kode yang sudah ada akan diperbarui,
              bukan digandakan.
            </p>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={!berkas || mengirim}>
              Impor
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DialogPemetaan({
  alkes,
  kategoriAset,
  modelAset,
  wajib,
}: {
  alkes: AlkesAspak[];
  kategoriAset: Acuan[];
  modelAset: Acuan[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    AlkesAspakId: TANPA_PILIHAN,
    KategoriAsetId: TANPA_PILIHAN,
    ModelAsetId: TANPA_PILIHAN,
  });

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    router.post(
      ruteAspak.pemetaan,
      {
        AlkesAspakId: form.data.AlkesAspakId === TANPA_PILIHAN ? null : form.data.AlkesAspakId,
        KategoriAsetId: form.data.KategoriAsetId === TANPA_PILIHAN ? null : form.data.KategoriAsetId,
        ModelAsetId: form.data.ModelAsetId === TANPA_PILIHAN ? null : form.data.ModelAsetId,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          setBuka(false);
          form.reset();
        },
      },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Pemetaan</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Petakan ke Kode ASPAK</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={kirim} className="space-y-4">
            <div className="space-y-2">
              <Label nama="AlkesAspakId">Alkes ASPAK</Label>
              <Combobox
                nilai={form.data.AlkesAspakId}
                onPilih={(v) => form.setData('AlkesAspakId', v)}
                opsi={opsiDari(
                  alkes,
                  (a) => `${a.Kode} — ${a.Nama}`,
                  (a) => a.Kelompok,
                )}
              />
            </div>
            <p className="text-sm text-muted-foreground">
              Isi salah satu saja. Model aset lebih spesifik daripada kategori, dan bila keduanya
              cocok maka pemetaan model yang dipakai.
            </p>
            <div className="space-y-2">
              <Label nama="KategoriAsetId">Kategori Aset</Label>
              <Combobox
                nilai={form.data.KategoriAsetId}
                onPilih={(v) => form.setData('KategoriAsetId', v)}
                opsi={[opsiKosong('Tidak dipetakan lewat kategori'), ...opsiDari(kategoriAset, (k) => k.Nama)]}
              />
            </div>
            <div className="space-y-2">
              <Label nama="ModelAsetId">Model Aset</Label>
              <Combobox
                nilai={form.data.ModelAsetId}
                onPilih={(v) => form.setData('ModelAsetId', v)}
                opsi={[opsiKosong('Tidak dipetakan lewat model'), ...opsiDari(modelAset, (m) => m.Nama)]}
              />
            </div>
            <DialogFooter>
              <Button type="submit">Simpan</Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function AspakIndex({
  alkes,
  filter,
  pemetaan,
  kategoriAset,
  modelAset,
  ringkasan,
  kolomEkspor,
  wajib,
}: Props) {
  const konfirmasi = useKonfirmasi();
  const { boleh } = useIzin();
  const bolehKelola = boleh('Aspak.Kelola');

  const hapusPemetaan = async (satu: PemetaanAspak) => {
    if (
      !(await konfirmasi({
        judul: `Hapus pemetaan ke "${satu.KodeAlkes}"?`,
        deskripsi: 'Aset yang bergantung padanya tidak akan ikut dalam ekspor ASPAK berikutnya.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteAspak.pemetaanDetail(satu.Id), { preserveScroll: true });
  };

  const kolomAlkes = useMemo<ColumnDef<AlkesAspak>[]>(
    () => [
      {
        accessorKey: 'Kode',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
        cell: ({ row }) => <span className="font-mono text-sm">{row.original.Kode}</span>,
        meta: { label: 'Kode' },
      },
      {
        accessorKey: 'Nama',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nama Alat" />,
        meta: { label: 'Nama Alat' },
      },
      {
        accessorKey: 'Kelompok',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kelompok" />,
        cell: ({ row }) => row.original.Kelompok ?? '—',
        meta: { label: 'Kelompok' },
      },
      {
        accessorKey: 'JumlahPemetaan',
        enableSorting: false,
        header: 'Dipetakan',
        cell: ({ row }) =>
          row.original.JumlahPemetaan > 0 ? (
            <Badge variant="sukses">{row.original.JumlahPemetaan}</Badge>
          ) : (
            <Badge variant="netral">belum</Badge>
          ),
        meta: { label: 'Dipetakan' },
      },
    ],
    [],
  );

  return (
    <KerangkaAplikasi>
      <Head title="ASPAK" />
      <KepalaHalaman
        judul="ASPAK"
        deskripsi="Pertukaran data sarana, prasarana, dan alat kesehatan dengan Kemenkes."
        className="mb-6"
        aksi={
          <>
            {bolehKelola && <DialogImporKatalog />}
            <Button variant="outline" asChild>
              <a href={ruteAspak.ekspor}>Ekspor Aset</a>
            </Button>
          </>
        }
      />

      <div className="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <KartuAngka label="Alkes di Katalog" nilai={ringkasan.JumlahAlkes} />
        <KartuAngka label="Pemetaan Aktif" nilai={ringkasan.JumlahPemetaan} />
        <KartuAngka
          label="Aset Siap Diekspor"
          nilai={ringkasan.AsetTerpetakan}
          catatan={`${ringkasan.AsetBelumTerpetakan} aset belum terpetakan`}
        />
        <KartuAngka
          label="Lokasi Tanpa Kode Ruang"
          nilai={ringkasan.LokasiTanpaKodeRuang}
          catatan={
            ringkasan.LokasiTanpaKodeRuang > 0 ? 'kolom ruang akan kosong' : 'seluruh lokasi siap'
          }
        />
      </div>

      <Tabs defaultValue="katalog">
        <TabsList>
          <TabsTrigger value="katalog">Katalog Alkes</TabsTrigger>
          <TabsTrigger value="pemetaan">Pemetaan</TabsTrigger>
          <TabsTrigger value="ekspor">Format Ekspor</TabsTrigger>
        </TabsList>

        <TabsContent value="katalog">
          <DataTable
            columns={kolomAlkes}
            data={alkes.data}
            server={{ meta: alkes.meta, filter }}
            pencarianPlaceholder="Cari kode atau nama alat..."
            pesanKosong={
              adaPenyaringAktif(filter)
                ? 'Tidak ada alkes yang cocok.'
                : 'Katalog masih kosong. Impor berkas katalog ASPAK terlebih dahulu.'
            }
          />
        </TabsContent>

        <TabsContent value="pemetaan">
          <div className="mb-4 flex justify-end">
            {bolehKelola && (
              <DialogPemetaan
                alkes={alkes.data}
                kategoriAset={kategoriAset}
                modelAset={modelAset}
                wajib={wajib.pemetaan}
              />
            )}
          </div>
          {pemetaan.length === 0 ? (
            <p className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
              Belum ada pemetaan. Tanpa pemetaan, tidak ada aset yang dapat diekspor ke ASPAK.
            </p>
          ) : (
            <div className="divide-y rounded-lg border">
              {pemetaan.map((satu) => (
                <div key={satu.Id} className="flex items-center justify-between gap-4 p-3">
                  <div className="min-w-0">
                    <div className="truncate font-medium text-foreground">
                      {satu.NamaKategoriAset ?? satu.NamaModelAset}
                      <Badge variant="outline" className="ml-2">
                        {satu.NamaModelAset ? 'Model' : 'Kategori'}
                      </Badge>
                    </div>
                    <div className="truncate text-sm text-muted-foreground">
                      <span className="font-mono">{satu.KodeAlkes}</span> — {satu.NamaAlkes}
                    </div>
                  </div>
                  {bolehKelola && (
                    <Button variant="ghost" size="sm" onClick={() => hapusPemetaan(satu)}>
                      Hapus
                    </Button>
                  )}
                </div>
              ))}
            </div>
          )}
        </TabsContent>

        <TabsContent value="ekspor">
          <div className="space-y-4 rounded-lg border p-4">
            <div>
              <h3 className="font-medium text-foreground">Kolom berkas ekspor</h3>
              <p className="text-sm text-muted-foreground">
                Urutan dan judul kolom mengikuti profil pada pengaturan organisasi (kunci{' '}
                <span className="font-mono">Aspak.ProfilKolom</span>). Cocokkan dengan templat ASPAK
                yang sedang berlaku sebelum mengunggah.
              </p>
            </div>
            <ol className="grid gap-1 text-sm sm:grid-cols-2">
              {kolomEkspor.map((judul, urutan) => (
                <li key={judul} className="flex gap-2">
                  <span className="w-6 shrink-0 text-right text-muted-foreground">{urutan + 1}.</span>
                  <span className="font-mono">{judul}</span>
                </li>
              ))}
            </ol>
            <p className="text-sm text-muted-foreground">
              Aset yang belum dipetakan tidak diikutkan: ASPAK menolak baris tanpa kode alat.
            </p>
          </div>
        </TabsContent>
      </Tabs>
    </KerangkaAplikasi>
  );
}
