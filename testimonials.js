(function () {
  'use strict';

  function initTestimonials() {
    var slider = document.querySelector('[data-testimonial-slider]');
    if (!slider || slider.dataset.ready === 'true') return;

    var slides = Array.prototype.slice.call(slider.querySelectorAll('.testimonial-slide'));
    var prevButton = slider.querySelector('.testimonial-arrow.prev');
    var nextButton = slider.querySelector('.testimonial-arrow.next');
    var dotsContainer = slider.querySelector('.testimonial-dots');
    var counter = slider.querySelector('.testimonial-counter');
    var progress = slider.querySelector('.testimonial-progress-bar');

    if (slides.length < 2 || !prevButton || !nextButton) return;
    slider.dataset.ready = 'true';

    var current = Math.max(0, slides.findIndex(function (slide) {
      return slide.classList.contains('is-active');
    }));
    var timeoutId = null;
    var delay = 4500;
    var dots = [];

    function resetProgress() {
      if (!progress) return;
      progress.style.animation = 'none';
      void progress.offsetWidth;
      progress.style.animation = 'testimonialProgress ' + delay + 'ms linear forwards';
    }

    function render(index) {
      current = (index + slides.length) % slides.length;
      slides.forEach(function (slide, slideIndex) {
        var active = slideIndex === current;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      });
      dots.forEach(function (dot, dotIndex) {
        dot.classList.toggle('is-active', dotIndex === current);
        dot.setAttribute('aria-current', dotIndex === current ? 'true' : 'false');
      });
      if (counter) {
        counter.textContent = String(current + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
      }
      resetProgress();
    }

    function schedule() {
      window.clearTimeout(timeoutId);
      timeoutId = window.setTimeout(function () {
        render(current + 1);
        schedule();
      }, delay);
      resetProgress();
    }

    function move(direction, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      render(current + direction);
      schedule();
    }

    if (dotsContainer) {
      dotsContainer.innerHTML = '';
      slides.forEach(function (_, index) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'testimonial-dot';
        dot.setAttribute('aria-label', 'Show testimonial ' + (index + 1));
        dot.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          render(index);
          schedule();
        });
        dotsContainer.appendChild(dot);
        dots.push(dot);
      });
    }

    prevButton.addEventListener('click', function (event) { move(-1, event); });
    nextButton.addEventListener('click', function (event) { move(1, event); });
    prevButton.addEventListener('pointerup', function (event) {
      if (event.pointerType === 'touch') move(-1, event);
    });
    nextButton.addEventListener('pointerup', function (event) {
      if (event.pointerType === 'touch') move(1, event);
    });

    var startX = null;
    slider.addEventListener('touchstart', function (event) {
      startX = event.changedTouches[0].clientX;
    }, { passive: true });
    slider.addEventListener('touchend', function (event) {
      if (startX === null) return;
      var distance = event.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(distance) > 45) move(distance < 0 ? 1 : -1);
    }, { passive: true });

    slider.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') move(-1, event);
      if (event.key === 'ArrowRight') move(1, event);
    });

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) window.clearTimeout(timeoutId);
      else schedule();
    });

    render(current);
    schedule();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTestimonials);
  } else {
    initTestimonials();
  }
})();
