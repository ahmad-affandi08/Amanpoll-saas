import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type {
  AturanTingkatLayanan,
  EskalasiTingkatLayanan,
  PemicuEskalasi,
  PrioritasKeluhan,
  TingkatLayanan,
} from '@/features/Keluhan/types';
import { ruteTingkatLayanan } from '@/features/TingkatLayanan/api';
import { KANAL_NOTIFIKASI } from '@/features/Notifikasi/status';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { TANPA_PILIHAN, opsiDari, opsiKosong } from '@/lib/pilihan';
import { BidangKode } from '@/components/shared/BidangKode';
import { AturanWajibProvider, type AturanWajib } from '@/lib/aturan-wajib';
import { Combobox } from '@/components/ui/combobox';

interface Ringkas {
  Id: string;
  Nama: string;
}
interface Props {
  tingkatLayanan: TingkatLayanan[];
  peran: Ringkas[];
  pengguna: Ringkas[];
  /** Peta field wajib per formulir, dibaca dari FormRequest di server. */
  wajib: Record<string, AturanWajib>;
}
const PRIORITAS: PrioritasKeluhan[] = ['Rendah', 'Normal', 'Tinggi', 'Kritis'];
/** Kombinasi kanal eskalasi; in-app selalu ikut supaya jejaknya terbaca di aplikasi. */
const PILIHAN_KANAL_ESKALASI = [
  { value: 'InApp', label: 'In-app' },
  { value: 'InApp,Email', label: 'In-app + Email' },
  { value: 'InApp,WhatsApp', label: 'In-app + WhatsApp' },
  { value: 'InApp,Email,WhatsApp', label: 'In-app + Email + WhatsApp' },
];

function nilaiKanalEskalasi(kanal: string[]): string {
  return KANAL_NOTIFIKASI.filter((satu) => satu.value === 'InApp' || kanal.includes(satu.value))
    .map((satu) => satu.value)
    .join(',');
}

const HARI = [
  { nilai: 1, label: 'Sen' },
  { nilai: 2, label: 'Sel' },
  { nilai: 3, label: 'Rab' },
  { nilai: 4, label: 'Kam' },
  { nilai: 5, label: 'Jum' },
  { nilai: 6, label: 'Sab' },
  { nilai: 7, label: 'Min' },
];
function aturanAwal(item: TingkatLayanan | null): AturanTingkatLayanan[] {
  if (item?.Aturan.length) return item.Aturan;
  return PRIORITAS.map((Prioritas) => ({
    Prioritas,
    MenitRespons: null,
    MenitPenyelesaian: null,
    MenghitungJamKerja: true,
  }));
}

