import { Link, router } from '@inertiajs/react';
import { isAxiosError } from 'axios';
import {
  ArrowLeft,
  Check,
  ChevronRight,
  CircleCheck,
  Pause,
  Plus,
  Send,
  Smartphone,
  TriangleAlert,
  X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type ChangeEvent } from 'react';
import { toast } from 'sonner';
import { http } from '@/lib/http';
import { cn } from '@/lib/utils';
import { useKonfirmasi } from '@/hooks/use-konfirmasi';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { Ikon3D } from '@/features/Lapangan/components/Ikon3D';
import { AreaTiket, IsianTiket, MasukanTiket } from '@/features/Lapangan/components/IsianTiket';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import { RuteJam } from '@/features/Lapangan/components/RuteJam';
import { Tiket } from '@/features/Lapangan/components/Tiket';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type {
  ButirChecklistTeknisi,
  JawabanChecklistTeknisi,
  PropsKerjakanTeknisi,
} from '@/features/Lapangan/types';
import { jamPendek } from '@/features/Lapangan/waktu';
import { useAksiTiket } from '@/features/Lapangan/components/teknisi/aksiTiket';
import { BarisDikte } from '@/features/Lapangan/components/teknisi/BarisDikte';
import { perkecilFoto } from '@/features/Lapangan/components/teknisi/foto';
import { KanvasTandaTangan } from '@/features/Lapangan/components/teknisi/KanvasTandaTangan';
import { labelStatusTiket } from '@/features/Lapangan/components/teknisi/KartuTiketTeknisi';
import { LembarMintaSukuCadang } from '@/features/Lapangan/components/teknisi/LembarMintaSukuCadang';
import {
  useFotoTertunda,
  useSesiKerja,
  useUrlBlob,
  type FotoTertunda,
  type SesiKerja,
} from '@/features/Lapangan/components/teknisi/sesiKerja';
import {
  STATUS_BISA_MULAI,
  STATUS_SEDANG_DIKERJAKAN,
  antrikanBerurutan,
  detikSesiTertunda,
  keadaanLokal,
  rencanaUbahStatus,
  rencanaWaktuKerja,
  type PermintaanMutasiTeknisi,
} from '@/features/Lapangan/components/teknisi/statusLokal';
import {
  BilahTetap,
  KELAS_ISI_BERBILAH,
  KartuLangkahKerja,
  LANGKAH_KERJA,
  PewaktuKerja,
  PitaMasalahTiket,
  ikonKodeKegagalan,
  useDetikBerjalan,
  useKirimAntreanSaatBuka,
  usePeringatanOffline,
  type LangkahKerja,
} from '@/features/Lapangan/components/teknisi/umum';
import { STATUS_SELESAI_TEKNISI, durasiPendek } from '@/features/Lapangan/components/teknisi/waktuTiket';

const JUDUL_LANGKAH: Record<LangkahKerja, string> = {
  checklist: 'Checklist',
  diagnosis: 'Diagnosis & tindakan',
  'suku-cadang': 'Suku cadang',
  foto: 'Foto pekerjaan',
  ringkasan: 'Ringkasan',
};

const KONDISI_ASET = ['Berfungsi normal', 'Terbatas', 'Perlu tindak lanjut'] as const;

type SesiKerjaHook = ReturnType<typeof useSesiKerja>;
type FotoHook = ReturnType<typeof useFotoTertunda>;

function langkahAwal(): LangkahKerja {
  if (typeof window === 'undefined') return 'checklist';
  const langkah = new URLSearchParams(window.location.search).get('langkah');
  return LANGKAH_KERJA.find((satu) => satu === langkah) ?? 'checklist';
}

/**
 * Mengerjakan tiket (DESIGN §36.6 layar 07–12) dalam satu halaman supaya seluruh alurnya
 * tetap terbuka tanpa sinyal: Checklist → Diagnosis → Suku cadang → Foto → Ringkasan → Selesai.
 * Setiap perubahan tiket lewat antrean offline FASE 20 dan diterapkan Action Pemeliharaan.
 */
export default function KerjakanTeknisi(props: PropsKerjakanTeknisi) {
  const [langkah, setLangkahState] = useState<LangkahKerja>(langkahAwal);
  const sesiKerja = useSesiKerja(props.tiket.Id);
  const foto = useFotoTertunda(props.tiket.Id);
  const selesai = STATUS_SELESAI_TEKNISI.includes(props.tiket.Status) || Boolean(sesiKerja.sesi.Selesai);

  const setLangkah = (baru: LangkahKerja) => {
    setLangkahState(baru);
    window.scrollTo({ top: 0 });
    // Langkah diingat di URL (tanpa kunjungan Inertia) supaya muat ulang kembali ke langkah ini.
    window.history.replaceState(
      window.history.state,
      '',
      ruteLapangan.teknisi.kerjakan(props.tiket.Id, baru),
    );
  };

  const kembali = () => {
    const indeks = LANGKAH_KERJA.indexOf(langkah);
    if (indeks > 0) setLangkah(LANGKAH_KERJA[indeks - 1]);
    else router.visit(ruteLapangan.teknisi.tugasDetail(props.tiket.Id));
  };

  if (selesai) {
    return (
      <KerangkaLapangan
        varian="polos"
        latar="putih"
        judulHalaman="Pekerjaan selesai"
        bilahAksi={
          <TombolLapangan asChild penuh>
            <Link href={ruteLapangan.teknisi.beranda}>Kembali ke Beranda</Link>
          </TombolLapangan>
        }
      >
        <LayarSelesai {...props} sesi={sesiKerja.sesi} />
      </KerangkaLapangan>
    );
  }

  return (
    <KerangkaLapangan
      varian="appbar"
      judulHalaman={`${JUDUL_LANGKAH[langkah]} · ${props.tiket.Nomor}`}
      judul={JUDUL_LANGKAH[langkah]}
      subjudul={`${props.tiket.Nomor} · ${props.tiket.Aset?.Nama ?? props.tiket.Judul}`}
      kembali={kembali}
      panjang
      aksiKanan={<PewaktuTiket {...props} sesi={sesiKerja.sesi} />}
      classNameIsi={KELAS_ISI_BERBILAH}
    >
      <IsiKerjakan
        {...props}
        langkah={langkah}
        setLangkah={setLangkah}
        sesiKerja={sesiKerja}
        fotoHook={foto}
      />
    </KerangkaLapangan>
  );
}

/** Total waktu kerja: yang sudah tercatat server, sesi yang masih di antrean, dan sesi berjalan. */
function useDetikKerja(props: PropsKerjakanTeknisi, sesi: SesiKerja): { detik: number; berjalan: boolean } {
  const { antrian } = useSinkronisasiOffline();
  const berjalan = useDetikBerjalan(sesi.MulaiPada);
  return {
    detik: props.waktuKerja.TotalMenit * 60 + detikSesiTertunda(props.tiket.Id, antrian) + berjalan,
    berjalan: Boolean(sesi.MulaiPada),
  };
}

function PewaktuTiket(props: PropsKerjakanTeknisi & { sesi: SesiKerja }) {
  const { detik, berjalan } = useDetikKerja(props, props.sesi);
  return <PewaktuKerja detik={detik} berjalan={berjalan} />;
}

