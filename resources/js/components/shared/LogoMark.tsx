interface Props {
  className?: string;
}

/* Mark sementara: geometris, memakai palet Amanpoll (Teknisi + Safety). Ganti bila logo final tersedia. */
export function LogoMark({ className }: Props) {
  return (
    <svg viewBox="0 0 32 32" fill="none" className={className} aria-hidden="true">
      <rect width="32" height="32" rx="7" fill="#17324D" />
      <path d="M16 7l7.5 18h-4.1l-1.5-3.8h-7.8L8.6 25H4.5L12 7h4zm-.1 5.3l-2.6 6.5h5.2l-2.6-6.5z" fill="#FFFFFF" />
      <rect x="21.5" y="21.5" width="5" height="5" rx="1.2" fill="#D97706" />
    </svg>
  );
}
