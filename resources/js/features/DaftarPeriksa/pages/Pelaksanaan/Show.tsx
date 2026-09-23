import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ArrowLeft, AlertTriangle, Lock, Save, CheckCheck, Building, Wrench, User } from 'lucide-react';
import type { PelaksanaanDaftarPeriksa } from '@/features/PreventifInspeksi/types';
import { statusPelaksanaanBadge } from '@/features/PreventifInspeksi/status';
import { ruteDaftarPeriksa } from '@/features/DaftarPeriksa/api';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { BreadcrumbHalaman } from '@/components/shared/BreadcrumbHalaman';
import { formatAngkaUkur } from '@/lib/angka';

interface Props {
  pelaksanaan: PelaksanaanDaftarPeriksa;
}

export default function DaftarPeriksaPelaksanaanShow({ pelaksanaan }: Props) {
  const konfirmasi = useKonfirmasi();
  const terkunci = pelaksanaan.Status === 'Selesai';
  const butirList = pelaksanaan.templat_daftar_periksa?.butir ?? [];

  // Inisialisasi state jawaban dari data tersimpan
  const jawabanAwal: Record<
    string,
    {
      NilaiTeks: string;
      NilaiAngka: string;
      NilaiBoolean: boolean | null;
      Catatan: string;
    }
  > = {};

  pelaksanaan.jawaban?.forEach((j) => {
    jawabanAwal[j.ButirTemplatDaftarPeriksaId] = {
      NilaiTeks: j.NilaiTeks || '',
      NilaiAngka: j.NilaiAngka !== null && j.NilaiAngka !== undefined ? String(j.NilaiAngka) : '',
      NilaiBoolean: j.NilaiBoolean ?? null,
      Catatan: j.Catatan || '',
    };
  });

  const [jawabanState, setJawabanState] = useState(jawabanAwal);
  const [catatanPelaksanaan, setCatatanPelaksanaan] = useState(pelaksanaan.Catatan || '');
  const [sedangMenyimpan, setSedangMenyimpan] = useState(false);

  const updateJawaban = (butirId: string, field: string, value: any) => {
    if (terkunci) return;
    setJawabanState((prev) => ({
      ...prev,
      [butirId]: {
        ...(prev[butirId] || { NilaiTeks: '', NilaiAngka: '', NilaiBoolean: null, Catatan: '' }),
        [field]: value,
      },
    }));
  };

  const simpanKemajuan = () => {
    setSedangMenyimpan(true);
    const daftar = Object.entries(jawabanState).map(([butirId, val]) => ({
      ButirTemplatDaftarPeriksaId: butirId,
      NilaiTeks: val.NilaiTeks || null,
      NilaiAngka: val.NilaiAngka !== '' ? Number(val.NilaiAngka) : null,
      NilaiBoolean: val.NilaiBoolean,
      Catatan: val.Catatan || null,
    }));

    router.put(
      `/preventif-inspeksi/pelaksanaan-daftar-periksa/${pelaksanaan.Id}/jawaban`,
      { jawaban: daftar },
      {
        onFinish: () => setSedangMenyimpan(false),
      },
    );
  };

  const finalisasi = async () => {
    if (
      await konfirmasi({
        judul: 'Selesaikan pelaksanaan daftar periksa ini?',
        deskripsi: 'Jawaban dikunci dan skor dihitung; perubahan setelah ini tidak dapat dilakukan.',
        ragam: 'perhatian',
        labelAksi: 'Selesaikan',
      })
    ) {
      setSedangMenyimpan(true);
      // Simpan jawaban terlebih dahulu lalu finalisasi
      const daftar = Object.entries(jawabanState).map(([butirId, val]) => ({
        ButirTemplatDaftarPeriksaId: butirId,
        NilaiTeks: val.NilaiTeks || null,
        NilaiAngka: val.NilaiAngka !== '' ? Number(val.NilaiAngka) : null,
        NilaiBoolean: val.NilaiBoolean,
        Catatan: val.Catatan || null,
      }));

      router.put(
        `/preventif-inspeksi/pelaksanaan-daftar-periksa/${pelaksanaan.Id}/jawaban`,
        { jawaban: daftar },
        {
          onSuccess: () => {
            router.post(ruteDaftarPeriksa.pelaksanaanFinalisasi(pelaksanaan.Id), {
              catatan: catatanPelaksanaan || null,
            });
          },
          onFinish: () => setSedangMenyimpan(false),
        },
      );
    }
  };

  const badgeInfo = statusPelaksanaanBadge[pelaksanaan.Status] ?? { label: pelaksanaan.Status, kelas: '' };

  return (
    <KerangkaAplikasi>
      <Head title={`Pelaksanaan: ${pelaksanaan.templat_daftar_periksa?.Nama ?? 'Checklist'}`} />
      <BreadcrumbHalaman />

      <div className="space-y-6 max-w-4xl mx-auto">
        {/* Navigasi Balik */}
        <div className="flex items-center gap-2 text-sm text-grafit-500">
          {pelaksanaan.PerintahKerjaId ? (
            <Link
              href={`/pemeliharaan/perintah-kerja/${pelaksanaan.PerintahKerjaId}`}
              className="inline-flex min-h-11 items-center gap-1 rounded-[5px] underline-offset-4 hover:text-grafit-950 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
            >
              <ArrowLeft className="h-4 w-4" />
              <span>Kembali ke Perintah Kerja ({pelaksanaan.perintah_kerja?.Nomor ?? 'PK'})</span>
            </Link>
          ) : (
            <Link
              href={ruteDaftarPeriksa.index}
              className="inline-flex min-h-11 items-center gap-1 rounded-[5px] underline-offset-4 hover:text-grafit-950 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring md:min-h-0"
            >
              <ArrowLeft className="h-4 w-4" />
              <span>Kembali ke Templat</span>
            </Link>
          )}
        </div>

        {/* Header Lembar Periksa */}
        <div className="bg-card border border-garis-200 rounded-xl p-6 shadow-sm">
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-permukaan-100 text-grafit-700">
                  {pelaksanaan.templat_daftar_periksa?.Kode}
                </span>
                <Badge variant="outline" className={badgeInfo.kelas}>
                  {badgeInfo.label}
                </Badge>
                {terkunci && (
                  <Badge
                    variant="outline"
                    className="bg-safety-500/10 text-safety-700 border-safety-600/30 gap-1"
                  >
                    <Lock className="h-3 w-3" /> Terkunci
                  </Badge>
                )}
              </div>

              <h1 className="text-xl font-bold text-grafit-950">
                {pelaksanaan.templat_daftar_periksa?.Nama}
              </h1>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-grafit-700 pt-2">
                {pelaksanaan.aset && (
                  <div className="flex items-center gap-1.5">
                    <Building className="h-3.5 w-3.5 text-grafit-500" />
                    <span>
                      Aset:{' '}
                      <strong>
                        {pelaksanaan.aset.KodeAset} - {pelaksanaan.aset.Nama}
                      </strong>
                    </span>
                  </div>
                )}
                {pelaksanaan.perintah_kerja && (
                  <div className="flex items-center gap-1.5">
                    <Wrench className="h-3.5 w-3.5 text-grafit-500" />
                    <span>
                      Perintah Kerja: <strong>{pelaksanaan.perintah_kerja.Nomor}</strong>
                    </span>
                  </div>
                )}
                {pelaksanaan.dilaksanakan_oleh && (
                  <div className="flex items-center gap-1.5">
                    <User className="h-3.5 w-3.5 text-grafit-500" />
                    <span>
                      Pelaksana: <strong>{pelaksanaan.dilaksanakan_oleh.Nama}</strong>
                    </span>
                  </div>
                )}
              </div>
            </div>

            {/* Skor Card jika selesai */}
            {pelaksanaan.Skor !== null && pelaksanaan.Skor !== undefined && (
              <div className="p-4 bg-sukses-50 border border-sukses-200 rounded-xl text-center self-start sm:self-auto min-w-[140px]">
                <span className="text-xs font-medium text-sukses-700">Skor Kepatuhan</span>
                <div className="text-3xl font-black text-sukses-700 mt-0.5">{pelaksanaan.Skor}%</div>
              </div>
            )}
          </div>
        </div>

        {/* Form Interaktif Butir Pertanyaan */}
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-semibold text-grafit-950">Daftar Pemeriksaan Lapangan</h2>
            {!terkunci && (
              <span className="text-xs text-grafit-500">
                Lengkapi seluruh butir wajib sebelum melakukan finalisasi.
              </span>
            )}
          </div>

          <div className="space-y-3">
            {butirList.map((b, idx) => {
              const current = jawabanState[b.Id] || {
                NilaiTeks: '',
                NilaiAngka: '',
                NilaiBoolean: null,
                Catatan: '',
              };

              // Validasi numeric range
              let outOfRange = false;
              if (b.TipeJawaban === 'Angka' && current.NilaiAngka !== '') {
                const n = Number(current.NilaiAngka);
                if (b.NilaiMinimum !== null && b.NilaiMinimum !== undefined && n < b.NilaiMinimum) {
                  outOfRange = true;
                }
                if (b.NilaiMaksimum !== null && b.NilaiMaksimum !== undefined && n > b.NilaiMaksimum) {
                  outOfRange = true;
                }
              }

              return (
                <div
                  key={b.Id}
                  className={`bg-card border rounded-xl p-4 transition-all ${
                    outOfRange ? 'border-bahaya-600/40 bg-bahaya-600/5' : 'border-garis-200'
                  }`}
                >
                  <div className="flex items-start gap-3">
                    <span className="flex-shrink-0 w-6 h-6 rounded-full bg-permukaan-100 text-grafit-700 text-xs font-bold flex items-center justify-center mt-0.5">
                      {idx + 1}
                    </span>

                    <div className="space-y-3 flex-1">
                      <div>
                        <div className="flex items-center gap-2 flex-wrap">
                          <span className="font-medium text-grafit-950 text-sm">{b.Pertanyaan}</span>
                          {b.Wajib && <span className="text-xs text-destructive font-semibold">*Wajib</span>}
                        </div>
                        {b.Satuan && <span className="text-xs text-grafit-500">Satuan: {b.Satuan}</span>}
                      </div>

                      {/* Input Sesuai Tipe */}
                      <div className="pt-1">
                        {b.TipeJawaban === 'YaTidak' && (
                          <div className="flex items-center gap-3">
                            <button
                              type="button"
                              disabled={terkunci}
                              className={`px-4 py-1.5 rounded-lg text-xs font-medium border cursor-pointer transition-colors ${
                                current.NilaiBoolean === true
                                  ? 'bg-sukses-600 text-white border-sukses-600'
                                  : 'bg-white text-grafit-700 border-garis-300 hover:bg-accent'
                              }`}
                              onClick={() => updateJawaban(b.Id, 'NilaiBoolean', true)}
                            >
                              Ya / Sesuai
                            </button>
                            <button
                              type="button"
                              disabled={terkunci}
                              className={`px-4 py-1.5 rounded-lg text-xs font-medium border cursor-pointer transition-colors ${
                                current.NilaiBoolean === false
                                  ? 'bg-destructive text-white border-bahaya-600'
                                  : 'bg-white text-grafit-700 border-garis-300 hover:bg-accent'
                              }`}
                              onClick={() => updateJawaban(b.Id, 'NilaiBoolean', false)}
                            >
                              Tidak / Tidak Sesuai
                            </button>
                          </div>
                        )}

                        {b.TipeJawaban === 'Angka' && (
                          <div className="space-y-1.5 max-w-xs">
                            <div className="flex items-center gap-2">
                              <Input
                                type="number"
                                step="any"
                                disabled={terkunci}
                                placeholder={`Rentang: ${formatAngkaUkur(b.NilaiMinimum)} - ${formatAngkaUkur(b.NilaiMaksimum)}`}
                                value={current.NilaiAngka}
                                onChange={(e) => updateJawaban(b.Id, 'NilaiAngka', e.target.value)}
                                className={outOfRange ? 'border-bahaya-600 focus:ring-bahaya-600' : ''}
                              />
                              {b.Satuan && <span className="text-xs text-grafit-500">{b.Satuan}</span>}
                            </div>
                            {outOfRange && (
                              <p className="text-xs text-destructive flex items-center gap-1 font-medium">
                                <AlertTriangle className="h-3 w-3" />
                                Nilai di luar batas normal ({b.NilaiMinimum} - {b.NilaiMaksimum})
                              </p>
                            )}
                          </div>
                        )}

                        {b.TipeJawaban === 'Pilihan' && b.Pilihan && (
                          <div className="max-w-xs">
                            <select
                              disabled={terkunci}
                              className="w-full text-xs rounded-md border border-garis-300 bg-white py-2 px-3 focus:outline-none focus:ring-2 focus:ring-teknisi-500 cursor-pointer"
                              value={current.NilaiTeks}
                              onChange={(e) => updateJawaban(b.Id, 'NilaiTeks', e.target.value)}
                            >
                              <option value="">-- Pilih Opsi --</option>
                              {b.Pilihan.map((p) => (
                                <option key={p} value={p}>
                                  {p}
                                </option>
                              ))}
                            </select>
                          </div>
                        )}

                        {(b.TipeJawaban === 'Teks' || b.TipeJawaban === 'Foto') && (
                          <Input
                            type="text"
                            disabled={terkunci}
                            placeholder="Tuliskan keterangan hasil..."
                            value={current.NilaiTeks}
                            onChange={(e) => updateJawaban(b.Id, 'NilaiTeks', e.target.value)}
                          />
                        )}
                      </div>

                      {/* Catatan Per Butir */}
                      <div className="pt-2 border-t border-permukaan-100">
                        <Input
                          type="text"
                          disabled={terkunci}
                          placeholder="Catatan tambahan / temuan khusus untuk parameter ini..."
                          value={current.Catatan}
                          onChange={(e) => updateJawaban(b.Id, 'Catatan', e.target.value)}
                          className="text-xs h-8 bg-permukaan-50/50"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Catatan Keseluruhan */}
        <div className="bg-card border border-garis-200 rounded-xl p-5 space-y-2">
          <Label htmlFor="CatatanPelaksanaan">Catatan Keseluruhan Pelaksanaan</Label>
          <Textarea
            id="CatatanPelaksanaan"
            disabled={terkunci}
            placeholder="Catatan umum atau rekomendasi terkait kondisi peralatan..."
            value={catatanPelaksanaan}
            onChange={(e) => setCatatanPelaksanaan(e.target.value)}
            rows={2}
          />
        </div>

        {/* Tombol Aksi */}
        {!terkunci && (
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-garis-200">
            <Button
              type="button"
              variant="outline"
              className="cursor-pointer gap-2"
              onClick={simpanKemajuan}
              disabled={sedangMenyimpan}
            >
              <Save className="h-4 w-4" />
              {sedangMenyimpan ? 'Menyimpan...' : 'Simpan Draf'}
            </Button>
            <Button
              type="button"
              className="cursor-pointer bg-sukses-600 hover:bg-sukses-700 text-white gap-2"
              onClick={finalisasi}
              disabled={sedangMenyimpan}
            >
              <CheckCheck className="h-4 w-4" />
              Selesaikan & Kunci Checklist
            </Button>
          </div>
        )}
      </div>
    </KerangkaAplikasi>
  );
}
