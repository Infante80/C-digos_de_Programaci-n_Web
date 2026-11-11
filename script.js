const modal=document.getElementById("cotizacion"),form=document.getElementById("formularioCotizacion"),ok=document.getElementById("ok");
function abrirModal(){modal.classList.add("activo");ok.style.display="none";document.getElementById("nombre").focus()}
function cerrarModal(){modal.classList.remove("activo")}
document.querySelectorAll("[data-abrir]").forEach(b=>b.addEventListener("click",abrirModal));
document.getElementById("cerrarCotizacion").addEventListener("click",cerrarModal);
modal.addEventListener("click",e=>{if(e.target===modal)cerrarModal()});
document.addEventListener("keydown",e=>{if(e.key==="Escape")cerrarModal()});

// Galería: filtros
const botones=document.querySelectorAll(".filtros button"),piezas=document.querySelectorAll(".pieza");
botones.forEach(b=>b.addEventListener("click",()=>{
  botones.forEach(x=>x.setAttribute("aria-pressed",x===b));
  piezas.forEach(p=>p.classList.toggle("oculta",b.dataset.f!=="todo"&&p.dataset.c!==b.dataset.f));
}));

// Validación
function mostrarError(id,msg){document.getElementById(id).textContent=msg;return !msg}
const reglas={
  nombre:v=>!v?"El nombre es obligatorio.":!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ'\s]{2,50}$/.test(v)?"El nombre solo debe contener letras y espacios.":"",
  email:v=>!v?"El correo es obligatorio.":!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(v)?"Ingresa un correo electrónico válido.":"",
  mensaje:v=>!v?"El mensaje es obligatorio.":(v.length<10||v.length>500)?"El mensaje debe tener entre 10 y 500 caracteres.":""
};
const ids={nombre:"errorNombre",email:"errorEmail",mensaje:"errorMensaje"};
function validar(c){return mostrarError(ids[c],reglas[c](document.getElementById(c).value.trim()))}
Object.keys(reglas).forEach(c=>document.getElementById(c).addEventListener("blur",()=>validar(c)));
form.addEventListener("submit",e=>{
  e.preventDefault();
  const valido=Object.keys(reglas).map(validar).every(Boolean);
  if(valido){form.reset();ok.style.display="block";setTimeout(cerrarModal,1800)}
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
