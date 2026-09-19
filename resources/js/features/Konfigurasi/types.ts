export interface KonfigurasiOrganisasi {
  Kunci: string;
  Namespace: string;
  Tipe: 'boolean' | 'integer' | 'string';
  Label: string;
  Rahasia: boolean;
  Nilai: boolean | number | string | null;
}
