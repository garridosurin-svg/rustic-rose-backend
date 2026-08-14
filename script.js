const menuBtn=document.querySelector(".menu-toggle"),nav=document.querySelector(".site-header nav");
menuBtn?.addEventListener("click",()=>{const open=nav.classList.toggle("open");menuBtn.setAttribute("aria-expanded",String(open));menuBtn.setAttribute("aria-label",open?"Close menu":"Open menu")});
document.querySelectorAll(".site-header nav a").forEach(a=>a.addEventListener("click",()=>{nav?.classList.remove("open");menuBtn?.setAttribute("aria-expanded","false")}));
if("IntersectionObserver" in window){const observer=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add("visible")}),{threshold:.12});document.querySelectorAll(".reveal").forEach(el=>observer.observe(el))}else{document.querySelectorAll(".reveal").forEach(el=>el.classList.add("visible"))}
document.querySelectorAll("#year").forEach(el=>el.textContent=new Date().getFullYear());
const today=new Date();today.setHours(0,0,0,0);const minDate=today.toISOString().split("T")[0];document.querySelectorAll('input[type="date"]').forEach(input=>input.min=minDate);
function refNumber(){return `RR-${new Date().getFullYear()}-${Math.random().toString(36).slice(2,8).toUpperCase()}`}
function showToast(message){const toast=document.querySelector(".toast");if(!toast)return;toast.textContent=message;toast.classList.add("show");setTimeout(()=>toast.classList.remove("show"),3500)}
function validateForm(form){let valid=true;form.querySelectorAll("[required]").forEach(field=>{field.classList.remove("field-error");if(!field.checkValidity()){field.classList.add("field-error");valid=false}});const status=form.querySelector(".form-status");if(status){status.classList.toggle("success",valid);status.textContent=valid?"":"Please complete all required fields correctly."}if(!valid)form.querySelector(".field-error")?.focus();return valid}
function saveBooking(form,type){const data=Object.fromEntries(new FormData(form).entries());data.reference=refNumber();data.type=type;data.status="Pending";data.createdAt=new Date().toISOString();const bookings=JSON.parse(localStorage.getItem("rusticRoseBookings")||"[]");bookings.unshift(data);localStorage.setItem("rusticRoseBookings",JSON.stringify(bookings.slice(0,100)));sessionStorage.setItem("rusticRoseLatest",JSON.stringify(data));return data}
document.querySelectorAll("form").forEach(form=>form.addEventListener("input",e=>e.target.classList.remove("field-error")));
document.getElementById("contact-form")?.addEventListener("submit",e=>{if(!validateForm(e.currentTarget))e.preventDefault()});

const latest=JSON.parse(sessionStorage.getItem("rusticRoseLatest")||"null");if(latest&&document.getElementById("reference-number")){document.getElementById("reference-number").textContent=latest.reference;const details=document.getElementById("confirmation-details");const rows=[["Name",`${latest.firstName||""} ${latest.lastName||""}`.trim()],["Service",latest.service||"—"],["Event Date",latest.eventDate||"—"],["Consultation",latest.consultDate?`${latest.consultDate} · ${latest.consultTime||"Time pending"}`:"Team follow-up pending"]];details.innerHTML=rows.map(([k,v])=>`<div><small>${k}</small><strong>${v}</strong></div>`).join("")}
window.addEventListener("error",e=>{const logs=JSON.parse(localStorage.getItem("rusticRoseErrorLogs")||"[]");logs.unshift({message:e.message,source:e.filename,line:e.lineno,createdAt:new Date().toISOString()});localStorage.setItem("rusticRoseErrorLogs",JSON.stringify(logs.slice(0,25)))})