interface PropsIsi extends PropsKerjakanTeknisi {
  langkah: LangkahKerja;
  setLangkah: (langkah: LangkahKerja) => void;
  sesiKerja: SesiKerjaHook;
  fotoHook: FotoHook;
}

function IsiKerjakan(props: PropsIsi) {
  usePeringatanOffline();
  useKirimAntreanSaatBuka();
  const { tiket, langkah, sesiKerja, fotoHook } = props;
  const { antrian, daring } = useSinkronisasiOffline();
  const { mulai, memproses } = useAksiTiket();
  const keadaan = keadaanLokal(tiket, antrian);
  const sedangDikerjakan = STATUS_SEDANG_DIKERJAKAN.includes(keadaan.Status);

  // Membuka layar kerja pada tiket yang sedang dikerjakan berarti waktu kerja berjalan.
  useEffect(() => {
    if (sesiKerja.dimuat && sedangDikerjakan && !sesiKerja.sesi.MulaiPada) {
      void sesiKerja.ubah({ MulaiPada: new Date().toISOString() });
    }
  }, [sesiKerja.dimuat, sedangDikerjakan]);

  // Foto yang tersimpan di HP diunggah begitu ada sinyal.
  useEffect(() => {
    if (daring && fotoHook.foto.length > 0) void fotoHook.unggahSemua();
  }, [daring, fotoHook.foto.length]);

  if (!sedangDikerjakan) {
    const bisaMulai = keadaan.PerluRespons || STATUS_BISA_MULAI.includes(keadaan.Status);
    return (
      <Kartu className="mt-2">
        <IlustrasiMomen
          jenis={keadaan.Dialihkan ? 'kosong' : 'tanpa-izin'}
          ikon={keadaan.Dialihkan ? 'satellite_antenna' : 'alarm_clock'}
          judul={keadaan.Dialihkan ? 'Tiket sedang dialihkan' : 'Tiket belum dimulai'}
          teks={
            keadaan.Dialihkan
              ? 'Permintaan alihkan sudah kamu kirim. Tiket ini tidak dikerjakan lagi dari HP ini.'
              : `Status tiket: ${labelStatusTiket(keadaan.Status)}. Mulai dulu supaya waktu kerja tercatat.`
          }
          aksi={
            bisaMulai && !keadaan.Dialihkan ? (
              <TombolLapangan penuh disabled={memproses === tiket.Id} onClick={() => void mulai(tiket)}>
                {keadaan.PerluRespons ? 'Terima & Mulai' : 'Mulai kerja'}
              </TombolLapangan>
            ) : (
              <TombolLapangan asChild ragam="garis" penuh>
                <Link href={ruteLapangan.teknisi.tugasDetail(tiket.Id)}>Kembali ke detail</Link>
              </TombolLapangan>
            )
          }
        />
      </Kartu>
    );
  }

  return (
    <>
      <KartuLangkahKerja aktif={langkah} />
      <PitaMasalahTiket keadaan={keadaan} />
      {langkah === 'checklist' && <LangkahChecklist {...props} />}
      {langkah === 'diagnosis' && <LangkahDiagnosis {...props} />}
      {langkah === 'suku-cadang' && <LangkahSukuCadang {...props} />}
      {langkah === 'foto' && <LangkahFoto {...props} />}
      {langkah === 'ringkasan' && <LangkahRingkasan {...props} />}
    </>
  );
}

/* ---------------------------------------------------------------- Jeda */

function useJeda(props: PropsIsi) {
  const { antrian, antrikan } = useSinkronisasiOffline();
  const konfirmasi = useKonfirmasi();

  return async () => {
    const setuju = await konfirmasi({
      judul: 'Jeda pekerjaan ini?',
      deskripsi: 'Waktu kerja berhenti dihitung dan tiket berstatus Dijeda sampai kamu melanjutkannya.',
      labelAksi: 'Jeda',
    });
    if (!setuju) return;

    const rencana: PermintaanMutasiTeknisi[] = [];
    if (props.sesiKerja.sesi.MulaiPada) {
      const sesi = rencanaWaktuKerja(props.tiket, props.sesiKerja.sesi.MulaiPada, new Date());
      if (sesi) rencana.push(sesi);
    }
    rencana.push(
      rencanaUbahStatus(props.tiket, keadaanLokal(props.tiket, antrian), 'Dijeda', 'dijeda', {
        Catatan: 'Dijeda dari Mode Lapangan.',
      }),
    );
    await antrikanBerurutan(antrikan, rencana);
    await props.sesiKerja.ubah({ MulaiPada: null });
    router.visit(ruteLapangan.teknisi.tugasDetail(props.tiket.Id));
  };
}

/* ---------------------------------------------------------------- Checklist (07) */

function nilaiTampil(jawaban: JawabanChecklistTeknisi | undefined): string {
  if (!jawaban) return '';
  if (jawaban.NilaiAngka != null) return String(jawaban.NilaiAngka);
  if (jawaban.NilaiBoolean != null) return jawaban.NilaiBoolean ? 'Sesuai' : 'Tidak sesuai';
  return jawaban.NilaiTeks ?? '';
}

/** Kesesuaian dihitung sama seperti server: angka dalam rentang, Ya/Tidak bernilai Ya. */
function sesuai(butir: ButirChecklistTeknisi, jawaban: JawabanChecklistTeknisi): boolean | null {
  if (jawaban.Sesuai != null) return jawaban.Sesuai;
  if (butir.TipeJawaban === 'Angka' && jawaban.NilaiAngka != null) {
    if (butir.NilaiMinimum != null && jawaban.NilaiAngka < butir.NilaiMinimum) return false;
    if (butir.NilaiMaksimum != null && jawaban.NilaiAngka > butir.NilaiMaksimum) return false;
    return true;
  }
  if (butir.TipeJawaban === 'YaTidak' && jawaban.NilaiBoolean != null) return jawaban.NilaiBoolean;
  return null;
}

function rentang(butir: ButirChecklistTeknisi): string | null {
  const satuan = butir.Satuan ? ` ${butir.Satuan}` : '';
  if (butir.NilaiMinimum != null && butir.NilaiMaksimum != null) {
    return `Normal ${butir.NilaiMinimum}–${butir.NilaiMaksimum}${satuan}`;
  }
  if (butir.NilaiMinimum != null) return `Minimal ${butir.NilaiMinimum}${satuan}`;
  if (butir.NilaiMaksimum != null) return `Maksimal ${butir.NilaiMaksimum}${satuan}`;
  return null;
}

