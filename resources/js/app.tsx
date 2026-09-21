import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';
import { PenyediaKonfirmasi } from '@/hooks/use-konfirmasi';

createInertiaApp({
  title: (title) => (title ? title + ' - Amanpoll' : 'Amanpoll'),
  resolve: (name) => {
    const pages = import.meta.glob<ComponentType>('./features/*/pages/**/*.tsx', { import: 'default' });

    const segments = name.split('/');
    const feature = segments.length > 1 ? segments[0] : 'Shared';
    const pagePath = segments.length > 1 ? segments.slice(1).join('/') : name;

    return resolvePageComponent(`./features/${feature}/pages/${pagePath}.tsx`, pages);
  },
  setup({ el, App, props }) {
    if (!el) return;
    createRoot(el).render(
      <>
        <PenyediaKonfirmasi>
          <App {...props} />
        </PenyediaKonfirmasi>
        <Toaster richColors position="top-right" />
      </>,
    );
  },
  progress: { color: '#18181b' },
});
