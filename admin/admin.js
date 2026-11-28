// Cerrar sesión desde el panel
const salir = document.getElementById("salir");
salir.addEventListener("click", async () => {
  salir.disabled = true;
  try {
    const resp = await fetch("../login/sesion.php", { method: "POST" });
    if (!resp.ok) throw new Error("Error del servidor");
    location.replace("../index.html");
  } catch {
    salir.disabled = false;
    salir.textContent = "Reintentar";
  }
});
