(function(){
  const toggle = document.getElementById('menuToggle');
  const nav = document.getElementById('mainNav');

  if (!toggle || !nav) {
    console.warn('menuToggle or mainNav not found');
    return;
  }

  let backdrop = document.querySelector('.nav-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'nav-backdrop';
    document.body.appendChild(backdrop);
  }

  function openNav(){
    nav.classList.add('show');
    toggle.classList.add('active');
    toggle.setAttribute('aria-expanded','true');
    backdrop.classList.add('show');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
  }

  function closeNav(){
    nav.classList.remove('show');
    toggle.classList.remove('active');
    toggle.setAttribute('aria-expanded','false');
    backdrop.classList.remove('show');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', ()=> {
    nav.classList.contains('show') ? closeNav() : openNav();
  });

  backdrop.addEventListener('click', closeNav);

  nav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', closeNav);
  });

  window.addEventListener('keydown', e => {
    if(e.key === 'Escape') closeNav();
  });
})();
