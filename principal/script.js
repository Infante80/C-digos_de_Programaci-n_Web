const modal=document.getElementById("cotizacion"),form=document.getElementById("formularioCotizacion"),ok=document.getElementById("ok");
const caja=modal.querySelector(".caja");
function abrirModal(){modal.classList.add("activo");caja.classList.remove("enviado");caja.style.minHeight="";ok.style.display="none";document.getElementById("nombre").focus()}
function cerrarModal(){modal.classList.remove("activo")}
document.querySelectorAll("[data-abrir]").forEach(b=>b.addEventListener("click",abrirModal));
document.getElementById("cerrarCotizacion").addEventListener("click",cerrarModal);
modal.addEventListener("click",e=>{if(e.target===modal)cerrarModal()});
document.addEventListener("keydown",e=>{if(e.key==="Escape")cerrarModal()});

// Galería: filtros
const botones=document.querySelectorAll(".filtros button"),piezas=document.querySelectorAll(".pieza");
const EASE_OUT = "cubic-bezier(0.23, 1, 0.32, 1)";
const reducirMov = matchMedia("(prefers-reduced-motion: reduce)");

botones.forEach(b=>b.addEventListener("click",()=>{
  botones.forEach(x=>x.setAttribute("aria-pressed",x===b));
  piezas.forEach(p=>p.classList.toggle("oculta",b.dataset.f!=="todo"&&p.dataset.c!==b.dataset.f));
  // Las piezas visibles entran escalonadas para que el cambio no sea un salto brusco
  let k = 0;
  piezas.forEach(p => {
    p.getAnimations().forEach(a => a.cancel());
    if (p.classList.contains("oculta")) return;
    p.animate(
      reducirMov.matches
        ? { opacity: [0, 1] }
        : [{ opacity: 0, transform: "scale(.96)" }, { opacity: 1, transform: "none" }],
      { duration: 240, easing: EASE_OUT, delay: Math.min(k++, 8) * 40, fill: "backwards" }
    );
  });
}));

// Galería: visor (lightbox)
const visor = document.getElementById("visor"),
  vImg = document.getElementById("visorImg"),
  vEscena = document.getElementById("visorEscena"),
  vFondo = visor.querySelector(".visor-fondo"),
  vPie = document.getElementById("visorPie"),
  vCat = document.getElementById("visorCategoria"),
  vCont = document.getElementById("visorContador"),
  vMinis = document.getElementById("visorMiniaturas");
const nombresCat = { escaleras: "Escaleras", rejas: "Rejas", estructuras: "Estructuras", barandales: "Barandales" };
let lista = [], actual = 0, animando = false, turno = 0;

// Tamaño final de la foto: cabe completa en la escena sin pasar de su tamaño real
function ajustar() {
  if (!vImg.naturalWidth) return;
  const cs = getComputedStyle(vEscena);
  const aw = vEscena.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
  const ah = vEscena.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
  const s = Math.min(aw / vImg.naturalWidth, ah / vImg.naturalHeight, 1);
  vImg.style.width = Math.round(vImg.naturalWidth * s) + "px";
  vImg.style.height = Math.round(vImg.naturalHeight * s) + "px";
}

// Rectángulo de la miniatura si está a la vista; si no, null
function rectMiniatura(fig) {
  if (!fig || fig.classList.contains("oculta")) return null;
  const r = fig.getBoundingClientRect();
  return r.bottom > 0 && r.top < innerHeight ? r : null;
}

// Fotograma que coloca la foto grande encima de la miniatura, recortada igual (object-fit: cover)
function sobreMiniatura(r) {
  const f = vImg.getBoundingClientRect();
  const s = Math.max(r.width / f.width, r.height / f.height);
  const dx = r.left + r.width / 2 - (f.left + f.width / 2);
  const dy = r.top + r.height / 2 - (f.top + f.height / 2);
  const ix = (f.width - r.width / s) / 2, iy = (f.height - r.height / s) / 2;
  return { transform: `translate(${dx}px, ${dy}px) scale(${s})`, clipPath: `inset(${iy}px ${ix}px)` };
}

function construirMiniaturas() {
  vMinis.replaceChildren(...lista.map((fig, k) => {
    const b = document.createElement("button");
    const src = fig.querySelector("img");
    b.type = "button";
    b.setAttribute("aria-label", `Ver imagen ${k + 1}: ${fig.querySelector("figcaption").textContent}`);
    b.innerHTML = `<img src="${src.getAttribute("src")}" alt="" loading="lazy" decoding="async">`;
    b.addEventListener("click", () => ir(k - actual));
    return b;
  }));
}

