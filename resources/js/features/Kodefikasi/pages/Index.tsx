import { FormEvent, useMemo, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Combobox } from '@/components/ui/combobox';
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
import { DeretStatistik, KartuStatistik } from '@/components/shared/KartuStatistik';
import { adaPenyaringAktif, type FilterDaftar } from '@/components/data-table/daftar-server';
import { useIzin } from '@/hooks/use-izin';
import type { Paginasi } from '@/types/global';
import { ruteKodefikasi } from '@/features/Kodefikasi/api';
import type { KodeBarang, PilihanStandar, RingkasanKodefikasi } from '@/features/Kodefikasi/types';

interface Props {
  standar: string;
  daftarStandar: PilihanStandar[];
  kodeBarang: Paginasi<KodeBarang>;
  filter: FilterDaftar;
  ringkasan: RingkasanKodefikasi;
}

function DialogImpor({ standar, label }: { standar: string; label: string }) {
  const [buka, setBuka] = useState(false);
  const [berkas, setBerkas] = useState<File | null>(null);
  const [mengirim, setMengirim] = useState(false);
  const masukan = useRef<HTMLInputElement>(null);

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    if (!berkas) return;

    setMengirim(true);
    router.post(
      ruteKodefikasi.imporKatalog,
      { Berkas: berkas, Standar: standar },
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
          <DialogTitle>Impor Katalog {label}</DialogTitle>
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
              <span className="font-mono">Uraian</span>. Baris yang kodenya tidak sesuai format standar ini
              ditolak dan jumlahnya dilaporkan — kode salah bentuk baru ketahuan saat sudah masuk laporan,
              jadi lebih baik ditahan di sini.
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

export default function KodefikasiIndex({ standar, daftarStandar, kodeBarang, filter, ringkasan }: Props) {
  const { boleh } = useIzin();
  const bolehKelola = boleh('Aset.Ubah');
  const label = daftarStandar.find((s) => s.nilai === standar)?.label ?? standar;

  const gantiStandar = (nilai: string) => {
    router.get(ruteKodefikasi.index, { standar: nilai }, { preserveState: false });
  };

  const columns = useMemo<ColumnDef<KodeBarang>[]>(
    () => [
      {
        accessorKey: 'Kode',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Kode Barang" />,
        cell: ({ row }) => <span className="font-mono text-sm">{row.original.Kode}</span>,
        meta: { label: 'Kode Barang' },
      },
      {
        accessorKey: 'Uraian',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Uraian" />,
        meta: { label: 'Uraian' },
      },
      {
        accessorKey: 'JumlahAset',
        enableSorting: false,
        header: 'Aset Terdaftar',
        cell: ({ row }) =>
          row.original.JumlahAset > 0 ? (
            <Badge variant="sukses">{row.original.JumlahAset}</Badge>
          ) : (
            <Badge variant="netral">belum</Badge>
          ),
        meta: { label: 'Aset Terdaftar' },
      },
    ],
    [],
  );

  return (
    <KerangkaAplikasi>
      <Head title="Kodefikasi Barang" />
      <KepalaHalaman
        judul="Kodefikasi Barang"
        deskripsi="Kode barang milik negara dan daerah, beserta NUP dan kode registrasinya."
        className="mb-5"
        aksi={
          <>
            {bolehKelola && <DialogImpor standar={standar} label={label} />}
            <Button variant="outline" asChild>
              <a href={`${ruteKodefikasi.ekspor}?standar=${standar}`}>Ekspor</a>
            </Button>
          </>
        }
      />

      <div className="mb-4 w-full space-y-1.5 sm:w-64">
        <Label nama="standar">Standar</Label>
        <Combobox
          nilai={standar}
          onPilih={gantiStandar}
          opsi={daftarStandar.map((s) => ({ nilai: s.nilai, label: s.label }))}
        />
      </div>

      <DeretStatistik kolom={3} className="mb-5">
        <KartuStatistik menyatu label="Kode di Katalog" nilai={ringkasan.JumlahKode} />
        <KartuStatistik menyatu label="Aset Sudah Berkode" nilai={ringkasan.AsetBerkode} />
        <KartuStatistik
          menyatu
          label="Aset Belum Berkode"
          nilai={ringkasan.AsetBelumBerkode}
          keterangan={ringkasan.AsetBelumBerkode > 0 ? 'belum dapat dilaporkan' : 'seluruhnya siap'}
        />
      </DeretStatistik>

      <DataTable
        columns={columns}
        data={kodeBarang.data}
        server={{ meta: kodeBarang.meta, filter }}
        pencarianPlaceholder="Cari kode atau uraian..."
        pesanKosong={
          adaPenyaringAktif(filter)
            ? 'Tidak ada kode yang cocok.'
            : 'Katalog masih kosong. Impor daftar kodefikasi resmi terlebih dahulu.'
        }
      />
    </KerangkaAplikasi>
  );
}
