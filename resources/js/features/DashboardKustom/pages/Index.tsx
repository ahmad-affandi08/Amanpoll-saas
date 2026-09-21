import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { ArrowDown, ArrowUp, LayoutDashboard, Plus, Star, Trash2, X } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { EmptyState } from '@/components/shared/EmptyState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { rutePelaporan } from '@/features/Pelaporan/api';
import type {
  BentukKomponen,
  DasborTersimpanPenuh,
  DefinisiKpi,
  KomponenSusunan,
  SusunanDasbor,
} from '@/features/Pelaporan/types';
import type { PageProps } from '@/types/global';
import { PageHeader } from '@/components/shared/PageHeader';

interface Props {
  dasbor: DasborTersimpanPenuh[];
  preset: SusunanDasbor;
  katalogKpi: DefinisiKpi[];
  batasKomponen: number;
}

interface KomponenDraf {
  KunciKpi: string;
  Bentuk: BentukKomponen;
  Judul: string;
  Lebar: number;
}

/**
 * Penyusun dasbor kustom (21.04).
 *
 * Pengguna memilih KPI, bentuk tampilannya, lebar dalam grid empat kolom, dan
 * urutannya. Tata letak bebas seret-lepas sengaja tidak dipakai: kolom
 * PosisiX/PosisiY disediakan skema untuk kebutuhan itu nanti, tetapi urutan dan
 * lebar sudah cukup untuk susunan yang rapi di ponsel sampai desktop tanpa
 * memaksa pengguna menata ulang tiap kali layarnya berganti ukuran.
 *
 * Bentuk yang ditawarkan per KPI berasal dari server, jadi tidak mungkin
 * memilih bagan yang nanti ditolak saat disimpan.
 */
