import { Link } from '@inertiajs/react';
import { ArrowLeftRight, ChevronRight, EllipsisVertical } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan, { TombolAppbar } from '@/layouts/KerangkaLapangan';
import { ruteLapangan } from '@/features/Lapangan/api';
import { PitaInfo } from '@/features/Lapangan/components/Banner';
import { AreaTiket, IsianTiket } from '@/features/Lapangan/components/IsianTiket';
import { WadahIkon3D } from '@/components/shared/Ikon3D';
import { BarisDaftar, Kartu } from '@/features/Lapangan/components/Kartu';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { RuteJam } from '@/features/Lapangan/components/RuteJam';
import { JudulTiket, Tiket } from '@/features/Lapangan/components/Tiket';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { ikonKategori } from '@/features/Lapangan/ikon';
import type { PropsDetailTiketTeknisi } from '@/features/Lapangan/types';
import { jamPendek, tanggalPendek } from '@/features/Lapangan/waktu';
import { useAksiTiket } from '@/features/Lapangan/components/teknisi/aksiTiket';
import { ChipKepalaTiket } from '@/features/Lapangan/components/teknisi/KartuTiketTeknisi';
import {
  STATUS_BISA_MULAI,
  STATUS_SEDANG_DIKERJAKAN,
  keadaanLokal,
  tiketLokal,
} from '@/features/Lapangan/components/teknisi/statusLokal';
import {
  PitaMasalahTiket,
  usePeringatanOffline,
  useKirimAntreanSaatBuka,
} from '@/features/Lapangan/components/teknisi/umum';
import {
  STATUS_SELESAI_TEKNISI,
  ruteTiket,
  teksLokasi,
} from '@/features/Lapangan/components/teknisi/waktuTiket';

const ALASAN_ALIHKAN = [
  'Sedang mengerjakan tiket lain',
  'Di luar keahlian saya',
  'Saya tidak di lokasi',
  'Butuh alat atau vendor khusus',
];

/** Detail tiket (DESIGN §36.6 layar 06): tiket besar, aset, asal keluhan, checklist, lokasi, riwayat. */
export default function DetailTiketTeknisi(props: PropsDetailTiketTeknisi) {
  const [menuBuka, setMenuBuka] = useState(false);
  const ditugaskan = props.tiket.DitugaskanPada;

  return (
    <KerangkaLapangan
      varian="appbar"
      judulHalaman={`Tiket ${props.tiket.Nomor}`}
      judul="Detail tiket"
      subjudul={ditugaskan ? `Ditugaskan ke kamu · ${jamPendek(ditugaskan)}` : props.tiket.Nomor}
      kembali={ruteLapangan.teknisi.tugas}
      panjang
      aksiKanan={
        <TombolAppbar label="Pilihan lain" ikon={EllipsisVertical} onClick={() => setMenuBuka(true)} />
      }
      bilahAksi={<BilahAksiDetail {...props} />}
    >
      <IsiDetail {...props} />
      <LembarBawah buka={menuBuka} onBukaBerubah={setMenuBuka} judul="Pilihan lain">
        <Kartu className="overflow-hidden shadow-none ring-[1.5px] ring-lapangan-garis ring-inset">
          {props.tiket.Aset && (
            <BarisDaftar
              ikon={<WadahIkon3D nama="magnifying_glass_tilted_left" ukuran="kecil" />}
              judul="Lihat aset"
              keterangan={props.tiket.Aset.KodeAset}
              href={ruteLapangan.teknisi.pindaiAset(props.tiket.Aset.Id)}
            />
          )}
          {props.tiket.Aset && (
            <BarisDaftar
              ikon={<WadahIkon3D nama="card_index_dividers" tint="hijau" ukuran="kecil" />}
              judul="Riwayat aset"
              href={ruteLapangan.teknisi.riwayatAset(props.tiket.Aset.Id)}
            />
          )}
          <BarisDaftar
            ikon={<WadahIkon3D nama="clipboard" tint="oranye" ukuran="kecil" />}
            judul="Semua tiket saya"
            href={ruteLapangan.teknisi.tugas}
          />
        </Kartu>
      </LembarBawah>
    </KerangkaLapangan>
  );
}

