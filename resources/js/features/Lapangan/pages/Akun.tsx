import { router, usePage } from '@inertiajs/react';
import { CloudCheck, CloudOff, LogOut, RefreshCw, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import KerangkaLapangan, { AvatarLapangan } from '@/layouts/KerangkaLapangan';
import { DialogKonflik } from '@/features/Sinkronisasi/components/DialogKonflik';
import type { MutasiOffline, OperasiOffline } from '@/features/Sinkronisasi/types';
import { ruteLapangan } from '@/features/Lapangan/api';
import { ChipStatus } from '@/features/Lapangan/components/ChipStatus';
import { Ikon3D, WadahIkon3D, type NamaIkon3D } from '@/components/shared/Ikon3D';
import { BarisDaftar, Kartu, KartuApung } from '@/features/Lapangan/components/Kartu';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { useKeluarLapangan } from '@/features/Lapangan/hooks/use-keluar-lapangan';
import type { PropsHalamanAkun } from '@/features/Lapangan/types';
import { jamPendek, waktuRelatif } from '@/features/Lapangan/waktu';

/** Baris antrean yang ditampilkan sebelum diringkas menjadi "+N lainnya". */
const BATAS_BARIS_ANTREAN = 4;

const IKON_OPERASI: Record<OperasiOffline, NamaIkon3D> = {
  'PerintahKerja.ResponsPenugasan': 'clipboard',
  'PerintahKerja.UbahStatus': 'clipboard',
  'PerintahKerja.CatatWaktuKerja': 'stopwatch',
  'PerintahKerja.TambahCatatan': 'memo',
  'DaftarPeriksa.SimpanJawaban': 'memo',
  'DaftarPeriksa.Finalisasi': 'check_mark_button',
  'Keluhan.Buat': 'megaphone',
};

/** Akun & sinkronisasi (papan Teknisi layar 17, Pelapor layar 15). */
export default function LapanganAkun() {
  const { props } = usePage<PropsHalamanAkun>();
  const nama = props.akun?.Nama ?? props.auth.pengguna?.Nama ?? 'Pengguna';
  const organisasi = props.auth.pengguna?.organisasi?.Nama;
  const keterangan =
    props.akun?.Jabatan ?? props.auth.pengguna?.Jabatan ?? props.akun?.Peran.join(' · ') ?? null;

  return (
    <KerangkaLapangan
      judulHalaman="Akun"
      navAktif="akun"
      statusSinkron={false}
      isiHero={
        <div className="flex items-center gap-3.5">
          <AvatarLapangan nama={nama} url={props.akun?.AvatarUrl ?? props.auth.pengguna?.AvatarUrl} besar />
          <div className="min-w-0 flex-1">
            <h1 className="truncate text-xl leading-tight font-bold tracking-[-0.01em]">{nama}</h1>
            {keterangan && <p className="truncate text-[13.5px] font-medium text-white/80">{keterangan}</p>}
            {organisasi && <p className="truncate text-[13px] font-medium text-white/75">{organisasi}</p>}
          </div>
        </div>
      }
      apung
    >
      <IsiAkun />
    </KerangkaLapangan>
  );
}

function IsiAkun() {
  const { props } = usePage<PropsHalamanAkun>();
  const {
    status,
    daring,
    paket,
    antrian,
    jumlahBelumTersinkron,
    memuat,
    muatPaket,
    dorong,
    selesaikanKonflik,
  } = useSinkronisasiOffline();
  const keluar = useKeluarLapangan();
  const [konflikTerpilih, setKonflikTerpilih] = useState<MutasiOffline | null>(null);
  const [beralih, setBeralih] = useState(false);
  const teknisi = props.lapangan?.mode !== 'Pelapor';
  const ringkasan = props.ringkasan ?? [];

  const antreanTampil = antrian.filter((m) => m.Status !== 'Selesai' && m.Status !== 'Dibatalkan');
  const antreanUrut = [...antreanTampil].sort((a, b) =>
    a.Status === 'Konflik' ? -1 : b.Status === 'Konflik' ? 1 : a.DibuatPada.localeCompare(b.DibuatPada),
  );
  const sisa = antreanUrut.length - BATAS_BARIS_ANTREAN;

  const bukaDasbor = () => {
    setBeralih(true);
    router.post(ruteLapangan.tampilan, { Tampilan: 'dasbor' }, { onFinish: () => setBeralih(false) });
  };

  const kartuSinkron = (
    <>
      <div className="flex items-center gap-3 px-4 py-3.5">
        <WadahIkon3D nama="cloud" ukuran="kecil" />
        <div className="min-w-0 flex-1">
          <h2 className="text-base leading-tight font-bold tracking-[-0.01em]">Sinkronisasi</h2>
          <p className="text-[13px] text-lapangan-teks-3">
            {paket
              ? `Terakhir berhasil ${jamPendek(paket.DibuatPada)}`
              : teknisi
                ? 'Belum pernah mengunduh data'
                : 'Yang dibuat saat offline terkirim otomatis'}
          </p>
        </div>
        <ChipStatusSinkron status={status} />
      </div>
      <div className="border-t-[1.5px] border-lapangan-garis-2" />
      {antreanUrut.length === 0 ? (
        <div className="flex items-center gap-3 px-4 py-3">
          <Ikon3D nama="check_mark_button" ukuran={30} />
          <p className="flex-1 text-sm font-semibold">Semua perubahan sudah terkirim</p>
        </div>
      ) : (
        <ul>
          {antreanUrut.slice(0, BATAS_BARIS_ANTREAN).map((mutasi) => (
            <BarisAntrean
              key={mutasi.KunciOperasi}
              mutasi={mutasi}
              // Teknisi memilih versi di layar "Pilih versi" (papan layar 18); pelapor tetap lewat dialog.
              onPilihVersi={
                teknisi
                  ? (satu) => router.visit(ruteLapangan.teknisi.konflik(satu.KunciOperasi))
                  : setKonflikTerpilih
              }
            />
          ))}
          {sisa > 0 && (
            <li className="px-4 py-2 text-[13px] font-semibold text-lapangan-teks-3">+{sisa} lainnya</li>
          )}
        </ul>
      )}
      {jumlahBelumTersinkron > 0 && (
        <div className="px-4 pt-1 pb-4">
          <TombolLapangan
            ragam="lembut"
            ukuran="kecil"
            penuh
            disabled={!daring || status === 'Menyinkronkan'}
            onClick={() => void dorong()}
          >
            <RefreshCw aria-hidden className={cn(status === 'Menyinkronkan' && 'animate-spin')} />
            {daring ? 'Coba kirim sekarang' : 'Terkirim otomatis saat online'}
          </TombolLapangan>
        </div>
      )}
    </>
  );

  return (
    <>
      {ringkasan.length > 0 ? (
        <KartuApung className="grid grid-cols-3 px-2 py-4">
          {ringkasan.slice(0, 3).map((satu, i) => (
            <div
              key={satu.Label}
              className={cn('text-center', i > 0 && 'border-l-[1.5px] border-lapangan-garis-2')}
            >
              <strong className="block text-[22px] leading-tight font-extrabold tracking-[-0.02em] tabular-nums">
                {satu.Nilai}
              </strong>
              <span className="text-[12.5px] font-semibold text-lapangan-teks-3">{satu.Label}</span>
            </div>
          ))}
        </KartuApung>
      ) : null}

      {ringkasan.length > 0 ? (
        <Kartu className="overflow-hidden">{kartuSinkron}</Kartu>
      ) : (
        <KartuApung className="overflow-hidden">{kartuSinkron}</KartuApung>
      )}

      <Kartu className="overflow-hidden">
        {teknisi && (
          <BarisDaftar
            ikon={<WadahIkon3D nama="package" tint="ungu" ukuran="kecil" />}
            judul="Data offline"
            keterangan={
              memuat
                ? 'Mengunduh data terbaru…'
                : paket
                  ? `${paket.Penugasan.length} tiket, ${paket.Aset.length} aset · ${waktuRelatif(paket.DibuatPada)}`
                  : 'Unduh tiket dan aset agar tetap bisa bekerja tanpa sinyal'
            }
            kanan={
              daring ? (
                <span className="text-sm font-semibold text-lapangan-oranye-teks">Perbarui</span>
              ) : undefined
            }
            chevron={false}
            onClick={daring && !memuat ? () => void muatPaket() : undefined}
          />
        )}
        <BarisDaftar
          ikon={<WadahIkon3D nama="bell" tint="kuning" ukuran="kecil" />}
          judul="Notifikasi"
          href={ruteLapangan.notifikasi}
        />
        {props.akun?.Telepon && (
          <BarisDaftar
            ikon={<WadahIkon3D nama="telephone_receiver" tint="hijau" ukuran="kecil" />}
            judul="Nomor telepon"
            kanan={<span className="text-sm font-semibold text-lapangan-teks-3">{props.akun.Telepon}</span>}
          />
        )}
        {props.lapangan?.bisaBeralih && (
          <BarisDaftar
            ikon={<WadahIkon3D nama="office_building" ukuran="kecil" />}
            judul="Buka dasbor web"
            keterangan="Kembali ke tampilan dasbor di perangkat ini"
            onClick={beralih ? undefined : bukaDasbor}
          />
        )}
      </Kartu>

      <Kartu className="overflow-hidden">
        <BarisDaftar
          ikon={<WadahIkon3D nama="door" tint="merah" ukuran="kecil" />}
          judul="Keluar"
          bahaya
          keterangan={
            jumlahBelumTersinkron > 0
              ? `Kirim ${jumlahBelumTersinkron} perubahan dulu sebelum keluar`
              : undefined
          }
          ikonKanan={<LogOut aria-hidden className="size-[18px] text-lapangan-teks-3" />}
          onClick={() => void keluar()}
        />
      </Kartu>

      <p className="text-center text-[13px] font-medium text-lapangan-teks-3">Amanpoll Lapangan</p>

      <DialogKonflik
        mutasi={konflikTerpilih}
        onTutup={() => setKonflikTerpilih(null)}
        onSelesaikan={selesaikanKonflik}
      />
    </>
  );
}

function ChipStatusSinkron({ status }: { status: ReturnType<typeof useSinkronisasiOffline>['status'] }) {
  switch (status) {
    case 'Offline':
      return (
        <ChipStatus warna="kuning" ikon={CloudOff}>
          Offline
        </ChipStatus>
      );
    case 'Menyinkronkan':
      return (
        <ChipStatus warna="biru" ikon={RefreshCw}>
          Mengirim
        </ChipStatus>
      );
    case 'GagalSinkron':
      return (
        <ChipStatus warna="merah" ikon={TriangleAlert}>
          Gagal kirim
        </ChipStatus>
      );
    case 'Konflik':
      return (
        <ChipStatus warna="merah" ikon={TriangleAlert}>
          Konflik
        </ChipStatus>
      );
    default:
      return (
        <ChipStatus warna="hijau" ikon={CloudCheck}>
          Online
        </ChipStatus>
      );
  }
}

const LABEL_STATUS_ANTREAN: Record<MutasiOffline['Status'], string> = {
  Menunggu: 'Menunggu',
  Diproses: 'Dikirim…',
  Selesai: 'Terkirim',
  Gagal: 'Gagal',
  Konflik: 'Pilih versi',
  Dibatalkan: 'Dibatalkan',
};

function BarisAntrean({
  mutasi,
  onPilihVersi,
}: {
  mutasi: MutasiOffline;
  onPilihVersi: (mutasi: MutasiOffline) => void;
}) {
  const konflik = mutasi.Status === 'Konflik';
  const gagal = mutasi.Status === 'Gagal';

  return (
    <li
      className={cn(
        'flex items-center gap-3 px-4 py-[11px] [&+&]:border-t-[1.5px] [&+&]:border-lapangan-garis-2',
        konflik && 'bg-lapangan-merah-50',
      )}
    >
      <Ikon3D nama={konflik ? 'warning' : IKON_OPERASI[mutasi.Operasi]} ukuran={30} />
      <div className="min-w-0 flex-1">
        <b className={cn('block text-sm leading-[1.3] font-bold', konflik && 'text-lapangan-merah-700')}>
          {konflik ? `Konflik: ${mutasi.Label}` : mutasi.Label}
        </b>
        <span className="block text-[12.5px] font-medium text-lapangan-teks-3">
          {konflik
            ? (mutasi.Konflik?.Pesan ?? 'Diubah juga oleh orang lain')
            : waktuRelatif(mutasi.DibuatPada)}
        </span>
        {gagal && (
          <span className="mt-0.5 block text-[12.5px] leading-[1.35] font-medium text-lapangan-merah-700">
            {mutasi.Konflik?.Pesan ?? 'Ditolak server. Buka tiketnya untuk memperbaiki.'}
          </span>
        )}
      </div>
      {konflik ? (
        <button
          type="button"
          onClick={() => onPilihVersi(mutasi)}
          className="-my-2 inline-flex min-h-11 items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500"
        >
          <ChipStatus warna="merah" className="bg-white">
            Pilih versi
          </ChipStatus>
        </button>
      ) : (
        <span
          className={cn(
            'text-[13px] font-semibold',
            mutasi.Status === 'Gagal' ? 'text-lapangan-merah-700' : 'text-lapangan-teks-3',
          )}
        >
          {LABEL_STATUS_ANTREAN[mutasi.Status]}
        </span>
      )}
    </li>
  );
}
