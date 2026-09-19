export interface NomorDokumen {
  Id: string;
  JenisDokumen: string;
  Awalan: string | null;
  FormatNomor: string;
  NomorTerakhir: number;
  ResetPeriode: 'Tahunan' | 'Bulanan' | 'TidakAda';
  PeriodeAktif: string | null;
  Pratinjau: string;
}