function LangkahChecklist(props: PropsIsi) {
  const { daftarPeriksa, sesiKerja, setLangkah, tiket } = props;
  const { antrikan } = useSinkronisasiOffline();
  const jeda = useJeda(props);
  const [indeksEdit, setIndeksEdit] = useState<number | null>(null);
  const [isian, setIsian] = useState<JawabanChecklistTeknisi | null>(null);
  const [galat, setGalat] = useState<string | null>(null);
  const [menyimpan, setMenyimpan] = useState(false);

  const jawaban = useMemo(() => {
    const peta: Record<string, JawabanChecklistTeknisi> = {};
    daftarPeriksa?.Jawaban.forEach((satu) => {
      peta[satu.ButirTemplatDaftarPeriksaId] = satu;
    });
    return { ...peta, ...(sesiKerja.sesi.Jawaban ?? {}) };
  }, [daftarPeriksa, sesiKerja.sesi.Jawaban]);

  if (!daftarPeriksa) {
    return (
      <>
        <Kartu>
          <IlustrasiMomen
            ringkas
            ikon="clipboard"
            judul="Tanpa checklist"
            teks="Tiket ini tidak memakai checklist. Lanjut ke diagnosis dan tindakan."
          />
        </Kartu>
        <BilahTetap>
          <TombolLapangan ragam="garis" className="flex-1" onClick={() => void jeda()}>
            <Pause aria-hidden />
            Jeda
          </TombolLapangan>
          <TombolLapangan className="flex-[1.6]" onClick={() => setLangkah('diagnosis')}>
            Lanjut
          </TombolLapangan>
        </BilahTetap>
      </>
    );
  }

  const butir = daftarPeriksa.Butir;
  const final = daftarPeriksa.Status === 'Selesai' || Boolean(sesiKerja.sesi.ChecklistFinal);
  const indeksBelum = butir.findIndex((satu) => !jawaban[satu.Id]);
  const indeksKini = final ? -1 : (indeksEdit ?? indeksBelum);
  const kini = indeksKini >= 0 ? butir[indeksKini] : undefined;
  const jumlahDijawab = butir.filter((satu) => jawaban[satu.Id]).length;
  const nilai = isian ?? (kini ? jawaban[kini.Id] : undefined) ?? null;

  const ubahIsian = (perubahan: Partial<JawabanChecklistTeknisi>) => {
    if (!kini) return;
    setGalat(null);
    setIsian({
      ...(nilai ?? { ButirTemplatDaftarPeriksaId: kini.Id }),
      ...perubahan,
      ButirTemplatDaftarPeriksaId: kini.Id,
      Sesuai: null,
    });
  };

  const simpanDanLanjut = async () => {
    if (!kini) {
      if (!final && indeksBelum === -1) {
        setMenyimpan(true);
        await antrikan({
          Operasi: 'DaftarPeriksa.Finalisasi',
          EntitasId: daftarPeriksa.Id,
          VersiKlien: null,
          MuatanData: { Catatan: null },
          Label: `${tiket.Nomor}: checklist selesai`,
        });
        await sesiKerja.ubah({ ChecklistFinal: true });
        setMenyimpan(false);
      }
      setLangkah('diagnosis');
      return;
    }

    const kosong = !nilai || nilaiTampil(nilai).trim() === '';
    if (kosong && kini.Wajib) {
      setGalat('Langkah ini wajib diisi sebelum lanjut.');
      return;
    }

    setMenyimpan(true);
    if (!kosong && nilai) {
      await antrikan({
        Operasi: 'DaftarPeriksa.SimpanJawaban',
        EntitasId: daftarPeriksa.Id,
        VersiKlien: null,
        MuatanData: { Jawaban: [nilai] },
        Label: `${tiket.Nomor}: checklist langkah ${kini.Urutan}`,
      });
      await sesiKerja.ubah({ Jawaban: { ...(sesiKerja.sesi.Jawaban ?? {}), [kini.Id]: nilai } });
    }
    setIsian(null);
    setIndeksEdit(null);

    const sisa = butir.filter((satu) => satu.Id !== kini.Id && !jawaban[satu.Id]);
    if (sisa.length === 0) {
      await antrikan({
        Operasi: 'DaftarPeriksa.Finalisasi',
        EntitasId: daftarPeriksa.Id,
        VersiKlien: null,
        MuatanData: { Catatan: null },
        Label: `${tiket.Nomor}: checklist selesai`,
      });
      await sesiKerja.ubah({ ChecklistFinal: true });
      setLangkah('diagnosis');
    }
    setMenyimpan(false);
  };

  const dijawabTampil = butir.filter((satu, i) => jawaban[satu.Id] && i !== indeksKini);

  return (
    <>
      <div className="flex items-baseline justify-between px-0.5 pt-1">
        <h2 className="text-[17px] font-bold tracking-[-0.01em]">
          {kini
            ? `Langkah ${indeksKini + 1} dari ${butir.length}`
            : final
              ? 'Checklist selesai'
              : 'Semua langkah terjawab'}
        </h2>
        <span className="text-sm font-semibold text-lapangan-teks-3">{jumlahDijawab} dijawab</span>
      </div>

      {dijawabTampil.length > 0 && (
        <Kartu className="overflow-hidden">
          <ul>
            {dijawabTampil.map((satu) => {
              const j = jawaban[satu.Id];
              const hasil = j ? sesuai(satu, j) : null;
              return (
                <li key={satu.Id} className="[&+&]:border-t-[1.5px] [&+&]:border-lapangan-garis-2">
                  <button
                    type="button"
                    disabled={final}
                    onClick={() => {
                      setIndeksEdit(butir.indexOf(satu));
                      setIsian(null);
                    }}
                    className="flex w-full items-start gap-3 px-4 py-3.5 text-left focus-visible:bg-lapangan-latar focus-visible:outline-none"
                  >
                    <span
                      aria-hidden
                      className={cn(
                        'flex size-7 shrink-0 items-center justify-center rounded-full text-white',
                        hasil === false ? 'bg-lapangan-merah-700' : 'bg-lapangan-hijau-700',
                      )}
                    >
                      {hasil === false ? (
                        <X className="size-[15px]" strokeWidth={3} />
                      ) : (
                        <Check className="size-[15px]" strokeWidth={3} />
                      )}
                    </span>
                    <span className="min-w-0 flex-1">
                      <b className="block text-[15px] leading-snug font-bold">{satu.Pertanyaan}</b>
                      <span className="block text-[13px] text-lapangan-teks-3">
                        {[
                          satu.TipeJawaban === 'Angka'
                            ? `${nilaiTampil(j)}${satu.Satuan ? ` ${satu.Satuan}` : ''}`
                            : null,
                          satu.TipeJawaban === 'Teks' || satu.TipeJawaban === 'Pilihan'
                            ? nilaiTampil(j)
                            : null,
                          j?.Catatan,
                        ]
                          .filter(Boolean)
                          .join(' · ')}
                      </span>
                    </span>
                    {hasil !== null && (
                      <ChipStatus warna={hasil ? 'hijau' : 'merah'} ukuran="kecil">
                        {hasil ? 'Sesuai' : 'Tidak sesuai'}
                      </ChipStatus>
                    )}
                  </button>
                </li>
              );
            })}
          </ul>
        </Kartu>
      )}

      {kini && (
        <Kartu pad>
          <div className="flex items-start gap-3">
            <span
              aria-hidden
              className="flex size-7 shrink-0 items-center justify-center rounded-full bg-lapangan-navy-800 text-[13px] font-bold text-white"
            >
              {indeksKini + 1}
            </span>
            <div className="min-w-0 flex-1">
              <h3 className="text-[15px] leading-snug font-bold">
                {kini.Pertanyaan}
                {kini.Wajib && <span className="sr-only"> (wajib)</span>}
              </h3>
              {rentang(kini) && <p className="text-[13px] text-lapangan-teks-3">{rentang(kini)}</p>}
            </div>
          </div>
          <IsianButir butir={kini} nilai={nilai} onUbah={ubahIsian} />
          {galat && (
            <p role="alert" className="mt-2 text-[13px] font-semibold text-lapangan-merah-700">
              {galat}
            </p>
          )}
        </Kartu>
      )}

      {!kini && !final && (
        <PitaInfo
          nada="hijau"
          ikon="check_mark_button"
          judul="Semua langkah sudah dijawab"
          teks="Checklist ditutup saat kamu lanjut."
        />
      )}

      <BilahTetap>
        <TombolLapangan ragam="garis" className="flex-1" onClick={() => void jeda()}>
          <Pause aria-hidden />
          Jeda
        </TombolLapangan>
        <TombolLapangan className="flex-[1.6]" disabled={menyimpan} onClick={() => void simpanDanLanjut()}>
          {kini ? 'Simpan & lanjut' : 'Lanjut ke Diagnosis'}
        </TombolLapangan>
      </BilahTetap>
    </>
  );
}

