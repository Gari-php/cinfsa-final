// Cámara de las pantallas de escaneo (control de entradas y retiro de pedidos).
// Usa html5-qrcode (assets/js/vendor) y los elementos #lector, #camara-estado, #camara-mensaje y #btn-camara.
// alLeer(texto) se llama con cada QR leído (varias veces por segundo mientras esté a la vista).
function iniciarEscaner(alLeer) {
    const mensaje = document.getElementById('camara-mensaje');
    const boton = document.getElementById('btn-camara');

    if (!window.isSecureContext) {
        mensaje.textContent = 'La cámara solo funciona en una dirección segura (https). Usá la carga manual.';
        boton.hidden = true;
        return;
    }
    if (typeof Html5Qrcode === 'undefined') {
        mensaje.textContent = 'No se pudo cargar el lector de QR. Usá la carga manual.';
        return;
    }

    const lector = new Html5Qrcode('lector');
    lector.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: (w, h) => { const lado = Math.floor(Math.min(w, h) * 0.7); return { width: lado, height: lado }; } },
        (texto) => alLeer(texto),
        () => {}
    ).then(() => {
        document.getElementById('camara-estado').hidden = true;
    }).catch(() => {
        mensaje.textContent = 'No se pudo usar la cámara (¿falta el permiso?). Tocá para reintentar o usá la carga manual.';
    });
}
