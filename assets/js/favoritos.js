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

document.addEventListener('DOMContentLoaded', function () {
  var favoritos = neObtenerFavoritos();
  document.querySelectorAll('[data-fav-id]').forEach(function (btn) {
    var id = parseInt(btn.getAttribute('data-fav-id'), 10);
    if (favoritos.indexOf(id) !== -1) {
      btn.classList.add('activo');
    }
  });
});