function IsianButir({
  butir,
  nilai,
  onUbah,
}: {
  butir: ButirChecklistTeknisi;
  nilai: JawabanChecklistTeknisi | null;
  onUbah: (perubahan: Partial<JawabanChecklistTeknisi>) => void;
}) {
  if (butir.TipeJawaban === 'YaTidak') {
    const pilihan = nilai?.NilaiBoolean;
    return (
      <>
        <div role="radiogroup" aria-label={butir.Pertanyaan} className="mt-3 grid grid-cols-2 gap-2.5">
          {[true, false].map((ya) => {
            const aktif = pilihan === ya;
            return (
              <button
                key={String(ya)}
                type="button"
                role="radio"
                aria-checked={aktif}
                onClick={() => onUbah({ NilaiBoolean: ya })}
                className={cn(
                  'flex h-[54px] items-center justify-center gap-2 rounded-[14px] text-[15px] font-bold transition-colors focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-lapangan-biru-500/50',
                  aktif &&
                    ya &&
                    'bg-lapangan-hijau-50 text-lapangan-hijau-700 shadow-[inset_0_0_0_2px_var(--color-lapangan-hijau-700)]',
                  aktif &&
                    !ya &&
                    'bg-lapangan-merah-50 text-lapangan-merah-700 shadow-[inset_0_0_0_2px_var(--color-lapangan-merah-700)]',
                  !aktif &&
                    'bg-white text-lapangan-teks-2 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
                )}
              >
                {ya ? (
                  <CircleCheck aria-hidden className="size-5" strokeWidth={2.6} />
                ) : (
                  <X aria-hidden className="size-5" strokeWidth={2.6} />
                )}
                {ya ? 'Sesuai' : 'Tidak sesuai'}
              </button>
            );
          })}
        </div>
        {pilihan === false && (
          <IsianTiket label="Catatan temuan" className="mt-3">
            <AreaTiket
              rows={2}
              value={nilai?.Catatan ?? ''}
              onChange={(event) => onUbah({ Catatan: event.target.value })}
              placeholder="Mis. kontaktor utama berbau hangus"
            />
          </IsianTiket>
        )}
      </>
    );
  }

  if (butir.TipeJawaban === 'Angka') {
    const angka = nilai?.NilaiAngka;
    const luar =
      angka != null &&
      ((butir.NilaiMinimum != null && angka < butir.NilaiMinimum) ||
        (butir.NilaiMaksimum != null && angka > butir.NilaiMaksimum));
    const bawah = angka != null && butir.NilaiMinimum != null && angka < butir.NilaiMinimum;
    return (
      <>
        <label className="mt-3 flex h-16 items-baseline gap-1.5 rounded-[14px] bg-white px-4 pt-3 shadow-[inset_0_0_0_2px_var(--color-lapangan-biru-500),0_0_0_4px_var(--color-lapangan-biru-50)]">
          <span className="sr-only">{butir.Pertanyaan}</span>
          <input
            type="number"
            inputMode="decimal"
            step="any"
            value={angka ?? ''}
            onChange={(event) =>
              onUbah({ NilaiAngka: event.target.value === '' ? null : Number(event.target.value) })
            }
            className="w-full min-w-0 [appearance:textfield] bg-transparent text-[32px] leading-none font-bold tracking-[-0.02em] tabular-nums outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
            placeholder="0"
            autoFocus
          />
          {butir.Satuan && <span className="text-[17px] font-bold text-lapangan-teks-2">{butir.Satuan}</span>}
        </label>
        {luar && (
          <p
            role="status"
            className="mt-3 flex items-center gap-2 rounded-xl bg-lapangan-kuning-50 px-3 py-2.5 text-[13px] font-semibold text-lapangan-kuning-700"
          >
            <TriangleAlert aria-hidden className="size-[18px] shrink-0" />
            {bawah ? 'Di bawah normal' : 'Di atas normal'}, akan ditandai di laporan
          </p>
        )}
      </>
    );
  }

  if (butir.TipeJawaban === 'Pilihan' && butir.Pilihan && butir.Pilihan.length > 0) {
    return (
      <div role="radiogroup" aria-label={butir.Pertanyaan} className="mt-3 flex flex-wrap gap-2">
        {butir.Pilihan.map((satu) => (
          <button
            key={satu}
            type="button"
            role="radio"
            aria-checked={nilai?.NilaiTeks === satu}
            onClick={() => onUbah({ NilaiTeks: satu })}
            className={cn(
              'min-h-11 rounded-xl px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
              nilai?.NilaiTeks === satu
                ? 'bg-lapangan-navy-800 text-white'
                : 'bg-white text-lapangan-teks-2 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
            )}
          >
            {satu}
          </button>
        ))}
      </div>
    );
  }

  return (
    <IsianTiket
      label={butir.TipeJawaban === 'Foto' ? 'Keterangan (foto diambil di langkah Foto)' : 'Jawaban'}
      className="mt-3"
    >
      <AreaTiket
        rows={2}
        value={nilai?.NilaiTeks ?? ''}
        onChange={(event) => onUbah({ NilaiTeks: event.target.value })}
      />
    </IsianTiket>
  );
}

/* ---------------------------------------------------------------- Diagnosis (08) */

function pesanGalat(galat: unknown): Record<string, string> | null {
  if (isAxiosError(galat) && galat.response?.status === 422) {
    const data: unknown = galat.response.data;
    if (
      data &&
      typeof data === 'object' &&
      'errors' in data &&
      data.errors &&
      typeof data.errors === 'object'
    ) {
      return Object.fromEntries(
        Object.entries(data.errors).map(([kunci, isi]) => [
          kunci,
          Array.isArray(isi) ? String(isi[0]) : String(isi),
        ]),
      );
    }
  }
  return null;
}

