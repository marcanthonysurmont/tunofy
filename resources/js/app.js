import "./bootstrap";
import "../css/app.css";
import "../css/vue-animations.css";
import "../css/hovers.css";
import "../css/tippy.css";
import "../css/scrollbars.css";

import { createApp, h } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { createPinia } from "pinia";
import { autoAnimatePlugin } from "@formkit/auto-animate/vue";
import VueTippy from "vue-tippy";
import "tippy.js/dist/tippy.css"; // optional for styling
import toast from "@/stores/StoreToast.js";
import VueLazyLoad from "vue3-lazyload";
import "vue-fast-marquee/style.css";
const pinia = createPinia();

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob("./pages/**/*.vue", { eager: true });
        return pages[`./pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({
            created() {
                router.on("invalid", (event) => {
                    //prevent a white modal from inertia
                    event.preventDefault();

                    // Show a toast message to the user to inform them about the error.
                    switch (event.detail.response.status) {
                        case 422:
                            toast.add({
                                message: "422: Validation error.",
                                type: "danger",
                            });
                            break;
                        case 500:
                            toast.add({
                                message: "500: Server error.",
                                type: "danger",
                            });
                            break;
                        case 403:
                            toast.add({
                                message: "403: Not authorized.",
                                type: "danger",
                            });
                            break;
                        case 404:
                            toast.add({
                                message: "404: Not found.",
                                type: "danger",
                            });
                            break;
                        case 429:
                            toast.add({
                                message: "429: Too many requests.",
                                type: "danger",
                            });
                            break;
                        default:
                            toast.add({
                                message: `${event.detail.response.status}: Whoops, something went wrong.`,
                                type: "danger",
                            });
                            break;
                    }
                });
            },
            render: () => h(App, props),
        });

        app.use(ZiggyVue)
            .use(plugin)
            .use(pinia)
            .use(autoAnimatePlugin)
            .use(VueTippy, {
                defaultProps: {
                    touch: false,
                    theme: "tunofy",
                    hideOnClick: true,
                    trigger: "mouseenter",
                },
            })
            .use(VueLazyLoad, {
                loading: "/images/default-song.png",
                error: "/images/default-song.png",
                // lifecycle: {
                //     loading: (el) => {
                //         console.log("loading", el);
                //     },
                //     error: (el) => {
                //         console.log("error", el);
                //     },
                //     loaded: (el) => {
                //         console.log("loaded", el);
                //     },
                // },
            });
        app.mount(el);

        return app;
    },
    progress: {
        color: "#3361E3",
    },
});
