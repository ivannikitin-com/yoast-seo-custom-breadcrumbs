<?php
/**
 * Класс мета-бокса выбора хлебных крошек
 */
namespace YSCB;

class Metabox
{
    /**
     * ID мета-поля записи  
     */
    const META_FIELD = 'crumbs_menu';


    /**
     * ID мета-бокса
     */
    const METABOX_ID = 'yoast_seo_custom_breadcrumbs';

    /**
     * Поле nonce и данные
     */
    const NONCE = 'yoast_seo_custom_breadcrumbs_nonce';
    
    /**
     * Поле nonce и данные
     */
    const DATA = 'yoast_seo_custom_breadcrumbs_data';

    /**
     * Объект плагина
     */
    private $plugin = null; 

    /**
     * Типы записей, для которых устанавливаются хлебные крошки
     */
    private $screen = array(
        'post',
        'page',
        'product',
        'edit-product_cat'
    );

    /**
     * Поля мета-бокса
     */
    private $meta_fields = array();

    /**
     * Конструктор класса
     */
    public function __construct( $plugin )
    {
        // Плагин
        $this->plugin = $plugin;

        // Хуки
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'admin_footer', array( $this, 'media_fields' ) );
        add_action( 'save_post', array( $this, 'save_fields' ) );
        add_action('product_cat_edit_form_fields', array( $this, 'add_product_cat_edit_form' ), 10, 1);
        add_action('edited_product_cat', array( $this, 'save_taxonomy_custom_meta' ), 10, 1);
    }

    /**
     * Инициализация
     */
    public function init() {
        /* Инициализация описания мета-полей */
        $this->meta_fields = array(
            array(
                'id'        => self::META_FIELD,
                'type'      => 'select',
                'label'     => __('Select breadcrumbs', YSCB ),
                'options'   => $this->plugin->get_menu_names(), // Список меню
                'default'   => $this->plugin->get_default()     // Значение по умолчанию
            )
        );
    }

    /**
     * Возвращает выбранное меню для текущей страницы
     */
    public function get_current_menu() {
        $current_obj = $this->plugin->get_current_obj();
        $this->plugin->log( ['Текущий объект', $current_obj] );

        if ( ! $current_obj ) return null;

        if ($current_obj instanceof \WP_Post) {
            $menu = get_post_meta($current_obj->ID, self::META_FIELD, true);

            $this->plugin->log( ['Меню', var_export( $menu, true)] );
            $this->plugin->log( ['Текущее значение мета-поля', $menu] );

            if ($menu) {    
                return $menu;
            }
        }
        return null;
    }

    /**
     * Метод возвращает название текущего объекта для хлебных крошек 
     */
    public function get_current_obj_name() {
        // Если текущий объект не определен, пусто
        if ( ! $this->plugin->get_current_obj() ) return '';

        return $this->plugin->get_current_obj()->post_title;
    }


    /* ------------------------------------------------------------------------------------------------ */
    
    /**
     * Добавление мета-бокса к требуемым типам записей
     */
    public function add_meta_boxes()
    {
        foreach ($this->screen as $single_screen) {
            add_meta_box(
                self::METABOX_ID,
                __( 'Breadcrumbs', YSCB ),
                array( $this, 'meta_box_callback' ),
                $single_screen,
                'normal',
                'default'
            );
        }
    }

    /**
     * Описание и вывод полей мета-бокса
     * 
     * @param mixed $post   Текущий объект 
     */
    public function meta_box_callback( $post )
    {
        wp_nonce_field(self::DATA, self::NONCE);
        _e ( 'Custom breadcrumbs for this post/page/product', YSCB );
        $this->field_generator( $post );
    }

    /**
     * Скрипты
     */    
    public function media_fields()
    { ?>
        <script>
            jQuery(document).ready(function ($) {
                if (typeof wp.media !== 'undefined') {
                    var _custom_media = true,
                        _orig_send_attachment = wp.media.editor.send.attachment;
                    $('.new-media').click(function (e) {
                        var send_attachment_bkp = wp.media.editor.send.attachment;
                        var button = $(this);
                        var id = button.attr('id').replace('_button', '');
                        _custom_media = true;
                        wp.media.editor.send.attachment = function (props, attachment) {
                            if (_custom_media) {
                                if ($('input#' + id).data('return') == 'url') {
                                    $('input#' + id).val(attachment.url);
                                } else {
                                    $('input#' + id).val(attachment.id);
                                }
                                $('div#preview' + id).css('background-image', 'url(' + attachment.url + ')');
                            } else {
                                return _orig_send_attachment.apply(this, [props, attachment]);
                            };
                        }
                        wp.media.editor.open(button);
                        return false;
                    });
                    $('.add_media').on('click', function () {
                        _custom_media = false;
                    });
                    $('.remove-media').on('click', function () {
                        var parent = $(this).parents('td');
                        parent.find('input[type="text"]').val('');
                        parent.find('div').css('background-image', 'url()');
                    });
                }
            });
        </script>
    <?php
    }

    /**
     * вывод полей мета-бокса
     * 
     * @param mixed $post   Текущий объект 
     */
    public function field_generator( $post )
    {
        $output = '';
        foreach ($this->meta_fields as $meta_field) {
            $label = '<label for="' . $meta_field['id'] . '">' . $meta_field['label'] . '</label>';
            $meta_value = get_post_meta( $post->ID, $meta_field['id'], true );
            if (empty($meta_value)) {
                if (isset($meta_field['default'])) {
                    $meta_value = $meta_field['default'];
                }
            }
            switch ($meta_field['type']) {

                case 'select':
                    $input = sprintf(
                        '<select id="%s" name="%s">',
                        $meta_field['id'],
                        $meta_field['id']
                    );
                    foreach ($meta_field['options'] as $key => $value) {
                        $meta_field_value = !is_numeric($key) ? $key : $value;
                        $input .= sprintf(
                            '<option %s value="%s">%s</option>',
                            $meta_value === $meta_field_value ? 'selected' : '',
                            $meta_field_value,
                            $value
                        );
                    }
                    $input .= '</select>';
                    break;
            }
            $output .= $this->format_rows($label, $input);
        }
        echo '<table class="form-table"><tbody>' . $output . '</tbody></table>';
    }

    /**
     * Форматирование поля
     * 
     * @param string $label     Название поля
     * @param string $input     Элемент поля
     */
    public function format_rows( $label, $input )
    {
        return '<tr><th>' . $label . '</th><td>' . $input . '</td></tr>';
    }

    /**
     * Сохранение поля
     * 
     * @param int $post_id      ID текущего объекта
     */
    public function save_fields( $post_id )
    {
        if (!isset($_POST[self::NONCE]))
            return $post_id;

        $nonce = $_POST[self::NONCE];
        if (!wp_verify_nonce($nonce, self::DATA))
            return $post_id;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;

        foreach ($this->meta_fields as $meta_field) {
            if (isset($_POST[$meta_field['id']])) {
                switch ($meta_field['type']) {
                    case 'email':
                        $_POST[$meta_field['id']] = sanitize_email($_POST[$meta_field['id']]);
                        break;
                    case 'text':
                        $_POST[$meta_field['id']] = sanitize_text_field($_POST[$meta_field['id']]);
                        break;
                }
                update_post_meta($post_id, $meta_field['id'], $_POST[$meta_field['id']]);
            } 
            else if ($meta_field['type'] === 'checkbox') {
                update_post_meta($post_id, $meta_field['id'], '0');
            }
        }
    }


    /**
     * Поле выбора хлебных крошек в категориях
     */
    public function add_product_cat_edit_form($term) {

    //getting term ID
    $term_id = $term->term_id;

    // retrieve the existing value(s) for this meta field.
    $product_cat_breadcrumbs = get_term_meta($term_id, 'product_cat_breadcrumbs', true);
    $default_value = $this->get_default();
    if ('' == $product_cat_breadcrumbs) $product_cat_breadcrumbs = $default_value;

    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="product_cat_breadcrumbs"><?php _e('Custom breadcrumbs for this category', YSCB); ?></label></th>
        <td>
            <select name="product_cat_breadcrumbs" id="product_cat_breadcrumbs">
                <option value="<?php echo $default_value ?>" <?php if ($product_cat_breadcrumbs == $default_value) echo 'selected' ?>><?php echo $default_value ?></option>
                <?php foreach (wp_get_nav_menus() as $menu ): ?>
                    <option value="<?php echo $menu->name ?>" <?php if ($product_cat_breadcrumbs == $menu->name) echo 'selected' ?>> <?php echo $menu->name ?></option>            
                <?php endforeach ?>
            </select>
        </td>
    </tr>
    <?php
    }


    /**
     * Сохраняет хлебные кношки для таксономии категории
     */
    public function save_taxonomy_custom_meta($term_id) {
        $product_cat_breadcrumbs = isset($_POST['product_cat_breadcrumbs']) ? sanitize_text_field($_POST['product_cat_breadcrumbs']) : '';
        update_term_meta($term_id, 'product_cat_breadcrumbs', $product_cat_breadcrumbs);
    }
}
