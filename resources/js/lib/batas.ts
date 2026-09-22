/**
 * Batas atas daftar yang masih dipaginasi di browser (FASE 25.01).
 *
 * Nilainya dikunci bersama `BatasDaftar::MAKS` di
 * app/Shared/Infrastructure/Persistence/BatasDaftar.php, dan kesamaannya dijaga
 * oleh BatasDaftarTest supaya keduanya tidak pernah bergeser sendiri-sendiri.
 */
export const BATAS_DAFTAR = 500;