function DialogTingkatLayanan({
  item,
  peran,
  pengguna,
  wajib,
}: {
  item: TingkatLayanan | null;
  peran: Ringkas[];
  pengguna: Ringkas[];
  wajib: AturanWajib;
}) {
  const [buka, setBuka] = useState(false);
  const form = useForm({
    Kode: item?.Kode ?? '',
    Nama: item?.Nama ?? '',
    Deskripsi: item?.Deskripsi ?? '',
    HariKerja: item?.HariKerja ?? [1, 2, 3, 4, 5],
    JamKerjaMulai: item?.JamKerjaMulai ?? '08:00',
    JamKerjaSelesai: item?.JamKerjaSelesai ?? '17:00',
    MemperhitungkanHariLibur: item?.MemperhitungkanHariLibur ?? true,
    Aktif: item?.Aktif ?? true,
    Aturan: aturanAwal(item),
    Eskalasi: item?.Eskalasi ?? ([] as EskalasiTingkatLayanan[]),
  });

  const ubahAturan = (indeks: number, data: Partial<AturanTingkatLayanan>) =>
    form.setData(
      'Aturan',
      form.data.Aturan.map((a, i) => (i === indeks ? { ...a, ...data } : a)),
    );
  const ubahEskalasi = (indeks: number, data: Partial<EskalasiTingkatLayanan>) =>
    form.setData(
      'Eskalasi',
      form.data.Eskalasi.map((e, i) => (i === indeks ? { ...e, ...data } : e)),
    );
  const tambahEskalasi = () =>
    form.setData('Eskalasi', [
      ...form.data.Eskalasi,
      {
        Tahap: form.data.Eskalasi.length + 1,
        Pemicu: 'Menjelang',
        SetelahMenit: 60,
        PeranId: null,
        PenggunaId: null,
        Kanal: ['InApp'],
        Aktif: true,
      },
    ]);
  const submit = (event: FormEvent) => {
    event.preventDefault();
    const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };
    item ? form.put(ruteTingkatLayanan.detail(item.Id), opsi) : form.post(ruteTingkatLayanan.index, opsi);
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant={item ? 'outline' : 'default'} size={item ? 'sm' : 'default'}>
          {item ? 'Ubah' : 'Tambah Tingkat Layanan'}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[92vh] overflow-y-auto sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>{item ? 'Ubah' : 'Tambah'} Tingkat Layanan</DialogTitle>
        </DialogHeader>
        <AturanWajibProvider aturan={wajib}>
          <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2">
              <BidangKode
                nilai={form.data.Kode}
                onUbah={(nilai) => form.setData('Kode', nilai)}
                galat={form.errors.Kode}
              />
              <div className="space-y-1.5">
                <Label nama="Nama">Nama</Label>
                <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
                {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
              </div>
            </div>
            <div className="space-y-1.5">
              <Label nama="Deskripsi">Deskripsi</Label>
              <Textarea
                value={form.data.Deskripsi}
                onChange={(e) => form.setData('Deskripsi', e.target.value)}
                rows={2}
              />
            </div>
            <section className="space-y-3 rounded-[9px] border border-border p-4">
              <div>
                <h3 className="font-medium">Kalender kerja</h3>
                <p className="text-xs text-muted-foreground">
                  Deadline yang menghitung jam kerja hanya berjalan pada hari dan rentang waktu ini.
                </p>
              </div>
              <div className="flex flex-wrap gap-2">
                {HARI.map((hari) => {
                  const aktif = form.data.HariKerja.includes(hari.nilai);
                  return (
                    <Button
                      key={hari.nilai}
                      type="button"
                      size="sm"
                      variant={aktif ? 'default' : 'outline'}
                      onClick={() =>
                        form.setData(
                          'HariKerja',
                          aktif
                            ? form.data.HariKerja.filter((h) => h !== hari.nilai)
                            : [...form.data.HariKerja, hari.nilai].sort(),
                        )
                      }
                    >
                      {hari.label}
                    </Button>
                  );
                })}
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <Label nama="JamKerjaMulai">Jam mulai</Label>
                  <Input
                    type="time"
                    value={form.data.JamKerjaMulai}
                    onChange={(e) => form.setData('JamKerjaMulai', e.target.value)}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label nama="JamKerjaSelesai">Jam selesai</Label>
                  <Input
                    type="time"
                    value={form.data.JamKerjaSelesai}
                    onChange={(e) => form.setData('JamKerjaSelesai', e.target.value)}
                  />
                </div>
              </div>
              <label className="flex items-center gap-2 text-sm">
                <Switch
                  checked={form.data.MemperhitungkanHariLibur}
                  onCheckedChange={(v) => form.setData('MemperhitungkanHariLibur', v)}
                />{' '}
                Lewati hari libur organisasi/lokasi
              </label>
            </section>
            <section className="space-y-3">
              <div>
                <h3 className="font-medium">Target per prioritas</h3>
                <p className="text-xs text-muted-foreground">
                  Nilai dalam menit. Kosongkan target yang tidak digunakan.
                </p>
              </div>
              {form.data.Aturan.map((aturan, indeks) => (
                <div
                  key={aturan.Prioritas}
                  className="grid items-end gap-3 rounded-[9px] border border-border p-3 sm:grid-cols-[100px_1fr_1fr_auto]"
                >
                  <div>
                    <Label>Prioritas</Label>
                    <div className="pt-2 text-sm font-medium">{aturan.Prioritas}</div>
                  </div>
                  <div className="space-y-1.5">
                    <Label>Respons</Label>
                    <Input
                      type="number"
                      min={1}
                      value={aturan.MenitRespons ?? ''}
                      onChange={(e) =>
                        ubahAturan(indeks, { MenitRespons: e.target.value ? Number(e.target.value) : null })
                      }
                    />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Penyelesaian</Label>
                    <Input
                      type="number"
                      min={1}
                      value={aturan.MenitPenyelesaian ?? ''}
                      onChange={(e) =>
                        ubahAturan(indeks, {
                          MenitPenyelesaian: e.target.value ? Number(e.target.value) : null,
                        })
                      }
                    />
                  </div>
                  <label className="flex items-center gap-2 pb-2 text-xs">
                    <Switch
                      checked={aturan.MenghitungJamKerja}
                      onCheckedChange={(v) => ubahAturan(indeks, { MenghitungJamKerja: v })}
                    />{' '}
                    Jam kerja
                  </label>
                </div>
              ))}
            </section>
            <section className="space-y-3">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="font-medium">Eskalasi</h3>
                  <p className="text-xs text-muted-foreground">
                    Tahap mendekati batas atau setelah SLA terlewati.
                  </p>
                </div>
                <Button type="button" variant="outline" size="sm" onClick={tambahEskalasi}>
                  Tambah Tahap
                </Button>
              </div>
              {form.data.Eskalasi.map((eskalasi, indeks) => (
                <div key={indeks} className="space-y-3 rounded-[9px] border border-border p-3">
                  <div className="grid gap-3 sm:grid-cols-3">
                    <div className="space-y-1.5">
                      <Label>Pemicu</Label>
                      <Select
                        value={eskalasi.Pemicu}
                        onValueChange={(v) => ubahEskalasi(indeks, { Pemicu: v as PemicuEskalasi })}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Menjelang">Menjelang batas</SelectItem>
                          <SelectItem value="Terlewati">Setelah terlewati</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-1.5">
                      <Label>Menit</Label>
                      <Input
                        type="number"
                        min={0}
                        value={eskalasi.SetelahMenit}
                        onChange={(e) => ubahEskalasi(indeks, { SetelahMenit: Number(e.target.value) })}
                      />
                    </div>
                    <div className="space-y-1.5">
                      <Label>Kanal</Label>
                      <Select
                        value={nilaiKanalEskalasi(eskalasi.Kanal)}
                        onValueChange={(v) => ubahEskalasi(indeks, { Kanal: v.split(',') })}
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {PILIHAN_KANAL_ESKALASI.map((pilihan) => (
                            <SelectItem key={pilihan.value} value={pilihan.value}>
                              {pilihan.label}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <div className="space-y-1.5">
                      <Label>Peran penerima</Label>
                      <Combobox
                        nilai={eskalasi.PeranId ?? TANPA_PILIHAN}
                        onPilih={(v) => ubahEskalasi(indeks, { PeranId: v === TANPA_PILIHAN ? null : v })}
                        opsi={[opsiKosong('Tanpa peran'), ...opsiDari(peran, (p) => p.Nama)]}
                      />
                    </div>
                    <div className="space-y-1.5">
                      <Label>Pengguna penerima</Label>
                      <Combobox
                        nilai={eskalasi.PenggunaId ?? TANPA_PILIHAN}
                        onPilih={(v) => ubahEskalasi(indeks, { PenggunaId: v === TANPA_PILIHAN ? null : v })}
                        opsi={[opsiKosong('Tanpa pengguna khusus'), ...opsiDari(pengguna, (p) => p.Nama)]}
                      />
                    </div>
                  </div>
                  <div className="flex justify-end">
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() =>
                        form.setData(
                          'Eskalasi',
                          form.data.Eskalasi.filter((_, i) => i !== indeks).map((e, i) => ({
                            ...e,
                            Tahap: i + 1,
                          })),
                        )
                      }
                    >
                      Hapus tahap
                    </Button>
                  </div>
                </div>
              ))}
            </section>
            <label className="flex items-center gap-2 text-sm">
              <Switch checked={form.data.Aktif} onCheckedChange={(v) => form.setData('Aktif', v)} /> Tingkat
              layanan aktif
            </label>
            {Object.values(form.errors).length > 0 && (
              <p className="text-sm text-destructive">Periksa kembali isian target dan penerima eskalasi.</p>
            )}
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

export default function TingkatLayananIndex({ tingkatLayanan, peran, pengguna, wajib }: Props) {
  const konfirmasi = useKonfirmasi();
  return (
    <KerangkaAplikasi>
      <Head title="Tingkat Layanan" />
      <KepalaHalaman
        judul="Tingkat Layanan"
        deskripsi="Konfigurasi kalender, target respons dan penyelesaian, serta tahapan eskalasi."
        aksi={
          <>
            <TombolEkspor url={ruteTingkatLayanan.ekspor} />
            <DialogTingkatLayanan
              item={null}
              peran={peran}
              pengguna={pengguna}
              wajib={wajib.tingkatLayanan}
            />
          </>
        }
        className="mb-6"
      />
      <div className="grid gap-4 xl:grid-cols-2">
        {tingkatLayanan.map((sla) => (
          <Card key={sla.Id}>
            <CardHeader>
              <div className="flex items-start justify-between gap-3">
                <div>
                  <CardTitle>{sla.Nama}</CardTitle>
                  <p className="font-mono text-xs text-muted-foreground">{sla.Kode}</p>
                </div>
                <Badge variant={sla.Aktif ? 'sukses' : 'netral'}>{sla.Aktif ? 'Aktif' : 'Nonaktif'}</Badge>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="text-sm text-muted-foreground">{sla.Deskripsi || 'Tanpa deskripsi.'}</p>
              <div className="text-sm">
                <div className="font-medium">Kalender kerja</div>
                <div className="text-muted-foreground">
                  {sla.JamKerjaMulai}–{sla.JamKerjaSelesai} · {sla.HariKerja.length} hari/minggu ·{' '}
                  {sla.MemperhitungkanHariLibur ? 'melewati hari libur' : 'hari libur tetap dihitung'}
                </div>
              </div>
              <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                {sla.Aturan.map((aturan) => (
                  <div key={aturan.Prioritas} className="rounded-md border border-border p-2 text-xs">
                    <div className="font-medium">{aturan.Prioritas}</div>
                    <div className="text-muted-foreground">R {aturan.MenitRespons ?? '—'}m</div>
                    <div className="text-muted-foreground">S {aturan.MenitPenyelesaian ?? '—'}m</div>
                  </div>
                ))}
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs text-muted-foreground">{sla.Eskalasi.length} tahap eskalasi</span>
                <div className="flex gap-2">
                  <DialogTingkatLayanan
                    item={sla}
                    peran={peran}
                    pengguna={pengguna}
                    wajib={wajib.tingkatLayanan}
                  />
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={async () => {
                      const lanjut = await konfirmasi({
                        judul: `Hapus tingkat layanan "${sla.Nama}"?`,
                        deskripsi: 'Aturan respons, resolusi, dan eskalasinya ikut terhapus.',
                        ragam: 'bahaya',
                      });
                      if (lanjut) router.delete(ruteTingkatLayanan.detail(sla.Id));
                    }}
                  >
                    Hapus
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
        {tingkatLayanan.length === 0 && (
          <div className="rounded-[9px] border border-dashed border-border p-10 text-center text-sm text-muted-foreground xl:col-span-2">
            Belum ada tingkat layanan.
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
