import '../css/app.css';
// import './bootstrap';

const page = document.body.dataset.page;

if (page === 'home') {
  import('./swiper.js');
}