// Login / registro de usuarios
const cuerpo = document.body,
  form = document.getElementById("formAcceso"),
  accion = document.getElementById("accion"),
  titulo = document.getElementById("accesoTitulo"),
  sub = document.getElementById("accesoSub"),
  pestanas = document.querySelector(".pestanas"),
  tabs = pestanas.querySelectorAll("[role=tab]"),
  cambiar = document.getElementById("cambiar"),
  aviso = document.getElementById("aviso"),
  boton = document.getElementById("btnEnviar"),
  btnTexto = document.getElementById("btnTexto"),
  inUsuario = document.getElementById("usuario"),
  inPassword = document.getElementById("password"),
  inConfirmar = document.getElementById("confirmar"),
  verConfirmar = document.querySelector('.ver-clave[data-para="confirmar"]');

const textos = {
  login: {
    titulo: "Iniciar sesión",
    sub: "Ingresa con tu usuario y contraseña.",
    boton: "Entrar",
    cambiar: '¿No tienes cuenta? <button type="button" data-modo="registro">Crea una</button>'
  },
  registro: {
    titulo: "Crear cuenta",
    sub: "Elige un usuario y una contraseña para registrarte.",
    boton: "Crear cuenta",
    cambiar: '¿Ya tienes cuenta? <button type="button" data-modo="login">Inicia sesión</button>'
  }
};

let modo = "login";

function ponerModo(nuevo) {
  if (nuevo === modo) return;
  modo = nuevo;
  const t = textos[modo];
  const registro = modo === "registro";

  cuerpo.dataset.modo = modo;
  pestanas.dataset.modo = modo;
  tabs.forEach(b => b.setAttribute("aria-selected", b.dataset.modo === modo));
  accion.value = modo;
  titulo.textContent = t.titulo;
  sub.textContent = t.sub;
  btnTexto.textContent = t.boton;
  cambiar.innerHTML = t.cambiar;
  document.title = `${t.titulo} · Herrería Hernández`;

  inPassword.autocomplete = registro ? "new-password" : "current-password";
  inConfirmar.disabled = !registro;
  verConfirmar.tabIndex = registro ? 0 : -1;
  if (!registro) inConfirmar.value = "";

  limpiarErrores();
  inUsuario.focus();
}

tabs.forEach(b => b.addEventListener("click", () => ponerModo(b.dataset.modo)));
cambiar.addEventListener("click", e => {
  const b = e.target.closest("[data-modo]");
  if (b) ponerModo(b.dataset.modo);
});

// Flechas izquierda/derecha entre pestañas
pestanas.addEventListener("keydown", e => {
  if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;
  const otro = modo === "login" ? "registro" : "login";
  ponerModo(otro);
  pestanas.querySelector(`[data-modo="${otro}"]`).focus();
});

// Mostrar / ocultar contraseña
document.querySelectorAll(".ver-clave").forEach(b => b.addEventListener("click", () => {
  const input = document.getElementById(b.dataset.para);
  const ver = input.type === "password";
  input.type = ver ? "text" : "password";
  b.setAttribute("aria-pressed", ver);
  b.setAttribute("aria-label", ver ? "Ocultar contraseña" : "Mostrar contraseña");
}));

// Validación (el servidor vuelve a validar)
const campos = {
  usuario: { input: inUsuario, error: "errorUsuario" },
  password: { input: inPassword, error: "errorPassword" },
  confirmar: { input: inConfirmar, error: "errorConfirmar" }
};

const reglas = {
  usuario: v => !v ? "El usuario es obligatorio."
    : modo === "registro" && !/^[a-zA-Z0-9_.]{3,30}$/.test(v)
      ? "De 3 a 30 caracteres: letras, números, punto o guion bajo." : "",
  password: v => !v ? "La contraseña es obligatoria."
    : modo === "registro" && (v.length < 8 || v.length > 72)
      ? "La contraseña debe tener entre 8 y 72 caracteres." : "",
  confirmar: v => modo !== "registro" ? ""
    : !v ? "Confirma tu contraseña."
    : v !== inPassword.value ? "Las contraseñas no coinciden." : ""
};

function mostrarError(c, msg) {
  document.getElementById(campos[c].error).textContent = msg;
  campos[c].input.setAttribute("aria-invalid", msg ? "true" : "false");
  if (msg) campos[c].input.setAttribute("aria-describedby", campos[c].error);
  else campos[c].input.removeAttribute("aria-describedby");
  return !msg;
}

function validar(c) {
  const v = c === "usuario" ? campos[c].input.value.trim() : campos[c].input.value;
  return mostrarError(c, reglas[c](v));
}

function limpiarErrores() {
  Object.keys(campos).forEach(c => mostrarError(c, ""));
  ocultarAviso();
}

function mostrarAviso(msg, tipo = "error") {
  aviso.textContent = msg;
  aviso.classList.toggle("exito", tipo === "exito");
  aviso.classList.remove("visible");
  void aviso.offsetWidth; // reinicia la animación si ya estaba visible
  aviso.classList.add("visible");
}

function ocultarAviso() {
  aviso.classList.remove("visible", "exito");
  aviso.textContent = "";
}

// Valida al salir del campo; si ya tenía error, revalida mientras escribe
Object.keys(campos).forEach(c => {
  const input = campos[c].input;
  input.addEventListener("blur", () => { if (input.value) validar(c); });
  input.addEventListener("input", () => {
    if (input.getAttribute("aria-invalid") === "true") validar(c);
    if (aviso.classList.contains("visible") && !aviso.classList.contains("exito")) ocultarAviso();
  });
});

// Envío
form.addEventListener("submit", async e => {
  e.preventDefault();
  ocultarAviso();

  const resultados = Object.keys(campos).map(validar);
  if (!resultados.every(Boolean)) {
    const primero = Object.keys(campos).find((c, k) => !resultados[k]);
    campos[primero].input.focus();
    return;
  }

  boton.classList.add("cargando");
  boton.setAttribute("aria-busy", "true");
  try {
    const resp = await fetch("auth.php", { method: "POST", body: new FormData(form) });
    const datos = await resp.json();

    if (resp.ok && datos.ok) {
      const nombre = inUsuario.value.trim();
      mostrarAviso(modo === "registro"
        ? `Cuenta creada. Bienvenido, ${nombre}.`
        : `Bienvenido, ${nombre}.`, "exito");
      setTimeout(() => { location.href = datos.redirigir || "../index.html"; }, 900);
      return; // deja el botón en carga hasta redirigir
    }

    if (datos.errores) {
      Object.keys(datos.errores).forEach(c => campos[c] && mostrarError(c, datos.errores[c]));
      const primero = Object.keys(datos.errores)[0];
      if (campos[primero]) campos[primero].input.focus();
    } else {
      mostrarAviso(datos.mensaje || "Ocurrió un error. Inténtalo de nuevo.");
      if (resp.status === 401) { inPassword.value = ""; inPassword.focus(); }
    }
  } catch {
    mostrarAviso("No se pudo conectar con el servidor. Inténtalo más tarde.");
  }
  boton.classList.remove("cargando");
  boton.removeAttribute("aria-busy");
});

// login.html#registro abre directo en "Crear cuenta"
if (location.hash === "#registro") {
  cuerpo.classList.add("sin-transicion"); // al cargar no se anima el cambio
  ponerModo("registro");
  requestAnimationFrame(() => requestAnimationFrame(() => cuerpo.classList.remove("sin-transicion")));
}