function IsiDetail({ tiket: tiketServer, keluhan, daftarPeriksa, riwayatAset }: PropsDetailTiketTeknisi) {
  usePeringatanOffline();
  useKirimAntreanSaatBuka();
  const { antrian } = useSinkronisasiOffline();
  const keadaan = keadaanLokal(tiketServer, antrian);
  const tiket = tiketLokal(tiketServer, antrian);
  const rute = ruteTiket(tiket);
  const ikon = ikonKategori(tiket.Aset?.Kategori ?? tiket.Aset?.Nama ?? tiket.Judul);
  const lokasi = tiket.Aset?.Lokasi ?? tiket.Lokasi;

  return (
    <>
      <Tiket
        apung
        atas={
          <>
            <div className="flex items-start justify-between gap-2">
              <ChipKepalaTiket tiket={tiket} />
              <span className="shrink-0 pt-1 text-[13px] font-semibold text-lapangan-teks-3 tabular-nums">
                {tiket.Nomor}
              </span>
            </div>
            <JudulTiket className="text-[19px]">{tiket.Judul}</JudulTiket>
            {rute && <RuteJam {...rute} />}
          </>
        }
        bawah={
          tiket.Aset ? (
            <Link
              href={ruteLapangan.teknisi.pindaiAset(tiket.Aset.Id)}
              className="-m-1 flex items-center gap-3.5 rounded-2xl p-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
            >
              <WadahIkon3D nama={ikon.ikon} tint={ikon.tint} ukuran="besar" className="size-16" />
              <span className="min-w-0 flex-1">
                <b className="block truncate text-base font-bold">{tiket.Aset.Nama}</b>
                <span className="block truncate text-[13px] text-lapangan-teks-3">
                  {[tiket.Aset.KodeAset, teksLokasi(tiket.Aset.Lokasi)].filter(Boolean).join(' · ')}
                </span>
              </span>
              <ChevronRight aria-hidden className="size-5 shrink-0 text-lapangan-teks-3" />
            </Link>
          ) : (
            <p className="text-sm text-lapangan-teks-3">Tiket ini tidak terhubung ke aset tertentu.</p>
          )
        }
      />

      <PitaMasalahTiket keadaan={keadaan} />
      {keadaan.Dialihkan && (
        <PitaInfo
          nada="kuning"
          ikon="satellite_antenna"
          judul="Permintaan alihkan menunggu dikirim"
          teks="Koordinator akan menugaskan teknisi lain setelah permintaan ini terkirim."
        />
      )}

      {keluhan ? (
        <Kartu pad>
          <div className="flex items-center gap-3">
            <WadahIkon3D nama="megaphone" tint="merah" />
            <div className="min-w-0 flex-1">
              <b className="block text-base leading-snug font-bold">Dari keluhan {keluhan.Nomor}</b>
              <span className="block truncate text-[13px] text-lapangan-teks-3">
                {[keluhan.Pelapor, keluhan.Lokasi].filter(Boolean).join(' · ') || 'Pelapor tidak disebut'}
              </span>
            </div>
          </div>
          {keluhan.Deskripsi && (
            <blockquote className="mt-3 rounded-2xl bg-lapangan-latar px-3.5 py-3 text-[15px] leading-normal text-lapangan-teks-2">
              “{keluhan.Deskripsi}”
            </blockquote>
          )}
        </Kartu>
      ) : tiket.Deskripsi ? (
        <Kartu pad>
          <h2 className="text-base font-bold">Uraian pekerjaan</h2>
          <p className="mt-1.5 text-[15px] leading-normal whitespace-pre-line text-lapangan-teks-2">
            {tiket.Deskripsi}
          </p>
        </Kartu>
      ) : null}

      <Kartu className="overflow-hidden">
        <BarisDaftar
          ikon={<WadahIkon3D nama="clipboard" tint="kuning" />}
          judul={daftarPeriksa?.NamaTemplat ?? 'Checklist'}
          keterangan={
            daftarPeriksa
              ? `${daftarPeriksa.JumlahButir} langkah${daftarPeriksa.Status === 'Selesai' ? ' · sudah selesai' : ''}`
              : 'Tiket ini tidak memakai checklist'
          }
          href={
            daftarPeriksa && STATUS_SEDANG_DIKERJAKAN.includes(tiket.Status)
              ? ruteLapangan.teknisi.kerjakan(tiket.Id, 'checklist')
              : undefined
          }
          chevron={Boolean(daftarPeriksa)}
        />
        <BarisDaftar
          ikon={<WadahIkon3D nama="round_pushpin" />}
          judul={lokasi?.Nama ?? 'Lokasi belum diisi'}
          keterangan={lokasi?.Induk ?? undefined}
        />
        {tiket.Aset && (
          <BarisDaftar
            ikon={<WadahIkon3D nama="card_index_dividers" tint="hijau" />}
            judul="Riwayat aset"
            keterangan={
              riwayatAset?.TerakhirDiservisPada
                ? `Servis terakhir ${tanggalPendek(riwayatAset.TerakhirDiservisPada)}`
                : `${riwayatAset?.JumlahPekerjaan ?? 0} pekerjaan tercatat`
            }
            href={ruteLapangan.teknisi.riwayatAset(tiket.Aset.Id)}
          />
        )}
      </Kartu>
    </>
  );
}