export default function DashboardKustomIndex({ dasbor, preset, katalogKpi, batasKomponen }: Props) {
  const { flash } = usePage<PageProps>().props;
  const konfirmasi = useKonfirmasi();
  const [diedit, setDiedit] = useState<DasborTersimpanPenuh | null>(null);
  const [menyusun, setMenyusun] = useState(false);

  const hapus = async (satu: DasborTersimpanPenuh) => {
    const lanjut = await konfirmasi({
      judul: `Hapus dasbor "${satu.Nama}"?`,
      deskripsi: 'Susunan komponennya akan hilang. Tindakan ini tidak dapat dibatalkan.',
      ragam: 'bahaya',
      labelAksi: 'Hapus',
    });

    if (lanjut) {
      router.delete(rutePelaporan.dasborKustomDetail(satu.Id), { preserveScroll: true });
    }
  };

  return (
    <AppLayout>
      <Head title="Dasbor Kustom" />
      <div className="space-y-5">
        <PageHeader
          judul="Dasbor Kustom"
          deskripsi="Pilih KPI yang ingin Anda lihat, atur urutan dan lebarnya, lalu tandai satu sebagai bawaan."
          aksi={
            <>
              <div className="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" asChild>
                  <Link href={rutePelaporan.dasbor}>
                    <LayoutDashboard className="size-4" />
                    Buka dasbor
                  </Link>
                </Button>
                <Button
                  size="sm"
                  onClick={() => {
                    setDiedit(null);
                    setMenyusun(true);
                  }}
                >
                  <Plus className="size-4" />
                  Dasbor baru
                </Button>
              </div>
            </>
          }
        />

        {flash.sukses && (
          <p className="rounded-[5px] border border-sukses-600/25 bg-sukses-600/10 px-3 py-2 text-sm text-sukses-600">
            {flash.sukses}
          </p>
        )}

        {menyusun ? (
          <Penyusun
            awal={diedit}
            preset={preset}
            katalogKpi={katalogKpi}
            batasKomponen={batasKomponen}
            onSelesai={() => {
              setMenyusun(false);
              setDiedit(null);
            }}
          />
        ) : dasbor.length === 0 ? (
          <EmptyState
            ilustrasi="/assets/3d/dashboard-analitik.webp"
            judul="Belum ada dasbor kustom."
            deskripsi={`Saat ini Anda memakai "${preset.Nama}" bawaan. Buat dasbor sendiri bila ingin susunan lain.`}
            aksi={
              <Button size="sm" onClick={() => setMenyusun(true)}>
                Dasbor baru
              </Button>
            }
          />
        ) : (
          <div className="grid gap-3 md:grid-cols-2">
            {dasbor.map((satu) => (
              <Card key={satu.Id}>
                <CardContent className="space-y-3 p-4">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <p className="flex items-center gap-2 font-medium">
                        <span className="truncate">{satu.Nama}</span>
                        {satu.Bawaan && (
                          <Badge variant="info">
                            <Star className="size-3" /> Bawaan
                          </Badge>
                        )}
                      </p>
                      <p className="text-xs text-muted-foreground">{satu.Komponen.length} komponen</p>
                    </div>
                    {satu.Milik && (
                      <div className="flex shrink-0 gap-1">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            setDiedit(satu);
                            setMenyusun(true);
                          }}
                        >
                          Ubah
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          aria-label={`Hapus ${satu.Nama}`}
                          onClick={() => void hapus(satu)}
                        >
                          <Trash2 className="size-4 text-destructive" />
                        </Button>
                      </div>
                    )}
                  </div>
                  <ul className="flex flex-wrap gap-1.5">
                    {satu.Komponen.map((komponen) => (
                      <li key={komponen.Id}>
                        <Badge variant="netral">
                          {namaKpi(katalogKpi, komponen.KunciKpi)} · {komponen.Bentuk}
                        </Badge>
                      </li>
                    ))}
                  </ul>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>
    </AppLayout>
  );
}

function namaKpi(katalog: DefinisiKpi[], kunci: string): string {
  return katalog.find((satu) => satu.Kunci === kunci)?.Nama ?? kunci;
}

function Penyusun({
  awal,
  preset,
  katalogKpi,
  batasKomponen,
  onSelesai,
}: {
  awal: DasborTersimpanPenuh | null;
  preset: SusunanDasbor;
  katalogKpi: DefinisiKpi[];
  batasKomponen: number;
  onSelesai: () => void;
}) {
  const [nama, setNama] = useState(awal?.Nama ?? '');
  const [bawaan, setBawaan] = useState(awal?.Bawaan ?? false);
  const [komponen, setKomponen] = useState<KomponenDraf[]>((awal?.Komponen ?? []).map(dariSusunan));
  const [memproses, setMemproses] = useState(false);

  const tambah = (kunci: string) => {
    const definisi = katalogKpi.find((satu) => satu.Kunci === kunci);
    if (!definisi || komponen.length >= batasKomponen) return;

    setKomponen((lama) => [
      ...lama,
      {
        KunciKpi: kunci,
        Bentuk: definisi.Bentuk?.[0]?.Nilai ?? 'Angka',
        Judul: '',
        Lebar: 1,
      },
    ]);
  };

  const ubah = (indeks: number, ubahan: Partial<KomponenDraf>) => {
    setKomponen((lama) => lama.map((satu, i) => (i === indeks ? { ...satu, ...ubahan } : satu)));
  };

  const geser = (indeks: number, arah: -1 | 1) => {
    const tujuan = indeks + arah;
    if (tujuan < 0 || tujuan >= komponen.length) return;

    setKomponen((lama) => {
      const salinan = [...lama];
      [salinan[indeks], salinan[tujuan]] = [salinan[tujuan], salinan[indeks]];
      return salinan;
    });
  };

  const simpan = () => {
    setMemproses(true);
    const muatan = {
      Nama: nama,
      Bawaan: bawaan,
      Komponen: komponen.map((satu) => ({
        KunciKpi: satu.KunciKpi,
        Bentuk: satu.Bentuk,
        Judul: satu.Judul.trim() === '' ? null : satu.Judul,
        Lebar: satu.Lebar,
      })),
    };

    const opsi = {
      preserveScroll: true,
      onSuccess: () => onSelesai(),
      onError: () => toast.error('Dasbor gagal disimpan. Periksa kembali komponennya.'),
      onFinish: () => setMemproses(false),
    };

    if (awal) {
      router.put(rutePelaporan.dasborKustomDetail(awal.Id), muatan, opsi);
    } else {
      router.post(rutePelaporan.dasborKustom, muatan, opsi);
    }
  };

  return (
    <Card>
      <CardContent className="space-y-5 p-4">
        <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
          <div className="space-y-1.5">
            <Label htmlFor="nama-dasbor">Nama dasbor</Label>
            <Input
              id="nama-dasbor"
              value={nama}
              onChange={(e) => setNama(e.target.value)}
              placeholder={`mis. ${preset.Nama} saya`}
            />
          </div>
          <div className="flex items-end gap-2 pb-2">
            <Switch id="dasbor-bawaan" checked={bawaan} onCheckedChange={setBawaan} />
            <Label htmlFor="dasbor-bawaan" className="text-sm font-normal">
              Jadikan bawaan
            </Label>
          </div>
        </div>

        <div className="space-y-1.5">
          <Label>Tambah komponen</Label>
          <Select value="" onValueChange={tambah} disabled={komponen.length >= batasKomponen}>
            <SelectTrigger className="w-full sm:w-[22rem]">
              <SelectValue placeholder="Pilih KPI untuk ditambahkan" />
            </SelectTrigger>
            <SelectContent>
              {katalogKpi.map((kpi) => (
                <SelectItem key={kpi.Kunci} value={kpi.Kunci}>
                  {kpi.LabelKelompok} — {kpi.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <p className="text-xs text-muted-foreground">
            {komponen.length} dari {batasKomponen} komponen.
          </p>
        </div>

        {komponen.length === 0 ? (
          <EmptyState
            judul="Belum ada komponen."
            deskripsi="Tambahkan minimal satu KPI sebelum menyimpan dasbor."
          />
        ) : (
          <ol className="space-y-2">
            {komponen.map((satu, indeks) => {
              const definisi = katalogKpi.find((k) => k.Kunci === satu.KunciKpi);

              return (
                <li
                  key={`${satu.KunciKpi}-${indeks}`}
                  className="grid gap-2 rounded-[5px] border border-border p-2.5 sm:grid-cols-[1fr_8rem_6rem_auto] sm:items-end"
                >
                  <div className="min-w-0 space-y-1.5">
                    <p className="truncate text-sm font-medium">{definisi?.Nama ?? satu.KunciKpi}</p>
                    <Input
                      value={satu.Judul}
                      onChange={(e) => ubah(indeks, { Judul: e.target.value })}
                      placeholder="Judul khusus (opsional)"
                      aria-label={`Judul untuk ${definisi?.Nama ?? satu.KunciKpi}`}
                    />
                  </div>

                  <div className="space-y-1.5">
                    <Label className="text-xs">Bentuk</Label>
                    <Select
                      value={satu.Bentuk}
                      onValueChange={(nilai) => ubah(indeks, { Bentuk: nilai as BentukKomponen })}
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {(definisi?.Bentuk ?? []).map((bentuk) => (
                          <SelectItem key={bentuk.Nilai} value={bentuk.Nilai}>
                            {bentuk.Label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-1.5">
                    <Label className="text-xs">Lebar</Label>
                    <Select
                      value={String(satu.Lebar)}
                      onValueChange={(nilai) => ubah(indeks, { Lebar: Number(nilai) })}
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {[1, 2, 3, 4].map((lebar) => (
                          <SelectItem key={lebar} value={String(lebar)}>
                            {lebar} kolom
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="flex gap-1">
                    <Button
                      variant="ghost"
                      size="icon"
                      aria-label="Naikkan urutan"
                      disabled={indeks === 0}
                      onClick={() => geser(indeks, -1)}
                    >
                      <ArrowUp className="size-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      aria-label="Turunkan urutan"
                      disabled={indeks === komponen.length - 1}
                      onClick={() => geser(indeks, 1)}
                    >
                      <ArrowDown className="size-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      aria-label="Hapus komponen"
                      onClick={() => setKomponen((lama) => lama.filter((_, i) => i !== indeks))}
                    >
                      <X className="size-4 text-destructive" />
                    </Button>
                  </div>
                </li>
              );
            })}
          </ol>
        )}

        <div className="flex flex-wrap justify-end gap-2">
          <Button variant="outline" onClick={onSelesai}>
            Batal
          </Button>
          <Button onClick={simpan} disabled={nama.trim() === '' || komponen.length === 0 || memproses}>
            Simpan dasbor
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

function dariSusunan(komponen: KomponenSusunan): KomponenDraf {
  return {
    KunciKpi: komponen.KunciKpi,
    Bentuk: komponen.Bentuk,
    Judul: komponen.Judul ?? '',
    Lebar: komponen.Lebar,
  };
}
