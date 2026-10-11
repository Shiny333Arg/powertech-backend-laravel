// Un solo listener para todos los campos de la pagina.
document.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-mostrar-password]');
    if (!boton) return;

    const campo = document.getElementById(boton.dataset.mostrarPassword);
    const mostrar = campo.type === 'password';

    campo.type = mostrar ? 'text' : 'password';
    boton.setAttribute('aria-pressed', String(mostrar));
    boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    boton.classList.toggle('active', mostrar);
});