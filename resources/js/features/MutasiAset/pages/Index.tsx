import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { KontrolPaginasi, navigasiHalaman } from '@/components/shared/KontrolPaginasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import type { Paginasi } from '@/types/global';
import type { PermintaanMutasiAset, PilihanJenisMutasiAset } from '@/features/SiklusAset/types';
import { VARIAN_BADGE_STATUS_MUTASI } from '@/features/SiklusAset/status';
import type { Lokasi } from '@/features/Lokasi/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import { ruteMutasiAset } from '@/features/MutasiAset/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';
import { opsiDari } from '@/lib/pilihan';

interface Props {
  permintaan: Paginasi<PermintaanMutasiAset>;
  filter: { status?: string };
  lokasi: Lokasi[];
  unitOrganisasi: UnitOrganisasi[];
  /** Pilihan jenis mutasi, dibaca dari enum di server. */
  daftarJenis: PilihanJenisMutasiAset[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}

const SEMUA = '__semua__';
const DAFTAR_STATUS = ['Draft', 'Menunggu', 'Disetujui', 'Ditolak', 'Dibatalkan', 'Selesai'];

function DialogBuatMutasi({
  lokasi,
  unitOrganisasi,
  daftarJenis,
  wajib,
}: {
  lokasi: Lokasi[];
  unitOrganisasi: UnitOrganisasi[];
  daftarJenis: PilihanJenisMutasiAset[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    JenisMutasi: 'AntarLokasi',
    LokasiTujuanId: SEMUA,
    UnitTujuanId: SEMUA,
    Alasan: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      LokasiTujuanId: form.data.LokasiTujuanId === SEMUA ? null : form.data.LokasiTujuanId,
      UnitTujuanId: form.data.UnitTujuanId === SEMUA ? null : form.data.UnitTujuanId,
    };
    router.post(ruteMutasiAset.index, payload, { onSuccess: () => setBuka(false) });
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Buat Permintaan Mutasi</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Buat Permintaan Mutasi</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-1.5">
              <Label nama="JenisMutasi">Jenis Mutasi</Label>
              <Select value={form.data.JenisMutasi} onValueChange={(v) => form.setData('JenisMutasi', v)}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {daftarJenis.map((j) => (
                    <SelectItem key={j.nilai} value={j.nilai}>
                      {j.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label nama="LokasiTujuanId">Lokasi Tujuan</Label>
              <Combobox
                nilai={form.data.LokasiTujuanId}
                onPilih={(v) => form.setData('LokasiTujuanId', v)}
                opsi={[{ nilai: SEMUA, label: 'Tidak diubah' }, ...opsiDari(lokasi, (l) => l.Nama)]}
              />
            </div>
            <div className="space-y-1.5">
              <Label nama="UnitTujuanId">Unit Tujuan</Label>
              <Combobox
                nilai={form.data.UnitTujuanId}
                onPilih={(v) => form.setData('UnitTujuanId', v)}
                opsi={[{ nilai: SEMUA, label: 'Tidak diubah' }, ...opsiDari(unitOrganisasi, (u) => u.Nama)]}
              />
            </div>
            <p className="text-sm text-muted-foreground">
              Minimal salah satu tujuan (lokasi/unit) harus diisi. Daftar aset dilengkapi setelah draft
              dibuat.
            </p>
            {form.data.JenisMutasi === 'Reposisi' && (
              <p className="text-sm text-muted-foreground">
                Reposisi memindahkan aset antar ruangan di dalam unit yang sama, jadi lokasi tujuan
                wajib dan unit tujuan dibiarkan tidak diubah.
              </p>
            )}
            {form.data.JenisMutasi === 'Akuisisi' && (
              <p className="text-sm text-muted-foreground">
                Akuisisi mencatat aset yang masuk menjadi tanggung jawab sebuah unit, jadi unit tujuan
                wajib diisi.
              </p>
            )}
            {form.errors.LokasiTujuanId && (
              <p className="text-sm text-destructive">{form.errors.LokasiTujuanId}</p>
            )}
            <DialogFooter>
              <Button type="submit" disabled={form.processing}>
                Buat Draft
              </Button>
            </DialogFooter>
          </form>
        </AturanWajibProvider>
      </DialogContent>
    </Dialog>
  );
}

export default function MutasiAsetIndex({
  permintaan,
  filter,
  lokasi,
  unitOrganisasi,
  daftarJenis,
  wajib,
}: Props) {
  const [status, setStatus] = useState(filter.status ?? SEMUA);

  const terapkanFilter = (v: string) => {
    setStatus(v);
    router.get(ruteMutasiAset.index, v === SEMUA ? {} : { status: v }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  return (
    <KerangkaAplikasi>
      <Head title="Mutasi Aset" />
      <div className="space-y-4">
        <KepalaHalaman
          judul="Mutasi Aset"
          deskripsi="Permintaan perpindahan lokasi/unit aset -- draft, persetujuan, sampai eksekusi."
          aksi={
            <>
              <TombolEkspor url="/mutasi-aset/ekspor" filter={filter as Record<string, string>} />
              <DialogBuatMutasi
                lokasi={lokasi}
                unitOrganisasi={unitOrganisasi}
                daftarJenis={daftarJenis}
                wajib={wajib.mutasi}
              />
            </>
          }
        />

        <div className="w-56 space-y-1.5">
          <Label>Status</Label>
          <Select value={status} onValueChange={terapkanFilter}>
            <SelectTrigger>
              <SelectValue placeholder="Semua" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={SEMUA}>Semua</SelectItem>
              {DAFTAR_STATUS.map((s) => (
                <SelectItem key={s} value={s}>
                  {s}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {permintaan.data.length === 0 && (
          <div className="rounded-[9px] border border-border bg-card">
            <KeadaanKosong
              judul="Belum ada permintaan mutasi."
              deskripsi="Permintaan perpindahan aset akan muncul di sini."
            />
          </div>
        )}

        {permintaan.data.length > 0 && (
          <div className="rounded-[9px] border border-border bg-card">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nomor</TableHead>
                  <TableHead>Jenis</TableHead>
                  <TableHead>Tujuan</TableHead>
                  <TableHead>Diminta Oleh</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {permintaan.data.map((p) => (
                  <TableRow
                    key={p.Id}
                    className="cursor-pointer"
                    onClick={() => router.visit(ruteMutasiAset.detail(p.Id))}
                  >
                    <TableCell className="font-mono text-xs">{p.Nomor}</TableCell>
                    <TableCell>
                      {daftarJenis.find((j) => j.nilai === p.JenisMutasi)?.label ?? p.JenisMutasi}
                    </TableCell>
                    <TableCell>{p.NamaLokasiTujuan ?? p.NamaUnitTujuan ?? '—'}</TableCell>
                    <TableCell>{p.NamaDimintaOleh ?? '—'}</TableCell>
                    <TableCell>
                      <Badge variant={VARIAN_BADGE_STATUS_MUTASI[p.Status]}>{p.Status}</Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            <KontrolPaginasi
              meta={permintaan.meta}
              onNavigasi={(halaman) => navigasiHalaman(halaman, filter as Record<string, string>)}
            />
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
