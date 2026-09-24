import { router, usePage } from '@inertiajs/react';
import { useRef, useState, type RefObject } from 'react';
import type { KendaliPadTandaTangan } from '@/components/shared/PadTandaTangan';
import { Ikon3D } from '@/components/shared/Ikon3D';
import { ikonKategori } from '@/components/shared/ikon-kategori';
import KerangkaLapangan from '@/layouts/KerangkaLapangan';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { AreaTiket, IsianTiket } from '@/features/Lapangan/components/IsianTiket';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { RuteJam } from '@/features/Lapangan/components/RuteJam';
import { TandaTanganSaya, useSudahPunyaTandaTangan } from '@/features/Lapangan/components/TandaTanganSaya';
import { Tiket } from '@/features/Lapangan/components/Tiket';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import type { PekerjaanKonfirmasiPenerima, PropsKonfirmasiPenerima } from '@/features/Lapangan/types';
import { jamPendek } from '@/features/Lapangan/waktu';

/** Tujuan tombol tutup: beranda dasbor, yang mengalihkan pengguna lapangan ke Mode Lapangan. */
const TUJUAN_TUTUP = '/';

/** Pad tanda tangan dan galat kiriman, dipakai bersama isi halaman dan bilah aksi. */
interface KeadaanKirim {
  pad: RefObject<KendaliPadTandaTangan | null>;
  siapTtd: boolean;
  setSiapTtd: (siap: boolean) => void;
  galat: Record<string, string>;
  setGalat: (galat: Record<string, string>) => void;
}

/**
 * Konfirmasi penerima hasil pindai QR (PRD 8.22, cara 2). Dibuka dari kamera HP penerima,
 * baik pengguna lapangan maupun pengguna dasbor. Penerima melihat ringkasan pekerjaan dan
 * tanda tangannya (pratinjau bila sudah tersimpan, pad bila belum), lalu "Terima pekerjaan"
 * dengan satu ketukan atau "Masih bermasalah" beserta alasannya.
 */
export default function KonfirmasiPenerima() {
  const { props } = usePage<PropsKonfirmasiPenerima>();
  const punya = useSudahPunyaTandaTangan();
  const pad = useRef<KendaliPadTandaTangan>(null);
  const [siapTtd, setSiapTtd] = useState(punya);
  const [galat, setGalat] = useState<Record<string, string>>({});
  const kirim: KeadaanKirim = { pad, siapTtd, setSiapTtd, galat, setGalat };
  const siap = props.keadaan === 'Siap' && props.pekerjaan !== null;

  return (
    <KerangkaLapangan
      judulHalaman="Konfirmasi pekerjaan"
      varian="appbar"
      judul="Konfirmasi pekerjaan"
      subjudul={props.pekerjaan ? <span className="tabular-nums">{props.pekerjaan.Nomor}</span> : undefined}
      kembali={TUJUAN_TUTUP}
      ikonKembali="tutup"
      bilahAksi={siap ? <AksiKonfirmasi {...props} kirim={kirim} /> : undefined}
    >
      <IsiKonfirmasi {...props} />
      {siap && (
        <Kartu pad>
          <h2 className="mb-3 text-[17px] font-bold tracking-[-0.01em]">Tanda tanganmu</h2>
          <TandaTanganSaya padRef={pad} onSiap={setSiapTtd} />
          {galat.Konfirmasi || galat.TandaTangan ? (
            <p role="alert" className="mt-2 text-[13px] font-semibold text-lapangan-merah-700">
              {galat.Konfirmasi ?? galat.TandaTangan}
            </p>
          ) : null}
        </Kartu>
      )}
    </KerangkaLapangan>
  );
}

function IsiKonfirmasi(props: PropsKonfirmasiPenerima) {
  const { keadaan, pesan, pekerjaan, konfirmasi } = props;

  if (keadaan === 'Kedaluwarsa' || keadaan === 'TautanTidakSah') {
    return (
      <Kartu className="mt-2">
        <IlustrasiMomen
          jenis="galat"
          ikon="hourglass_done"
          judul={keadaan === 'Kedaluwarsa' ? 'QR sudah kedaluwarsa' : 'Tautan tidak sah'}
          teks={pesan}
        />
      </Kartu>
    );
  }

  if (keadaan === 'TanpaAkses') {
    return (
      <Kartu className="mt-2">
        <IlustrasiMomen jenis="tanpa-izin" judul="Kamu tidak dapat mengonfirmasi" teks={pesan} />
      </Kartu>
    );
  }

  return (
    <>
      {pekerjaan && <RingkasanPekerjaan pekerjaan={pekerjaan} />}
      {keadaan === 'SudahDikonfirmasi' && konfirmasi && (
        <PitaInfo
          nada="hijau"
          ikon="check_mark_button"
          judul={konfirmasi.OlehSaya ? 'Kamu sudah menerima pekerjaan ini' : 'Pekerjaan sudah diterima'}
          teks={`${konfirmasi.OlehSaya ? 'Dikonfirmasi' : `Oleh ${konfirmasi.NamaPenerima}`} pukul ${jamPendek(konfirmasi.DikonfirmasiPada)}.`}
        />
      )}
      {keadaan === 'TidakMenunggu' && (
        <PitaInfo nada="kuning" ikon="hourglass_done" judul="Belum bisa dikonfirmasi" teks={pesan} />
      )}
      {keadaan === 'Siap' && (
        <PitaInfo
          nada="biru"
          ikon="handshake"
          judul="Cek hasilnya dulu"
          teks="Pastikan pekerjaan sudah beres di lokasi, lalu terima atau beri tahu yang masih bermasalah."
        />
      )}
    </>
  );
}

