import { Head } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { BarisKosong, KartuAngka, durasi, tanggal } from '@/components/shared/riwayat';
import type { Pengguna, RingkasanPengguna } from '@/features/Pengguna/types';
import { TabBebanKerja } from '@/features/Pengguna/components/TabBebanKerja';
import { TabAktivitas } from '@/features/Pengguna/components/TabAktivitas';

interface Props {
  pengguna: Pengguna;
  ringkasan: RingkasanPengguna;
}

export default function PenggunaShow({ pengguna, ringkasan }: Props) {
  return (
    <KerangkaAplikasi>
      <Head title={pengguna.Nama} />
      <KepalaHalaman
        className="mb-6"
        judul={pengguna.Nama}
        labelBreadcrumb={pengguna.Nama}
        lencana={<Badge variant={pengguna.Status === 'Aktif' ? 'sukses' : 'netral'}>{pengguna.Status}</Badge>}
        deskripsi={
          <>
            {pengguna.Email}
            {pengguna.Jabatan && ` · ${pengguna.Jabatan}`}
            {pengguna.NomorPegawai && ` · NIP ${pengguna.NomorPegawai}`}
          </>
        }
        meta={
          pengguna.Peran.length > 0 ? (
            <div className="flex flex-wrap gap-1.5">
              {pengguna.Peran.map((satu) => (
                <Badge key={satu.Id} variant="outline">
                  {satu.NamaPeran ?? 'Peran'}
                </Badge>
              ))}
            </div>
          ) : undefined
        }
      />

      <div className="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <KartuAngka label="Penugasan Berjalan" nilai={ringkasan.PenugasanBerjalan} />
        <KartuAngka label="Total Waktu Kerja" nilai={durasi(ringkasan.TotalMenitKerja)} />
        <KartuAngka label="Aset Ditanggung" nilai={ringkasan.AsetDitanggung} />
        <KartuAngka
          label="Peran"
          nilai={ringkasan.JumlahPeran}
          catatan={pengguna.JenisPengguna === 'Eksternal' ? 'pengguna eksternal' : undefined}
        />
      </div>

      <Tabs defaultValue="beban-kerja">
        <TabsList>
          <TabsTrigger value="beban-kerja">Beban Kerja</TabsTrigger>
          <TabsTrigger value="aktivitas">Aktivitas</TabsTrigger>
          <TabsTrigger value="peran">Peran</TabsTrigger>
        </TabsList>
        <TabsContent value="beban-kerja">
          <TabBebanKerja pengguna={pengguna} />
        </TabsContent>
        <TabsContent value="aktivitas">
          <TabAktivitas pengguna={pengguna} />
        </TabsContent>
        <TabsContent value="peran">
          {pengguna.Peran.length === 0 ? (
            <BarisKosong teks="Pengguna ini belum diberi peran, jadi belum dapat membuka apa pun." />
          ) : (
            <ul className="divide-y divide-border">
              {pengguna.Peran.map((satu) => (
                <li key={satu.Id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                  <span className="text-sm font-medium text-foreground">{satu.NamaPeran ?? 'Peran'}</span>
                  <span className="text-xs text-muted-foreground">
                    {satu.BerlakuMulai || satu.BerlakuSampai
                      ? `${tanggal(satu.BerlakuMulai)} s/d ${tanggal(satu.BerlakuSampai)}`
                      : 'berlaku tanpa batas waktu'}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </TabsContent>
      </Tabs>
    </KerangkaAplikasi>
  );
}
