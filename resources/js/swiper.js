import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
// import Swiper styles
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const swiper = new Swiper('.swiper', {
    modules: [Navigation, Pagination],
    // Optional parameters
    direction: 'horizontal',
    // loop: true,
    rewind: true,

    // Responsive slides per view
    slidesPerView: 1,
    spaceBetween: 20,

    // Responsive breakpoints
    breakpoints: {
        640: {
            slidesPerView: 1,
            spaceBetween: 0,
        },
        1024: {
            slidesPerView: 3,
            spaceBetween: 0,
        },
    },

    centeredSlides: true,

    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },

    // Navigation arrows
    navigation: {
        nextEl: '.custom-next-btn',
        prevEl: '.custom-prev-btn',
    },
});