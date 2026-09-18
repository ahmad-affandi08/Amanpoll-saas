import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';

createInertiaApp({
  title: (title) => (title ? title + ' - Amanpoll' : 'Amanpoll'),
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.tsx`,
      import.meta.glob<ComponentType>('./pages/**/*.tsx', { import: 'default' }),
    ),
  setup({ el, App, props }) {
    if (!el) return;
    createRoot(el).render(
      <>
        <App {...props} />
        <Toaster richColors position="top-right" />
      </>,
    );
  },
  progress: { color: '#18181b' },
});
