document.addEventListener('DOMContentLoaded', () => {
  const header = document.getElementById('nordicHeader');
  const burger = document.getElementById('nordicBurger');
  const buyDropdown = document.getElementById('buyDropdown');
  const buyToggle = document.getElementById('buyToggle');
  const carouselTrack = document.getElementById('carouselTrack');
  const carouselPrev = document.getElementById('carouselPrev');
  const carouselNext = document.getElementById('carouselNext');
  const carouselDots = Array.from(document.querySelectorAll('#carouselDots .dot'));
  const totalSlides = 3;
  let currentSlide = 0;
  let autoSlideTimer;

  burger?.addEventListener('click', () => {
    const isOpen = header.classList.toggle('mobile-open');
    burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  buyToggle?.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = buyDropdown?.classList.toggle('open');
    buyToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  document.addEventListener('click', (event) => {
    if (!buyDropdown?.contains(event.target)) {
      buyDropdown?.classList.remove('open');
      buyToggle?.setAttribute('aria-expanded', 'false');
    }
  });

  function goToSlide(index) {
    if (!carouselTrack) return;
    currentSlide = (index + totalSlides) % totalSlides;
    carouselTrack.style.transform = `translateX(-${currentSlide * 100}%)`;

    carouselDots.forEach((dot, dotIndex) => {
      dot.classList.toggle('active', dotIndex === currentSlide);
    });
  }

  function startAutoSlide() {
    clearInterval(autoSlideTimer);
    autoSlideTimer = setInterval(() => {
      goToSlide(currentSlide + 1);
    }, 4000);
  }

  carouselNext?.addEventListener('click', () => {
    goToSlide(currentSlide + 1);
    startAutoSlide();
  });

  carouselPrev?.addEventListener('click', () => {
    goToSlide(currentSlide - 1);
    startAutoSlide();
  });

  carouselDots.forEach((dot) => {
    dot.addEventListener('click', () => {
      goToSlide(Number(dot.dataset.slide));
      startAutoSlide();
    });
  });

  goToSlide(0);
  startAutoSlide();
});