function LangkahDiagnosis(props: PropsIsi) {
  const { tiket, analisis, kodeKegagalan, sesiKerja, setLangkah } = props;
  const { antrikan, daring } = useSinkronisasiOffline();
  const draf = sesiKerja.sesi.Diagnosis;
  const [kodeId, setKodeId] = useState<string | null>(draf?.KodeMasalahId ?? analisis?.KodeMasalahId ?? null);
  const [penyebab, setPenyebab] = useState(draf?.AkarMasalah ?? analisis?.AkarMasalah ?? '');
  const [tindakan, setTindakan] = useState(draf?.TindakanKorektif ?? analisis?.TindakanKorektif ?? '');
  const [tujuanDikte, setTujuanDikte] = useState<'penyebab' | 'tindakan'>('tindakan');
  const [galat, setGalat] = useState<Record<string, string>>({});
  const [menyimpan, setMenyimpan] = useState(false);

  // Draf dari perangkat dimuat asinkron; isi formulir sekali saat tersedia.
  const sudahDiisi = useRef(false);
  useEffect(() => {
    if (sudahDiisi.current || !sesiKerja.dimuat) return;
    sudahDiisi.current = true;
    if (draf) {
      setKodeId(draf.KodeMasalahId);
      setPenyebab(draf.AkarMasalah);
      setTindakan(draf.TindakanKorektif);
    }
  }, [sesiKerja.dimuat]);

  const lanjut = async () => {
    const galatBaru: Record<string, string> = {};
    if (penyebab.trim() === '') galatBaru.AkarMasalah = 'Tulis penyebab kerusakannya.';
    if (tindakan.trim() === '') galatBaru.TindakanKorektif = 'Tulis tindakan yang kamu lakukan.';
    setGalat(galatBaru);
    if (Object.keys(galatBaru).length > 0) return;

    setMenyimpan(true);
    const data = { KodeMasalahId: kodeId, AkarMasalah: penyebab.trim(), TindakanKorektif: tindakan.trim() };
    let tersimpan = false;

    if (daring) {
      try {
        // Endpoint dasbor menjawab dengan pengalihan `back()`; PUT yang dialihkan diulang peramban
        // sebagai PUT ke halaman ini (405), jadi dikirim sebagai POST dengan `_method` Laravel.
        await http.post(ruteLapangan.teknisi.analisisKegagalan(tiket.Id), { ...data, _method: 'PUT' });
        tersimpan = true;
      } catch (galatKirim) {
        const validasi = pesanGalat(galatKirim);
        if (validasi) {
          setGalat(validasi);
          setMenyimpan(false);
          return;
        }
      }
    }

    // Tanpa sinyal: diagnosis tetap sampai ke koordinator sebagai catatan lapangan lewat antrean.
    const teks = [
      'Diagnosis dari Mode Lapangan.',
      kodeId ? `Kode: ${kodeKegagalan.find((satu) => satu.Id === kodeId)?.Nama ?? kodeId}.` : null,
      `Penyebab: ${data.AkarMasalah}`,
      `Tindakan: ${data.TindakanKorektif}`,
    ]
      .filter(Boolean)
      .join('\n');
    const sudahDicatat =
      draf?.Tercatat &&
      draf.AkarMasalah === data.AkarMasalah &&
      draf.TindakanKorektif === data.TindakanKorektif;
    if (!tersimpan && !sudahDicatat) {
      await antrikan({
        Operasi: 'PerintahKerja.TambahCatatan',
        EntitasId: tiket.Id,
        VersiKlien: null,
        MuatanData: { Isi: teks },
        Label: `${tiket.Nomor}: diagnosis`,
      });
    }

    await sesiKerja.ubah({
      Diagnosis: { ...data, Tersimpan: tersimpan, Tercatat: tersimpan ? draf?.Tercatat : true },
    });
    setMenyimpan(false);
    setLangkah('suku-cadang');
  };

  const tambahDikte = (teks: string) => {
    if (tujuanDikte === 'penyebab') setPenyebab((lama) => (lama ? `${lama} ${teks}` : teks));
    else setTindakan((lama) => (lama ? `${lama} ${teks}` : teks));
  };

  return (
    <>
      {kodeKegagalan.length > 0 && (
        <Kartu pad>
          <h2 className="text-[17px] font-bold tracking-[-0.01em]">Kode kegagalan</h2>
          <p className="text-[13px] text-lapangan-teks-3">Pilih yang paling sesuai</p>
          <div role="radiogroup" aria-label="Kode kegagalan" className="mt-3.5 flex flex-wrap gap-2">
            {kodeKegagalan.map((kode) => {
              const aktif = kodeId === kode.Id;
              return (
                <button
                  key={kode.Id}
                  type="button"
                  role="radio"
                  aria-checked={aktif}
                  onClick={() => setKodeId(aktif ? null : kode.Id)}
                  className={cn(
                    'inline-flex h-[38px] items-center gap-2 rounded-xl px-3.5 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
                    aktif
                      ? 'bg-lapangan-navy-800 text-white'
                      : 'bg-white text-lapangan-teks-2 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
                  )}
                >
                  <Ikon3D nama={ikonKodeKegagalan(kode.Nama)} ukuran={20} />
                  {kode.Nama}
                </button>
              );
            })}
          </div>
        </Kartu>
      )}

      <Kartu pad className="flex flex-col gap-3">
        <IsianTiket label="Penyebab" galat={galat.AkarMasalah}>
          <AreaTiket
            rows={2}
            value={penyebab}
            onFocus={() => setTujuanDikte('penyebab')}
            onChange={(event) => setPenyebab(event.target.value)}
            placeholder="Mis. kontaktor utama terbakar karena tegangan turun"
            maxLength={10000}
          />
        </IsianTiket>
        <IsianTiket label="Tindakan" galat={galat.TindakanKorektif}>
          <AreaTiket
            rows={2}
            value={tindakan}
            onFocus={() => setTujuanDikte('tindakan')}
            onChange={(event) => setTindakan(event.target.value)}
            placeholder="Mis. ganti kontaktor, kencangkan terminal, uji jalan"
            maxLength={10000}
          />
        </IsianTiket>
        <BarisDikte tujuan={tujuanDikte === 'penyebab' ? 'Penyebab' : 'Tindakan'} onTeks={tambahDikte} />
      </Kartu>

      <BilahTetap>
        <TombolLapangan
          ragam="garis"
          className="w-[76px] px-0"
          aria-label="Kembali ke checklist"
          onClick={() => setLangkah('checklist')}
        >
          <ArrowLeft aria-hidden />
        </TombolLapangan>
        <TombolLapangan className="flex-1" disabled={menyimpan} onClick={() => void lanjut()}>
          {menyimpan ? 'Menyimpan…' : 'Lanjut ke Suku cadang'}
        </TombolLapangan>
      </BilahTetap>
    </>
  );
}

/* ---------------------------------------------------------------- Suku cadang (09) */

const LABEL_RESERVASI: Record<string, { teks: string; warna: 'kuning' | 'hijau' | 'abu' }> = {
  Aktif: { teks: 'Disiapkan gudang', warna: 'kuning' },
  Dipakai: { teks: 'Diserahkan', warna: 'hijau' },
  Dilepas: { teks: 'Dibatalkan', warna: 'abu' },
  Kadaluarsa: { teks: 'Kedaluwarsa', warna: 'abu' },
};