function BilahAksiDetail({ tiket: tiketServer }: PropsDetailTiketTeknisi) {
  const { antrian } = useSinkronisasiOffline();
  const { mulai, alihkan, memproses } = useAksiTiket();
  const [alihkanBuka, setAlihkanBuka] = useState(false);
  const [alasan, setAlasan] = useState('');
  const [galat, setGalat] = useState<string | null>(null);
  const keadaan = keadaanLokal(tiketServer, antrian);
  const tiket = tiketLokal(tiketServer, antrian);
  const sibuk = memproses === tiket.Id;

  if (STATUS_SELESAI_TEKNISI.includes(tiket.Status)) {
    return (
      <TombolLapangan asChild ragam="garis" penuh>
        <Link href={ruteLapangan.teknisi.kerjakan(tiket.Id)}>Lihat bukti selesai</Link>
      </TombolLapangan>
    );
  }

  if (keadaan.Dialihkan) {
    return (
      <TombolLapangan asChild ragam="garis" penuh>
        <Link href={ruteLapangan.teknisi.tugas}>Kembali ke Tiket Saya</Link>
      </TombolLapangan>
    );
  }

  const bisaMulai = keadaan.PerluRespons || STATUS_BISA_MULAI.includes(tiket.Status);
  const labelUtama = keadaan.PerluRespons
    ? 'Terima & Mulai'
    : STATUS_SEDANG_DIKERJAKAN.includes(tiket.Status)
      ? 'Lanjutkan pekerjaan'
      : tiket.Status === 'Diterima'
        ? 'Mulai kerja'
        : 'Lanjutkan';

  const kirimAlihkan = () => {
    if (alasan.trim().length < 3) {
      setGalat('Tulis alasan singkat supaya koordinator tahu.');
      return;
    }
    setAlihkanBuka(false);
    void alihkan(tiket, alasan.trim());
  };

  return (
    <>
      {keadaan.PerluRespons && (
        <TombolLapangan
          ragam="garis"
          className="flex-[0.85]"
          disabled={sibuk}
          onClick={() => setAlihkanBuka(true)}
        >
          <ArrowLeftRight aria-hidden />
          Alihkan
        </TombolLapangan>
      )}
      <TombolLapangan
        className="flex-[1.6]"
        disabled={sibuk || (!bisaMulai && !STATUS_SEDANG_DIKERJAKAN.includes(tiket.Status))}
        onClick={() => void mulai(tiket)}
      >
        {sibuk ? 'Menyimpan…' : labelUtama}
      </TombolLapangan>

      <LembarBawah
        buka={alihkanBuka}
        onBukaBerubah={setAlihkanBuka}
        judul="Alihkan tiket ini?"
        deskripsi="Penugasanmu ditolak dan koordinator menugaskan teknisi lain."
        kaki={
          <TombolLapangan penuh onClick={kirimAlihkan}>
            Minta dialihkan
          </TombolLapangan>
        }
      >
        <div className="flex flex-wrap gap-2">
          {ALASAN_ALIHKAN.map((satu) => (
            <button
              key={satu}
              type="button"
              aria-pressed={alasan === satu}
              onClick={() => {
                setAlasan(satu);
                setGalat(null);
              }}
              className={cn(
                'min-h-11 rounded-xl px-3.5 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
                alasan === satu
                  ? 'bg-lapangan-navy-800 text-white'
                  : 'bg-white text-lapangan-teks-2 ring-[1.5px] ring-lapangan-garis ring-inset',
              )}
            >
              {satu}
            </button>
          ))}
        </div>
        <IsianTiket label="Alasan" galat={galat}>
          <AreaTiket
            value={alasan}
            onChange={(event) => {
              setAlasan(event.target.value);
              setGalat(null);
            }}
            placeholder="Mis. sedang di gedung lain sampai sore"
            maxLength={1000}
          />
        </IsianTiket>
      </LembarBawah>
    </>
  );
}
