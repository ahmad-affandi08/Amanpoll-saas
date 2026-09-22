import { FormEvent, useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DatePicker } from '@/components/ui/date-picker';
import { PanelKolaborasi } from '@/components/kolaborasi/PanelKolaborasi';
import { http } from '@/lib/http';
import { formatUang } from '@/lib/uang';
import type {
  Aset,
  KategoriAset,
  ModelAset,
  RiwayatLokasiAset,
  RiwayatPenanggungJawabAset,
  RelasiAset,
  GaransiAset,
  NilaiAset,
  MeterAset,
  PembacaanMeterAset,
} from '@/features/Aset/types';
import type { Lokasi } from '@/features/Lokasi/types';
import type { UnitOrganisasi } from '@/features/UnitOrganisasi/types';
import type { Penyedia } from '@/features/Penyedia/types';
import { VARIAN_BADGE_STATUS_ASET } from '@/features/Aset/status';
import { ruteAset } from '@/features/Aset/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { TANPA_PILIHAN } from '@/lib/pilihan';
import { DialogTambahMeter } from '@/features/Aset/components/DialogTambahMeter';

interface Props {
  aset: Aset;
  kategoriAset: KategoriAset[];
  modelAset: ModelAset[];
  penyedia: Penyedia[];
  unitOrganisasi: UnitOrganisasi[];
  lokasi: Lokasi[];
}