function LangkahSukuCadang({ tiket, permintaanSukuCadang, setLangkah }: PropsIsi) {
  const { daring } = useSinkronisasiOffline();
  const [lembarBuka, setLembarBuka] = useState(false);

  return (
    <>
      <Kartu className="overflow-hidden">
        <div className="flex items-center gap-3 px-4 pt-4 pb-2">
          <Ikon3D nama="nut_and_bolt" ukuran={32} />
          <div className="min-w-0 flex-1">
            <h2 className="text-base font-bold">Permintaan suku cadang</h2>
            <p className="text-[13px] text-lapangan-teks-3">Untuk {tiket.Nomor}</p>
          </div>
        </div>
        {permintaanSukuCadang.length === 0 ? (
          <p className="px-4 pb-4 text-sm text-lapangan-teks-3">
            Belum ada. Tidak butuh suku cadang? Lanjut saja.
          </p>
        ) : (
          <ul>
            {permintaanSukuCadang.map((satu) => {
              const label = LABEL_RESERVASI[satu.Status] ?? { teks: satu.Status, warna: 'abu' as const };
              return (
                <li
                  key={satu.Id}
                  className="flex items-center gap-3 border-t-[1.5px] border-lapangan-garis-2 px-4 py-3"
                >
                  <div className="min-w-0 flex-1">
                    <b className="block truncate text-sm font-bold">{satu.NamaSukuCadang}</b>
                    <span className="block truncate text-[12.5px] text-lapangan-teks-3">
                      {satu.Jumlah} {satu.Satuan ?? ''} · {satu.NamaGudang}
                    </span>
                  </div>
                  <ChipStatus warna={label.warna} ukuran="kecil">
                    {label.teks}
                  </ChipStatus>
                </li>
              );
            })}
          </ul>
        )}
        <div className="px-4 pt-1 pb-4">
          <TombolLapangan ragam="lembut" ukuran="kecil" penuh onClick={() => setLembarBuka(true)}>
            <Plus aria-hidden />
            Minta suku cadang
          </TombolLapangan>
        </div>
      </Kartu>

      {!daring && (
        <PitaInfo
          nada="kuning"
          ikon="satellite_antenna"
          judul="Minta suku cadang butuh sinyal"
          teks="Stok gudang dicek langsung. Lanjutkan dulu, minta saat online."
        />
      )}

      <LembarMintaSukuCadang
        buka={lembarBuka}
        onBukaBerubah={setLembarBuka}
        perintahKerjaId={tiket.Id}
        daring={daring}
      />

      <BilahTetap>
        <TombolLapangan
          ragam="garis"
          className="w-[76px] px-0"
          aria-label="Kembali ke diagnosis"
          onClick={() => setLangkah('diagnosis')}
        >
          <ArrowLeft aria-hidden />
        </TombolLapangan>
        <TombolLapangan className="flex-1" onClick={() => setLangkah('foto')}>
          Lanjut ke Foto
        </TombolLapangan>
      </BilahTetap>
    </>
  );
}

/* ---------------------------------------------------------------- Foto (10) */

function FotoLokal({ foto, onHapus }: { foto: FotoTertunda; onHapus: () => void }) {
  const url = useUrlBlob(foto.Berkas);
  return (
    <figure className="relative h-32 overflow-hidden rounded-[14px] bg-lapangan-navy-800">
      {url && <img src={url} alt={`Foto ${jamPendek(foto.DibuatPada)}`} className="size-full object-cover" />}
      <span className="absolute top-2 right-2 inline-flex h-[26px] items-center gap-1 rounded-full bg-white/90 px-2 text-[11.5px] font-bold text-lapangan-navy-800">
        <Smartphone aria-hidden className="size-[13px]" />
        Di HP
      </span>
      <figcaption className="absolute bottom-2 left-2 rounded-lg bg-lapangan-navy-900/75 px-2 py-0.5 text-xs font-bold text-white tabular-nums">
        {jamPendek(foto.DibuatPada)}
      </figcaption>
      <button
        type="button"
        onClick={onHapus}
        aria-label="Hapus foto ini"
        className="absolute top-1 left-1 flex size-11 items-center justify-center text-white focus-visible:outline-none"
      >
        <span className="flex size-7 items-center justify-center rounded-full bg-lapangan-navy-900/70">
          <X aria-hidden className="size-4" />
        </span>
      </button>
    </figure>
  );
}

function KelompokFoto({
  judul,
  kategori,
  props,
}: {
  judul: string;
  kategori: 'FotoSebelum' | 'FotoSesudah';
  props: PropsIsi;
}) {
  const { daring } = useSinkronisasiOffline();
  const masukan = useRef<HTMLInputElement | null>(null);
  const [memproses, setMemproses] = useState(false);
  const diServer = props.foto.filter((satu) => satu.Kategori === kategori);
  const diHp = props.fotoHook.foto.filter((satu) => satu.Kategori === kategori);

  const ambil = async (event: ChangeEvent<HTMLInputElement>) => {
    const berkas = event.target.files?.[0];
    event.target.value = '';
    if (!berkas) return;
    setMemproses(true);
    try {
      const kecil = await perkecilFoto(berkas);
      await props.fotoHook.tambah(props.tiket.Id, kategori, kecil);
      if (daring) void props.fotoHook.unggahSemua();
    } finally {
      setMemproses(false);
    }
  };

  return (
    <Kartu pad>
      <div className="flex items-baseline justify-between">
        <h2 className="text-[17px] font-bold tracking-[-0.01em]">{judul}</h2>
        <span className="text-sm font-semibold text-lapangan-teks-3">
          {diServer.length + diHp.length} foto
        </span>
      </div>
      <div className="mt-3 grid grid-cols-2 gap-2.5">
        {diServer.map((satu) => (
          <figure key={satu.Id} className="relative h-32 overflow-hidden rounded-[14px] bg-lapangan-navy-800">
            {satu.Url && (
              <img
                src={satu.Url}
                alt={`${judul} ${jamPendek(satu.DibuatPada)}`}
                loading="lazy"
                className="size-full object-cover"
              />
            )}
            <figcaption className="absolute bottom-2 left-2 rounded-lg bg-lapangan-navy-900/75 px-2 py-0.5 text-xs font-bold text-white tabular-nums">
              {jamPendek(satu.DibuatPada)}
            </figcaption>
          </figure>
        ))}
        {diHp.map((satu) => (
          <FotoLokal key={satu.Kunci} foto={satu} onHapus={() => void props.fotoHook.hapus(satu.Kunci)} />
        ))}
        <button
          type="button"
          disabled={memproses}
          onClick={() => masukan.current?.click()}
          className="flex h-32 flex-col items-center justify-center gap-1.5 rounded-[14px] border-2 border-dashed border-lapangan-teks-3/35 bg-white text-sm font-bold text-lapangan-navy-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500 disabled:opacity-60"
        >
          <Ikon3D nama="camera_with_flash" ukuran={48} />
          {memproses ? 'Menyimpan…' : 'Ambil foto'}
        </button>
        <input
          ref={masukan}
          type="file"
          accept="image/*"
          capture="environment"
          className="sr-only"
          tabIndex={-1}
          aria-label={`Ambil ${judul.toLowerCase()}`}
          onChange={(event) => void ambil(event)}
        />
      </div>
    </Kartu>
  );
}

