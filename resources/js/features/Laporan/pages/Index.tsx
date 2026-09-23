import { useMemo, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { Download, FileDown, Lock, Plus, Share2, Trash2 } from 'lucide-react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KeadaanKosong } from '@/components/shared/KeadaanKosong';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Switch } from '@/components/ui/switch';
import { KartuKpi } from '@/components/grafik/KartuKpi';
import { BarisFilter } from '@/features/Pelaporan/components/BarisFilter';
import { rutePelaporan } from '@/features/Pelaporan/api';
import { formatUkuranByte, waktuLokal } from '@/features/Pelaporan/format';
import type {
  DefinisiKpi,
  EksporItem,
  FilterMetrik,
  FormatEkspor,
  LaporanTersimpanItem,
  MetrikKpi,
  PilihanDimensi,
} from '@/features/Pelaporan/types';
import type { PageProps } from '@/types/global';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TombolEkspor } from '@/components/shared/TombolEkspor';
import { Combobox } from '@/components/ui/combobox';

interface Props {
  laporan: LaporanTersimpanItem[];
  dibuka: LaporanTersimpanItem | null;
  metrik: Record<string, MetrikKpi>;
  filter: FilterMetrik;
  katalogKpi: DefinisiKpi[];
  formatEkspor: { Nilai: FormatEkspor; Label: string }[];
  pilihanUnit: PilihanDimensi[];
  pilihanLokasi: PilihanDimensi[];
  eksporTerakhir: EksporItem[];
}

