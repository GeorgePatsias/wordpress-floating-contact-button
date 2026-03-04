<?php
/**
 * Plugin Name: Floating Contact Button
 * Description: A customizable floating contact button with multiple links (Facebook, WhatsApp, LinkedIn, etc.) using Custom Post Types.
 * Version: 2.0.1
 * Author: George Patsias
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('FCB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FCB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load plugin text domain for translations
add_action('plugins_loaded', 'fcb_load_textdomain');
function fcb_load_textdomain()
{
    load_plugin_textdomain('fcb', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Include metaboxes logic
if (is_admin()) {
    require_once FCB_PLUGIN_DIR . 'admin/meta-boxes.php';
}

// Register Custom Post Type
add_action('init', 'fcb_register_post_type');
function fcb_register_post_type()
{
    $labels = array(
        'name' => _x('Floating Buttons', 'Post Type General Name', 'fcb'),
        'singular_name' => _x('Floating Button', 'Post Type Singular Name', 'fcb'),
        'menu_name' => __('Floating Buttons', 'fcb'),
        'name_admin_bar' => __('Floating Button', 'fcb'),
        'archives' => __('Button Archives', 'fcb'),
        'parent_item_colon' => __('Parent Button:', 'fcb'),
        'all_items' => __('All Buttons', 'fcb'),
        'add_new_item' => __('Add New Button', 'fcb'),
        'add_new' => __('Add New', 'fcb'),
        'new_item' => __('New Button', 'fcb'),
        'edit_item' => __('Edit Button', 'fcb'),
        'update_item' => __('Update Button', 'fcb'),
        'view_item' => __('View Button', 'fcb'),
        'search_items' => __('Search Button', 'fcb'),
        'not_found' => __('Not found', 'fcb'),
        'not_found_in_trash' => __('Not found in Trash', 'fcb'),
    );
    $args = array(
        'label' => __('Floating Button', 'fcb'),
        'description' => __('Manage your floating buttons', 'fcb'),
        'labels' => $labels,
        'supports' => array('title'),
        'taxonomies' => array(),
        'hierarchical' => false,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 80,
        'menu_icon' => 'dashicons-admin-links',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => false,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'capability_type' => 'post',
    );
    register_post_type('fcb_button', $args);
}

// Make fcb_button translatable by Polylang
add_filter('pll_get_post_types', 'fcb_add_polylang_post_type', 10, 2);
function fcb_add_polylang_post_type($post_types, $is_settings)
{
    $post_types['fcb_button'] = 'fcb_button';
    return $post_types;
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'fcb_enqueue_assets');
function fcb_enqueue_assets()
{
    wp_enqueue_style('fcb-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
    wp_enqueue_style('fcb-style', FCB_PLUGIN_URL . 'assets/css/style.css', array(), '2.0.0');
    wp_enqueue_script('fcb-script', FCB_PLUGIN_URL . 'assets/js/script.js', array('jquery'), '2.0.0', true);
}

// Output front-end HTML for all published buttons
add_action('wp_footer', 'fcb_render_buttons');
function fcb_render_buttons()
{
    $args = array(
        'post_type' => 'fcb_button',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'suppress_filters' => false, // Allow Polylang to filter by current language
    );
    $buttons = new WP_Query($args);

    if ($buttons->have_posts()) {
        while ($buttons->have_posts()) {
            $buttons->the_post();

            $post_id = get_the_ID();
            $settings = get_post_meta($post_id, '_fcb_settings', true);

            if (!is_array($settings)) {
                $settings = array();
            }

            // Defaults
            $main_icon = isset($settings['main_icon']) && !empty($settings['main_icon']) ? $settings['main_icon'] : 'fas fa-comment-dots';
            $main_color = isset($settings['main_color']) && !empty($settings['main_color']) ? $settings['main_color'] : '#007bff';
            $position = isset($settings['position']) ? $settings['position'] : 'bottom-right';

            $size = isset($settings['size']) ? $settings['size'] : 'md';
            $custom_size_main = isset($settings['custom_size_main']) ? intval($settings['custom_size_main']) : 65;
            $custom_size_links = isset($settings['custom_size_links']) ? intval($settings['custom_size_links']) : 50;

            $offset_bottom = isset($settings['offset_bottom']) && $settings['offset_bottom'] !== '' ? intval($settings['offset_bottom']) : 30;
            $offset_side = isset($settings['offset_side']) && $settings['offset_side'] !== '' ? intval($settings['offset_side']) : 30;
            $offset_top = isset($settings['offset_top']) && $settings['offset_top'] !== '' ? intval($settings['offset_top']) : '';
            $offset_left = isset($settings['offset_left']) && $settings['offset_left'] !== '' ? intval($settings['offset_left']) : '';
            $custom_css_class = isset($settings['custom_css_class']) ? trim($settings['custom_css_class']) : '';

            $hide_desktop = isset($settings['hide_desktop']) ? $settings['hide_desktop'] : 'no';
            $hide_tablet = isset($settings['hide_tablet']) ? $settings['hide_tablet'] : 'no';
            $hide_mobile = isset($settings['hide_mobile']) ? $settings['hide_mobile'] : 'no';
            $links = isset($settings['links']) ? $settings['links'] : array();

            // Build classes array
            $container_classes = array('fcb-container', 'fcb-position-' . esc_attr($position));
            if ($size !== 'custom') {
                $container_classes[] = 'fcb-size-' . esc_attr($size);
            } else {
                $container_classes[] = 'fcb-size-custom';
            }

            if ($hide_desktop === 'yes')
                $container_classes[] = 'fcb-hide-desktop';
            if ($hide_tablet === 'yes')
                $container_classes[] = 'fcb-hide-tablet';
            if ($hide_mobile === 'yes')
                $container_classes[] = 'fcb-hide-mobile';
            if ($custom_css_class !== '')
                $container_classes[] = esc_attr($custom_css_class);

            $container_class_str = implode(' ', $container_classes);

            // Build container inline styles for offsets
            $container_style = '';

            if ($offset_top !== '') {
                $container_style .= 'top: ' . esc_attr($offset_top) . 'px; bottom: auto; ';
            } else {
                $container_style .= 'bottom: ' . esc_attr($offset_bottom) . 'px; top: auto; ';
            }

            if ($offset_left !== '') {
                $container_style .= 'left: ' . esc_attr($offset_left) . 'px; right: auto; ';
            } else {
                if ($position === 'bottom-right') {
                    $container_style .= 'right: ' . esc_attr($offset_side) . 'px; left: auto;';
                } else {
                    $container_style .= 'left: ' . esc_attr($offset_side) . 'px; right: auto;';
                }
            }

            // Build item inline styles for custom sizes
            $main_btn_style = 'background-color: ' . esc_attr($main_color) . ';';
            $link_item_style_base = '';

            if ($size === 'custom') {
                $main_btn_style .= ' width: ' . esc_attr($custom_size_main) . 'px; height: ' . esc_attr($custom_size_main) . 'px; font-size: ' . esc_attr($custom_size_main * 0.45) . 'px;';
                $link_item_style_base = 'width: ' . esc_attr($custom_size_links) . 'px; height: ' . esc_attr($custom_size_links) . 'px; font-size: ' . esc_attr($custom_size_links * 0.45) . 'px;';
            }

            // It's possible to have multiple buttons in same position overlapping, user manages it.
            ?>
            <div id="fcb-button-<?php echo esc_attr($post_id); ?>" class="<?php echo esc_attr($container_class_str); ?>"
                style="<?php echo esc_attr($container_style); ?>">
                <div class="fcb-links-container">
                    <?php
                    if (!empty($links) && is_array($links)) {
                        foreach ($links as $link) {
                            if (!empty($link['url'])) {
                                $icon = !empty($link['icon']) ? trim($link['icon']) : 'fas fa-link';
                                $color = !empty($link['color']) ? $link['color'] : '#333333';
                                $text = !empty($link['text']) ? $link['text'] : '';

                                $link_style = $link_item_style_base . ' background-color: ' . esc_attr($color) . ';';
                                ?>
                                <a href="<?php echo esc_url($link['url']); ?>" target="_blank" class="fcb-link-item"
                                    style="<?php echo esc_attr($link_style); ?>" title="<?php echo esc_attr($text); ?>">
                                    <i class="<?php echo esc_attr($icon); ?>"></i>
                                    <?php if ($text): ?>
                                        <span class="fcb-tooltip"><?php echo esc_html($text); ?></span>
                                    <?php endif; ?>
                                </a>
                                <?php
                            }
                        }
                    }
                    ?>
                </div>
                <button class="fcb-main-button" style="<?php echo esc_attr($main_btn_style); ?>" aria-label="Toggle contact links">
                    <i class="<?php echo esc_attr($main_icon); ?>"></i>
                    <i class="fas fa-times fcb-close-icon" style="display:none;"></i>
                </button>
            </div>
            <?php
        }
        wp_reset_postdata();
    }
}

// Function to provide default platforms list - moved here for accessibility to meta boxes config if needed
function fcb_get_default_platforms()
{
    return array(
        array('name' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'color' => '#25d366'),
        array('name' => 'Facebook', 'icon' => 'fab fa-facebook-f', 'color' => '#1877f2'),
        array('name' => 'Instagram', 'icon' => 'fab fa-instagram', 'color' => '#e1306c'),
        array('name' => 'LinkedIn', 'icon' => 'fab fa-linkedin-in', 'color' => '#0077b5'),
        array('name' => 'Twitter', 'icon' => 'fab fa-twitter', 'color' => '#1da1f2'),
        array('name' => 'Viber', 'icon' => 'fab fa-viber', 'color' => '#7360f2'),
        array('name' => 'Email', 'icon' => 'fas fa-envelope', 'color' => '#ea4335'),
        array('name' => 'Phone', 'icon' => 'fas fa-phone', 'color' => '#34a853'),
        array('name' => 'Maps', 'icon' => 'fas fa-map-marker-alt', 'color' => '#4285F4'),
    );
}
