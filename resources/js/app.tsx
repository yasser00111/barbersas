import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as ziggyRoute } from 'ziggy-js';

declare global {
    // eslint-disable-next-line no-var
    var route: typeof ziggyRoute;
}

window.route = ziggyRoute;

const appName = import.meta.env.VITE_APP_NAME || 'BarberSaaS';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ) as never,
    setup({ el, App, props }) {
        createRoot(el as HTMLElement).render(<App {...props} />);
    },
    progress: {
        color: '#d4af37',
    },
});
