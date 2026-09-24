import { Link, usePage } from '@inertiajs/react';
import { MessageCircle, Share2 } from 'lucide-react';
import { useState } from 'react';
import KerangkaLapangan, { TombolAppbar } from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { Ikon3D } from '@/features/Lapangan/components/Ikon3D';
import { Kartu } from '@/features/Lapangan/components/Kartu';
import { PerhentianLinimasa, type TitikLinimasa } from '@/features/Lapangan/components/Perhentian';
import { RuteJam } from '@/features/Lapangan/components/RuteJam';
import { Sobekan } from '@/features/Lapangan/components/Tiket';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { KartuTeknisi } from '@/features/Lapangan/components/pelapor/KartuTeknisi';
import { LembarKeterangan } from '@/features/Lapangan/components/pelapor/LembarKeterangan';
import { PitaPelapor } from '@/features/Lapangan/components/pelapor/PitaPelapor';
import {
  durasiRingkas,
  indeksPerhentian,
  namaDepan,
  PERHENTIAN_LAPORAN,
  statusPerhentian,
  tampilanStatus,
} from '@/features/Lapangan/components/pelapor/status';
import { ikonLaporan } from '@/features/Lapangan/components/pelapor/TiketLaporan';
import { labelHari } from '@/features/Lapangan/components/pelapor/waktu';
import type {
  KeadaanPerhentian,
  LaporanPelapor,
  PropsLacakPelapor,
  RiwayatLaporan,
} from '@/features/Lapangan/types';
import { jamPendek } from '@/features/Lapangan/waktu';

const STATUS_AKHIR = ['Ditutup', 'Ditolak', 'Dibatalkan'];

/** Riwayat terakhir yang masuk ke salah satu status perhentian itu. */
function riwayatTerakhir(riwayat: RiwayatLaporan[], indeks: number): RiwayatLaporan | undefined {
  const status = statusPerhentian(indeks);
  return [...riwayat].reverse().find((satu) => status.includes(satu.StatusSesudah));
}

/**
 * Perhentian "Perjalanan laporan" dari riwayat status keluhan yang sesungguhnya:
 * jam tiap perhentian adalah saat status itu tercatat; perhentian saat ini bercincin
 * oranye; yang belum dilalui abu. Keluhan yang ditolak/dibatalkan berhenti di sana.
 */
function susunPerhentian(laporan: LaporanPelapor, riwayat: RiwayatLaporan[]): TitikLinimasa[] {
  const kini = indeksPerhentian(laporan.Status);
  const tuntas = laporan.Status === 'Ditutup';
  const teknisi = laporan.Teknisi;
  const berhenti = kini < 0;
  const terjauh = berhenti
    ? Math.max(0, ...PERHENTIAN_LAPORAN.map((_, i) => (riwayatTerakhir(riwayat, i) ? i : 0)))
    : kini;

  const titik: TitikLinimasa[] = [];
  PERHENTIAN_LAPORAN.forEach((label, i) => {
    if (berhenti && i > terjauh) return;

    const catatan = riwayatTerakhir(riwayat, i);
    const keadaan: KeadaanPerhentian =
      tuntas || berhenti || i < kini ? 'lewat' : i === kini ? 'kini' : 'nanti';
    let judul: string = label;
    let keterangan: string | undefined;
    let jam = catatan ? jamPendek(catatan.DiubahPada) : undefined;
    let isi: TitikLinimasa['isi'];

    switch (i) {
      case 0:
        keterangan = 'oleh kamu';
        jam = jamPendek(laporan.DilaporkanPada);
        break;
      case 1:
        keterangan = catatan?.NamaPengubah
          ? `oleh ${catatan.NamaPengubah}`
          : 'Tim teknik akan meninjau laporanmu';
        break;
      case 2:
        judul = teknisi ? `Ditugaskan ke ${namaDepan(teknisi.Nama)}` : 'Ditugaskan';
        keterangan = teknisi
          ? (teknisi.Jabatan ?? 'Teknisi')
          : catatan
            ? 'Diterima tim teknik'
            : 'Teknisi dipilih sesudah ditinjau';
        if (keadaan === 'kini' && teknisi) isi = <KartuTeknisi teknisi={teknisi} />;
        break;
      case 3:
        keterangan =
          catatan?.StatusSebelum === 'Selesai' && catatan.Catatan
            ? `Dibuka lagi. ${catatan.Catatan}`
            : teknisi && keadaan !== 'kini'
              ? `oleh ${teknisi.Nama}`
              : undefined;
        if (keadaan === 'kini' && teknisi) isi = <KartuTeknisi teknisi={teknisi} />;
        break;
      case 4:
        if (keadaan === 'nanti') {
          jam = laporan.BatasPenyelesaianPada ? `±${jamPendek(laporan.BatasPenyelesaianPada)}` : undefined;
          keterangan = 'Kamu akan diminta konfirmasi';
        } else if (laporan.Status === 'Selesai') {
          keterangan = teknisi?.Ringkasan ?? 'Cek hasilnya, lalu konfirmasi';
        } else {
          judul = 'Selesai & ditutup';
          keterangan = laporan.Rating ? `Kamu memberi ${laporan.Rating} bintang` : 'Dikonfirmasi beres';
        }
        break;
    }

    titik.push({ judul, keterangan, jam, keadaan, isi });
  });

  if (berhenti) {
    const akhir = [...riwayat].reverse().find((satu) => satu.StatusSesudah === laporan.Status);
    titik.push({
      judul: tampilanStatus(laporan.Status).label,
      keterangan: akhir?.Catatan ?? undefined,
      jam: akhir ? jamPendek(akhir.DiubahPada) : undefined,
      keadaan: 'kini',
    });
  }

  return titik;
}