function TabInfo({ aset, kategoriAset, modelAset, penyedia, unitOrganisasi }: Props) {
  const form = useForm({
    KategoriAsetId: aset.KategoriAsetId,
    ModelAsetId: aset.ModelAsetId ?? TANPA_PILIHAN,
    PenyediaId: aset.PenyediaId ?? TANPA_PILIHAN,
    UnitOrganisasiId: aset.UnitOrganisasiId ?? TANPA_PILIHAN,
    KodeAset: aset.KodeAset,
    Nama: aset.Nama,
    NomorSeri: aset.NomorSeri ?? '',
    NomorInventaris: aset.NomorInventaris ?? '',
    NomorRegistrasiEksternal: aset.NomorRegistrasiEksternal ?? '',
    TanggalPerolehan: aset.TanggalPerolehan ?? '',
    TanggalMulaiOperasi: aset.TanggalMulaiOperasi ?? '',
    TanggalAkhirOperasi: aset.TanggalAkhirOperasi ?? '',
    HargaPerolehan: aset.HargaPerolehan ?? '',
    NilaiResidu: aset.NilaiResidu ?? '',
    MataUang: aset.MataUang,
    SumberDana: aset.SumberDana ?? '',
    MetodePenyusutan: aset.MetodePenyusutan ?? '',
    UmurManfaatBulan: aset.UmurManfaatBulan?.toString() ?? '',
    Status: aset.Status,
    Kondisi: aset.Kondisi,
    TingkatKritis: aset.TingkatKritis,
    NfcUid: aset.NfcUid ?? '',
    KodeBatang: aset.KodeBatang ?? '',
    Catatan: aset.Catatan ?? '',
    Versi: aset.Versi,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      ModelAsetId: form.data.ModelAsetId === TANPA_PILIHAN ? null : form.data.ModelAsetId,
      PenyediaId: form.data.PenyediaId === TANPA_PILIHAN ? null : form.data.PenyediaId,
      UnitOrganisasiId: form.data.UnitOrganisasiId === TANPA_PILIHAN ? null : form.data.UnitOrganisasiId,
    };
    router.put(ruteAset.detail(aset.Id), payload, { preserveScroll: true });
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label>Kode Aset</Label>
          <Input
            value={form.data.KodeAset}
            onChange={(e) => form.setData('KodeAset', e.target.value)}
            className="font-mono"
          />
          {form.errors.KodeAset && <p className="text-sm text-destructive">{form.errors.KodeAset}</p>}
        </div>
        <div className="space-y-2">
          <Label>Nama</Label>
          <Input value={form.data.Nama} onChange={(e) => form.setData('Nama', e.target.value)} />
          {form.errors.Nama && <p className="text-sm text-destructive">{form.errors.Nama}</p>}
        </div>
      </div>
      <div className="grid grid-cols-3 gap-4">
        <div className="space-y-2">
          <Label>Kategori</Label>
          <Select value={form.data.KategoriAsetId} onValueChange={(v) => form.setData('KategoriAsetId', v)}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {kategoriAset.map((k) => (
                <SelectItem key={k.Id} value={k.Id}>
                  {k.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-2">
          <Label>Model</Label>
          <Select value={form.data.ModelAsetId} onValueChange={(v) => form.setData('ModelAsetId', v)}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN}>Tanpa model</SelectItem>
              {modelAset.map((m) => (
                <SelectItem key={m.Id} value={m.Id}>
                  {m.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-2">
          <Label>Penyedia</Label>
          <Select value={form.data.PenyediaId} onValueChange={(v) => form.setData('PenyediaId', v)}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN}>Tanpa penyedia</SelectItem>
              {penyedia.map((p) => (
                <SelectItem key={p.Id} value={p.Id}>
                  {p.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>
      <div className="grid grid-cols-3 gap-4">
        <div className="space-y-2">
          <Label>Unit Organisasi</Label>
          <Select
            value={form.data.UnitOrganisasiId}
            onValueChange={(v) => form.setData('UnitOrganisasiId', v)}
          >
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN}>Tidak ditautkan</SelectItem>
              {unitOrganisasi.map((u) => (
                <SelectItem key={u.Id} value={u.Id}>
                  {u.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-2">
          <Label>Nomor Seri</Label>
          <Input value={form.data.NomorSeri} onChange={(e) => form.setData('NomorSeri', e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label>Nomor Inventaris</Label>
          <Input
            value={form.data.NomorInventaris}
            onChange={(e) => form.setData('NomorInventaris', e.target.value)}
          />
        </div>
      </div>
      <div className="grid grid-cols-3 gap-4">
        <div className="space-y-2">
          <Label>Tanggal Perolehan</Label>
          <DatePicker
            value={form.data.TanggalPerolehan}
            onChange={(val) => form.setData('TanggalPerolehan', val)}
            placeholder="Pilih tanggal perolehan"
          />
        </div>
        <div className="space-y-2">
          <Label>Tanggal Mulai Operasi</Label>
          <DatePicker
            value={form.data.TanggalMulaiOperasi}
            onChange={(val) => form.setData('TanggalMulaiOperasi', val)}
            placeholder="Pilih tanggal mulai"
          />
        </div>
        <div className="space-y-2">
          <Label>Tanggal Akhir Operasi</Label>
          <DatePicker
            value={form.data.TanggalAkhirOperasi}
            onChange={(val) => form.setData('TanggalAkhirOperasi', val)}
            placeholder="Pilih tanggal akhir"
          />
        </div>
      </div>
      <div className="grid grid-cols-3 gap-4">
        <div className="space-y-2">
          <Label>Harga Perolehan</Label>
          <Input
            type="number"
            min={0}
            value={form.data.HargaPerolehan}
            onChange={(e) => form.setData('HargaPerolehan', e.target.value)}
          />
        </div>
        <div className="space-y-2">
          <Label>Nilai Residu</Label>
          <Input
            type="number"
            min={0}
            value={form.data.NilaiResidu}
            onChange={(e) => form.setData('NilaiResidu', e.target.value)}
          />
        </div>
        <div className="space-y-2">
          <Label>Umur Manfaat (bulan)</Label>
          <Input
            type="number"
            min={1}
            value={form.data.UmurManfaatBulan}
            onChange={(e) => form.setData('UmurManfaatBulan', e.target.value)}
          />
        </div>
      </div>
      <div className="grid grid-cols-3 gap-4">
        <div className="space-y-2">
          <Label>Status</Label>
          <Select value={form.data.Status} onValueChange={(v) => form.setData('Status', v as Aset['Status'])}>
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {['Aktif', 'Nonaktif', 'Dipinjam', 'Rusak', 'Diarsipkan'].map((s) => (
                <SelectItem key={s} value={s}>
                  {s}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-2">
          <Label>Kondisi</Label>
          <Select
            value={form.data.Kondisi}
            onValueChange={(v) => form.setData('Kondisi', v as Aset['Kondisi'])}
          >
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {['Baik', 'PerluPerhatian', 'Rusak'].map((s) => (
                <SelectItem key={s} value={s}>
                  {s}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-2">
          <Label>Tingkat Kritis</Label>
          <Select
            value={form.data.TingkatKritis}
            onValueChange={(v) => form.setData('TingkatKritis', v as Aset['TingkatKritis'])}
          >
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {['Normal', 'Tinggi', 'SangatTinggi'].map((s) => (
                <SelectItem key={s} value={s}>
                  {s}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>
      <div className="space-y-2">
        <Label>Catatan</Label>
        <Textarea
          value={form.data.Catatan}
          onChange={(e) => form.setData('Catatan', e.target.value)}
          rows={2}
        />
      </div>
      <div className="flex justify-end">
        <Button type="submit" disabled={form.processing}>
          Simpan Perubahan
        </Button>
      </div>
    </form>
  );
}

function TabLokasi({ aset, lokasi }: { aset: Aset; lokasi: Lokasi[] }) {
  const [data, setData] = useState<RiwayatLokasiAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({ LokasiTujuanId: TANPA_PILIHAN, Alasan: '' });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.riwayatLokasi(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      LokasiTujuanId: form.data.LokasiTujuanId === TANPA_PILIHAN ? null : form.data.LokasiTujuanId,
      Alasan: form.data.Alasan || null,
    };
    router.post(ruteAset.riwayatLokasi(aset.Id), payload, { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-4">
      <div className="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
        Lokasi saat ini:{' '}
        <span className="font-semibold text-foreground">{aset.NamaLokasi ?? 'Belum ditentukan'}</span>
      </div>
      <form onSubmit={submit} className="flex flex-wrap items-end gap-2 border-b border-border pb-4">
        <div className="space-y-1">
          <Label className="text-xs">Pindahkan ke</Label>
          <Select value={form.data.LokasiTujuanId} onValueChange={(v) => form.setData('LokasiTujuanId', v)}>
            <SelectTrigger className="w-56">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN}>Tidak ada (kosongkan lokasi)</SelectItem>
              {lokasi.map((l) => (
                <SelectItem key={l.Id} value={l.Id}>
                  {l.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <Input
          placeholder="Alasan (opsional)"
          value={form.data.Alasan}
          onChange={(e) => form.setData('Alasan', e.target.value)}
          className="w-56"
        />
        <Button type="submit" disabled={form.processing}>
          Pindahkan
        </Button>
      </form>
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && (
        <p className="text-sm text-muted-foreground">Belum ada riwayat lokasi.</p>
      )}
      <div className="space-y-2">
        {data.map((r) => (
          <div key={r.Id} className="rounded-md border border-border px-3 py-2 text-sm">
            <div className="font-medium text-foreground">
              {r.NamaLokasiAsal ?? '—'} → {r.NamaLokasiTujuan ?? '—'}
            </div>
            <div className="text-xs text-muted-foreground">
              {r.JenisPerpindahan} · {new Date(r.DipindahkanPada).toLocaleString('id-ID')} ·{' '}
              {r.NamaDipindahkanOleh ?? '—'}
            </div>
            {r.Alasan && <div className="text-xs text-muted-foreground">"{r.Alasan}"</div>}
          </div>
        ))}
      </div>
    </div>
  );
}

function TabPenanggungJawab({ aset, unitOrganisasi }: { aset: Aset; unitOrganisasi: UnitOrganisasi[] }) {
  const [data, setData] = useState<RiwayatPenanggungJawabAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({ Jenis: 'unit' as 'unit', UnitOrganisasiId: TANPA_PILIHAN, Catatan: '' });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.penanggungJawab(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (form.data.UnitOrganisasiId === TANPA_PILIHAN) return;
    router.post(
      ruteAset.penanggungJawab(aset.Id),
      {
        UnitOrganisasiId: form.data.UnitOrganisasiId,
        Catatan: form.data.Catatan || null,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          form.reset();
          muat();
        },
      },
    );
  };

  const aktif = data.find((r) => r.SelesaiPada === null);

  return (
    <div className="space-y-4">
      <div className="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
        Penanggung jawab saat ini:{' '}
        <span className="font-semibold text-foreground">
          {aktif ? (aktif.NamaPengguna ?? aktif.NamaUnitOrganisasi ?? '—') : 'Belum ditetapkan'}
        </span>
      </div>
      <form onSubmit={submit} className="flex flex-wrap items-end gap-2 border-b border-border pb-4">
        <div className="space-y-1">
          <Label className="text-xs">Unit Penanggung Jawab</Label>
          <Select
            value={form.data.UnitOrganisasiId}
            onValueChange={(v) => form.setData('UnitOrganisasiId', v)}
          >
            <SelectTrigger className="w-56">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN}>Pilih unit</SelectItem>
              {unitOrganisasi.map((u) => (
                <SelectItem key={u.Id} value={u.Id}>
                  {u.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <Input
          placeholder="Catatan (opsional)"
          value={form.data.Catatan}
          onChange={(e) => form.setData('Catatan', e.target.value)}
          className="w-56"
        />
        <Button type="submit" disabled={form.processing}>
          Tetapkan
        </Button>
      </form>
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && (
        <p className="text-sm text-muted-foreground">Belum ada riwayat penanggung jawab.</p>
      )}
      <div className="space-y-2">
        {data.map((r) => (
          <div key={r.Id} className="rounded-md border border-border px-3 py-2 text-sm">
            <div className="flex items-center justify-between">
              <span className="font-medium text-foreground">
                {r.NamaPengguna ?? r.NamaUnitOrganisasi ?? '—'}
              </span>
              {r.SelesaiPada === null && <Badge variant="sukses">Aktif</Badge>}
            </div>
            <div className="text-xs text-muted-foreground">
              {new Date(r.MulaiPada).toLocaleString('id-ID')}{' '}
              {r.SelesaiPada && `-- ${new Date(r.SelesaiPada).toLocaleString('id-ID')}`}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function TabRelasi({ aset }: { aset: Aset }) {
  const konfirmasi = useKonfirmasi();
  const [sebagaiInduk, setSebagaiInduk] = useState<RelasiAset[]>([]);
  const [sebagaiAnak, setSebagaiAnak] = useState<RelasiAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({ AsetAnakId: '', JenisRelasi: 'Komponen' as 'Komponen' | 'Terkait' });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.relasi(aset.Id))
      .then((res) => {
        setSebagaiInduk(res.data.sebagaiInduk);
        setSebagaiAnak(res.data.sebagaiAnak);
      })
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteAset.relasi(aset.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        muat();
      },
    });
  };

  const hapus = async (relasi: RelasiAset) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus relasi ini?',
        deskripsi: 'Hubungan antar aset dilepas; kedua aset tetap tersimpan.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteAset.relasiDetail(relasi.Id), { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-4">
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && (
        <>
          <div>
            <h4 className="mb-2 text-sm font-medium text-foreground">
              Komponen / Aset Terkait (sebagai induk)
            </h4>
            {sebagaiInduk.length === 0 && <p className="text-sm text-muted-foreground">Tidak ada.</p>}
            <div className="space-y-2">
              {sebagaiInduk.map((r) => (
                <div
                  key={r.Id}
                  className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
                >
                  <div>
                    <span className="font-medium text-foreground">{r.NamaAsetAnak}</span>{' '}
                    <Badge variant="secondary">{r.JenisRelasi}</Badge>
                  </div>
                  <Button variant="ghost" size="sm" onClick={() => hapus(r)}>
                    Hapus
                  </Button>
                </div>
              ))}
            </div>
          </div>
          <div>
            <h4 className="mb-2 text-sm font-medium text-foreground">
              Bagian dari (sebagai komponen aset lain)
            </h4>
            {sebagaiAnak.length === 0 && <p className="text-sm text-muted-foreground">Tidak ada.</p>}
            <div className="space-y-2">
              {sebagaiAnak.map((r) => (
                <div key={r.Id} className="rounded-md border border-border px-3 py-2 text-sm">
                  <span className="font-medium text-foreground">{r.NamaAsetInduk}</span>{' '}
                  <Badge variant="secondary">{r.JenisRelasi}</Badge>
                </div>
              ))}
            </div>
          </div>
        </>
      )}
      <form onSubmit={submit} className="flex flex-wrap items-end gap-2 border-t border-border pt-4">
        <div className="space-y-1">
          <Label className="text-xs">ID Aset Terkait</Label>
          <Input
            value={form.data.AsetAnakId}
            onChange={(e) => form.setData('AsetAnakId', e.target.value)}
            placeholder="Id aset lain"
            className="w-56 font-mono text-xs"
          />
        </div>
        <div className="space-y-1">
          <Label className="text-xs">Jenis</Label>
          <Select
            value={form.data.JenisRelasi}
            onValueChange={(v) => form.setData('JenisRelasi', v as 'Komponen' | 'Terkait')}
          >
            <SelectTrigger className="w-40">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Komponen">Komponen</SelectItem>
              <SelectItem value="Terkait">Terkait</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <Button type="submit" disabled={form.processing}>
          Tambah Relasi
        </Button>
      </form>
    </div>
  );
}

function TabGaransi({ aset, penyedia }: { aset: Aset; penyedia: Penyedia[] }) {
  const konfirmasi = useKonfirmasi();
  const [data, setData] = useState<GaransiAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const form = useForm({
    PenyediaId: TANPA_PILIHAN,
    NomorGaransi: '',
    MulaiPada: '',
    BerakhirPada: '',
    Status: 'Aktif' as const,
  });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.garansi(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const payload = {
      ...form.data,
      PenyediaId: form.data.PenyediaId === TANPA_PILIHAN ? null : form.data.PenyediaId,
    };
    router.post(ruteAset.garansi(aset.Id), payload, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        muat();
      },
    });
  };

  const hapus = async (garansi: GaransiAset) => {
    if (
      !(await konfirmasi({
        judul: 'Hapus garansi ini?',
        deskripsi: 'Pengingat masa garansi untuk aset ini ikut berhenti.',
        ragam: 'bahaya',
      }))
    )
      return;
    router.delete(ruteAset.garansiDetail(garansi.Id), { preserveScroll: true, onSuccess: muat });
  };

  return (
    <div className="space-y-4">
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && <p className="text-sm text-muted-foreground">Belum ada garansi.</p>}
      <div className="space-y-2">
        {data.map((g) => (
          <div key={g.Id} className="rounded-md border border-border px-3 py-2 text-sm">
            <div className="flex items-center justify-between">
              <span className="font-medium text-foreground">
                {g.NamaPenyedia ?? g.NomorGaransi ?? 'Garansi'}
              </span>
              <div className="flex gap-1">
                {g.AkanBerakhir && <Badge variant="perhatian">Akan berakhir {g.SisaHari} hari</Badge>}
                {g.SudahBerakhir && <Badge variant="bahaya">Sudah berakhir</Badge>}
                <Button variant="ghost" size="sm" onClick={() => hapus(g)}>
                  Hapus
                </Button>
              </div>
            </div>
            <div className="text-xs text-muted-foreground">
              {g.MulaiPada} s/d {g.BerakhirPada}
            </div>
          </div>
        ))}
      </div>
      <form onSubmit={submit} className="space-y-2 border-t border-border pt-4">
        <div className="grid grid-cols-2 gap-2">
          <Select value={form.data.PenyediaId} onValueChange={(v) => form.setData('PenyediaId', v)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={TANPA_PILIHAN}>Tanpa penyedia</SelectItem>
              {penyedia.map((p) => (
                <SelectItem key={p.Id} value={p.Id}>
                  {p.Nama}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Input
            placeholder="Nomor Garansi"
            value={form.data.NomorGaransi}
            onChange={(e) => form.setData('NomorGaransi', e.target.value)}
          />
        </div>
        <div className="grid grid-cols-2 gap-2">
          <div className="space-y-1">
            <Label className="text-xs">Mulai</Label>
            <DatePicker
              value={form.data.MulaiPada}
              onChange={(val) => form.setData('MulaiPada', val)}
              placeholder="Pilih tanggal mulai"
            />
          </div>
          <div className="space-y-1">
            <Label className="text-xs">Berakhir</Label>
            <DatePicker
              value={form.data.BerakhirPada}
              onChange={(val) => form.setData('BerakhirPada', val)}
              placeholder="Pilih tanggal berakhir"
            />
          </div>
        </div>
        <Button type="submit" disabled={form.processing}>
          Tambah Garansi
        </Button>
      </form>
    </div>
  );
}

function TabNilai({ aset }: { aset: Aset }) {
  const [data, setData] = useState<NilaiAset[]>([]);
  const [memuat, setMemuat] = useState(true);
  const [errorPratinjau, setErrorPratinjau] = useState<string | null>(null);
  const form = useForm({
    TanggalNilai: '',
    NilaiBuku: '',
    AkumulasiPenyusutan: '',
    BebanPenyusutanPeriode: '',
  });

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.nilai(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  const hitungPratinjau = () => {
    if (!form.data.TanggalNilai) return;
    setErrorPratinjau(null);
    http
      .get(ruteAset.nilaiPratinjau(aset.Id), { params: { tanggal: form.data.TanggalNilai } })
      .then((res) => {
        form.setData({
          ...form.data,
          NilaiBuku: String(res.data.NilaiBuku),
          AkumulasiPenyusutan: String(res.data.AkumulasiPenyusutan),
          BebanPenyusutanPeriode: String(res.data.BebanPenyusutanPeriode),
        });
      })
      .catch((err) => {
        setErrorPratinjau(err.response?.data?.pesan ?? 'Gagal menghitung penyusutan otomatis.');
      });
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteAset.nilai(aset.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        muat();
      },
    });
  };

  return (
    <div className="space-y-4">
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && (
        <p className="text-sm text-muted-foreground">Belum ada catatan nilai.</p>
      )}
      <div className="space-y-2">
        {data.map((n) => (
          <div
            key={n.Id}
            className="flex items-center justify-between rounded-md border border-border px-3 py-2 text-sm"
          >
            <span className="text-foreground">{n.TanggalNilai}</span>
            <span className="font-medium text-foreground">{formatUang(n.NilaiBuku, aset.MataUang)}</span>
          </div>
        ))}
      </div>
      <form onSubmit={submit} className="space-y-2 border-t border-border pt-4">
        <div className="flex items-end gap-2">
          <div className="space-y-1 flex-1">
            <Label className="text-xs">Tanggal Nilai</Label>
            <DatePicker
              value={form.data.TanggalNilai}
              onChange={(val) => form.setData('TanggalNilai', val)}
              placeholder="Pilih tanggal nilai"
            />
          </div>
          <Button type="button" variant="outline" onClick={hitungPratinjau}>
            Hitung Otomatis (Garis Lurus)
          </Button>
        </div>
        {errorPratinjau && <p className="text-sm text-destructive">{errorPratinjau}</p>}
        <div className="grid grid-cols-3 gap-2">
          <Input
            placeholder="Nilai Buku"
            type="number"
            value={form.data.NilaiBuku}
            onChange={(e) => form.setData('NilaiBuku', e.target.value)}
          />
          <Input
            placeholder="Akumulasi Penyusutan"
            type="number"
            value={form.data.AkumulasiPenyusutan}
            onChange={(e) => form.setData('AkumulasiPenyusutan', e.target.value)}
          />
          <Input
            placeholder="Beban Periode"
            type="number"
            value={form.data.BebanPenyusutanPeriode}
            onChange={(e) => form.setData('BebanPenyusutanPeriode', e.target.value)}
          />
        </div>
        <Button type="submit" disabled={form.processing}>
          Simpan Nilai
        </Button>
      </form>
    </div>
  );
}

function KartuMeter({ meter, onUbah }: { meter: MeterAset; onUbah: () => void }) {
  const [data, setData] = useState<PembacaanMeterAset[]>([]);
  const [buka, setBuka] = useState(false);
  const form = useForm({ Nilai: '', DibacaPada: '' });

  const muat = () => {
    http.get(ruteAset.meterPembacaan(meter.Id)).then((res) => setData(res.data));
  };

  useEffect(muat, [meter.Id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    router.post(ruteAset.meterPembacaan(meter.Id), form.data, {
      preserveScroll: true,
      onSuccess: () => {
        setBuka(false);
        form.reset();
        muat();
        onUbah();
      },
    });
  };

  return (
    <div className="rounded-md border border-border p-3">
      <div className="flex items-center justify-between">
        <div>
          <span className="font-medium text-foreground">{meter.Nama}</span>{' '}
          <Badge variant="secondary">{meter.Jenis}</Badge>
        </div>
        <Dialog open={buka} onOpenChange={setBuka}>
          <DialogTrigger asChild>
            <Button variant="outline" size="sm">
              Catat Pembacaan
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Catat Pembacaan -- {meter.Nama}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
              <Input
                placeholder={`Nilai (${meter.Satuan})`}
                type="number"
                value={form.data.Nilai}
                onChange={(e) => form.setData('Nilai', e.target.value)}
              />
              <Input
                type="datetime-local"
                value={form.data.DibacaPada}
                onChange={(e) => form.setData('DibacaPada', e.target.value)}
              />
              {form.errors.Nilai && <p className="text-sm text-destructive">{form.errors.Nilai}</p>}
              <DialogFooter>
                <Button type="submit" disabled={form.processing}>
                  Simpan
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <div className="mt-1 text-sm text-muted-foreground">
        Nilai terakhir: {meter.NilaiTerakhir ?? meter.NilaiAwal} {meter.Satuan}
      </div>
      <div className="mt-2 space-y-1">
        {data.slice(0, 5).map((p) => (
          <div key={p.Id} className="text-xs text-muted-foreground">
            {new Date(p.DibacaPada).toLocaleString('id-ID')}:{' '}
            <span className="font-medium text-foreground">
              {p.Nilai} {meter.Satuan}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

function TabMeter({ aset }: { aset: Aset }) {
  const [data, setData] = useState<MeterAset[]>([]);
  const [memuat, setMemuat] = useState(true);

  const muat = () => {
    setMemuat(true);
    http
      .get(ruteAset.meter(aset.Id))
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, [aset.Id]);

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <DialogTambahMeter aset={aset} onSukses={muat} />
      </div>
      {memuat && <p className="text-sm text-muted-foreground">Memuat...</p>}
      {!memuat && data.length === 0 && <p className="text-sm text-muted-foreground">Belum ada meter.</p>}
      <div className="space-y-3">
        {data.map((m) => (
          <KartuMeter key={m.Id} meter={m} onUbah={muat} />
        ))}
      </div>
    </div>
  );
}

export default function AsetShow({ aset, kategoriAset, modelAset, penyedia, unitOrganisasi, lokasi }: Props) {
  return (
    <KerangkaAplikasi>
      <Head title={aset.Nama} />
      <KepalaHalaman
        className="mb-6"
        judul={aset.Nama}
        labelBreadcrumb={aset.KodeAset}
        lencana={<Badge variant={VARIAN_BADGE_STATUS_ASET[aset.Status]}>{aset.Status}</Badge>}
        deskripsi={
          <span className="font-mono">
            {aset.KodeAset}
            {aset.KodeQr && ` · QR: ${aset.KodeQr}`}
          </span>
        }
      />

      <Tabs defaultValue="info">
        <TabsList>
          <TabsTrigger value="info">Info</TabsTrigger>
          <TabsTrigger value="lokasi">Lokasi</TabsTrigger>
          <TabsTrigger value="penanggung-jawab">Penanggung Jawab</TabsTrigger>
          <TabsTrigger value="relasi">Relasi</TabsTrigger>
          <TabsTrigger value="garansi">Garansi</TabsTrigger>
          <TabsTrigger value="nilai">Nilai</TabsTrigger>
          <TabsTrigger value="meter">Meter</TabsTrigger>
          <TabsTrigger value="kolaborasi">Kolaborasi</TabsTrigger>
        </TabsList>
        <TabsContent value="info">
          <TabInfo
            aset={aset}
            kategoriAset={kategoriAset}
            modelAset={modelAset}
            penyedia={penyedia}
            unitOrganisasi={unitOrganisasi}
            lokasi={lokasi}
          />
        </TabsContent>
        <TabsContent value="lokasi">
          <TabLokasi aset={aset} lokasi={lokasi} />
        </TabsContent>
        <TabsContent value="penanggung-jawab">
          <TabPenanggungJawab aset={aset} unitOrganisasi={unitOrganisasi} />
        </TabsContent>
        <TabsContent value="relasi">
          <TabRelasi aset={aset} />
        </TabsContent>
        <TabsContent value="garansi">
          <TabGaransi aset={aset} penyedia={penyedia} />
        </TabsContent>
        <TabsContent value="nilai">
          <TabNilai aset={aset} />
        </TabsContent>
        <TabsContent value="meter">
          <TabMeter aset={aset} />
        </TabsContent>
        <TabsContent value="kolaborasi">
          <PanelKolaborasi jenisEntitas="Aset" entitasId={aset.Id} />
        </TabsContent>
      </Tabs>
    </KerangkaAplikasi>
  );
}
