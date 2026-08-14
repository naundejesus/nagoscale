(function () {
  var apartamentoSelect = document.querySelector('select[name="apartamento_id"]');
  var inicioInput = document.getElementById('fecha_inicio');
  var finInput = document.getElementById('fecha_fin');
  var calendarEl = document.getElementById('calendario');

  if (!apartamentoSelect || !inicioInput || !finInput || !calendarEl) {
    return;
  }

  var excluirId = calendarEl.getAttribute('data-excluir-id') || '';
  var ocupadas = {};
  var mesActual = inicioInput.value ? new Date(inicioInput.value + 'T00:00:00') : new Date();
  mesActual.setDate(1);
  var seleccionInicio = inicioInput.value || null;
  var seleccionFin = finInput.value || null;

  var hoy = new Date();
  hoy.setHours(0, 0, 0, 0);

  var meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  var diasSemana = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

  function pad(n) {
    return String(n).length < 2 ? '0' + n : String(n);
  }

  function toISO(d) {
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }

  function cargarOcupadas() {
    var apId = apartamentoSelect.value;
    if (!apId) {
      ocupadas = {};
      render();
      return;
    }
    var url = 'reservas_ocupadas.php?apartamento_id=' + encodeURIComponent(apId);
    if (excluirId) {
      url += '&excluir_id=' + encodeURIComponent(excluirId);
    }
    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        ocupadas = {};
        data.forEach(function (f) { ocupadas[f] = true; });
        render();
      })
      .catch(function () {
        ocupadas = {};
        render();
      });
  }

  function rangoTieneOcupado(desdeISO, hastaISO) {
    var d = new Date(desdeISO + 'T00:00:00');
    var fin = new Date(hastaISO + 'T00:00:00');
    while (d <= fin) {
      if (ocupadas[toISO(d)]) {
        return true;
      }
      d.setDate(d.getDate() + 1);
    }
    return false;
  }

  function onDiaClick(iso) {
    if (!apartamentoSelect.value || ocupadas[iso]) {
      return;
    }
    if (!seleccionInicio || (seleccionInicio && seleccionFin) || iso < seleccionInicio) {
      seleccionInicio = iso;
      seleccionFin = null;
    } else if (rangoTieneOcupado(seleccionInicio, iso)) {
      seleccionInicio = iso;
      seleccionFin = null;
    } else {
      seleccionFin = iso;
    }
    inicioInput.value = seleccionInicio || '';
    finInput.value = seleccionFin || seleccionInicio || '';
    render();
  }

  function render() {
    var anio = mesActual.getFullYear();
    var mes = mesActual.getMonth();
    var primerDia = new Date(anio, mes, 1);
    var diaSemanaInicio = (primerDia.getDay() + 6) % 7; // lunes = 0
    var diasEnMes = new Date(anio, mes + 1, 0).getDate();

    var html = '<div class="cal-header">';
    html += '<button type="button" class="cal-nav" data-dir="-1">&lsaquo;</button>';
    html += '<span>' + meses[mes] + ' ' + anio + '</span>';
    html += '<button type="button" class="cal-nav" data-dir="1">&rsaquo;</button>';
    html += '</div>';

    html += '<div class="cal-grid cal-grid-head">';
    diasSemana.forEach(function (d) { html += '<span>' + d + '</span>'; });
    html += '</div>';

    html += '<div class="cal-grid">';
    for (var i = 0; i < diaSemanaInicio; i++) {
      html += '<span></span>';
    }
    for (var dia = 1; dia <= diasEnMes; dia++) {
      var fecha = new Date(anio, mes, dia);
      var iso = toISO(fecha);
      var clase = 'cal-day';
      var disabledAttr = '';

      if (fecha < hoy) {
        clase += ' cal-past';
        disabledAttr = ' disabled';
      } else if (ocupadas[iso]) {
        clase += ' cal-ocupado';
        disabledAttr = ' disabled';
      } else {
        clase += ' cal-disponible';
      }

      if (seleccionInicio && iso === seleccionInicio) {
        clase += ' cal-selec-inicio';
      }
      if (seleccionFin && iso === seleccionFin) {
        clase += ' cal-selec-fin';
      }
      if (seleccionInicio && seleccionFin && iso > seleccionInicio && iso < seleccionFin) {
        clase += ' cal-selec-rango';
      }

      html += '<button type="button" class="' + clase + '" data-iso="' + iso + '"' + disabledAttr + '>' + dia + '</button>';
    }
    html += '</div>';

    html += '<div class="cal-leyenda">';
    html += '<span class="cal-tag cal-tag-disponible">Disponible</span>';
    html += '<span class="cal-tag cal-tag-ocupado">Ocupado</span>';
    html += '</div>';

    if (!apartamentoSelect.value) {
      html += '<p class="cal-hint">Selecciona un apartamento para ver su disponibilidad.</p>';
    }

    calendarEl.innerHTML = html;

    var navButtons = calendarEl.querySelectorAll('.cal-nav');
    for (var n = 0; n < navButtons.length; n++) {
      navButtons[n].addEventListener('click', function () {
        mesActual.setMonth(mesActual.getMonth() + parseInt(this.getAttribute('data-dir'), 10));
        render();
      });
    }

    var diaButtons = calendarEl.querySelectorAll('.cal-day.cal-disponible');
    for (var b = 0; b < diaButtons.length; b++) {
      diaButtons[b].addEventListener('click', function () {
        onDiaClick(this.getAttribute('data-iso'));
      });
    }
  }

  function sincronizarDesdeInputs() {
    seleccionInicio = inicioInput.value || null;
    seleccionFin = finInput.value || null;
    render();
  }

  apartamentoSelect.addEventListener('change', function () {
    seleccionInicio = null;
    seleccionFin = null;
    cargarOcupadas();
  });
  inicioInput.addEventListener('change', sincronizarDesdeInputs);
  finInput.addEventListener('change', sincronizarDesdeInputs);

  cargarOcupadas();
})();