// Cambia la foto mostrada y espera a que esté decodificada
async function pintar(i) {
  const t = ++turno;
  actual = (i + lista.length) % lista.length;
  const fig = lista[actual], img = fig.querySelector("img");
  vImg.style.visibility = "hidden";
  vImg.src = img.currentSrc || img.src;
  vImg.alt = img.alt;
  vPie.textContent = fig.querySelector("figcaption").textContent;
  vCat.textContent = nombresCat[fig.dataset.c] || "";
  vCont.textContent = `${actual + 1} / ${lista.length}`;
  [...vMinis.children].forEach((b, k) => b.setAttribute("aria-current", k === actual));
  const activa = vMinis.children[actual];
  if (activa) vMinis.scrollTo({
    left: activa.offsetLeft - (vMinis.clientWidth - activa.offsetWidth) / 2,
    behavior: reducirMov.matches ? "auto" : "smooth"
  });
  // Precarga las vecinas para que el cambio sea inmediato
  [1, -1].forEach(d => { new Image().src = lista[(actual + d + lista.length) % lista.length].querySelector("img").src; });
  await vImg.decode().catch(() => {});
  if (t !== turno) return false;   // llegó otro cambio mientras cargaba
  ajustar();
  vImg.style.visibility = "";
  return true;
}

async function abrirVisor(fig) {
  if (animando || visor.open) return;
  lista = [...piezas].filter(p => !p.classList.contains("oculta"));
  construirMiniaturas();
  document.documentElement.classList.add("visor-abierto");
  visor.showModal();
  animando = true;
  if (!(await pintar(lista.indexOf(fig)))) { animando = false; return; }
  void visor.offsetWidth;          // fija el estado inicial para que el fondo haga la transición
  visor.classList.add("activo");
  const r = reducirMov.matches ? null : rectMiniatura(fig);
  let a;
  if (r) {
    // La miniatura "sale" de la cuadrícula y crece hasta su lugar
    fig.classList.add("en-visor");
    a = vImg.animate([sobreMiniatura(r), { transform: "none", clipPath: "inset(0px 0px)" }],
      { duration: 420, easing: EASE_OUT });
  } else {
    a = vImg.animate(reducirMov.matches ? { opacity: [0, 1] }
      : [{ opacity: 0, transform: "scale(.96)" }, { opacity: 1, transform: "none" }],
      { duration: 250, easing: EASE_OUT });
  }
  await a.finished.catch(() => {});
  fig.classList.remove("en-visor");
  animando = false;
}

async function cerrarVisor() {
  if (!visor.open || animando) return;
  animando = true;
  const fig = lista[actual];
  const desde = vImg.style.transform || "none";   // puede venir de un gesto de arrastre
  vImg.getAnimations().forEach(a => a.cancel());
  vImg.style.transform = "";
  vFondo.style.opacity = "";
  visor.classList.remove("activo");
  const r = reducirMov.matches ? null : rectMiniatura(fig);
  let a;
  if (r) {
    // Regresa a su lugar en la cuadrícula
    fig.classList.add("en-visor");
    a = vImg.animate([{ transform: desde, clipPath: "inset(0px 0px)" }, sobreMiniatura(r)],
      { duration: 340, easing: EASE_OUT, fill: "forwards" });
  } else {
    a = vImg.animate([{ opacity: 1, transform: desde },
      { opacity: 0, transform: reducirMov.matches ? desde : "scale(.96)" }],
      { duration: 220, easing: EASE_OUT, fill: "forwards" });
  }
  await a.finished.catch(() => {});
  visor.close();
  a.cancel();
  fig.focus({ preventScroll: true });
}

// Limpieza al cerrarse (también si el navegador cierra el diálogo por su cuenta)
visor.addEventListener("close", () => {
  vImg.getAnimations().forEach(a => a.cancel());
  vImg.style.transform = "";
  vFondo.style.opacity = "";
  visor.classList.remove("activo");
  piezas.forEach(p => p.classList.remove("en-visor"));
  document.documentElement.classList.remove("visor-abierto");
  arrastre = null;
  animando = false;
});

