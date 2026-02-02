(function(){
  const container = document.getElementById('heroPills');
  const titleEl   = document.getElementById('heroTitle');
  const leadEl    = document.getElementById('heroLead');
  const imgEl     = document.getElementById('heroImg');

  if (!container || !titleEl || !leadEl || !imgEl) return;

  const pills   = container.querySelectorAll('.btn');
  const swapEls = [titleEl, leadEl, imgEl];

  function setActive(btn){
    pills.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
    btn.classList.add('active'); btn.setAttribute('aria-selected','true');
  }

  function applyFrom(btn){
    if (btn.dataset.title) titleEl.textContent = btn.dataset.title;
    if (btn.dataset.lead)  leadEl.textContent  = btn.dataset.lead;
    if (btn.dataset.img) {
      imgEl.src = btn.dataset.img;
      imgEl.alt = btn.dataset.imgAlt || btn.dataset.title || 'Hero image';
    }
  }

  function swap(btn){
    setActive(btn);
    swapEls.forEach(el => el.classList.add('is-swapping'));
    setTimeout(() => {
      applyFrom(btn);
      swapEls.forEach(el => el.classList.remove('is-swapping'));
    }, 150);
  }

  pills.forEach(btn => {
    btn.addEventListener('click', () => swap(btn));
    btn.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); swap(btn); }
    });
  });

  const init = container.querySelector('.btn.active') || pills[0];
  if (init) applyFrom(init);
})();