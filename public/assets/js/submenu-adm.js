//FUNCION PARA DESPLEGAR EL SUBMENU EN EL ADMINISTRADOR//
  document.querySelectorAll('.toggle').forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();
      let submenu = item.nextElementSibling;
      submenu.classList.toggle('active');
    });
  });