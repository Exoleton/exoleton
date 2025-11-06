// Année dynamique dans le footer
document.addEventListener('DOMContentLoaded', () => {
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();

  // Smooth scroll pour les ancres internes
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener('click', (e)=>{
      const id = a.getAttribute('href');
      if (!id || id === '#') return;
      const target = document.querySelector(id);
      if (target){
        e.preventDefault();
        window.scrollTo({top: target.offsetTop-64, behavior: 'smooth'});
      }
    });
  });
});
