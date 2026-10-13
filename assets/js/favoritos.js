var NE_FAV_KEY = 'nagoscale_favoritos';

function neObtenerFavoritos() {
  try {
    return JSON.parse(localStorage.getItem(NE_FAV_KEY) || '[]');
  } catch (e) {
    return [];
  }
}

function neGuardarFavoritos(lista) {
  localStorage.setItem(NE_FAV_KEY, JSON.stringify(lista));
}

function neEsFavorito(id) {
  return neObtenerFavoritos().indexOf(id) !== -1;
}

function neMostrarToast(mensaje) {
  var toast = document.getElementById('ne-toast');
  if (!toast) return;
  toast.textContent = mensaje;
  toast.classList.add('visible');
  clearTimeout(toast._timeout);
  toast._timeout = setTimeout(function () {
    toast.classList.remove('visible');
  }, 2200);
}

function neToggleFav(boton, id) {
  var lista = neObtenerFavoritos();
  var idx = lista.indexOf(id);
  if (idx === -1) {
    lista.push(id);
    boton.classList.add('activo');
    neMostrarToast('Agregado a favoritos');
  } else {
    lista.splice(idx, 1);
    boton.classList.remove('activo');
    neMostrarToast('Eliminado de favoritos');
  }
  neGuardarFavoritos(lista);
}

function neCardFoto(boton, direccion) {
  var media = boton.closest('.ne-card-media');
  if (!media) return;
  var imgs = media.querySelectorAll('.ne-card-carrusel img');
  var dots = media.querySelectorAll('.ne-card-dot');
  if (!imgs.length) return;
  var actual = 0;
  imgs.forEach(function (img, i) {
    if (img.classList.contains('activa')) actual = i;
  });
  var siguiente = (actual + direccion + imgs.length) % imgs.length;
  imgs[actual].classList.remove('activa');
  imgs[siguiente].classList.add('activa');
  if (dots.length) {
    dots[actual].classList.remove('activo');
    dots[siguiente].classList.add('activo');
  }
}

document.addEventListener('DOMContentLoaded', function () {
  var favoritos = neObtenerFavoritos();
  document.querySelectorAll('[data-fav-id]').forEach(function (btn) {
    var id = parseInt(btn.getAttribute('data-fav-id'), 10);
    if (favoritos.indexOf(id) !== -1) {
      btn.classList.add('activo');
    }
  });
});
