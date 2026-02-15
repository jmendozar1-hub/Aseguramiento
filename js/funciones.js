// 1. Validar que las contraseñas coincidan 
function validarContrasenas() {
    const clave = document.getElementById('clave');
    const claveConfirm = document.getElementById('clave_confirm');
    const boton = document.querySelector('button[type="submit"]');

    if (clave && claveConfirm) {
        claveConfirm.addEventListener('input', function() {
            if (clave.value !== claveConfirm.value) {
                claveConfirm.setCustomValidity('Las contraseñas no coinciden');
                boton.disabled = true;
            } else {
                claveConfirm.setCustomValidity('');
                boton.disabled = false;
            }
        });
    }
}

// 2. Mostrar mensaje 
function confirmarAccion(mensaje) {
    return confirm(mensaje || "¿Estás seguro de realizar esta acción?");
}

// 3. Inicializar funciones 
document.addEventListener('DOMContentLoaded', function() {
        validarContrasenas();
});

// 4. Función para limpiar 
function limpiarMensajes() {
    const mensajes = document.querySelectorAll('.mensaje');
    if (mensajes.length > 0) {
        setTimeout(() => {
            mensajes.forEach(mensaje => {
                mensaje.style.opacity = '0';
                setTimeout(() => {
                    mensaje.remove();
                }, 500);
            });
        }, 5000);
    }
}

document.addEventListener('DOMContentLoaded', limpiarMensajes);