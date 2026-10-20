const modal = document.getElementById("cotizacion");
const btnAbrir = document.getElementById("btnCotizacion");
const btnCerrar = document.getElementById("cerrarCotizacion");

// Abrir el modal al picar el botón del menú
btnAbrir.addEventListener("click", function (e) {
  e.preventDefault(); // evita que el href="#" mueva la página
  modal.classList.add("activo");
});

// Cerrar con la X
btnCerrar.addEventListener("click", function () {
  modal.classList.remove("activo");
});

// Cerrar al picar en el fondo oscuro (fuera del cuadro)
modal.addEventListener("click", function (e) {
  if (e.target === modal) {
    modal.classList.remove("activo");
  }
});