function LangkahFoto(props: PropsIsi) {
  const { daring } = useSinkronisasiOffline();
  const lokal = props.fotoHook.foto.filter((satu) => satu.Kategori !== 'TandaTangan');

  return (
    <>
      {(!daring || lokal.length > 0) && (
        <PitaInfo
          nada="kuning"
          ikon="mobile_phone"
          judul={daring ? 'Foto sedang dikirim' : 'Sinyal hilang di sini'}
          teks="Foto disimpan di HP dan terkirim otomatis saat online."
        />
      )}
      <KelompokFoto judul="Sebelum" kategori="FotoSebelum" props={props} />
      <KelompokFoto judul="Sesudah" kategori="FotoSesudah" props={props} />
      <BilahTetap>
        <TombolLapangan
          ragam="garis"
          className="w-[76px] px-0"
          aria-label="Kembali ke suku cadang"
          onClick={() => props.setLangkah('suku-cadang')}
        >
          <ArrowLeft aria-hidden />
        </TombolLapangan>
        <TombolLapangan className="flex-1" onClick={() => props.setLangkah('ringkasan')}>
          Lanjut ke Ringkasan
        </TombolLapangan>
      </BilahTetap>
    </>
  );
}

/* ---------------------------------------------------------------- Ringkasan & tanda tangan (11) */

function RuteKerja({
  mulai,
  selesai,
  menit,
}: {
  mulai: string | null;
  selesai: string | null;
  menit: number;
}) {
  return (
    <RuteJam
      kiri={{ jam: jamPendek(mulai), label: 'Mulai' }}
      kanan={{ jam: jamPendek(selesai), label: 'Selesai' }}
      ikon="hammer_and_wrench"
      label={durasiPendek(menit)}
    />
  );
}

function LangkahRingkasan(props: PropsIsi) {
  const {
    tiket,
    daftarPeriksa,
    permintaanSukuCadang,
    foto,
    fotoHook,
    pengawas,
    sesiKerja,
    analisis,
    setLangkah,
  } = props;
  const { antrian, antrikan, daring, dorong } = useSinkronisasiOffline();
  const { detik } = useDetikKerja(props, sesiKerja.sesi);
  const [kondisi, setKondisi] = useState<string>(sesiKerja.sesi.Kondisi ?? KONDISI_ASET[0]);
  const [namaPengawas, setNamaPengawas] = useState(sesiKerja.sesi.Pengawas?.Nama ?? pengawas?.Nama ?? '');
  const [tandaTangan, setTandaTangan] = useState<Blob | null>(null);
  const [ubahNama, setUbahNama] = useState(false);
  const [mengirim, setMengirim] = useState(false);
  const ttdTersimpan = fotoHook.foto.find((satu) => satu.Kategori === 'TandaTangan');
  const urlTtd = useUrlBlob(ttdTersimpan?.Berkas);

  const jawaban = {
    ...Object.fromEntries(
      (daftarPeriksa?.Jawaban ?? []).map((satu) => [satu.ButirTemplatDaftarPeriksaId, satu]),
    ),
    ...(sesiKerja.sesi.Jawaban ?? {}),
  };
  const jumlahButir = daftarPeriksa?.Butir.length ?? 0;
  const jumlahDijawab = daftarPeriksa?.Butir.filter((satu) => jawaban[satu.Id]).length ?? 0;
  const jumlahFoto =
    foto.filter((satu) => satu.Kategori !== 'TandaTangan').length +
    fotoHook.foto.filter((satu) => satu.Kategori !== 'TandaTangan').length;
  const mulai = props.waktuKerja.MulaiPertama ?? sesiKerja.sesi.MulaiPertama ?? tiket.DimulaiPada;
  const [sekarang] = useState(() => new Date().toISOString());

  const kirim = async () => {
    const tindakan = sesiKerja.sesi.Diagnosis?.TindakanKorektif ?? analisis?.TindakanKorektif ?? '';
    const penyebab = sesiKerja.sesi.Diagnosis?.AkarMasalah ?? analisis?.AkarMasalah ?? '';
    if (tindakan.trim() === '') {
      toast.error('Isi dulu tindakan yang kamu lakukan.');
      setLangkah('diagnosis');
      return;
    }
    const wajibKosong =
      daftarPeriksa &&
      daftarPeriksa.Status !== 'Selesai' &&
      !sesiKerja.sesi.ChecklistFinal &&
      daftarPeriksa.Butir.some((satu) => satu.Wajib && !jawaban[satu.Id]);
    if (wajibKosong) {
      toast.error('Masih ada langkah checklist wajib yang belum dijawab.');
      setLangkah('checklist');
      return;
    }

    setMengirim(true);
    const selesaiPada = new Date();
    const menit = Math.round(detik / 60);
    const rencana: PermintaanMutasiTeknisi[] = [];

    if (daftarPeriksa && daftarPeriksa.Status !== 'Selesai' && !sesiKerja.sesi.ChecklistFinal) {
      rencana.push({
        Operasi: 'DaftarPeriksa.Finalisasi',
        EntitasId: daftarPeriksa.Id,
        VersiKlien: null,
        MuatanData: { Catatan: null },
        Label: `${tiket.Nomor}: checklist selesai`,
      });
    }
    if (sesiKerja.sesi.MulaiPada) {
      const sesi = rencanaWaktuKerja(tiket, sesiKerja.sesi.MulaiPada, selesaiPada);
      if (sesi) rencana.push(sesi);
    }
    const ringkasan = [
      `Tindakan: ${tindakan.trim()}`,
      penyebab.trim() ? `Penyebab: ${penyebab.trim()}` : null,
      `Kondisi aset: ${kondisi}`,
      namaPengawas.trim()
        ? `Disaksikan: ${namaPengawas.trim()}${pengawas?.Jabatan && namaPengawas.trim() === pengawas.Nama ? ` (${pengawas.Jabatan})` : ''}`
        : null,
    ]
      .filter(Boolean)
      .join('\n');
    rencana.push(
      rencanaUbahStatus(
        tiket,
        keadaanLokal(tiket, antrian),
        'MenungguVerifikasi',
        'selesai, menunggu verifikasi',
        {
          Catatan: `Diselesaikan dari Mode Lapangan. Kondisi aset: ${kondisi}.`,
          Ringkasan: ringkasan,
        },
      ),
    );

    try {
      if (tandaTangan) {
        if (ttdTersimpan) await fotoHook.hapus(ttdTersimpan.Kunci);
        await fotoHook.tambah(
          tiket.Id,
          'TandaTangan',
          tandaTangan,
          namaPengawas.trim() ? `Tanda tangan ${namaPengawas.trim()}` : 'Tanda tangan pengawas',
        );
      }
      await antrikanBerurutan(antrikan, rencana);
      await dorong();
      await sesiKerja.ubah({
        MulaiPada: null,
        Kondisi: kondisi,
        Pengawas: { Nama: namaPengawas.trim(), Jabatan: pengawas?.Jabatan ?? null },
        Selesai: { MulaiPada: mulai, SelesaiPada: selesaiPada.toISOString(), Menit: menit, Kondisi: kondisi },
      });
      if (daring) {
        await fotoHook.unggahSemua();
        router.reload();
      }
    } finally {
      setMengirim(false);
    }
  };

  return (
    <>
      <Tiket
        atas={<RuteKerja mulai={mulai} selesai={sekarang} menit={detik / 60} />}
        bawah={
          <dl className="grid grid-cols-3 text-center">
            {[
              { nilai: jumlahButir > 0 ? `${jumlahDijawab}/${jumlahButir}` : '—', label: 'Checklist' },
              { nilai: String(permintaanSukuCadang.length), label: 'Suku cadang' },
              { nilai: String(jumlahFoto), label: 'Foto' },
            ].map((satu, i) => (
              <div key={satu.label} className={cn(i > 0 && 'border-l-[1.5px] border-lapangan-garis-2')}>
                <dt className="sr-only">{satu.label}</dt>
                <dd>
                  <strong className="block text-lg leading-tight font-bold tabular-nums">{satu.nilai}</strong>
                  <span className="text-[13px] font-semibold text-lapangan-teks-3">{satu.label}</span>
                </dd>
              </div>
            ))}
          </dl>
        }
      />

      <Kartu pad>
        <h2 className="text-[17px] font-bold tracking-[-0.01em]">Kondisi aset sekarang</h2>
        <div role="radiogroup" aria-label="Kondisi aset sekarang" className="mt-3 flex flex-wrap gap-2">
          {KONDISI_ASET.map((satu) => {
            const aktif = kondisi === satu;
            return (
              <button
                key={satu}
                type="button"
                role="radio"
                aria-checked={aktif}
                onClick={() => setKondisi(satu)}
                className={cn(
                  'inline-flex h-[38px] items-center gap-2 rounded-xl px-3.5 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
                  aktif
                    ? 'bg-lapangan-navy-800 text-white'
                    : 'bg-white text-lapangan-teks-2 shadow-[inset_0_0_0_1.5px_var(--color-lapangan-garis)]',
                )}
              >
                {aktif && <CircleCheck aria-hidden className="size-[18px]" />}
                {satu}
              </button>
            );
          })}
        </div>
      </Kartu>

      <Kartu pad className="relative">
        <h2 className="pr-24 text-[17px] font-bold tracking-[-0.01em]">Tanda tangan pengawas</h2>
        {ubahNama || !pengawas ? (
          <IsianTiket label="Nama pengawas" className="mt-3">
            <MasukanTiket
              value={namaPengawas}
              onChange={(event) => setNamaPengawas(event.target.value)}
              placeholder="Nama yang menyaksikan"
              autoFocus={ubahNama}
            />
          </IsianTiket>
        ) : (
          <p className="text-[13px] text-lapangan-teks-3">
            {[namaPengawas, pengawas.Jabatan].filter(Boolean).join(' · ')}{' '}
            <button
              type="button"
              onClick={() => setUbahNama(true)}
              className="-my-2 inline-flex min-h-11 items-center font-bold text-lapangan-biru-600"
            >
              Ganti
            </button>
          </p>
        )}
        <KanvasTandaTangan label="Kotak tanda tangan pengawas" urlAwal={urlTtd} onBerubah={setTandaTangan} />
      </Kartu>

      {!daring && (
        <PitaInfo
          nada="kuning"
          ikon="satellite_antenna"
          judul="Tanpa sinyal pun bisa dikirim"
          teks="Laporan tersimpan di HP dan terkirim otomatis saat online."
        />
      )}

      <BilahTetap>
        <TombolLapangan penuh disabled={mengirim} onClick={() => void kirim()}>
          <Send aria-hidden />
          {mengirim ? 'Mengirim…' : 'Kirim laporan'}
        </TombolLapangan>
      </BilahTetap>
    </>
  );
}

