import { useEffect, useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import {
  CheckCircle2,
  CloudOff,
  Download,
  ListChecks,
  MessageSquarePlus,
  RefreshCw,
  Timer,
  TriangleAlert,
} from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { cn } from '@/lib/utils';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { VARIAN_STATUS_PERINTAH_KERJA } from '@/features/PerintahKerja/status';
import type { StatusPerintahKerja } from '@/features/PerintahKerja/types';
import type { DaftarPeriksaOffline, MutasiOffline, PenugasanOffline } from '@/features/Sinkronisasi/types';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { DialogKerjakan } from '@/features/Sinkronisasi/components/DialogKerjakan';
import { DialogDaftarPeriksa } from '@/features/Sinkronisasi/components/DialogDaftarPeriksa';
import { DialogKonflik } from '@/features/Sinkronisasi/components/DialogKonflik';

const VARIAN_STATUS_MUTASI = {
  Menunggu: 'perhatian',
  Diproses: 'proses',
  Selesai: 'sukses',
  Gagal: 'bahaya',
  Konflik: 'bahaya',
  Dibatalkan: 'netral',
} as const;

function waktuLokal(nilai: string | null | undefined): string {
  return nilai ? new Date(nilai).toLocaleString('id-ID') : '—';
}

/** Ruang kerja teknisi offline (FASE 20.05). */
export default function SinkronisasiTeknisi() {
  const {
    status,
    daring,
    paket,
    antrian,
    jumlahBelumTersinkron,
    jumlahKonflik,
    memuat,
    muatPaket,
    antrikan,
    dorong,
    selesaikanKonflik,
  } = useSinkronisasiOffline();

  const [pekerjaanTerpilih, setPekerjaanTerpilih] = useState<PenugasanOffline | null>(null);
  const [daftarPeriksaTerpilih, setDaftarPeriksaTerpilih] = useState<DaftarPeriksaOffline | null>(null);
  const [konflikTerpilih, setKonflikTerpilih] = useState<MutasiOffline | null>(null);

  // Paket ditarik sekali saat halaman dibuka dan masih ada koneksi; ketika
  // offline, isi terakhir yang tersimpan di perangkat yang dipakai.
  useEffect(() => {
    if (!paket && daring) {
      void muatPaket();
    }
  }, [paket, daring, muatPaket]);

  const asetPerId = useMemo(() => new Map((paket?.Aset ?? []).map((aset) => [aset.Id, aset])), [paket?.Aset]);

  const daftarPeriksaPerPekerjaan = useMemo(() => {
    const peta = new Map<string, DaftarPeriksaOffline[]>();
    for (const item of paket?.DaftarPeriksa ?? []) {
      if (!item.PerintahKerjaId) continue;
      peta.set(item.PerintahKerjaId, [...(peta.get(item.PerintahKerjaId) ?? []), item]);
    }
    return peta;
  }, [paket?.DaftarPeriksa]);

  const konflik = antrian.filter((m) => m.Status === 'Konflik');
  const gagal = antrian.filter((m) => m.Status === 'Gagal');

  return (
    <KerangkaAplikasi>
      <Head title="Mode Teknisi (Offline)" />
      <div className="space-y-6">
        <KepalaHalaman
          judul="Mode Teknisi"
          deskripsi="Penugasan, daftar periksa, dan catatan lapangan yang tetap dapat dikerjakan tanpa sinyal."
          aksi={
            <>
              <div className="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" onClick={() => void dorong()} disabled={!daring}>
                  <RefreshCw className={cn('size-4', status === 'Menyinkronkan' && 'animate-spin')} />
                  Kirim antrean
                </Button>
                <Button size="sm" onClick={() => void muatPaket()} disabled={!daring || memuat}>
                  <Download className="size-4" />
                  Perbarui paket
                </Button>
              </div>
            </>
          }
        />

        <div className="grid gap-3 sm:grid-cols-3">
          <RingkasanKartu
            ikon={daring ? CheckCircle2 : CloudOff}
            label={daring ? 'Terhubung' : 'Offline'}
            nilai={paket ? `Paket ${waktuLokal(paket.DibuatPada)}` : 'Paket belum tersedia'}
            nada={daring ? 'baik' : 'perhatian'}
          />
          <RingkasanKartu
            ikon={Timer}
            label="Belum tersinkron"
            nilai={`${jumlahBelumTersinkron} perubahan`}
            nada={jumlahBelumTersinkron > 0 ? 'perhatian' : 'netral'}
          />
          <RingkasanKartu
            ikon={TriangleAlert}
            label="Perlu keputusan"
            nilai={`${jumlahKonflik} konflik`}
            nada={jumlahKonflik > 0 ? 'bahaya' : 'netral'}
          />
        </div>

        {memuat && !paket ? (
          <div className="space-y-3">
            <Skeleton className="h-24 w-full animate-pulse" />
            <Skeleton className="h-24 w-full animate-pulse" />
            <Skeleton className="h-24 w-full animate-pulse" />
          </div>
        ) : (
          <Tabs defaultValue="penugasan">
            <TabsList>
              <TabsTrigger value="penugasan">
                Penugasan
                <Badge variant="netral">{paket?.Penugasan.length ?? 0}</Badge>
              </TabsTrigger>
              <TabsTrigger value="aset">
                Aset
                <Badge variant="netral">{paket?.Aset.length ?? 0}</Badge>
              </TabsTrigger>
              <TabsTrigger value="antrean">
                Antrean
                <Badge variant={konflik.length > 0 ? 'bahaya' : 'netral'}>{antrian.length}</Badge>
              </TabsTrigger>
            </TabsList>

            <TabsContent value="penugasan" className="space-y-3">
              {(paket?.Penugasan.length ?? 0) === 0 ? (
                <KeadaanKosong
                  judul="Belum ada penugasan aktif."
                  deskripsi="Pekerjaan yang ditugaskan kepada Anda akan muncul di sini dan ikut tersimpan di perangkat."
                />
              ) : (
                paket?.Penugasan.map((pekerjaan) => (
                  <KartuPenugasan
                    key={pekerjaan.Id}
                    pekerjaan={pekerjaan}
                    namaAset={
                      pekerjaan.AsetId.map((id) => asetPerId.get(id)?.Nama).filter(Boolean) as string[]
                    }
                    daftarPeriksa={daftarPeriksaPerPekerjaan.get(pekerjaan.Id) ?? []}
                    onKerjakan={() => setPekerjaanTerpilih(pekerjaan)}
                    onDaftarPeriksa={setDaftarPeriksaTerpilih}
                  />
                ))
              )}
            </TabsContent>

            <TabsContent value="aset" className="space-y-3">
              {(paket?.Aset.length ?? 0) === 0 ? (
                <KeadaanKosong
                  judul="Belum ada aset pada paket ini."
                  deskripsi="Ringkasan aset mengikuti pekerjaan yang ditugaskan kepada Anda."
                />
              ) : (
                <div className="grid gap-3 md:grid-cols-2">
                  {paket?.Aset.map((aset) => (
                    <Card key={aset.Id}>
                      <CardHeader className="pb-1">
                        <CardTitle className="text-sm">
                          {aset.KodeAset} · {aset.Nama}
                        </CardTitle>
                      </CardHeader>
                      <CardContent className="space-y-1 pt-2 text-xs text-muted-foreground">
                        <p>Lokasi: {aset.NamaLokasi ?? '—'}</p>
                        <p>Nomor seri: {aset.NomorSeri ?? '—'}</p>
                        <div className="flex flex-wrap gap-1.5 pt-1">
                          <Badge variant="netral">{aset.Status}</Badge>
                          {aset.Kondisi && <Badge variant="info">{aset.Kondisi}</Badge>}
                          {aset.TingkatKritis && <Badge variant="perhatian">{aset.TingkatKritis}</Badge>}
                        </div>
                      </CardContent>
                    </Card>
                  ))}
                </div>
              )}
            </TabsContent>

            <TabsContent value="antrean" className="space-y-3">
              {antrian.length === 0 ? (
                <KeadaanKosong
                  judul="Antrean kosong."
                  deskripsi="Semua perubahan lapangan Anda sudah diterima server."
                />
              ) : (
                <div className="overflow-hidden rounded-[9px] border border-border bg-card">
                  <ul className="divide-y divide-border">
                    {antrian.map((mutasi) => (
                      <li key={mutasi.KunciOperasi} className="flex flex-wrap items-center gap-3 px-4 py-3">
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-medium">{mutasi.Label}</p>
                          <p className="text-xs text-muted-foreground">
                            {waktuLokal(mutasi.DibuatPada)}
                            {mutasi.Percobaan > 0 && ` · percobaan ke-${mutasi.Percobaan}`}
                          </p>
                          {mutasi.Konflik?.Pesan && (
                            <p className="text-xs text-bahaya-600">{mutasi.Konflik.Pesan}</p>
                          )}
                        </div>
                        <Badge variant={VARIAN_STATUS_MUTASI[mutasi.Status]}>{mutasi.Status}</Badge>
                        {mutasi.Status === 'Konflik' && (
                          <Button size="sm" variant="outline" onClick={() => setKonflikTerpilih(mutasi)}>
                            Selesaikan
                          </Button>
                        )}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {gagal.length > 0 && (
                <p className="text-xs text-muted-foreground">
                  {gagal.length} perubahan ditolak server secara permanen. Periksa pesannya, perbaiki di jalur
                  normal, lalu hapus dengan memperbarui paket.
                </p>
              )}
            </TabsContent>
          </Tabs>
        )}
      </div>

      <DialogKerjakan
        pekerjaan={pekerjaanTerpilih}
        onTutup={() => setPekerjaanTerpilih(null)}
        onAntrikan={antrikan}
      />
      <DialogDaftarPeriksa
        daftarPeriksa={daftarPeriksaTerpilih}
        onTutup={() => setDaftarPeriksaTerpilih(null)}
        onAntrikan={antrikan}
      />
      <DialogKonflik
        mutasi={konflikTerpilih}
        onTutup={() => setKonflikTerpilih(null)}
        onSelesaikan={selesaikanKonflik}
      />
    </KerangkaAplikasi>
  );
}

function RingkasanKartu({
  ikon: Ikon,
  label,
  nilai,
  nada,
}: {
  ikon: typeof Timer;
  label: string;
  nilai: string;
  nada: 'baik' | 'perhatian' | 'bahaya' | 'netral';
}) {
  const warna = {
    baik: 'text-sukses-600',
    perhatian: 'text-safety-600',
    bahaya: 'text-bahaya-600',
    netral: 'text-muted-foreground',
  }[nada];

  return (
    <Card>
      <CardContent className="flex items-center gap-3 p-4">
        <Ikon className={cn('size-5 shrink-0', warna)} />
        <div className="min-w-0">
          <p className="text-xs text-muted-foreground">{label}</p>
          <p className="truncate text-sm font-medium">{nilai}</p>
        </div>
      </CardContent>
    </Card>
  );
}

function KartuPenugasan({
  pekerjaan,
  namaAset,
  daftarPeriksa,
  onKerjakan,
  onDaftarPeriksa,
}: {
  pekerjaan: PenugasanOffline;
  namaAset: string[];
  daftarPeriksa: DaftarPeriksaOffline[];
  onKerjakan: () => void;
  onDaftarPeriksa: (daftarPeriksa: DaftarPeriksaOffline) => void;
}) {
  return (
    <Card>
      <CardHeader className="pb-1">
        <div className="flex flex-wrap items-center gap-2">
          <CardTitle className="text-base">{pekerjaan.Judul}</CardTitle>
          <Badge variant={VARIAN_STATUS_PERINTAH_KERJA[pekerjaan.Status as StatusPerintahKerja]}>
            {pekerjaan.Status}
          </Badge>
        </div>
        <p className="font-mono text-xs text-muted-foreground">{pekerjaan.Nomor}</p>
      </CardHeader>
      <CardContent className="space-y-3 pt-2">
        <div className="grid gap-1 text-xs text-muted-foreground sm:grid-cols-2">
          <p>Lokasi: {pekerjaan.NamaLokasi ?? '—'}</p>
          <p>Batas selesai: {waktuLokal(pekerjaan.BatasPenyelesaianPada)}</p>
          <p className="sm:col-span-2">Aset: {namaAset.length > 0 ? namaAset.join(', ') : '—'}</p>
        </div>

        <div className="flex flex-wrap gap-2">
          <Button size="sm" variant="outline" onClick={onKerjakan}>
            <MessageSquarePlus className="size-4" />
            Catat pekerjaan
          </Button>
          {daftarPeriksa.map((item) => (
            <Button key={item.Id} size="sm" variant="outline" onClick={() => onDaftarPeriksa(item)}>
              <ListChecks className="size-4" />
              {item.NamaTemplat ?? 'Daftar periksa'}
            </Button>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
