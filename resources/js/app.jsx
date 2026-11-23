import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Plateforme Facturation B2B';
const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: async (name) => {
        const loader = pages[`./Pages/${name}.jsx`];
        if (!loader) {
            throw new Error(`Page Inertia introuvable : ${name}`);
        }
        const page = await loader();
        return page.default ?? page;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#2563eb',
        showSpinner: true,
        delay: 250,
    },
});