/* ---------------------------------------------------------------- Selesai (12) */

function LayarSelesai(props: PropsKerjakanTeknisi & { sesi: SesiKerja }) {
  const { tiket, sesi, berikutnya, waktuKerja } = props;
  const { antrian } = useSinkronisasiOffline();
  const keadaan = keadaanLokal(tiket, antrian);
  const selesaiLokal = sesi.Selesai;
  const mulai = selesaiLokal?.MulaiPada ?? waktuKerja.MulaiPertama ?? tiket.DimulaiPada;
  const selesai = selesaiLokal?.SelesaiPada ?? tiket.DiperbaruiPada;
  const menit = selesaiLokal?.Menit ?? waktuKerja.TotalMenit;
  const normal = (selesaiLokal?.Kondisi ?? sesi.Kondisi) === KONDISI_ASET[0];
  const namaAset = tiket.Aset?.Nama;
  const ikonBerikutnya = berikutnya
    ? ikonKategori(berikutnya.Aset?.Kategori ?? berikutnya.Aset?.Nama ?? berikutnya.Judul)
    : null;

  return (
    <>
      <IlustrasiMomen
        jenis="sukses"
        className="pt-6"
        pendamping={[
          { nama: 'sparkles', letak: 'kiri-atas' },
          { nama: 'trophy', letak: 'kanan-bawah' },
        ]}
        judul="Pekerjaan selesai!"
        teks={`${normal && namaAset ? `${namaAset} kembali beroperasi. ` : ''}Laporan dikirim ke koordinator untuk diverifikasi.`}
      />

      {keadaan.Tertunda > 0 && (
        <PitaInfo
          nada="kuning"
          ikon="satellite_antenna"
          judul="Laporan tersimpan di HP"
          teks="Terkirim otomatis saat ada sinyal. Tidak perlu diulang."
        />
      )}

      <Tiket
        bergaris
        latarLekuk="putih"
        atas={
          <>
            <div className="flex items-center justify-between gap-2">
              <strong className="text-lg font-bold tabular-nums">{tiket.Nomor}</strong>
              <ChipStatus warna="kuning">Menunggu Verifikasi</ChipStatus>
            </div>
            <p className="mt-0.5 text-sm text-lapangan-teks-3">{tiket.Judul}</p>
          </>
        }
        bawah={<RuteKerja mulai={mulai} selesai={selesai} menit={menit} />}
      />

      {berikutnya && ikonBerikutnya && (
        <Link
          href={ruteLapangan.teknisi.tugasDetail(berikutnya.Id)}
          className="flex items-center gap-3 rounded-[20px] bg-lapangan-latar px-4 py-3.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
        >
          <span className="flex size-12 shrink-0 items-center justify-center rounded-[14px] bg-white">
            <Ikon3D nama={ikonBerikutnya.ikon} ukuran={32} />
          </span>
          <span className="min-w-0 flex-1">
            <span className="block text-[13px] font-semibold text-lapangan-teks-3">
              Berikutnya
              {berikutnya.DijadwalkanMulaiPada ? ` · ${jamPendek(berikutnya.DijadwalkanMulaiPada)}` : ''}
            </span>
            <b className="block text-[15px] leading-snug font-bold">{berikutnya.Judul}</b>
          </span>
          <ChevronRight aria-hidden className="size-5 shrink-0 text-lapangan-teks-3" />
        </Link>
      )}
    </>
  );
}
