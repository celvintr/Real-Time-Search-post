<?php
/*
 * Plugin Name: Real Time Search post
 * Description: Implementa un buscador en tiempo real para entradas de blog.
 * Version: 1.0
 * Author: Celvin Javier Turcios
 * License: GPL-2.0-or-later
 */

function rts_add_options_page() {
    add_menu_page(
        'Real Time Search Options', // Título de la página
        'RTSP Options', // Título del menú
        'manage_options', // Capacidad requerida para acceder a esta página
        'rts-options', // ID único de la página
        'rts_options_page_content' // Función que mostrará el contenido de la página
    );
}
add_action('admin_menu', 'rts_add_options_page');

function rts_options_page_content() {
    if (isset($_POST['rts_submit'])) {
        // Guardar las opciones de configuración
        update_option('rts_placeholder_text', sanitize_text_field($_POST['rts_placeholder_text']));
        // Convierte el array de categorías seleccionadas en una cadena separada por comas y guárdalo en la opción
        update_option('rts_included_categories', implode(',', $_POST['rts_selected_categories']));
        update_option('rts_vermas_text', sanitize_text_field($_POST['rts_vermas_text']));
    }

    // Obtener los valores de configuración guardados
    $placeholder_text = get_option('rts_placeholder_text', 'Buscar entradas...');
    $vermas_text = get_option('rts_vermas_text', 'Ver más resultados');
    $selected_categories = get_option('rts_included_categories', ''); // Recuperar las categorías como una cadena

    // Obtener todas las categorías de WordPress
    $categories = get_categories(array(
        'hide_empty' => false
    ));

    // Mostrar el formulario de opciones
    ?>
    <div class="wrap">
        <h2>Configuración de Búsqueda en Tiempo Real</h2>
        <form method="post">
            <label for="rts_placeholder_text">Texto del Placeholder:</label><br>
            <input type="text" name="rts_placeholder_text" id="rts_placeholder_text" value="<?php echo esc_attr($placeholder_text); ?>"><br><br>
            <label for="rts_vermas_text">Texto del enlace "Ver más resultados":</label><br>
            <input type="text" name="rts_vermas_text" id="rts_vermas_text" value="<?php echo esc_attr($vermas_text); ?>"><br><br>

            <label for="rts_selected_categories">Incluir Categorías al Buscador:</label><br>
            <select id="rts_selected_categories" name="rts_selected_categories[]" multiple="multiple" style="width: 100%;">
                <?php
                foreach ($categories as $category) {
                    $selected = in_array($category->term_id, explode(',', $selected_categories)) ? 'selected="selected"' : '';
                    echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                }
                ?>
            </select><br><br>

            <input type="submit" name="rts_submit" class="button-primary" value="Guardar Cambios">
        </form>
<br>
<p> Para mostrar el buscador en cualquier parte de tu sitio, usa el shortcode <strong>[real_time_search]</strong>. </p>
        Shortcode: 
        <td class="shortcode column-shortcode" data-colname="Shortcode"><input type="text" class="nta-shortcode-table" name="country" value="[real_time_search]" readonly=""></td>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#rts_selected_categories').select2({
                width: '30%', // Ajusta el ancho del campo de selección al 100% del contenedor
            });
        });
    </script>
    <?php
}




// Enqueue Styles
function rts_enqueue_styles() {
    wp_enqueue_style('rts-styles', plugin_dir_url(__FILE__) . 'styles.css');
}
add_action('wp_enqueue_scripts', 'rts_enqueue_styles');

// Enqueue Scripts
function rts_enqueue_scripts() {
    wp_enqueue_script('rts-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
    wp_localize_script('rts-script', 'rts_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
    

}
add_action('wp_enqueue_scripts', 'rts_enqueue_scripts');


// Función de Búsqueda en Tiempo Real
function rts_real_time_search() {
    $searchTerm = sanitize_text_field($_POST['searchTerm']);
    $vermas_text = get_option('rts_vermas_text', 'Ver más resultados');

    // Obtener los IDs de las categorías incluidas de las opciones de configuración
    $included_categories = get_option('rts_included_categories', '');

    // Convertir los IDs de las categorías a un array para la consulta
    $included_categories_array = !empty($included_categories) ? explode(',', $included_categories) : array();

    $args = array(
        's' => $searchTerm,
        'post_type' => 'post',
    );

    // Añadir los parámetros de categoría solo si hay categorías configuradas
    if (!empty($included_categories_array)) {
        $args['category__in'] = $included_categories_array;
    }

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="search-result">';
            // Obtener la URL de la imagen destacada o usar una imagen por defecto si no hay imagen
            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
            if (empty($thumbnail_url)) {
                // Usar una imagen por defecto si no hay imagen destacada
                $thumbnail_url = plugin_dir_url(__FILE__) . 'no-image.jpg'; 
            }
            echo '<img src="' . esc_url($thumbnail_url) . '" alt="Thumbnail">';
            echo '<div class="result-info">';
            echo '<a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>';
            echo '<p class="small-text">Publicado el ' . esc_html(get_the_date()) .'</p>';
            echo '</div>'; 
            echo '</div>'; 
        }
    
        // Agregar el enlace "Ver todos los resultados"
        echo '<li class="live-search_lnk live-search_more live-search_selected"><a href="' . esc_url(home_url('/')) . '?s=' . esc_attr($searchTerm) . '">' . esc_html($vermas_text) . '</a></li>';
    } else {
        echo '<p class="small-text"> No se encontraron resultados.</p>';
    }
    

    wp_reset_postdata();

    $output = ob_get_clean();

    echo json_encode(array(
        'html' => $output
    ));

    die();  
}





add_action('wp_ajax_rts_real_time_search', 'rts_real_time_search');
add_action('wp_ajax_nopriv_rts_real_time_search', 'rts_real_time_search'); // Para usuarios no autenticados



// Función para Mostrar el Formulario de Búsqueda
function rts_search_form() {
    $placeholder_text = get_option('rts_placeholder_text', 'Buscar entradas...');

    ob_start(); ?>
    <div class="rts-search">
    <input type="text" id="search-input" placeholder="<?php echo esc_attr($placeholder_text); ?>">
        <div id="search-results"></div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('real_time_search', 'rts_search_form');

?>
