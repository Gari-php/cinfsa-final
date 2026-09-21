// alertas.js

export function mostrarAlerta(mensaje, tipo = 'error', contenedor = 'body', desaparece = true) {
    const alertaExistente = document.querySelector('.alerta');
    if(alertaExistente) alertaExistente.remove();

    const alerta = document.createElement('DIV');
    alerta.classList.add('alerta', tipo);
    alerta.textContent = mensaje;

    const referencia = document.querySelector(contenedor);
    referencia.appendChild(alerta);

    if(desaparece) {
        setTimeout(() => alerta.remove(), 3000);
    }
}
