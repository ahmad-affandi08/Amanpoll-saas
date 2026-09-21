import { useEffect, useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import { toast } from 'sonner';
import {
  CheckCircle2,
  ClipboardList,
  CloudOff,
  Download,
  ListChecks,
  MessageSquarePlus,
  Package,
  RefreshCw,
  Timer,
  TriangleAlert,
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { cn } from '@/lib/utils';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { EmptyState } from '@/components/shared/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { VARIAN_STATUS_PERINTAH_KERJA } from '@/features/PerintahKerja/status';
import type { StatusPerintahKerja } from '@/features/PerintahKerja/types';
import type {
  DaftarPeriksaOffline,
  JawabanDaftarPeriksaOffline,
  MutasiOffline,
  PenugasanOffline,
} from '@/features/Sinkronisasi/types';
import { PageHeader } from '@/components/shared/PageHeader';

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

function nilaiJawaban(jawaban: JawabanDaftarPeriksaOffline | undefined): string {
  if (!jawaban) return '';
  if (jawaban.NilaiTeks != null) return jawaban.NilaiTeks;
  if (jawaban.NilaiAngka != null) return String(jawaban.NilaiAngka);
  if (jawaban.NilaiBoolean != null) return jawaban.NilaiBoolean ? 'Ya' : 'Tidak';
  return '';
}

/**
 * Ruang kerja teknisi offline (FASE 20.05).
 *
 * Seluruh isi halaman datang dari paket yang tersimpan di perangkat, bukan
 * dari props Inertia, sehingga halaman tetap berfungsi penuh ketika sinyal
 * hilang. Setiap aksi hanya mengantrikan mutasi; tidak ada yang dinyatakan
 * berhasil sebelum server mengonfirmasi (DESIGN.md 24).
 */
export default function TeknisiOffline() {
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
    <AppLayout>
      <Head title="Mode Teknisi (Offline)" />
      <div className="space-y-6">
        <PageHeader
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
                <EmptyState
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
                <EmptyState
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
                <EmptyState
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
    </AppLayout>
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

type FungsiAntrikan = ReturnType<typeof useSinkronisasiOffline>['antrikan'];

/**
 * Draft pekerjaan dan draft catatan (20.05). Ketiga aksinya hanya menambah
 * mutasi ke antrean; status pekerjaan di layar baru berubah setelah paket
 * ditarik ulang dan server mengonfirmasi.
 */
function DialogKerjakan({
  pekerjaan,
  onTutup,
  onAntrikan,
}: {
  pekerjaan: PenugasanOffline | null;
  onTutup: () => void;
  onAntrikan: FungsiAntrikan;
}) {
  const [catatan, setCatatan] = useState('');
  const [ringkasan, setRingkasan] = useState('');
  const [mulaiPada, setMulaiPada] = useState('');
  const [selesaiPada, setSelesaiPada] = useState('');

  useEffect(() => {
    setCatatan('');
    setRingkasan('');
    setMulaiPada('');
    setSelesaiPada('');
  }, [pekerjaan?.Id]);

  if (!pekerjaan) return null;

  const tutupSetelahAntri = async (pesan: string, jalankan: () => Promise<void>) => {
    await jalankan();
    toast.success(pesan);
    onTutup();
  };

  return (
    <Dialog open onOpenChange={(terbuka) => !terbuka && onTutup()}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{pekerjaan.Judul}</DialogTitle>
          <DialogDescription>
            Perubahan disimpan di perangkat lebih dulu, lalu dikirim saat koneksi kembali.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-5">
          {pekerjaan.PerluResponsPenugasan && (
            <section className="space-y-2 rounded-[9px] border border-border bg-permukaan-100 p-3">
              <Label>Penugasan belum direspons</Label>
              <p className="text-xs text-muted-foreground">
                Terima dulu penugasan ini sebelum mencatat pekerjaan.
              </p>
              <div className="flex flex-wrap gap-2">
                {(['Terima', 'Tolak'] as const).map((respons) => (
                  <Button
                    key={respons}
                    size="sm"
                    variant={respons === 'Terima' ? 'default' : 'outline'}
                    onClick={() =>
                      void tutupSetelahAntri(`Respons "${respons}" masuk antrean.`, () =>
                        onAntrikan({
                          Operasi: 'PerintahKerja.ResponsPenugasan',
                          EntitasId: pekerjaan.Id,
                          VersiKlien: pekerjaan.Versi,
                          MuatanData: { Respons: respons, Catatan: catatan || null },
                          Label: `${pekerjaan.Nomor}: penugasan ${respons.toLowerCase()}`,
                        }),
                      )
                    }
                  >
                    {respons} penugasan
                  </Button>
                ))}
              </div>
            </section>
          )}

          <section className="space-y-2">
            <Label>Ubah status</Label>
            {pekerjaan.StatusTujuan.length === 0 && (
              <p className="text-xs text-muted-foreground">
                Tidak ada perubahan status yang dapat Anda lakukan pada pekerjaan ini.
              </p>
            )}
            <div className="flex flex-wrap gap-2">
              {pekerjaan.StatusTujuan.map((tujuan) => (
                <Button
                  key={tujuan}
                  size="sm"
                  variant="outline"
                  onClick={() =>
                    void tutupSetelahAntri(`Perubahan status ke ${tujuan} masuk antrean.`, () =>
                      onAntrikan({
                        Operasi: 'PerintahKerja.UbahStatus',
                        EntitasId: pekerjaan.Id,
                        VersiKlien: pekerjaan.Versi,
                        MuatanData: {
                          Status: tujuan,
                          Catatan: catatan || null,
                          Ringkasan: ringkasan || null,
                        },
                        Label: `${pekerjaan.Nomor}: status → ${tujuan}`,
                      }),
                    )
                  }
                >
                  {tujuan}
                </Button>
              ))}
            </div>
            <Textarea
              value={ringkasan}
              onChange={(e) => setRingkasan(e.target.value)}
              placeholder="Ringkasan penyelesaian (wajib sebelum verifikasi)"
              rows={2}
            />
          </section>

          <section className="space-y-2">
            <Label htmlFor="catatan-lapangan">Catatan lapangan</Label>
            <Textarea
              id="catatan-lapangan"
              value={catatan}
              onChange={(e) => setCatatan(e.target.value)}
              placeholder="Temuan, kendala, atau tindakan yang dilakukan"
              rows={3}
            />
            <Button
              size="sm"
              variant="outline"
              disabled={catatan.trim() === ''}
              onClick={() =>
                void tutupSetelahAntri('Catatan masuk antrean.', () =>
                  onAntrikan({
                    Operasi: 'PerintahKerja.TambahCatatan',
                    EntitasId: pekerjaan.Id,
                    VersiKlien: null,
                    MuatanData: { Isi: catatan.trim() },
                    Label: `${pekerjaan.Nomor}: catatan lapangan`,
                  }),
                )
              }
            >
              <MessageSquarePlus className="size-4" />
              Antrikan catatan
            </Button>
          </section>

          <section className="space-y-2">
            <Label>Sesi waktu kerja</Label>
            <div className="grid gap-2 sm:grid-cols-2">
              <Input
                type="datetime-local"
                value={mulaiPada}
                onChange={(e) => setMulaiPada(e.target.value)}
                aria-label="Mulai pada"
              />
              <Input
                type="datetime-local"
                value={selesaiPada}
                onChange={(e) => setSelesaiPada(e.target.value)}
                aria-label="Selesai pada"
              />
            </div>
            <Button
              size="sm"
              variant="outline"
              disabled={mulaiPada === '' || selesaiPada === ''}
              onClick={() =>
                void tutupSetelahAntri('Sesi waktu kerja masuk antrean.', () =>
                  onAntrikan({
                    Operasi: 'PerintahKerja.CatatWaktuKerja',
                    EntitasId: pekerjaan.Id,
                    VersiKlien: null,
                    MuatanData: {
                      MulaiPada: new Date(mulaiPada).toISOString(),
                      SelesaiPada: new Date(selesaiPada).toISOString(),
                      Catatan: catatan || null,
                    },
                    Label: `${pekerjaan.Nomor}: sesi waktu kerja`,
                  }),
                )
              }
            >
              <Timer className="size-4" />
              Antrikan sesi
            </Button>
          </section>
        </div>
      </DialogContent>
    </Dialog>
  );
}

function DialogDaftarPeriksa({
  daftarPeriksa,
  onTutup,
  onAntrikan,
}: {
  daftarPeriksa: DaftarPeriksaOffline | null;
  onTutup: () => void;
  onAntrikan: FungsiAntrikan;
}) {
  const [isian, setIsian] = useState<Record<string, string>>({});

  useEffect(() => {
    if (!daftarPeriksa) return;
    const awal: Record<string, string> = {};
    for (const butir of daftarPeriksa.Butir) {
      awal[butir.Id] = nilaiJawaban(
        daftarPeriksa.Jawaban.find((j) => j.ButirTemplatDaftarPeriksaId === butir.Id),
      );
    }
    setIsian(awal);
  }, [daftarPeriksa?.Id]);

  if (!daftarPeriksa) return null;

  const jawabanTerisi = () =>
    daftarPeriksa.Butir.filter((butir) => (isian[butir.Id] ?? '').trim() !== '').map((butir) => {
      const nilai = (isian[butir.Id] ?? '').trim();

      if (butir.TipeJawaban === 'Angka') {
        return { ButirTemplatDaftarPeriksaId: butir.Id, NilaiAngka: Number(nilai) };
      }
      if (butir.TipeJawaban === 'YaTidak') {
        return {
          ButirTemplatDaftarPeriksaId: butir.Id,
          NilaiBoolean: ['ya', 'true', '1'].includes(nilai.toLowerCase()),
        };
      }

      return { ButirTemplatDaftarPeriksaId: butir.Id, NilaiTeks: nilai };
    });

  return (
    <Dialog open onOpenChange={(terbuka) => !terbuka && onTutup()}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{daftarPeriksa.NamaTemplat ?? 'Daftar periksa'}</DialogTitle>
          <DialogDescription>
            Jawaban disimpan di perangkat dan diterapkan server setelah antrean terkirim.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {daftarPeriksa.Butir.map((butir) => (
            <div key={butir.Id} className="space-y-1.5">
              <Label htmlFor={`butir-${butir.Id}`}>
                {butir.Urutan}. {butir.Pertanyaan}
                {butir.Wajib && <span className="text-bahaya-600"> *</span>}
                {butir.Satuan && <span className="text-muted-foreground"> ({butir.Satuan})</span>}
              </Label>
              <Input
                id={`butir-${butir.Id}`}
                type={butir.TipeJawaban === 'Angka' ? 'number' : 'text'}
                value={isian[butir.Id] ?? ''}
                onChange={(e) => setIsian((lama) => ({ ...lama, [butir.Id]: e.target.value }))}
                placeholder={butir.TipeJawaban === 'YaTidak' ? 'Ya / Tidak' : 'Jawaban'}
              />
            </div>
          ))}
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            onClick={() =>
              void onAntrikan({
                Operasi: 'DaftarPeriksa.SimpanJawaban',
                EntitasId: daftarPeriksa.Id,
                VersiKlien: null,
                MuatanData: { Jawaban: jawabanTerisi() },
                Label: `${daftarPeriksa.NamaTemplat ?? 'Daftar periksa'}: simpan jawaban`,
              }).then(() => {
                toast.success('Jawaban daftar periksa masuk antrean.');
                onTutup();
              })
            }
            disabled={jawabanTerisi().length === 0}
          >
            <ClipboardList className="size-4" />
            Antrikan jawaban
          </Button>
          <Button
            onClick={() =>
              void onAntrikan({
                Operasi: 'DaftarPeriksa.Finalisasi',
                EntitasId: daftarPeriksa.Id,
                VersiKlien: null,
                MuatanData: { Catatan: null },
                Label: `${daftarPeriksa.NamaTemplat ?? 'Daftar periksa'}: finalisasi`,
              }).then(() => {
                toast.success('Finalisasi daftar periksa masuk antrean.');
                onTutup();
              })
            }
          >
            <CheckCircle2 className="size-4" />
            Antrikan finalisasi
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/**
 * Dialog konflik (20.06). Nilai perangkat dan nilai server ditampilkan
 * berdampingan lebih dulu; tidak ada pilihan yang menimpa server tanpa
 * teknisi melihat apa yang akan tertimpa.
 */
function DialogKonflik({
  mutasi,
  onTutup,
  onSelesaikan,
}: {
  mutasi: MutasiOffline | null;
  onTutup: () => void;
  onSelesaikan: ReturnType<typeof useSinkronisasiOffline>['selesaikanKonflik'];
}) {
  const [memproses, setMemproses] = useState(false);

  if (!mutasi) return null;

  const putuskan = async (keputusan: 'PakaiServer' | 'TerapkanUlang') => {
    setMemproses(true);
    try {
      await onSelesaikan(mutasi.KunciOperasi, keputusan);
      toast.success(
        keputusan === 'PakaiServer'
          ? 'Perubahan lokal dibuang, versi server dipertahankan.'
          : 'Perubahan diterapkan ulang di atas versi server.',
      );
      onTutup();
    } catch {
      toast.error('Konflik belum dapat diselesaikan. Coba lagi saat koneksi stabil.');
    } finally {
      setMemproses(false);
    }
  };

  return (
    <Dialog open onOpenChange={(terbuka) => !terbuka && onTutup()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Konflik perubahan</DialogTitle>
          <DialogDescription>{mutasi.Konflik?.Pesan}</DialogDescription>
        </DialogHeader>

        <div className="grid gap-3 sm:grid-cols-2">
          <div className="rounded-[9px] border border-border bg-permukaan-100 p-3">
            <p className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-foreground">
              <Package className="size-3.5" /> Di perangkat ini
            </p>
            <pre className="whitespace-pre-wrap break-words font-mono text-xs text-muted-foreground">
              {JSON.stringify(mutasi.Konflik?.NilaiKlien ?? mutasi.MuatanData, null, 2)}
            </pre>
            {mutasi.Konflik?.VersiKlien != null && (
              <p className="mt-1 text-xs text-muted-foreground">Versi {mutasi.Konflik.VersiKlien}</p>
            )}
          </div>
          <div className="rounded-[9px] border border-border bg-card p-3">
            <p className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-foreground">
              <CheckCircle2 className="size-3.5" /> Di server
            </p>
            <pre className="whitespace-pre-wrap break-words font-mono text-xs text-muted-foreground">
              {JSON.stringify(mutasi.Konflik?.NilaiServer ?? {}, null, 2)}
            </pre>
            {mutasi.Konflik?.VersiServer != null && (
              <p className="mt-1 text-xs text-muted-foreground">Versi {mutasi.Konflik.VersiServer}</p>
            )}
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" disabled={memproses} onClick={() => void putuskan('PakaiServer')}>
            Pakai versi server
          </Button>
          <Button disabled={memproses} onClick={() => void putuskan('TerapkanUlang')}>
            Terapkan ulang perubahan saya
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
