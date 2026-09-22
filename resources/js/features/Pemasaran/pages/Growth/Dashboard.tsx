import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CircleAlert, Info } from 'lucide-react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { HUE_UTAMA, WARNA_STATUS } from '@/components/grafik/palet';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type Filter = Record<string, string | null>;

interface ModelPerbandingan {
  Model: string;
  Label: string;
  PerChannel: Record<string, number>;
}

interface Attribution {
  Model: string;
  Label: string;
  Keterangan: string;
  ParuhHari: number;
  Perbandingan: ModelPerbandingan[];
}

interface TahapFunnel {
  Tahap: string;
  Jumlah: number;
  Sumber: string;
  PersenDariSebelumnya: number | null;
}

interface KpiGrowth {
  Kunci: string;
  Nama: string;
  Kelompok: string;
  LabelKelompok: string;
  Satuan: string;
  Desimal: number;
  Formula: string;
  Sumber: string;
  Tersedia: boolean;
  BelumTersedia: string | null;
  Nilai: number | null;
}

interface BarisKampanye {
  KampanyeId: string;
  Kode: string;
  Nama: string;
  Visitor: number;
  Lead: number;
  Trial: number;
  Bayar: number;
  Revenue: number;
  Biaya: number;
}

interface BarisCac {
  Channel: string;
  Biaya: number;
  Pelanggan: number;
  Cac: number | null;
  Alasan: string | null;
}

interface CacTakTerpecah {
  Biaya: number;
  Pelanggan: number;
  Kampanye: string[];
}

interface BarisHalaman {
  Landing: string;
  Pengunjung: number;
  Lead: number;
  Konversi: number;
}

interface AlertGrowth {
  Id: string;
  Kode: string;
  Tingkat: string;
  Judul: string;
  Isi: string;
  DibuatPada: string;
}

interface Props {
  filter: Filter;
  funnel: TahapFunnel[];
  kpi: KpiGrowth[];
  revenuePerChannel: Record<string, number>;
  attribution: Attribution;
  cacPerChannel: BarisCac[];
  cacTakTerpecah: CacTakTerpecah;
  kampanye: BarisKampanye[];
  halaman: BarisHalaman[];
  alert: AlertGrowth[];
  pilihan: {
    Channel: string[];
    Kampanye: string[];
    Industri: string[];
    Perangkat: string[];
    Paket: string[];
    Referral: string[];
    AlertBelumTersedia: string[];
  };
}

const AKAR = '/admin-platform/pemasaran/growth';

const angka = (nilai: number, satuan: string, desimal: number) => {
  if (satuan === 'Uang') {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(nilai);
  }

  return `${nilai.toLocaleString('id-ID', { maximumFractionDigits: desimal })}${satuan === 'Persen' ? '%' : ''}`;
};

