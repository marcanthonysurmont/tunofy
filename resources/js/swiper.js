import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
// import Swiper styles
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const swiper = new Swiper('.swiper', {
    modules: [Navigation, Pagination],
    direction: 'horizontal',
    // loop: true,
    rewind: true,

    grabCursor: true,

    slidesPerView: 1,
    spaceBetween: 20,

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

const progressBar = document.querySelector(".progress");
progressBar.addEventListener("animationend", myEndFunction);

console.log(progressBar);

// Retrigger Animation on Slide Change

function myEndFunction() {
  swiper.slideNext();
  progressBar.style.animation = "none";
  void progressBar.offsetWidth; // Triggers Reflow
  progressBar.style.animation = null;
}

// Reset Progress Bar On Slide Change

swiper.on("slideChange", function () {
  progressBar.style.animation = "none";
  void progressBar.offsetWidth; // Triggers Reflow
  progressBar.style.animation = null;
  progressBar.style.animationPlayState = "paused"; // Optional
});

// Pause Carousel/Progress Bar On Hover

document.querySelectorAll(".swiper, .carousel-progress").forEach((item) => {
  item.addEventListener("mouseenter", function () {
    progressBar.style.animationPlayState = "paused";
  });
});

document.querySelectorAll(".swiper, .carousel-progress").forEach((item) => {
  item.addEventListener("mouseleave", function () {
    progressBar.style.animationPlayState = "running";
  });
});

