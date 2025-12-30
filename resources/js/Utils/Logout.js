// Hacer accesible la función desde Blade
window.confirmLogout = function () {
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 no está cargado.');
        return;
    }

    Swal.fire({
        title: '¿Estás seguro que quieres salir?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#4b5563',
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('logout-form');
            if (form) form.submit();
        }
    });
};