// Responsive gallery lightbox with graceful fallbacks for unavailable online images.
(() => {
  const gallery = document.querySelector('.gallery-grid');
  if (!gallery) return;

  const localFallbacks = [
    'assets/images/reception-barn.jpg',
    'assets/images/bridal-portrait.jpg',
    'assets/images/grand-exit.jpg',
    'assets/images/group-celebration.jpg',
    'assets/images/outdoor-event.jpg'
  ];

  const figures = Array.from(gallery.querySelectorAll('figure'));
  figures.forEach((figure, index) => {
    const image = figure.querySelector('img');
    if (!image) return;
    image.addEventListener('error', () => {
      if (image.dataset.fallbackApplied) return;
      image.dataset.fallbackApplied = 'true';
      image.src = localFallbacks[index % localFallbacks.length];
    });
    figure.tabIndex = 0;
    figure.setAttribute('role', 'button');
    figure.setAttribute('aria-label', `View image: ${image.alt || 'gallery photograph'}`);
  });

  const lightbox = document.createElement('div');
  lightbox.className = 'gallery-lightbox';
  lightbox.setAttribute('aria-hidden', 'true');
  lightbox.innerHTML = `
    <button class="gallery-lightbox-close" type="button" aria-label="Close image viewer">×</button>
    <button class="gallery-lightbox-arrow prev" type="button" aria-label="Previous gallery image">←</button>
    <figure><img alt=""><figcaption></figcaption></figure>
    <button class="gallery-lightbox-arrow next" type="button" aria-label="Next gallery image">→</button>`;
  document.body.appendChild(lightbox);

  const lightboxImage = lightbox.querySelector('img');
  const lightboxCaption = lightbox.querySelector('figcaption');
  let activeIndex = 0;

  function openAt(index) {
    activeIndex = (index + figures.length) % figures.length;
    const sourceImage = figures[activeIndex].querySelector('img');
    lightboxImage.src = sourceImage.currentSrc || sourceImage.src;
    lightboxImage.alt = sourceImage.alt || '';
    lightboxCaption.textContent = figures[activeIndex].querySelector('figcaption')?.textContent || '';
    lightbox.classList.add('is-open');
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.classList.add('lightbox-open');
    lightbox.querySelector('.gallery-lightbox-close').focus();
  }

  function close() {
    lightbox.classList.remove('is-open');
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('lightbox-open');
  }

  figures.forEach((figure, index) => {
    figure.addEventListener('click', () => openAt(index));
    figure.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openAt(index); }
    });
  });
  lightbox.querySelector('.gallery-lightbox-close').addEventListener('click', close);
  lightbox.querySelector('.prev').addEventListener('click', () => openAt(activeIndex - 1));
  lightbox.querySelector('.next').addEventListener('click', () => openAt(activeIndex + 1));
  lightbox.addEventListener('click', event => { if (event.target === lightbox) close(); });
  document.addEventListener('keydown', event => {
    if (!lightbox.classList.contains('is-open')) return;
    if (event.key === 'Escape') close();
    if (event.key === 'ArrowLeft') openAt(activeIndex - 1);
    if (event.key === 'ArrowRight') openAt(activeIndex + 1);
  });
})();

// Premium header Admin Access modal — robust delegated version
(() => {
  const getModal = () => document.getElementById('adminAccessModal');
  let lastFocus = null;

  const setPasswordVisible = (visible) => {
    const modal = getModal();
    if (!modal) return;
    const password = modal.querySelector('#adminPopupPassword');
    const checkbox = modal.querySelector('#adminShowPassword');
    const eye = modal.querySelector('.admin-access-eye');
    if (password) password.type = visible ? 'text' : 'password';
    if (checkbox) checkbox.checked = visible;
    if (eye) {
      eye.setAttribute('aria-pressed', String(visible));
      eye.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    }
  };

  const openModal = () => {
    const modal = getModal();
    if (!modal) {
      window.location.href = 'admin/login.php';
      return;
    }
    lastFocus = document.activeElement;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('admin-modal-open');
    const username = modal.querySelector('input[name="username"]');
    window.setTimeout(() => username && username.focus(), 80);
  };

  const closeModal = () => {
    const modal = getModal();
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('admin-modal-open');
    setPasswordVisible(false);
    if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
  };

  // Exposed fallback used by the header button's inline handler.
  window.openAdminAccess = openModal;
  window.closeAdminAccess = closeModal;

  document.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-admin-access]');
    if (openButton) {
      event.preventDefault();
      openModal();
      return;
    }

    const closeButton = event.target.closest('[data-admin-close]');
    if (closeButton) {
      event.preventDefault();
      closeModal();
      return;
    }

    const eye = event.target.closest('.admin-access-eye');
    if (eye) {
      event.preventDefault();
      const modal = getModal();
      const password = modal && modal.querySelector('#adminPopupPassword');
      setPasswordVisible(Boolean(password && password.type === 'password'));
    }
  });

  document.addEventListener('change', (event) => {
    if (event.target && event.target.id === 'adminShowPassword') {
      setPasswordVisible(event.target.checked);
    }
  });

  document.addEventListener('keydown', (event) => {
    const modal = getModal();
    if (!modal || !modal.classList.contains('is-open')) return;
    if (event.key === 'Escape') {
      closeModal();
      return;
    }
    if (event.key !== 'Tab') return;
    const focusable = Array.from(modal.querySelectorAll('button:not([disabled]),input:not([disabled]),a[href]'))
      .filter((element) => element.offsetParent !== null);
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
})();