export default function PemasaranGrowthDashboard({
  filter,
  funnel,
  kpi,
  revenuePerChannel,
  attribution,
  cacPerChannel,
  cacTakTerpecah,
  kampanye,
  halaman,
  alert,
  pilihan,
}: Props) {
  const tersedia = kpi.filter((satu) => satu.Tersedia);
  const belum = kpi.filter((satu) => !satu.Tersedia);

  return (
    <KerangkaPlatform>
      <Head title="Dashboard Growth" />

      <KepalaHalaman
        judul="Dashboard Growth"
        deskripsi="Channel mana menghasilkan customer, campaign mana menghasilkan revenue, halaman mana paling efektif."
        tanpaBreadcrumb
        className="mb-6"
      />

      <BarisFilter filter={filter} pilihan={pilihan} />

      {alert.length > 0 ? <DaftarAlert alert={alert} /> : null}

      <section className="mt-6">
        <h2 className="mb-3 text-sm font-medium text-foreground">KPI Utama</h2>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {tersedia.map((satu) => (
            <KartuKpi key={satu.Kunci} kpi={satu} />
          ))}
        </div>

        {belum.length > 0 ? (
          <div className="mt-3 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            {belum.length} KPI belum dapat dihitung karena sumbernya belum ada:{' '}
            {belum.map((satu) => satu.Nama).join(', ')}. Angkanya sengaja dikosongkan, bukan
            ditampilkan sebagai nol.
          </div>
        ) : null}
      </section>

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <Funnel funnel={funnel} />
        <RevenueChannel revenue={revenuePerChannel} attribution={attribution} />
      </div>

      <section className="mt-6">
        <PerbandinganModel attribution={attribution} />
      </section>

      <section className="mt-6">
        <CacChannel baris={cacPerChannel} takTerpecah={cacTakTerpecah} />
      </section>

      <Tabs defaultValue="kampanye" className="mt-6">
        <TabsList>
          <TabsTrigger value="kampanye">Kampanye</TabsTrigger>
          <TabsTrigger value="halaman">Landing Page</TabsTrigger>
          <TabsTrigger value="definisi">Definisi KPI</TabsTrigger>
        </TabsList>

        <TabsContent value="kampanye" className="mt-4">
          <TabelKampanye kampanye={kampanye} />
        </TabsContent>

        <TabsContent value="halaman" className="mt-4">
          <TabelHalaman halaman={halaman} />
        </TabsContent>

        <TabsContent value="definisi" className="mt-4">
          <TabelDefinisi kpi={kpi} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}

function BarisFilter({ filter, pilihan }: { filter: Filter; pilihan: Props['pilihan'] }) {
  const [nilai, setNilai] = useState<Filter>(filter);

  const ubah = (kunci: string, isi: string) =>
    setNilai((lama) => ({ ...lama, [kunci]: isi === '' ? null : isi }));

  const terapkan = (e: FormEvent) => {
    e.preventDefault();

    const bersih = Object.fromEntries(
      Object.entries(nilai).filter(([, isi]) => isi !== null && isi !== ''),
    );

    router.get(AKAR, bersih, { preserveState: true, preserveScroll: true });
  };

  const daftar: Array<[string, string, string[]]> = [
    ['channel', 'Channel', pilihan.Channel],
    ['kampanye', 'Campaign', pilihan.Kampanye],
    ['industri', 'Industri', pilihan.Industri],
    ['perangkat', 'Device', pilihan.Perangkat],
    ['paket', 'Paket', pilihan.Paket],
    ['referral', 'Referral', pilihan.Referral],
  ];

  return (
    <form onSubmit={terapkan} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
      <div className="grid gap-1.5">
        <Label htmlFor="dari">Dari</Label>
        <Input
          id="dari"
          type="date"
          value={nilai.dari ?? ''}
          onChange={(e) => ubah('dari', e.target.value)}
          className="w-40"
        />
      </div>

      <div className="grid gap-1.5">
        <Label htmlFor="sampai">Sampai</Label>
        <Input
          id="sampai"
          type="date"
          value={nilai.sampai ?? ''}
          onChange={(e) => ubah('sampai', e.target.value)}
          className="w-40"
        />
      </div>

      {daftar.map(([kunci, label, opsi]) => (
        <div key={kunci} className="grid gap-1.5">
          <Label htmlFor={kunci}>{label}</Label>
          <Select value={nilai[kunci] ?? 'semua'} onValueChange={(v) => ubah(kunci, v === 'semua' ? '' : v)}>
            <SelectTrigger id={kunci} className="w-40">
              <SelectValue placeholder="Semua" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="semua">Semua</SelectItem>
              {opsi.map((satu) => (
                <SelectItem key={satu} value={satu}>
                  {satu}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      ))}

      <div className="grid gap-1.5">
        <Label htmlFor="landing">Landing page</Label>
        <Input
          id="landing"
          value={nilai.landing ?? ''}
          onChange={(e) => ubah('landing', e.target.value)}
          placeholder="Cari jalur..."
          className="w-48"
        />
      </div>

      <Button type="submit">Terapkan</Button>
      <Button type="button" variant="ghost" onClick={() => router.get(AKAR)}>
        Reset
      </Button>
    </form>
  );
}

function KartuKpi({ kpi }: { kpi: KpiGrowth }) {
  return (
    <Card>
      <CardContent className="p-4">
        <p className="text-xs text-muted-foreground">{kpi.Nama}</p>
        <p className="mt-1 font-mono text-2xl font-medium text-foreground">
          {kpi.Nilai === null ? '—' : angka(kpi.Nilai, kpi.Satuan, kpi.Desimal)}
        </p>
        <p className="mt-1 text-xs text-muted-foreground">{kpi.LabelKelompok}</p>
      </CardContent>
    </Card>
  );
}

/** Magnitudo per tahap berurutan: satu deret, satu hue, tanpa legenda. */
function Funnel({ funnel }: { funnel: TahapFunnel[] }) {
  const puncak = Math.max(...funnel.map((satu) => satu.Jumlah), 1);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Funnel Visitor → Paid</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {funnel.map((tahap) => (
          <div key={tahap.Tahap}>
            <div className="flex items-baseline justify-between gap-2 text-sm">
              <span className="font-medium text-foreground">{tahap.Tahap}</span>
              <span className="font-mono text-foreground">
                {tahap.Jumlah.toLocaleString('id-ID')}
                {tahap.PersenDariSebelumnya === null ? null : (
                  <span className="ml-2 text-xs text-muted-foreground">
                    {tahap.PersenDariSebelumnya}%
                  </span>
                )}
              </span>
            </div>
            <div
              className="mt-1 h-2 rounded-sm bg-muted"
              role="img"
              aria-label={`${tahap.Tahap}: ${tahap.Jumlah}, sumber ${tahap.Sumber}`}
            >
              <div
                className="h-2 rounded-sm"
                style={{
                  width: `${Math.max((tahap.Jumlah / puncak) * 100, tahap.Jumlah > 0 ? 2 : 0)}%`,
                  backgroundColor: HUE_UTAMA,
                }}
              />
            </div>
            <p className="mt-0.5 text-xs text-muted-foreground">Sumber: {tahap.Sumber}</p>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}

/** Angka revenue tidak berarti apa-apa tanpa menyebut model pembagiannya. */
function RevenueChannel({
  revenue,
  attribution,
}: {
  revenue: Record<string, number>;
  attribution: Attribution;
}) {
  const baris = Object.entries(revenue);
  const puncak = Math.max(...baris.map(([, nilai]) => nilai), 1);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Revenue per Channel</CardTitle>
        <p className="text-xs text-muted-foreground">
          Model {attribution.Label.toLowerCase()}
          {attribution.Model === 'TimeDecay' ? ` · paruh ${attribution.ParuhHari} hari` : ''} ·{' '}
          {attribution.Keterangan}
        </p>
      </CardHeader>
      <CardContent className="space-y-3">
        {baris.length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada pembayaran pada rentang ini.</p>
        ) : (
          baris.map(([channel, nilai]) => (
            <div key={channel}>
              <div className="flex items-baseline justify-between gap-2 text-sm">
                <span className="text-foreground">{channel}</span>
                <span className="font-mono text-foreground">
                  {new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(nilai)}
                </span>
              </div>
              <div className="mt-1 h-2 rounded-sm bg-muted">
                <div
                  className="h-2 rounded-sm"
                  style={{ width: `${(nilai / puncak) * 100}%`, backgroundColor: HUE_UTAMA }}
                />
              </div>
            </div>
          ))
        )}
      </CardContent>
    </Card>
  );
}

/** Model lain ditampilkan berdampingan supaya pilihan setelan dapat ditimbang, bukan ditebak. */
function PerbandinganModel({ attribution }: { attribution: Attribution }) {
  const channel = Array.from(
    new Set(attribution.Perbandingan.flatMap((satu) => Object.keys(satu.PerChannel))),
  ).sort();

  if (channel.length === 0) {
    return null;
  }

  const rupiah = (nilai: number) =>
    new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(nilai);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Revenue menurut tiap model</CardTitle>
        <p className="text-xs text-muted-foreground">
          Angka yang dipakai kartu di atas adalah kolom {attribution.Label.toLowerCase()}.
        </p>
      </CardHeader>
      <CardContent className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b text-left text-xs text-muted-foreground">
              <th className="py-2 pr-4 font-medium">Channel</th>
              {attribution.Perbandingan.map((satu) => (
                <th key={satu.Model} className="py-2 pr-4 text-right font-medium">
                  {satu.Label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {channel.map((nama) => (
              <tr key={nama} className="border-b last:border-0">
                <td className="py-2 pr-4">{nama}</td>
                {attribution.Perbandingan.map((satu) => (
                  <td
                    key={satu.Model}
                    className={`py-2 pr-4 text-right font-mono ${satu.Model === attribution.Model ? 'font-semibold text-foreground' : 'text-muted-foreground'}`}
                  >
                    {rupiah(satu.PerChannel[nama] ?? 0)}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </CardContent>
    </Card>
  );
}

/** CAC hanya pasti untuk kampanye berchannel tunggal; yang tidak pasti disebut, bukan dibagi rata. */
function CacChannel({ baris, takTerpecah }: { baris: BarisCac[]; takTerpecah: CacTakTerpecah }) {
  const rupiah = (nilai: number) =>
    new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(nilai);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">CAC per Channel</CardTitle>
      </CardHeader>
      <CardContent>
        {baris.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Belum ada biaya kampanye tercatat pada rentang ini. Catat belanjanya di halaman kampanye
            agar CAC punya pembilang.
          </p>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Channel</TableHead>
                <TableHead className="text-right">Biaya</TableHead>
                <TableHead className="text-right">Pelanggan baru</TableHead>
                <TableHead className="text-right">CAC</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {baris.map((satu) => (
                <TableRow key={satu.Channel}>
                  <TableCell className="text-foreground">{satu.Channel}</TableCell>
                  <TableCell className="text-right font-mono">{rupiah(satu.Biaya)}</TableCell>
                  <TableCell className="text-right font-mono">{satu.Pelanggan}</TableCell>
                  <TableCell className="text-right">
                    {satu.Cac === null ? (
                      <span className="text-xs text-muted-foreground">{satu.Alasan}</span>
                    ) : (
                      <span className="font-mono text-foreground">{rupiah(satu.Cac)}</span>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}

        {takTerpecah.Kampanye.length > 0 ? (
          <p className="mt-3 rounded-lg border border-dashed p-3 text-xs text-muted-foreground">
            {takTerpecah.Kampanye.length} kampanye berjalan di lebih dari satu channel
            ({takTerpecah.Kampanye.join(', ')}), sehingga {rupiah(takTerpecah.Biaya)} belanja dan{' '}
            {takTerpecah.Pelanggan} pelanggan barunya tidak dapat dipecah per channel tanpa menebak.
            Angkanya sengaja tidak diselipkan ke tabel di atas.
          </p>
        ) : null}
      </CardContent>
    </Card>
  );
}

function DaftarAlert({ alert }: { alert: AlertGrowth[] }) {
  const ikon = (tingkat: string) => {
    if (tingkat === 'Kritis') return <CircleAlert aria-hidden="true" className="size-4 shrink-0" />;
    if (tingkat === 'Peringatan') return <AlertTriangle aria-hidden="true" className="size-4 shrink-0" />;

    return <Info aria-hidden="true" className="size-4 shrink-0" />;
  };

  const warna = (tingkat: string) =>
    tingkat === 'Kritis' ? WARNA_STATUS.bahaya : tingkat === 'Peringatan' ? WARNA_STATUS.perhatian : WARNA_STATUS.info;

  return (
    <section className="mt-6 space-y-2">
      <h2 className="text-sm font-medium text-foreground">Alert</h2>
      {alert.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="flex flex-wrap items-center justify-between gap-3 p-4">
            <div className="flex min-w-0 items-start gap-2">
              <span style={{ color: warna(satu.Tingkat) }}>{ikon(satu.Tingkat)}</span>
              <div className="min-w-0">
                <p className="text-sm font-medium text-foreground">{satu.Judul}</p>
                <p className="text-xs text-muted-foreground">{satu.Isi}</p>
              </div>
            </div>
            <div className="flex shrink-0 items-center gap-2">
              <Badge variant="outline">{satu.Tingkat}</Badge>
              <Button
                variant="ghost"
                size="sm"
                onClick={() =>
                  router.post(`${AKAR}/alert/${satu.Id}/selesai`, {}, { preserveScroll: true })
                }
              >
                Selesai
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </section>
  );
}

function TabelKampanye({ kampanye }: { kampanye: BarisKampanye[] }) {
  if (kampanye.length === 0) {
    return (
      <p className="text-sm text-muted-foreground">
        Belum ada metrik kampanye pada rentang ini. Metrik dihitung pekerjaan harian
        <span className="font-mono"> pemasaran:hitung-metrik</span>.
      </p>
    );
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Kampanye</TableHead>
          <TableHead className="text-right">Visitor</TableHead>
          <TableHead className="text-right">Lead</TableHead>
          <TableHead className="text-right">Trial</TableHead>
          <TableHead className="text-right">Bayar</TableHead>
          <TableHead className="text-right">Biaya</TableHead>
          <TableHead className="text-right">Revenue</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {kampanye.map((satu) => (
          <TableRow key={satu.KampanyeId}>
            <TableCell>
              <div className="font-medium text-foreground">{satu.Nama}</div>
              <div className="font-mono text-xs text-muted-foreground">{satu.Kode}</div>
            </TableCell>
            <TableCell className="text-right font-mono">{satu.Visitor}</TableCell>
            <TableCell className="text-right font-mono">{satu.Lead}</TableCell>
            <TableCell className="text-right font-mono">{satu.Trial}</TableCell>
            <TableCell className="text-right font-mono">{satu.Bayar}</TableCell>
            <TableCell className="text-right font-mono">
              {new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(satu.Biaya)}
            </TableCell>
            <TableCell className="text-right font-mono">
              {new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(satu.Revenue)}
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}

function TabelHalaman({ halaman }: { halaman: BarisHalaman[] }) {
  if (halaman.length === 0) {
    return <p className="text-sm text-muted-foreground">Belum ada kunjungan pada rentang ini.</p>;
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Landing page</TableHead>
          <TableHead className="text-right">Pengunjung</TableHead>
          <TableHead className="text-right">Lead</TableHead>
          <TableHead className="text-right">Konversi</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {halaman.map((satu) => (
          <TableRow key={satu.Landing}>
            <TableCell className="break-all font-mono text-xs">{satu.Landing}</TableCell>
            <TableCell className="text-right font-mono">{satu.Pengunjung}</TableCell>
            <TableCell className="text-right font-mono">{satu.Lead}</TableCell>
            <TableCell className="text-right font-mono">{satu.Konversi}%</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}

/** Rumus tiap KPI terbaca di layar, sehingga angkanya dapat ditelusuri tanpa membuka kode. */
function TabelDefinisi({ kpi }: { kpi: KpiGrowth[] }) {
  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>KPI</TableHead>
          <TableHead>Rumus</TableHead>
          <TableHead>Sumber</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {kpi.map((satu) => (
          <TableRow key={satu.Kunci}>
            <TableCell>
              <div className="font-medium text-foreground">{satu.Nama}</div>
              <div className="font-mono text-xs text-muted-foreground">{satu.Kunci}</div>
            </TableCell>
            <TableCell className="text-sm text-muted-foreground">
              {satu.Formula}
              {satu.BelumTersedia ? (
                <span className="mt-1 block text-destructive">{satu.BelumTersedia}</span>
              ) : null}
            </TableCell>
            <TableCell className="font-mono text-xs text-muted-foreground">{satu.Sumber}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
