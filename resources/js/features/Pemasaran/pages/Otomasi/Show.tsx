import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { Combobox } from '@/components/ui/combobox';
import type {
  DetailOtomasi,
  EksekusiOtomasi,
  LangkahOtomasi,
  VersiOtomasi,
} from '@/features/Pemasaran/types';

type Pilihan = {
  Jenis: string[];
  Aksi: Record<string, string>;
  /** bidang kondisi => operator yang berlaku untuknya. */
  Bidang: Record<string, string[]>;
};

interface Props {
  otomasi: DetailOtomasi;
  versi: VersiOtomasi[];
  eksekusi: EksekusiOtomasi[];
  pilihan: Pilihan;
}

const AKAR = rutePemasaran.otomasi;

const waktu = (nilai: string | null) => (nilai ? new Date(nilai).toLocaleString('id-ID') : '—');

export default function PemasaranOtomasiShow({ otomasi, versi, eksekusi, pilihan }: Props) {
  const konfirmasi = useKonfirmasi();
  const draf = versi.find((satu) => satu.Status === 'Draf');

  const ubahAktif = async (aktif: boolean) => {
    if (aktif) {
      const setuju = await konfirmasi({
        judul: 'Nyalakan otomasi?',
        deskripsi: 'Mulai sekarang otomasi ini akan mengirim pesan ke orang sungguhan.',
      });

      if (!setuju) return;
    }

    router.post(`${AKAR}/${otomasi.Kode}/aktif`, { Aktif: aktif }, { preserveScroll: true });
  };

  const aktifkanVersi = async (satu: VersiOtomasi) => {
    const setuju = await konfirmasi({
      judul: `Aktifkan versi ${satu.Nomor}?`,
      deskripsi:
        'Versi yang sedang aktif akan diarsipkan. Eksekusi yang sudah berjalan tetap memakai versi lamanya.',
    });

    if (setuju) {
      router.post(`${AKAR}/${otomasi.Kode}/versi/${satu.Id}/aktifkan`, {}, { preserveScroll: true });
    }
  };

  return (
    <KerangkaPlatform>
      <Head title={otomasi.Nama} />

      <KepalaHalaman
        judul={otomasi.Nama}
        deskripsi={otomasi.Keterangan ?? `Pemicu ${otomasi.Pemicu}.`}
        tanpaBreadcrumb
        aksi={
          <Button variant={otomasi.Aktif ? 'outline' : 'default'} onClick={() => ubahAktif(!otomasi.Aktif)}>
            {otomasi.Aktif ? 'Matikan' : 'Nyalakan'}
          </Button>
        }
        className="mb-6"
      />

      <div className="mb-6 flex flex-wrap gap-2">
        <Badge variant="outline" className="font-mono">
          {otomasi.Pemicu}
        </Badge>
        {otomasi.Aktif ? <Badge>Menyala</Badge> : <Badge variant="secondary">Mati</Badge>}
        {otomasi.PemicuBerlaku ? null : (
          <Badge variant="outline">Belum ada yang menghasilkan pemicu ini</Badge>
        )}
      </div>

      <Tabs defaultValue="versi">
        <TabsList>
          <TabsTrigger value="versi">Versi dan Langkah</TabsTrigger>
          <TabsTrigger value="eksekusi">Eksekusi Terakhir</TabsTrigger>
        </TabsList>

        <TabsContent value="versi" className="mt-4 space-y-4">
          {draf ? null : (
            <div className="flex justify-end">
              <Button
                variant="outline"
                onClick={() => router.post(`${AKAR}/${otomasi.Kode}/versi`, {}, { preserveScroll: true })}
              >
                Buat Draf Baru
              </Button>
            </div>
          )}

          {versi.map((satu) => (
            <KartuVersi
              key={satu.Id}
              otomasi={otomasi}
              versi={satu}
              pilihan={pilihan}
              onAktifkan={() => aktifkanVersi(satu)}
            />
          ))}
        </TabsContent>

        <TabsContent value="eksekusi" className="mt-4">
          <DaftarEksekusi eksekusi={eksekusi} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}

function KartuVersi({
  otomasi,
  versi,
  pilihan,
  onAktifkan,
}: {
  otomasi: DetailOtomasi;
  versi: VersiOtomasi;
  pilihan: Pilihan;
  onAktifkan: () => void;
}) {
  const konfirmasi = useKonfirmasi();
  const draf = versi.Status === 'Draf';

  const hapus = async (langkah: LangkahOtomasi) => {
    const setuju = await konfirmasi({
      judul: 'Hapus langkah?',
      deskripsi: `Langkah #${langkah.Urutan} dibuang dari draf ini.`,
      ragam: 'bahaya',
    });

    if (setuju) {
      router.delete(`${AKAR}/${otomasi.Kode}/versi/${versi.Id}/langkah/${langkah.Id}`, {
        preserveScroll: true,
      });
    }
  };

  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between gap-2 space-y-0">
        <div>
          <CardTitle className="text-base">Versi {versi.Nomor}</CardTitle>
          <p className="text-xs text-muted-foreground">
            {versi.Status}
            {versi.DiterbitkanPada ? ` · diaktifkan ${waktu(versi.DiterbitkanPada)}` : ''}
          </p>
        </div>
        {draf ? (
          <Button variant="outline" size="sm" onClick={onAktifkan}>
            Aktifkan
          </Button>
        ) : null}
      </CardHeader>

      <CardContent className="space-y-3">
        {versi.Langkah.length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada langkah.</p>
        ) : (
          <ol className="divide-y rounded-md border">
            {versi.Langkah.map((langkah) => (
              <li key={langkah.Id} className="flex items-start justify-between gap-3 p-3">
                <div className="min-w-0">
                  <p className="text-sm font-medium">
                    #{langkah.Urutan} · {langkah.Jenis}
                  </p>
                  <p className="break-all font-mono text-xs text-muted-foreground">
                    {JSON.stringify(langkah.Konfigurasi)}
                  </p>
                </div>
                {draf ? (
                  <Button variant="ghost" size="sm" onClick={() => hapus(langkah)}>
                    Hapus
                  </Button>
                ) : null}
              </li>
            ))}
          </ol>
        )}

        {draf ? (
          <div className="flex justify-end">
            <DialogLangkah otomasi={otomasi} versi={versi} pilihan={pilihan} />
          </div>
        ) : (
          <p className="text-sm text-muted-foreground">
            Versi yang sudah diaktifkan tidak dapat disunting; eksekusi yang berjalan memakainya apa adanya.
            Buat draf baru untuk mengubah alurnya.
          </p>
        )}
      </CardContent>
    </Card>
  );
}