// delta: +1 siguiente, -1 anterior. Con teclado el cambio es instantáneo.
async function ir(delta, sinAnimar = false) {
  if (animando || lista.length < 2 || delta === 0) return;
  vImg.getAnimations().forEach(a => a.cancel());
  vImg.style.transform = "";
  if (!(await pintar(actual + delta)) || sinAnimar) return;
  const dir = Math.sign(delta);
  vImg.animate(reducirMov.matches ? { opacity: [0, 1] }
    : [{ opacity: 0, transform: `translateX(${dir * 6}%)` }, { opacity: 1, transform: "none" }],
    { duration: 260, easing: EASE_OUT });
}

// Activar las piezas con clic, Enter o Espacio
piezas.forEach(fig => {
  fig.tabIndex = 0;
  fig.setAttribute("role", "button");
  fig.setAttribute("aria-label", `Ampliar imagen: ${fig.querySelector("figcaption").textContent}`);
  fig.addEventListener("click", () => abrirVisor(fig));
  fig.addEventListener("keydown", e => {
    if (e.key === "Enter" || e.key === " ") { e.preventDefault(); abrirVisor(fig); }
  });
});

document.getElementById("visorCerrar").addEventListener("click", cerrarVisor);
document.getElementById("visorPrev").addEventListener("click", () => ir(-1));
document.getElementById("visorNext").addEventListener("click", () => ir(1));
visor.addEventListener("cancel", e => { e.preventDefault(); cerrarVisor(); });   // tecla Escape
visor.addEventListener("keydown", e => {
  if (e.key === "ArrowRight") { e.preventDefault(); ir(1, true); }
  if (e.key === "ArrowLeft") { e.preventDefault(); ir(-1, true); }
});
addEventListener("resize", () => { if (visor.open) ajustar(); });

// Gestos táctiles: deslizar a los lados para cambiar, hacia abajo para cerrar
let arrastre = null, huboArrastre = false;
vEscena.addEventListener("click", e => {
  if (huboArrastre) { huboArrastre = false; return; }
  if (e.target === vEscena) cerrarVisor();   // clic en el fondo, fuera de la foto
});
vEscena.addEventListener("pointerdown", e => {
  if (e.pointerType === "mouse" || animando || e.target.closest("button")) return;
  vImg.getAnimations().forEach(a => a.cancel());
  arrastre = { id: e.pointerId, x: e.clientX, y: e.clientY, t: e.timeStamp, eje: null, dx: 0, dy: 0 };
  huboArrastre = false;
  vEscena.setPointerCapture(e.pointerId);
});
vEscena.addEventListener("pointermove", e => {
  if (!arrastre || e.pointerId !== arrastre.id) return;
  const dx = e.clientX - arrastre.x, dy = e.clientY - arrastre.y;
  if (!arrastre.eje) {
    if (Math.hypot(dx, dy) < 8) return;
    arrastre.eje = Math.abs(dx) > Math.abs(dy) ? "x" : "y";
    huboArrastre = true;
  }
  if (arrastre.eje === "x") {
    // En los extremos de la lista el arrastre ofrece resistencia
    arrastre.dx = lista.length < 2 ? dx * .2 : dx;
    vImg.style.transform = `translate(${arrastre.dx}px, 0px) scale(1)`;
  } else {
    arrastre.dy = dy > 0 ? dy : dy * .2;
    const k = 1 - Math.min(Math.abs(arrastre.dy) / 1500, .15);
    vImg.style.transform = `translate(0px, ${arrastre.dy}px) scale(${k})`;
    vFondo.style.opacity = 1 - Math.min(Math.abs(arrastre.dy) / 400, .6);
  }
});
function soltar(e) {
  if (!arrastre || e.pointerId !== arrastre.id) return;
  const { eje, dx, dy, t } = arrastre;
  const dt = Math.max(e.timeStamp - t, 1);
  arrastre = null;
  if (!eje) return;
  const desde = vImg.style.transform;
  if (eje === "x" && (Math.abs(dx) > 70 || (Math.abs(dx) > 20 && Math.abs(dx) / dt > .5)) && lista.length > 1) {
    ir(dx < 0 ? 1 : -1);
    return;
  }
  if (eje === "y" && (dy > 110 || (dy > 30 && dy / dt > .5))) {
    cerrarVisor();
    return;
  }
  // No llegó al umbral: vuelve a su sitio
  vImg.style.transform = "";
  vFondo.style.opacity = "";
  vImg.animate([{ transform: desde }, { transform: "translate(0px, 0px) scale(1)" }],
    { duration: 260, easing: EASE_OUT });
}
vEscena.addEventListener("pointerup", soltar);
vEscena.addEventListener("pointercancel", soltar);