// Public header dropdowns now use native <details>/<summary> for reliable mobile behavior.
(() => {
  const header = document.querySelector('.site-header.premium-public-header.one-line-public-header');
  if (!header) return;
  const nav = header.querySelector('nav');
  const menuButton = header.querySelector('.menu-toggle');
  const dropdowns = Array.from(header.querySelectorAll('details.nav-dropdown'));

  dropdowns.forEach(dropdown => {
    const syncDropdownState = () => {
      dropdown.classList.toggle('is-open', dropdown.open);
      const summary = dropdown.querySelector('summary.nav-dropdown-toggle');
      summary?.setAttribute('aria-expanded', dropdown.open ? 'true' : 'false');
    };
    syncDropdownState();
    dropdown.addEventListener('toggle', () => {
      syncDropdownState();
      if (!dropdown.open) return;
      dropdowns.forEach(other => {
        if (other !== dropdown) {
          other.open = false;
          other.classList.remove('is-open');
          other.querySelector('summary.nav-dropdown-toggle')?.setAttribute('aria-expanded','false');
        }
      });
    });
  });

  header.querySelectorAll('.nav-dropdown-menu a').forEach(link => {
    link.addEventListener('click', () => {
      dropdowns.forEach(dropdown => { dropdown.open = false; });
      if (window.matchMedia('(max-width: 1240px)').matches) {
        nav?.classList.remove('open', 'is-open');
        menuButton?.setAttribute('aria-expanded', 'false');
        menuButton?.setAttribute('aria-label', 'Open menu');
      }
    });
  });

  menuButton?.addEventListener('click', () => {
    window.setTimeout(() => {
      if (!nav?.classList.contains('open')) dropdowns.forEach(dropdown => { dropdown.open = false; });
    }, 0);
  });
})();


// Booking form usability helpers
(function(){
  function initFriendlyBookingForm(form){
    if(!form) return;
    var date=form.querySelector('input[name="event_date"]');
    if(date && !date.min){
      var now=new Date(), y=now.getFullYear(), m=String(now.getMonth()+1).padStart(2,'0'), d=String(now.getDate()).padStart(2,'0');
      date.min=y+'-'+m+'-'+d;
    }
    var message=form.querySelector('textarea[name="message"]');
    var count=form.querySelector('[data-message-count]');
    if(message && count){
      var update=function(){count.textContent=message.value.length+' / 3000';};
      message.addEventListener('input',update); update();
    }
    form.addEventListener('submit',function(e){
      if(!form.checkValidity()){
        e.preventDefault();
        var bad=form.querySelector(':invalid');
        if(bad){bad.focus(); bad.scrollIntoView({behavior:'smooth',block:'center'});}
        return;
      }
      var btn=form.querySelector('.submit-btn');
      if(btn && !btn.disabled){
        btn.disabled=true;
        btn.dataset.originalText=btn.innerHTML;
        btn.innerHTML='Sending your booking…';
      }
    });
  }
  document.addEventListener('DOMContentLoaded',function(){
    initFriendlyBookingForm(document.getElementById('booking-form'));
    initFriendlyBookingForm(document.getElementById('contact-form'));
  });
})();

/* Production enhancement: accessible smooth anchors + lazy images */
document.addEventListener('DOMContentLoaded', function () {
  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(function (link) {
    link.addEventListener('click', function (event) {
      const id = decodeURIComponent(this.getAttribute('href').slice(1));
      const target = document.getElementById(id);
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({
        behavior: reduceMotion ? 'auto' : 'smooth',
        block: 'start'
      });
      if (history.pushState) history.pushState(null, '', '#' + encodeURIComponent(id));
    });
  });

  document.querySelectorAll('img:not([loading])').forEach(function (img) {
    if (!img.closest('.hero, header')) {
      img.setAttribute('loading', 'lazy');
      img.setAttribute('decoding', 'async');
    }
  });

  document.querySelectorAll('video').forEach(function (video) {
    video.setAttribute('playsinline', '');
  });
});