function DialogLangkah({
  otomasi,
  versi,
  pilihan,
}: {
  otomasi: DetailOtomasi;
  versi: VersiOtomasi;
  pilihan: Pilihan;
}) {
  const [buka, setBuka] = useState(false);
  const urutanBerikut = versi.Langkah.reduce((maks, satu) => Math.max(maks, satu.Urutan + 1), 0);
  const daftarBidang = Object.keys(pilihan.Bidang);
  const daftarAksi = Object.entries(pilihan.Aksi);

  const [jenis, setJenis] = useState(pilihan.Jenis[0] ?? 'Kondisi');
  const [menit, setMenit] = useState('60');
  const [bidang, setBidang] = useState(daftarBidang[0] ?? '');
  const [operator, setOperator] = useState(pilihan.Bidang[daftarBidang[0] ?? '']?.[0] ?? '');
  const [nilai, setNilai] = useState('');
  const [aksi, setAksi] = useState(daftarAksi[0]?.[0] ?? '');
  const [konfigurasiAksi, setKonfigurasiAksi] = useState('{}');
  const [galat, setGalat] = useState<string | null>(null);

  // Bentuk datar; bentuk sebenarnya dipasang lewat transform agar tipenya tidak melingkar.
  const form = useForm({ Jenis: '', Urutan: 0, Konfigurasi: '' });

  const konfigurasi = (): Record<string, unknown> | null => {
    if (jenis === 'Jeda') {
      return { Menit: Number(menit) };
    }

    if (jenis === 'Kondisi') {
      return { Kondisi: [{ Bidang: bidang, Operator: operator, Nilai: nilai }] };
    }

    try {
      return { Aksi: aksi, Konfigurasi: JSON.parse(konfigurasiAksi) };
    } catch {
      return null;
    }
  };

  const kirim = (e: FormEvent) => {
    e.preventDefault();
    const isi = konfigurasi();

    if (isi === null) {
      setGalat('Konfigurasi aksi harus berupa JSON yang sah.');

      return;
    }

    setGalat(null);
    form.transform(() => ({ Jenis: jenis, Urutan: urutanBerikut, Konfigurasi: isi }));
    form.post(`${AKAR}/${otomasi.Kode}/versi/${versi.Id}/langkah`, {
      preserveScroll: true,
      onSuccess: () => setBuka(false),
    });
  };

  const gantiBidang = (nilaiBaru: string) => {
    setBidang(nilaiBaru);
    setOperator(pilihan.Bidang[nilaiBaru]?.[0] ?? '');
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          Tambah Langkah
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Tambah Langkah</DialogTitle>
        </DialogHeader>

        <form onSubmit={kirim} className="grid gap-4">
          <div className="grid gap-2">
            <Label htmlFor="JenisLangkah">Jenis</Label>
            <Select value={jenis} onValueChange={setJenis}>
              <SelectTrigger id="JenisLangkah">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Jenis.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {jenis === 'Jeda' ? (
            <div className="grid gap-2">
              <Label htmlFor="MenitJeda">Jeda (menit)</Label>
              <Input
                id="MenitJeda"
                type="number"
                min={1}
                value={menit}
                onChange={(e) => setMenit(e.target.value)}
                required
              />
            </div>
          ) : null}

          {jenis === 'Kondisi' ? (
            <>
              <div className="grid gap-2">
                <Label htmlFor="BidangKondisi">Bidang</Label>
                <Combobox
                  nilai={bidang}
                  onPilih={gantiBidang}
                  opsi={daftarBidang.map((satu) => ({ nilai: satu, label: satu }))}
                />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="OperatorKondisi">Operator</Label>
                <Select value={operator} onValueChange={setOperator}>
                  <SelectTrigger id="OperatorKondisi">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {(pilihan.Bidang[bidang] ?? []).map((satu) => (
                      <SelectItem key={satu} value={satu}>
                        {satu}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="grid gap-2">
                <Label htmlFor="NilaiKondisi">Nilai</Label>
                <Input
                  id="NilaiKondisi"
                  value={nilai}
                  onChange={(e) => setNilai(e.target.value)}
                  placeholder="Kosongkan untuk operator Ada / TidakAda"
                />
              </div>
            </>
          ) : null}

          {jenis === 'Aksi' ? (
            <>
              <div className="grid gap-2">
                <Label htmlFor="KodeAksi">Aksi</Label>
                <Select value={aksi} onValueChange={setAksi}>
                  <SelectTrigger id="KodeAksi">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {daftarAksi.map(([kode, label]) => (
                      <SelectItem key={kode} value={kode}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="grid gap-2">
                <Label htmlFor="KonfigurasiAksi">Konfigurasi (JSON)</Label>
                <Input
                  id="KonfigurasiAksi"
                  className="font-mono text-xs"
                  value={konfigurasiAksi}
                  onChange={(e) => setKonfigurasiAksi(e.target.value)}
                  placeholder='{"TemplateKode":"trial-hari-1"}'
                />
              </div>
            </>
          ) : null}

          {galat ? <p className="text-sm text-destructive">{galat}</p> : null}
          {form.errors.Konfigurasi ? (
            <p className="text-sm text-destructive">{form.errors.Konfigurasi}</p>
          ) : null}

          <DialogFooter>
            <Button type="submit" disabled={form.processing}>
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function DaftarEksekusi({ eksekusi }: { eksekusi: EksekusiOtomasi[] }) {
  if (eksekusi.length === 0) {
    return <p className="text-sm text-muted-foreground">Belum ada eksekusi.</p>;
  }

  return (
    <div className="space-y-2">
      {eksekusi.map((satu) => (
        <Card key={satu.Id}>
          <CardContent className="space-y-2 p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div className="min-w-0">
                <p className="truncate text-sm font-medium">{satu.Prospek ?? 'Tanpa prospek'}</p>
                <p className="text-xs text-muted-foreground">
                  Mulai {waktu(satu.DimulaiPada)}
                  {satu.LanjutPada ? ` · lanjut ${waktu(satu.LanjutPada)}` : ''}
                  {satu.Percobaan > 0 ? ` · percobaan ${satu.Percobaan}` : ''}
                </p>
              </div>
              <Badge variant={satu.Status === 'GagalPermanen' ? 'destructive' : 'outline'}>
                {satu.Status}
              </Badge>
            </div>

            {satu.Galat ? <p className="text-xs text-destructive">{satu.Galat}</p> : null}

            {satu.Log.length > 0 ? (
              <ol className="space-y-1 text-xs text-muted-foreground">
                {satu.Log.map((log) => (
                  <li key={`${satu.Id}-${log.Urutan}-${log.Hasil}`}>
                    #{log.Urutan} {log.Jenis} → {log.Hasil}
                    {log.Ringkasan ? `: ${log.Ringkasan}` : ''}
                  </li>
                ))}
              </ol>
            ) : null}
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