/** Laporan tersimpan (21.03) dan pemicu ekspor (21.05). */
export default function LaporanIndex({
  laporan,
  dibuka,
  metrik,
  filter,
  katalogKpi,
  formatEkspor,
  pilihanUnit,
  pilihanLokasi,
  eksporTerakhir,
}: Props) {
  const { flash } = usePage<PageProps>().props;
  const konfirmasi = useKonfirmasi();
  const [dialogBaru, setDialogBaru] = useState(false);
  const [dialogEkspor, setDialogEkspor] = useState(false);

  const kpiDibuka = useMemo(
    () => (dibuka ? dibuka.KunciKpi.filter((kunci) => metrik[kunci] !== undefined) : []),
    [dibuka, metrik],
  );

  const hapus = async (item: LaporanTersimpanItem) => {
    const lanjut = await konfirmasi({
      judul: `Hapus laporan "${item.Nama}"?`,
      deskripsi: 'Filter yang tersimpan akan hilang. Tindakan ini tidak dapat dibatalkan.',
      ragam: 'bahaya',
      labelAksi: 'Hapus',
    });

    if (lanjut) {
      router.delete(rutePelaporan.laporanDetail(item.Id), { preserveScroll: true });
    }
  };

  return (
    <KerangkaAplikasi>
      <Head title="Laporan" />
      <div className="space-y-5">
        <KepalaHalaman
          judul="Laporan"
          deskripsi="Simpan kombinasi KPI dan filter, lalu ekspor hasilnya saat dibutuhkan."
          aksi={
            <>
              <div className="flex flex-wrap gap-2">
                {/* Daftar laporannya sendiri; tombol "Ekspor" di sebelah mengunduh isi laporan yang dibuka. */}
                <TombolEkspor url={rutePelaporan.laporanEkspor} label="Ekspor daftar" />
                <Button size="sm" onClick={() => setDialogBaru(true)}>
                  <Plus className="size-4" />
                  Laporan baru
                </Button>
                {dibuka && kpiDibuka.length > 0 && (
                  <Button size="sm" variant="outline" onClick={() => setDialogEkspor(true)}>
                    <FileDown className="size-4" />
                    Ekspor
                  </Button>
                )}
              </div>
            </>
          }
        />

        {flash.sukses && (
          <p className="rounded-[5px] border border-sukses-600/25 bg-sukses-600/10 px-3 py-2 text-sm text-sukses-700">
            {flash.sukses}
          </p>
        )}

        <BarisFilter
          filter={filter}
          pilihanUnit={pilihanUnit}
          pilihanLokasi={pilihanLokasi}
          url={rutePelaporan.laporan}
          paramTambahan={dibuka ? { laporan: dibuka.Id } : {}}
        />

        <div className="grid gap-4 lg:grid-cols-[18rem_1fr]">
          <aside className="space-y-2">
            <h2 className="text-sm font-medium">Laporan tersimpan</h2>
            {laporan.length === 0 ? (
              <KeadaanKosong
                judul="Belum ada laporan tersimpan."
                deskripsi="Simpan kombinasi KPI yang sering Anda buka."
              />
            ) : (
              <ul className="space-y-1.5">
                {laporan.map((item) => (
                  <li key={item.Id}>
                    <div
                      className={`flex items-center gap-2 rounded-[5px] border px-2.5 py-2 ${
                        dibuka?.Id === item.Id ? 'border-teknisi-600 bg-teknisi-600/5' : 'border-border'
                      }`}
                    >
                      <Link
                        href={rutePelaporan.laporan}
                        data={{ ...filter, laporan: item.Id }}
                        preserveState
                        preserveScroll
                        className="min-w-0 flex-1"
                      >
                        <span className="block truncate text-sm font-medium">{item.Nama}</span>
                        <span className="block truncate text-xs text-muted-foreground">
                          {item.KunciKpi.length} KPI
                          {!item.Milik && item.NamaPemilik ? ` · ${item.NamaPemilik}` : ''}
                        </span>
                      </Link>
                      <Badge variant={item.Pribadi ? 'netral' : 'info'} className="shrink-0">
                        {item.Pribadi ? <Lock className="size-3" /> : <Share2 className="size-3" />}
                      </Badge>
                      {item.Milik && (
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          aria-label={`Hapus ${item.Nama}`}
                          onClick={() => void hapus(item)}
                        >
                          <Trash2 className="size-4 text-destructive" />
                        </Button>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
            )}

            {eksporTerakhir.length > 0 && (
              <div className="pt-3">
                <h2 className="mb-2 text-sm font-medium">Ekspor terakhir</h2>
                <ul className="space-y-1.5">
                  {eksporTerakhir.map((ekspor) => (
                    <li key={ekspor.Id}>
                      <a
                        href={rutePelaporan.eksporUnduh(ekspor.Id)}
                        className="flex items-center gap-2 rounded-[5px] border border-border px-2.5 py-2 hover:bg-accent"
                      >
                        <Download className="size-4 shrink-0 text-muted-foreground" />
                        <span className="min-w-0 flex-1">
                          <span className="block truncate text-sm">{ekspor.Judul}</span>
                          <span className="block truncate text-xs text-muted-foreground">
                            {ekspor.Format} · {ekspor.JumlahBaris} baris ·{' '}
                            {formatUkuranByte(ekspor.UkuranByte)} · {waktuLokal(ekspor.DibuatPada)}
                          </span>
                        </span>
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </aside>

          <section>
            {dibuka === null ? (
              <KeadaanKosong
                ilustrasi="/assets/3d/laporan.webp"
                judul="Pilih laporan untuk melihat hasilnya."
                deskripsi="Atau buat laporan baru dari KPI yang boleh Anda lihat."
              />
            ) : kpiDibuka.length === 0 ? (
              <KeadaanKosong
                judul="Tidak ada KPI yang dapat ditampilkan."
                deskripsi="KPI pada laporan ini berada di luar kewenangan Anda saat ini."
              />
            ) : (
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                {kpiDibuka.map((kunci) => (
                  <KartuKpi
                    key={kunci}
                    kpi={metrik[kunci]}
                    bentuk={metrik[kunci].Rincian.length > 0 ? 'Batang' : 'Angka'}
                    judul={null}
                    lebar={metrik[kunci].Rincian.length > 0 ? 2 : 1}
                  />
                ))}
              </div>
            )}
          </section>
        </div>
      </div>

      <DialogLaporanBaru
        terbuka={dialogBaru}
        onTutup={() => setDialogBaru(false)}
        katalogKpi={katalogKpi}
        filter={filter}
      />
      {dibuka && (
        <DialogEkspor
          terbuka={dialogEkspor}
          onTutup={() => setDialogEkspor(false)}
          laporan={dibuka}
          kunciKpi={kpiDibuka}
          filter={filter}
          formatEkspor={formatEkspor}
        />
      )}
    </KerangkaAplikasi>
  );
}

function DialogLaporanBaru({
  terbuka,
  onTutup,
  katalogKpi,
  filter,
}: {
  terbuka: boolean;
  onTutup: () => void;
  katalogKpi: DefinisiKpi[];
  filter: FilterMetrik;
}) {
  const form = useForm<{ Nama: string; Jenis: string; Pribadi: boolean; KunciKpi: string[] }>({
    Nama: '',
    Jenis: 'Kpi',
    Pribadi: true,
    KunciKpi: [],
  });

  const alihkan = (kunci: string) => {
    form.setData(
      'KunciKpi',
      form.data.KunciKpi.includes(kunci)
        ? form.data.KunciKpi.filter((satu) => satu !== kunci)
        : [...form.data.KunciKpi, kunci],
    );
  };

  const simpan = () => {
    router.post(
      rutePelaporan.laporan,
      {
        Nama: form.data.Nama,
        Jenis: form.data.Jenis,
        Pribadi: form.data.Pribadi,
        Konfigurasi: { KunciKpi: form.data.KunciKpi, Filter: filter },
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          form.reset();
          onTutup();
        },
        onError: () => toast.error('Laporan gagal disimpan. Periksa kembali isiannya.'),
      },
    );
  };

  return (
    <Dialog open={terbuka} onOpenChange={(buka) => !buka && onTutup()}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Laporan baru</DialogTitle>
          <DialogDescription>
            Pilih KPI yang ingin disimpan. Filter yang sedang aktif ikut tersimpan sebagai nilai awal.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="nama-laporan">Nama laporan</Label>
            <Input
              id="nama-laporan"
              value={form.data.Nama}
              onChange={(e) => form.setData('Nama', e.target.value)}
              placeholder="mis. Kinerja SLA bulanan"
            />
          </div>

          <div className="flex items-center gap-2">
            <Switch
              id="laporan-pribadi"
              checked={form.data.Pribadi}
              onCheckedChange={(nilai) => form.setData('Pribadi', nilai)}
            />
            <Label htmlFor="laporan-pribadi" className="text-sm font-normal">
              Pribadi — hanya Anda yang dapat membukanya
            </Label>
          </div>

          <fieldset className="space-y-2">
            <legend className="text-sm font-medium">KPI ({form.data.KunciKpi.length} dipilih)</legend>
            <div className="max-h-64 space-y-1.5 overflow-y-auto rounded-[5px] border border-border p-2">
              {katalogKpi.map((kpi) => (
                <label key={kpi.Kunci} className="flex items-start gap-2 rounded-[5px] p-1.5 hover:bg-accent">
                  <Checkbox
                    checked={form.data.KunciKpi.includes(kpi.Kunci)}
                    onCheckedChange={() => alihkan(kpi.Kunci)}
                    className="mt-0.5"
                  />
                  <span className="min-w-0">
                    <span className="block text-sm">{kpi.Nama}</span>
                    <span className="block text-xs text-muted-foreground">{kpi.LabelKelompok}</span>
                  </span>
                </label>
              ))}
            </div>
          </fieldset>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={onTutup}>
            Batal
          </Button>
          <Button
            onClick={simpan}
            disabled={form.data.Nama.trim() === '' || form.data.KunciKpi.length === 0 || form.processing}
          >
            Simpan laporan
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function DialogEkspor({
  terbuka,
  onTutup,
  laporan,
  kunciKpi,
  filter,
  formatEkspor,
}: {
  terbuka: boolean;
  onTutup: () => void;
  laporan: LaporanTersimpanItem;
  kunciKpi: string[];
  filter: FilterMetrik;
  formatEkspor: { Nilai: FormatEkspor; Label: string }[];
}) {
  const [format, setFormat] = useState<FormatEkspor>('Csv');

  const kirim = () => {
    router.post(
      rutePelaporan.ekspor,
      { Judul: laporan.Nama, Format: format, KunciKpi: kunciKpi, Filter: filter },
      {
        preserveScroll: true,
        onSuccess: () => onTutup(),
        onError: () => toast.error('Permintaan ekspor ditolak.'),
      },
    );
  };

  return (
    <Dialog open={terbuka} onOpenChange={(buka) => !buka && onTutup()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Ekspor {laporan.Nama}</DialogTitle>
          <DialogDescription>
            Berkas dibuat di antrean. Anda akan menerima notifikasi begitu siap diunduh.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-1.5">
          <Label>Format</Label>
          <Combobox
            nilai={format}
            onPilih={(nilai) => setFormat(nilai as FormatEkspor)}
            opsi={formatEkspor.map((satu) => ({ nilai: satu.Nilai, label: satu.Label }))}
          />
          {format === 'Pdf' && (
            <p className="text-xs text-muted-foreground">
              PDF dipotong pada 2.000 baris. Untuk data penuh, pilih CSV atau XLSX.
            </p>
          )}
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={onTutup}>
            Batal
          </Button>
          <Button onClick={kirim}>Buat ekspor</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
