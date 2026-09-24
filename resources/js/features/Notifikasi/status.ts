import type { KanalNotifikasi } from './types';

/* Urutan dan label kanal notifikasi (PRD 8.15); dipakai preferensi, templat, dan eskalasi SLA. */
export const KANAL_NOTIFIKASI: { value: KanalNotifikasi; label: string }[] = [
  { value: 'InApp', label: 'In-App' },
  { value: 'Email', label: 'Email' },
  { value: 'WhatsApp', label: 'WhatsApp' },
];
