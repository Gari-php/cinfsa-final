// fetchHelper.js

export async function enviarFetch(url, datos, metodo = 'POST') {
    try {
        const respuesta = await fetch(url, {
            method: metodo,
            body: datos
        });

        const resultado = await respuesta.json();
        return resultado;

    } catch (error) {
        console.error('Error en la solicitud Fetch:', error);
        return { error: true, mensaje: 'Error en la conexión con el servidor' };
    }
}