/** Lacak laporan (DESIGN.md 36.7 layar 10–11): tiket + rute jam, perjalanan laporan, teknisi. */
export default function LacakPelapor() {
  const { props } = usePage<PropsLacakPelapor>();
  const { laporan } = props;
  const [bukaKeterangan, setBukaKeterangan] = useState(false);
  const perluKonfirmasi = laporan.Status === 'Selesai';
  const akhir = STATUS_AKHIR.includes(laporan.Status);

  const bagikan = () => {
    const teks = `Laporan ${laporan.Nomor}: ${laporan.Judul} (${tampilanStatus(laporan.Status).label})`;
    if (navigator.share) {
      void navigator.share({ title: `Laporan ${laporan.Nomor}`, text: teks }).catch(() => undefined);
    } else {
      void navigator.clipboard?.writeText(teks);
    }
  };

  return (
    <KerangkaLapangan
      judulHalaman={`Lacak ${laporan.Nomor}`}
      varian="appbar"
      mode="Pelapor"
      judul="Lacak laporan"
      subjudul={<span className="tabular-nums">{laporan.Nomor}</span>}
      kembali={ruteLapangan.pelapor.laporan}
      panjang
      aksiKanan={<TombolAppbar label="Bagikan laporan" ikon={Share2} onClick={bagikan} />}
      bilahAksi={
        akhir ? undefined : (
          <>
            <TombolLapangan
              ragam="garis"
              className={perluKonfirmasi ? 'px-4' : 'flex-1'}
              onClick={() => setBukaKeterangan(true)}
            >
              <MessageCircle aria-hidden />
              {perluKonfirmasi ? 'Keterangan' : 'Tambah keterangan'}
            </TombolLapangan>
            {perluKonfirmasi && (
              <TombolLapangan asChild className="flex-1">
                <Link href={ruteLapangan.pelapor.konfirmasi(laporan.Id)}>Konfirmasi</Link>
              </TombolLapangan>
            )}
          </>
        )
      }
      navBawah={akhir ? true : undefined}
    >
      <IsiLacak {...props} />
      {!akhir && (
        <LembarKeterangan
          buka={bukaKeterangan}
          onBukaBerubah={setBukaKeterangan}
          keluhanId={laporan.Id}
          namaTeknisi={laporan.Teknisi ? namaDepan(laporan.Teknisi.Nama) : null}
        />
      )}
    </KerangkaLapangan>
  );
}

function IsiLacak({ laporan, riwayat, jumlahFoto }: PropsLacakPelapor) {
  const status = tampilanStatus(laporan.Status);
  const ikon = ikonLaporan(laporan);
  const selesaiPada = laporan.DiresolusikanPada ?? laporan.DitutupPada;
  const beres = laporan.Status === 'Selesai' || laporan.Status === 'Ditutup';

  return (
    <>
      <article className="relative z-10 -mt-14 rounded-[20px] bg-white shadow-lapangan-apung">
        <div className="px-4 pt-4 pb-3">
          <ChipStatus warna={status.warna} ikon={status.ikon}>
            {status.label}
          </ChipStatus>
          <h2 className="mt-2 mb-2.5 text-[17px] leading-[1.3] font-bold tracking-[-0.01em]">
            {laporan.Judul}
          </h2>
          <RuteJam
            kiri={{
              jam: jamPendek(laporan.DilaporkanPada),
              label: labelHari('Dilaporkan', laporan.DilaporkanPada, 'Dilapor'),
            }}
            kanan={
              beres && selesaiPada
                ? { jam: jamPendek(selesaiPada), label: labelHari('Beres', selesaiPada) }
                : laporan.BatasPenyelesaianPada
                  ? { jam: `±${jamPendek(laporan.BatasPenyelesaianPada)}`, label: 'Perkiraan beres' }
                  : { jam: '—', label: 'Perkiraan beres' }
            }
            ikon={beres ? 'hourglass_done' : 'hammer_and_wrench'}
            label={
              durasiRingkas(laporan.DilaporkanPada, beres && selesaiPada ? selesaiPada : new Date()) ??
              undefined
            }
          />
        </div>
        <Sobekan />
        <div className="flex items-center gap-3 px-4 pt-1.5 pb-3.5">
          <Ikon3D nama={laporan.Aset ? ikon.ikon : 'round_pushpin'} ukuran={32} />
          <div className="min-w-0 flex-1">
            <b className="block truncate text-sm font-bold">{laporan.Aset?.Nama ?? 'Lokasi saja'}</b>
            <span className="block truncate text-[13px] font-medium text-lapangan-teks-3">
              {[laporan.Aset?.KodeAset, laporan.LokasiLabel ?? laporan.LokasiNama]
                .filter(Boolean)
                .join(' · ')}
              {jumlahFoto > 0 ? ` · ${jumlahFoto} foto` : ''}
            </span>
          </div>
        </div>
      </article>

      <PitaPelapor />

      <Kartu className="pt-4 pr-3 pb-0 pl-2">
        <h2 className="mb-3.5 pl-2 text-[15px] font-bold tracking-[-0.01em]">Perjalanan laporan</h2>
        <PerhentianLinimasa titik={susunPerhentian(laporan, riwayat)} label="Perjalanan laporan" />
      </Kartu>
    </>
  );
}
