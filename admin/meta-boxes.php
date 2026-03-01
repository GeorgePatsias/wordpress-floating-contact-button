<?php
if (!defined('ABSPATH')) {
    exit;
}

// Add metaboxes
add_action('add_meta_boxes', 'fcb_add_meta_boxes');
function fcb_add_meta_boxes()
{
    add_meta_box(
        'fcb_settings_meta_box',
        __('Button Settings & Links', 'fcb'),
        'fcb_settings_meta_box_cb',
        'fcb_button',
        'normal',
        'high'
    );
}

// Save metaboxes
add_action('save_post', 'fcb_save_meta_box_data');
function fcb_save_meta_box_data($post_id)
{
    if (!isset($_POST['fcb_meta_box_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['fcb_meta_box_nonce'], 'fcb_save_meta_box_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $settings = array();

    // Main button settings
    $settings['main_icon'] = isset($_POST['fcb_main_icon']) ? sanitize_text_field($_POST['fcb_main_icon']) : 'fas fa-comment-dots';
    $settings['main_color'] = isset($_POST['fcb_main_color']) ? sanitize_hex_color($_POST['fcb_main_color']) : '#007bff';
    $settings['position'] = isset($_POST['fcb_position']) ? sanitize_text_field($_POST['fcb_position']) : 'bottom-right';

    // Sizing and positioning
    $settings['size'] = isset($_POST['fcb_size']) ? sanitize_text_field($_POST['fcb_size']) : 'md';
    $settings['custom_size_main'] = isset($_POST['fcb_custom_size_main']) ? intval($_POST['fcb_custom_size_main']) : 65;
    $settings['custom_size_links'] = isset($_POST['fcb_custom_size_links']) ? intval($_POST['fcb_custom_size_links']) : 50;

    $settings['offset_bottom'] = isset($_POST['fcb_offset_bottom']) ? $_POST['fcb_offset_bottom'] : '';
    $settings['offset_side'] = isset($_POST['fcb_offset_side']) ? $_POST['fcb_offset_side'] : '';
    $settings['offset_top'] = isset($_POST['fcb_offset_top']) ? $_POST['fcb_offset_top'] : '';
    $settings['offset_left'] = isset($_POST['fcb_offset_left']) ? $_POST['fcb_offset_left'] : '';
    $settings['custom_css_class'] = isset($_POST['fcb_custom_css_class']) ? sanitize_html_class($_POST['fcb_custom_css_class']) : '';

    // Visibility
    $settings['hide_desktop'] = isset($_POST['fcb_hide_desktop']) ? 'yes' : 'no';
    $settings['hide_tablet'] = isset($_POST['fcb_hide_tablet']) ? 'yes' : 'no';
    $settings['hide_mobile'] = isset($_POST['fcb_hide_mobile']) ? 'yes' : 'no';

    // Links (Order will be preserved via index logic because jQuery sortable submits input array in DOM order)
    $links = array();
    if (isset($_POST['fcb_links']) && is_array($_POST['fcb_links'])) {
        // Renumber array implicitly by pushing
        foreach ($_POST['fcb_links'] as $link) {
            if (!empty($link['url'])) {
                $links[] = array(
                    'url' => esc_url_raw($link['url']),
                    'icon' => sanitize_text_field($link['icon']),
                    'color' => sanitize_hex_color($link['color']),
                    'text' => sanitize_text_field($link['text']),
                );
            }
        }
    }
    $settings['links'] = $links;

    update_post_meta($post_id, '_fcb_settings', $settings);
}

// Metabox HTML callback
function fcb_settings_meta_box_cb($post)
{
    wp_nonce_field('fcb_save_meta_box_data', 'fcb_meta_box_nonce');

    $settings = get_post_meta($post->ID, '_fcb_settings', true);
    if (!is_array($settings)) {
        $settings = array();
    }

    $main_icon = isset($settings['main_icon']) ? $settings['main_icon'] : 'fas fa-comment-dots';
    $main_color = isset($settings['main_color']) ? $settings['main_color'] : '#007bff';
    $position = isset($settings['position']) ? $settings['position'] : 'bottom-right';

    $size = isset($settings['size']) ? $settings['size'] : 'md';
    $custom_size_main = isset($settings['custom_size_main']) ? $settings['custom_size_main'] : 65;
    $custom_size_links = isset($settings['custom_size_links']) ? $settings['custom_size_links'] : 50;

    $offset_bottom = isset($settings['offset_bottom']) ? $settings['offset_bottom'] : '';
    $offset_side = isset($settings['offset_side']) ? $settings['offset_side'] : '';
    $offset_top = isset($settings['offset_top']) ? $settings['offset_top'] : '';
    $offset_left = isset($settings['offset_left']) ? $settings['offset_left'] : '';
    $custom_css_class = isset($settings['custom_css_class']) ? $settings['custom_css_class'] : '';

    $hide_desktop = isset($settings['hide_desktop']) ? $settings['hide_desktop'] : 'no';
    $hide_tablet = isset($settings['hide_tablet']) ? $settings['hide_tablet'] : 'no';
    $hide_mobile = isset($settings['hide_mobile']) ? $settings['hide_mobile'] : 'no';
    $links = isset($settings['links']) ? $settings['links'] : array();

    $defaults = fcb_get_default_platforms();

    // Enqueue admin scripts/styles
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_style('fcb-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    wp_enqueue_style('fcb-frontend-style', FCB_PLUGIN_URL . 'assets/css/style.css'); // Load frontend CSS for preview
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_script('jquery-ui-sortable');

    ?>
    <style>
        .fcb-admin-wrap {
            display: flex;
            gap: 30px;
        }

        .fcb-admin-form {
            flex: 1;
        }

        .fcb-admin-preview {
            width: 350px;
            background: #f0f0f1;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            position: relative;
            min-height: 500px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fcb-preview-notice {
            position: absolute;
            top: 10px;
            left: 10px;
            font-weight: bold;
            color: #666;
            background: rgba(255, 255, 255, 0.8);
            padding: 4px 8px;
            border-radius: 4px;
            z-index: 10;
        }

        .fcb-admin-section {
            background: #fff;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ccd0d4;
        }

        .fcb-admin-section h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .fcb-field-row {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .fcb-field-row label.fcb-label {
            width: 170px;
            font-weight: 600;
        }

        .fcb-field-row .fcb-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fcb-link-row {
            background: #fafafa;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            cursor: move;
            /* Indicate sortable */
            border-left: 4px solid #007cba;
        }

        .fcb-link-row .fcb-drag-handle {
            color: #999;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .fcb-link-row .field-group {
            display: flex;
            flex-direction: column;
        }

        .fcb-link-row .field-group label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .fcb-remove-link {
            color: #d63638;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            margin-left: auto;
        }

        .fcb-remove-link:hover {
            color: #b32d2e;
        }

        .description-inline {
            margin-left: 10px;
            color: #666;
            font-style: italic;
            font-size: 12px;
        }

        .fcb-custom-size-fields {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px dashed #ccc;
            width: 100%;
        }

        /* Preview container overrides so it stays inside sandbox */
        .fcb-admin-preview .fcb-container {
            position: absolute !important;
            z-index: 1;
        }
    </style>

    <div class="fcb-admin-wrap">
        <div class="fcb-admin-form">
            <div class="fcb-admin-section">
                <h3>Main Button Appearance</h3>

                <div class="fcb-field-row">
                    <label class="fcb-label">Main Icon Class</label>
                    <input type="text" name="fcb_main_icon" id="fcb_main_icon_input"
                        value="<?php echo esc_attr($main_icon); ?>" class="regular-text fcb-preview-trigger" />
                    <span class="description-inline">e.g. <code>fas fa-comment-dots</code></span>
                </div>

                <div class="fcb-field-row">
                    <label class="fcb-label">Background Color</label>
                    <input type="text" name="fcb_main_color" id="fcb_main_color_input"
                        value="<?php echo esc_attr($main_color); ?>" class="fcb-color-picker fcb-preview-trigger" />
                </div>

                <div class="fcb-field-row">
                    <label class="fcb-label">Button Size</label>
                    <div class="fcb-input-group" style="flex-wrap: wrap;">
                        <select name="fcb_size" id="fcb_size_input" class="fcb-preview-trigger">
                            <option value="sm" <?php selected($size, 'sm'); ?>>Small</option>
                            <option value="md" <?php selected($size, 'md'); ?>>Medium (Default)</option>
                            <option value="lg" <?php selected($size, 'lg'); ?>>Large</option>
                            <option value="custom" <?php selected($size, 'custom'); ?>>Custom</option>
                        </select>

                        <div class="fcb-custom-size-fields"
                            style="<?php echo $size === 'custom' ? '' : 'display:none;'; ?>">
                            <label>Main Button Width/Height (px): </label>
                            <input type="number" name="fcb_custom_size_main" id="fcb_custom_size_main_input"
                                value="<?php echo esc_attr($custom_size_main); ?>" style="width:70px;"
                                class="fcb-preview-trigger">
                            &nbsp;&nbsp;&nbsp;
                            <label>Link Items Width/Height (px): </label>
                            <input type="number" name="fcb_custom_size_links" id="fcb_custom_size_links_input"
                                value="<?php echo esc_attr($custom_size_links); ?>" style="width:70px;"
                                class="fcb-preview-trigger">
                        </div>
                    </div>
                </div>
            </div>

            <div class="fcb-admin-section">
                <h3>Positioning & Custom CSS</h3>

                <div class="fcb-field-row">
                    <label class="fcb-label">Screen Position</label>
                    <select name="fcb_position" id="fcb_position_input" class="fcb-preview-trigger">
                        <option value="bottom-right" <?php selected($position, 'bottom-right'); ?>>Bottom Right</option>
                        <option value="bottom-left" <?php selected($position, 'bottom-left'); ?>>Bottom Left</option>
                    </select>
                </div>

                <div class="fcb-field-row">
                    <label class="fcb-label">Custom Offsets (px)</label>
                    <div class="fcb-input-group" style="flex-direction: column; align-items: flex-start; gap: 5px;">
                        <div>
                            <label style="display:inline-block; width:50px;">Bottom:</label>
                            <input type="number" name="fcb_offset_bottom" id="fcb_offset_bottom_input"
                                value="<?php echo esc_attr($offset_bottom); ?>" style="width:70px"
                                class="fcb-preview-trigger" placeholder="30" />
                            &nbsp;&nbsp;
                            <label style="display:inline-block; width:50px;" id="fcb_offset_side_label">Side:</label>
                            <input type="number" name="fcb_offset_side" id="fcb_offset_side_input"
                                value="<?php echo esc_attr($offset_side); ?>" style="width:70px" class="fcb-preview-trigger"
                                placeholder="30" />
                        </div>
                        <div style="color: #666; font-size: 12px; font-style: italic; margin-bottom: 5px;">Overrides:</div>
                        <div>
                            <label style="display:inline-block; width:50px;">Top:</label>
                            <input type="number" name="fcb_offset_top" id="fcb_offset_top_input"
                                value="<?php echo esc_attr($offset_top); ?>" style="width:70px" class="fcb-preview-trigger"
                                placeholder="e.g. 100" />
                            &nbsp;&nbsp;
                            <label style="display:inline-block; width:50px;">Left:</label>
                            <input type="number" name="fcb_offset_left" id="fcb_offset_left_input"
                                value="<?php echo esc_attr($offset_left); ?>" style="width:70px" class="fcb-preview-trigger"
                                placeholder="e.g. 50" />
                        </div>
                    </div>
                </div>

                <div class="fcb-field-row">
                    <label class="fcb-label">Custom CSS Class</label>
                    <input type="text" name="fcb_custom_css_class" value="<?php echo esc_attr($custom_css_class); ?>"
                        class="regular-text" />
                    <span class="description-inline">Add specific classes here, e.g. <code>my-cool-btn</code>. Space
                        separated.</span>
                </div>
            </div>

            <div class="fcb-admin-section">
                <h3>Device Visibility</h3>
                <p class="description">Check the boxes below to <strong>HIDE</strong> the button on specific devices.</p>

                <div class="fcb-field-row">
                    <label class="fcb-label">Hide on Desktop</label>
                    <input type="checkbox" name="fcb_hide_desktop" value="yes" <?php checked($hide_desktop, 'yes'); ?> />
                </div>

                <div class="fcb-field-row">
                    <label class="fcb-label">Hide on Tablet</label>
                    <input type="checkbox" name="fcb_hide_tablet" value="yes" <?php checked($hide_tablet, 'yes'); ?> />
                </div>

                <div class="fcb-field-row">
                    <label class="fcb-label">Hide on Mobile</label>
                    <input type="checkbox" name="fcb_hide_mobile" value="yes" <?php checked($hide_mobile, 'yes'); ?> />
                </div>
            </div>

            <div class="fcb-admin-section">
                <h3>Contact Links (Drag & Drop to Reorder)</h3>

                <div id="fcb-links-wrapper">
                    <?php
                    $link_index = 0;
                    if (!empty($links)) {
                        foreach ($links as $link) {
                            fcb_render_metabox_link_row($link_index, $link);
                            $link_index++;
                        }
                    } else {
                        foreach ($defaults as $default) {
                            $link_data = array(
                                'text' => $default['name'],
                                'icon' => $default['icon'],
                                'color' => $default['color'],
                                'url' => ''
                            );
                            fcb_render_metabox_link_row($link_index, $link_data);
                            $link_index++;
                        }
                    }
                    ?>
                </div>

                <p>
                    <button type="button" class="button button-secondary" id="fcb-add-link">Add Custom Link</button>
                </p>
            </div>
        </div>

        <div class="fcb-admin-preview" id="fcb-preview-sandbox">
            <div class="fcb-preview-notice">Live Preview Workspace</div>
            <!-- The preview HTML will be dynamically injected here via JS -->
        </div>
    </div>

    <!-- Template for new link row -->
    <script type="text/template" id="fcb-link-template">
                <?php
                $empty_link = array('text' => '', 'icon' => 'fas fa-link', 'color' => '#333333', 'url' => '');
                fcb_render_metabox_link_row('{{INDEX}}', $empty_link);
                ?>
            </script>

    <script>
        jQuery(document).ready(function ($) {
            // Initialize Color Picker inside metabox and hook preview trigger
            $('.fcb-color-picker').wpColorPicker({
                change: function (event, ui) {
                    updatePreview();
                }
            });

            // Initialize Sortable for drag and drop links
            $('#fcb-links-wrapper').sortable({
                handle: '.fcb-drag-handle',
                axis: 'y',
                update: function (event, ui) {
                    // Input names automatically take their new DOM order array indices on form submit
                    updatePreview();
                }
            });

            var linkIndex = <?php echo $link_index; ?>;

            // Add link functionality
            $('#fcb-add-link').on('click', function (e) {
                e.preventDefault();
                var template = $('#fcb-link-template').html();
                template = template.replace(/{{INDEX}}/g, linkIndex);
                $('#fcb-links-wrapper').append(template);

                // Re-bind specific events safely
                var newRow = $('#fcb-links-wrapper .fcb-link-row').last();
                newRow.find('.fcb-color-picker').wpColorPicker({ change: function () { updatePreview(); } });
                newRow.find('.fcb-preview-trigger').on('input change', updatePreview);

                linkIndex++;
                updatePreview();
            });

            // Remove link functionality
            $(document).on('click', '.fcb-remove-link', function (e) {
                e.preventDefault();
                if (confirm("Are you sure you want to remove this link?")) {
                    $(this).closest('.fcb-link-row').remove();
                    updatePreview();
                }
            });

            // Handle Size Dropdown toggle Custom Size fields
            $('#fcb_size_input').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('.fcb-custom-size-fields').slideDown();
                } else {
                    $('.fcb-custom-size-fields').slideUp();
                }
            });

            // Trigger updates on any input changes
            $(document).on('input change keyup', '.fcb-preview-trigger', function () {
                updatePreview();
            });

            // --- Live Preview Function ---
            function updatePreview() {
                var sandbox = $('#fcb-preview-sandbox');

                var mainIcon = $('#fcb_main_icon_input').val() || 'fas fa-comment-dots';
                var mainColor = $('#fcb_main_color_input').val() || '#007bff';
                var position = $('#fcb_position_input').val() || 'bottom-right';

                var size = $('#fcb_size_input').val() || 'md';
                var custMainPx = parseInt($('#fcb_custom_size_main_input').val()) || 65;
                var custLinkPx = parseInt($('#fcb_custom_size_links_input').val()) || 50;

                var offsetBottom = $('#fcb_offset_bottom_input').val();
                offsetBottom = offsetBottom !== '' ? parseInt(offsetBottom) : 30; // Default 30 if empty

                var offsetSide = $('#fcb_offset_side_input').val();
                offsetSide = offsetSide !== '' ? parseInt(offsetSide) : 30;

                var offsetTop = $('#fcb_offset_top_input').val();
                var offsetLeft = $('#fcb_offset_left_input').val();

                // Update label for side offset to be clear
                $('#fcb_offset_side_label').text(position === 'bottom-right' ? 'Right:' : 'Left:');

                // Build classes mimicking frontend
                var containerClasses = 'fcb-container fcb-position-' + position;
                if (size === 'custom') {
                    containerClasses += ' fcb-size-custom';
                } else {
                    containerClasses += ' fcb-size-' + size;
                }

                // Build styles for positioning inside sandbox
                var containerStyle = '';

                if (offsetTop !== '') {
                    containerStyle += 'top: ' + parseInt(offsetTop) + 'px; bottom: auto; ';
                } else {
                    containerStyle += 'bottom: ' + offsetBottom + 'px; top: auto; ';
                }

                if (offsetLeft !== '') {
                    containerStyle += 'left: ' + parseInt(offsetLeft) + 'px; right: auto; ';
                } else {
                    if (position === 'bottom-right') {
                        containerStyle += 'right: ' + offsetSide + 'px; left: auto;';
                    } else {
                        containerStyle += 'left: ' + offsetSide + 'px; right: auto;';
                    }
                }

                var mainBtnStyle = 'background-color: ' + mainColor + ';';
                var linkItemStyleBase = '';

                if (size === 'custom') {
                    mainBtnStyle += ' width: ' + custMainPx + 'px; height: ' + custMainPx + 'px; font-size: ' + (custMainPx * 0.45) + 'px;';
                    linkItemStyleBase = 'width: ' + custLinkPx + 'px; height: ' + custLinkPx + 'px; font-size: ' + (custLinkPx * 0.45) + 'px;';
                }

                // Build links HTML
                var linksHTML = '';
                $('#fcb-links-wrapper .fcb-link-row').each(function () {
                    // Must extract values carefully due to input array naming
                    var textInput = $(this).find('input[name*="[text]"]').val();
                    var iconInput = $(this).find('input[name*="[icon]"]').val() || 'fas fa-link';
                    var colorInput = $(this).find('input[name*="[color]"]').val() || '#333333';
                    var urlInput = $(this).find('input[name*="[url]"]').val();

                    // Only render preview if URL exists, similar to frontend
                    if (urlInput && urlInput.trim() !== '') {
                        var linkStyle = linkItemStyleBase + ' background-color: ' + colorInput + ';';
                        linksHTML += '<a href="javascript:void(0)" class="fcb-link-item" style="' + linkStyle + '">';
                        linksHTML += '<i class="' + iconInput + '"></i>';
                        if (textInput) {
                            linksHTML += '<span class="fcb-tooltip">' + textInput + '</span>';
                        }
                        linksHTML += '</a>';
                    }
                });

                // Assemble Full HTML
                var html = '';
                html += '<div class="' + containerClasses + '" style="' + containerStyle + '">';
                html += '  <div class="fcb-links-container fcb-active">'; // Force active in preview
                html += linksHTML;
                html += '  </div>';
                html += '  <button class="fcb-main-button fcb-open" style="' + mainBtnStyle + '">';
                html += '    <i class="' + mainIcon + '"></i>';
                html += '    <i class="fas fa-times fcb-close-icon" style="display:block; transform:rotate(90deg);"></i>'; // Force visual logic for preview
                html += '  </button>';
                html += '</div>';

                // Remove old preview container but preserve the notice
                sandbox.find('.fcb-container').remove();
                sandbox.append(html);
            }

            // Initialize preview on page load
            updatePreview();
        });
    </script>
    <?php
}

function fcb_render_metabox_link_row($index, $link)
{
    ?>
    <div class="fcb-link-row">
        <div class="fcb-drag-handle" title="Drag to reorder"><i class="fas fa-grip-lines"></i></div>
        <div class="field-group">
            <label>Platform/Text</label>
            <input type="text" name="fcb_links[<?php echo esc_attr($index); ?>][text]"
                value="<?php echo esc_attr($link['text']); ?>" placeholder="e.g. Facebook" class="fcb-preview-trigger" />
        </div>
        <div class="field-group">
            <label>Icon Class</label>
            <input type="text" name="fcb_links[<?php echo esc_attr($index); ?>][icon]"
                value="<?php echo esc_attr($link['icon']); ?>" placeholder="e.g. fab fa-facebook"
                class="fcb-preview-trigger" />
        </div>
        <div class="field-group">
            <label>Color</label>
            <input type="text" name="fcb_links[<?php echo esc_attr($index); ?>][color]"
                value="<?php echo esc_attr($link['color']); ?>" class="fcb-color-picker" />
        </div>
        <div class="field-group" style="flex-grow: 1;">
            <label>URL (Required to display)</label>
            <input type="text" name="fcb_links[<?php echo esc_attr($index); ?>][url]"
                value="<?php echo esc_attr($link['url']); ?>" class="regular-text fcb-preview-trigger"
                placeholder="https://..." style="width: 100%;" />
        </div>
        <a href="#" class="fcb-remove-link">Dismiss</a>
    </div>
    <?php
}