function RingkasanPekerjaan({ pekerjaan }: { pekerjaan: PekerjaanKonfirmasiPenerima }) {
  const ikon = ikonKategori(pekerjaan.Aset?.Kategori ?? pekerjaan.Aset?.Nama ?? pekerjaan.Judul);

  return (
    <Tiket
      atas={
        <>
          <div className="flex items-center justify-between gap-2">
            <strong className="text-lg font-bold tabular-nums">{pekerjaan.Nomor}</strong>
            <ChipStatus status={pekerjaan.Status} />
          </div>
          <div className="mt-2.5 flex items-center gap-3">
            <span className="flex size-12 shrink-0 items-center justify-center rounded-[14px] bg-lapangan-biru-50">
              <Ikon3D nama={ikon.ikon} ukuran={32} />
            </span>
            <div className="min-w-0 flex-1">
              <b className="block text-[15px] leading-snug font-bold">{pekerjaan.Judul}</b>
              <span className="block truncate text-[13px] text-lapangan-teks-3">
                {[pekerjaan.Aset?.Nama, pekerjaan.Lokasi].filter(Boolean).join(' · ') || 'Tanpa aset'}
              </span>
            </div>
          </div>
          {pekerjaan.RingkasanPenyelesaian && (
            <p className="mt-3 text-sm leading-[1.45] whitespace-pre-line text-lapangan-teks-2">
              {pekerjaan.RingkasanPenyelesaian}
            </p>
          )}
        </>
      }
      bawah={
        <>
          <RuteJam
            kiri={{ jam: jamPendek(pekerjaan.DimulaiPada), label: 'Mulai' }}
            kanan={{ jam: jamPendek(pekerjaan.DiserahkanPada), label: 'Diserahkan' }}
            ikon="hammer_and_wrench"
          />
          {pekerjaan.Teknisi.length > 0 && (
            <p className="mt-2.5 flex items-center gap-2 text-[13px] text-lapangan-teks-3">
              <Ikon3D nama="man_mechanic" ukuran={24} />
              Dikerjakan <b className="font-bold text-lapangan-teks">{pekerjaan.Teknisi.join(', ')}</b>
            </p>
          )}
        </>
      }
    />
  );
}

function AksiKonfirmasi({ urlKirim, kirim: keadaan }: PropsKonfirmasiPenerima & { kirim: KeadaanKirim }) {
  const [lembarMasalah, setLembarMasalah] = useState(false);
  const [alasan, setAlasan] = useState('');
  const [mengirim, setMengirim] = useState(false);
  const { galat, setGalat } = keadaan;

  const kirim = async (hasil: 'Diterima' | 'MasihBermasalah') => {
    const gambar = hasil === 'Diterima' ? await keadaan.pad.current?.ambilBlob() : null;
    router.post(
      urlKirim,
      {
        Hasil: hasil,
        Alasan: hasil === 'MasihBermasalah' ? alasan.trim() : null,
        TandaTangan: gambar ? new File([gambar], 'tanda-tangan.png', { type: 'image/png' }) : null,
      },
      {
        forceFormData: true,
        onStart: () => setMengirim(true),
        onError: setGalat,
        onNetworkError: () => {
          setGalat({ Konfirmasi: 'Sinyal terputus. Konfirmasi belum terkirim, coba lagi.' });
          return false;
        },
        onFinish: () => setMengirim(false),
      },
    );
  };

  return (
    <>
      <TombolLapangan ragam="garis" className="flex-1" onClick={() => setLembarMasalah(true)}>
        Masih bermasalah
      </TombolLapangan>
      <TombolLapangan
        className="flex-[1.4]"
        disabled={!keadaan.siapTtd || mengirim}
        onClick={() => void kirim('Diterima')}
      >
        {mengirim ? 'Mengirim…' : 'Terima pekerjaan'}
      </TombolLapangan>

      <LembarBawah
        buka={lembarMasalah}
        onBukaBerubah={(buka) => {
          setLembarMasalah(buka);
          if (!buka) setGalat({});
        }}
        judul="Apa yang masih bermasalah?"
        deskripsi="Pekerjaan dikembalikan ke teknisi dan ia langsung diberi tahu."
        kaki={
          <TombolLapangan
            penuh
            ragam="bahaya"
            disabled={alasan.trim() === '' || mengirim}
            onClick={() => void kirim('MasihBermasalah')}
          >
            {mengirim ? 'Mengirim…' : 'Kembalikan ke teknisi'}
          </TombolLapangan>
        }
      >
        <IsianTiket label="Ceritakan singkat" galat={galat.Alasan ?? galat.Konfirmasi ?? null}>
          <AreaTiket
            value={alasan}
            onChange={(event) => setAlasan(event.target.value)}
            rows={3}
            maxLength={2000}
            placeholder="Contoh: lampu masih berkedip di ujung koridor."
          />
        </IsianTiket>
      </LembarBawah>
    </>
  );
}