// Validación
function mostrarError(id,msg){document.getElementById(id).textContent=msg;return !msg}
const reglas={
  nombre:v=>!v?"El nombre es obligatorio.":!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ'\s]{2,50}$/.test(v)?"El nombre solo debe contener letras y espacios.":"",
  email:v=>!v?"El correo es obligatorio.":!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(v)?"Ingresa un correo electrónico válido.":"",
  telefono:v=>!v?"El teléfono es obligatorio.":!/^[0-9\s()-]+$/.test(v)||v.replace(/\D/g,"").length!==10?"Ingresa un teléfono de 10 dígitos.":"",
  mensaje:v=>!v?"El mensaje es obligatorio.":(v.length<10||v.length>500)?"El mensaje debe tener entre 10 y 500 caracteres.":""
};
const ids={nombre:"errorNombre",email:"errorEmail",telefono:"errorTelefono",mensaje:"errorMensaje"};
function validar(c){return mostrarError(ids[c],reglas[c](document.getElementById(c).value.trim()))}
Object.keys(reglas).forEach(c=>document.getElementById(c).addEventListener("blur",()=>validar(c)));

// Envío del formulario al servidor
form.addEventListener("submit", async e => {
  e.preventDefault();
  const valido = Object.keys(reglas).map(validar).every(Boolean);
  if (!valido) return;

  const boton = form.querySelector('button[type="submit"]');
  boton.disabled = true;
  try {
    const resp = await fetch("principal/guardar.php", { method: "POST", body: new FormData(form) });
    const datos = await resp.json();
    if (resp.ok && datos.ok) {
      form.reset();
      ok.style.display = "none";
      caja.style.minHeight = caja.offsetHeight + "px";
      caja.scrollTop = 0;
      caja.classList.add("enviado");
      setTimeout(cerrarModal, 3000);
    } else if (datos.errores) {
      Object.keys(datos.errores).forEach(c => mostrarError(ids[c], datos.errores[c]));
    } else {
      throw new Error("Error del servidor");
    }
  } catch {
    ok.textContent = "No se pudo enviar la solicitud. Inténtalo de nuevo más tarde.";
    ok.style.display = "block";
  } finally {
    boton.disabled = false;
  }
});

// Menú hamburguesa
const menuToggle = document.getElementById("menuToggle");
const menuLinks = document.getElementById("menuLinks");

function cerrarMenu() {
  menuLinks.classList.remove("abierto");
  menuToggle.setAttribute("aria-expanded", "false");
  menuToggle.setAttribute("aria-label", "Abrir menú");
}

menuToggle.addEventListener("click", () => {
  const abierto = menuLinks.classList.toggle("abierto");
  menuToggle.setAttribute("aria-expanded", abierto);
  menuToggle.setAttribute("aria-label", abierto ? "Cerrar menú" : "Abrir menú");
});

// Al tocar un link o el botón de cotización, se cierra el menú
menuLinks.querySelectorAll("a, button").forEach(el => el.addEventListener("click", cerrarMenu));

// Escape también lo cierra
document.addEventListener("keydown", e => { if (e.key === "Escape") cerrarMenu(); });

// Usuario con sesión iniciada: se muestra en la esquina
(async () => {
  try {
    const resp = await fetch("login/sesion.php", { cache: "no-store" });
    const datos = await resp.json();
    if (!resp.ok || !datos.logueado) return;

    const admin = datos.rol === "admin";
    const nombre = admin ? "David" : datos.usuario;
    document.getElementById("sesionInicial").textContent = nombre.charAt(0).toUpperCase();
    document.getElementById("sesionTexto").textContent = admin ? "¡Hola David!" : `Hola, ${nombre}`;
    document.getElementById("sesionPanel").hidden = !admin;
    document.getElementById("sesion").hidden = false;
    document.querySelector(".link-acceso").hidden = true;
  } catch {
    // Sin respuesta del servidor la página se queda como visitante
  }
})();

// Cerrar sesión
const botonSalir = document.getElementById("sesionSalir");
botonSalir.addEventListener("click", async () => {
  botonSalir.disabled = true;
  try {
    const resp = await fetch("login/sesion.php", { method: "POST" });
    if (!resp.ok) throw new Error("Error del servidor");
    location.reload();
  } catch {
    botonSalir.disabled = false;
    botonSalir.textContent = "Reintentar";
  }
});
