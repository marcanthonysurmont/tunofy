import './bootstrap';
import '../css/app.css';
import '../css/vue-animations.css';


import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { ZiggyVue } from '../../vendor/tightenco/ziggy';


createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./pages/**/*.vue', { eager: true })
    return pages[`./pages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({
        created() {
            router.on('invalid', (event) => {
                // Do not prevent the default behavior while developing -- meaning: show the white modal when developing
                // if (import.meta.env.DEV) {
                //     console.log('lol');
                //     return;
                // }

                // Do not prevent the handler for server errors or validation checks
                // if (event.detail.response.status === 500 || event.detail.response.status === 403) {
                //     return;
                // }

                // Prevent a white modal
                event.preventDefault();

                // Show a toast message to the user to inform them about the error.
                // switch (event.detail.response.status) {
                //     case 422:
                //         toast.add({
                //             message: '422: Validation error.',
                //             type: 'danger'
                //         });
                //         break;
                //     case 500:
                //         toast.add({
                //             message: '500: Server error.',
                //             type: 'danger'
                //         });
                //         break;
                //     case 403:
                //         toast.add({
                //             message: '403: Not authorized.',
                //             type: 'danger'
                //         });
                //         break;
                //     case 404:
                //         toast.add({
                //             message: '404: Not found.',
                //             type: 'danger'
                //         });
                //         break;
                //     case 429:
                //         toast.add({
                //             message: '429: Too many requests.',
                //             type: 'danger'
                //         });
                //         break;
                //     default:
                //         toast.add({
                //             message: `${event.detail.response.status}: Whoops, something went wrong.`,
                //             type: 'danger'
                //         });
                //         break;
                // }
            });
        },
        render: () => h(App, props)
    });

    app.use(ZiggyVue);

    app.mount(el);

    return app;
},
  progress: {
    color: '#3361E3',
  }
})