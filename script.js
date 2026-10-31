// Referencias del Modal
const modal = document.getElementById("cotizacion");
const btnAbrir = document.getElementById("btnCotizacion");
const btnCerrar = document.getElementById("cerrarCotizacion");

// Abrir el modal
btnAbrir.addEventListener("click", function (e) {
  e.preventDefault();
  modal.classList.add("activo");
});

// Cerrar con la X
btnCerrar.addEventListener("click", function () {
  modal.classList.remove("activo");
});

// Cerrar al dar clic fuera del cuadro
modal.addEventListener("click", function (e) {
  if (e.target === modal) {
    modal.classList.remove("activo");
  }
});

// Referencias del Formulario
const formulario = document.getElementById("formularioCotizacion");

formulario.addEventListener("submit", function (e) {
  e.preventDefault();

  const nombreInput = document.getElementById("nombre");
  const emailInput = document.getElementById("email");
  const mensajeInput = document.getElementById("mensaje");

  const nombreVal = nombreInput.value.trim();
  const emailVal = emailInput.value.trim();
  const mensajeVal = mensajeInput.value.trim();

  // Limpiar errores previos
  document.getElementById("errorNombre").textContent = "";
  document.getElementById("errorEmail").textContent = "";
  document.getElementById("errorMensaje").textContent = "";

  let esValido = true;

  // 1. Validar Nombre (solo letras y espacios)
  const regexNombre = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/;
  if (!nombreVal) {
    document.getElementById("errorNombre").textContent = "El nombre es obligatorio.";
    esValido = false;
  } else if (!regexNombre.test(nombreVal)) {
    document.getElementById("errorNombre").textContent = "El nombre solo debe contener letras y espacios.";
    esValido = false;
  }

  // 2. Validar Email
  const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  if (!emailVal) {
    document.getElementById("errorEmail").textContent = "El correo es obligatorio.";
    esValido = false;
  } else if (!regexEmail.test(emailVal)) {
    document.getElementById("errorEmail").textContent = "Ingresa un correo electrónico válido.";
    esValido = false;
  }

  // 3. Validar Mensaje
  const regexMensaje = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s.,?!#\-()]{10,500}$/;
  if (!mensajeVal) {
    document.getElementById("errorMensaje").textContent = "El mensaje es obligatorio.";
    esValido = false;
  } else if (!regexMensaje.test(mensajeVal)) {
    document.getElementById("errorMensaje").textContent = "El mensaje debe tener entre 10 y 500 caracteres sin símbolos inválidos.";
    esValido = false;
  }

  // Envío exitoso
  if (esValido) {
    alert("¡Formulario enviado correctamente!");
    formulario.reset();
    modal.classList.remove("activo");
  }
});