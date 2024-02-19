jQuery(document).ready(function($) {
    var searchInput = $('#search-input');
    var searchResults = $('#search-results');
    var timer; // Variable para el temporizador de búsqueda

    // Función para realizar la búsqueda en tiempo real
    function performSearch() {
        var searchTerm = searchInput.val().trim(); // Eliminar espacios al principio y al final
        if (searchTerm === '') {
            searchResults.empty(); // Si el campo de búsqueda está vacío, vaciar los resultados
            return;
        }
        $.ajax({
            type: 'POST',
            url: rts_vars.ajaxurl,
            data: {
                action: 'rts_real_time_search',
                searchTerm: searchTerm
            },
            success: function(response) {
                try {
                    var data = JSON.parse(response); // Intentar analizar la respuesta JSON
                    if (data && data.html) {
                        searchResults.html(data.html); // Actualizar los resultados de búsqueda con el HTML recibido
                    } else {
                        searchResults.html('No se encontraron resultados.'); // Manejar una respuesta JSON inválida
                    }
                } catch (error) {
                    console.error('Error al analizar la respuesta JSON:', error); // Manejar errores de análisis JSON
                    searchResults.html('Error al cargar los resultados.'); // Mostrar un mensaje de error al usuario
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud AJAX:', error); // Manejar errores de la solicitud AJAX
                searchResults.html('Error al cargar los resultados.'); // Mostrar un mensaje de error al usuario
            }
        });
    }
    

    // Evento cuando se escribe en el campo de búsqueda
    searchInput.on('input', function() {
        // Limpiar el temporizador si existe
        clearTimeout(timer);
        // Configurar un nuevo temporizador para evitar llamadas AJAX frecuentes mientras el usuario sigue escribiendo
        timer = setTimeout(performSearch, 500); // Espera 500 milisegundos (0.5 segundos) después de la última pulsación de tecla
    });

    // Ocultar los resultados cuando se hace clic fuera del input y los resultados
    $(document).on('click', function(event) {
        if (!searchInput.is(event.target) && !searchResults.is(event.target) && searchResults.has(event.target).length === 0) {
            searchResults.empty(); // Vaciar los resultados
        }
    });

    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    // Uso de la función de debouncing
    searchInput.on('input', debounce(performSearch, 500));
     
});

