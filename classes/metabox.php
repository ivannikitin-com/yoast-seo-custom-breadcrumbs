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
     * Типы записей, для которых устанавливаются хлебные крошки
     */
    private $screen = array(
        'post',
        'page',
        'product',
    );

    /**
     * Поля мета-бокса
     */
    private $meta_fields = array();

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        // Хуки
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'admin_footer', array( $this, 'media_fields' ) );
        add_action( 'save_post', array( $this, 'save_fields' ) );
    }

    /**
     * Возвращает значение пункта выбора по умолчанию
     * Нужно для согласованной работы классов Plugin и Metabox
     * 
     * @return string
     */
    public function get_default() {
        return __( 'По умолчанию', YSCB );
    }

    /**
     * Инициализация
     */
    public function init() {
        /* Список меню */
        $menus = array( $this->get_default() );
        foreach (wp_get_nav_menus() as $menu ){
            $menus[] = $menu->name;
        }

        /* Инициализация описания мета-полей */
        $this->meta_fields = array(
            array(
                'id'        => self::META_FIELD,
                'type'      => 'select',
                'label'     => __('Выберите хлебные крошки', YSCB ),
                'options'   => $menus,
                'default'   => $this->get_default()
            )
        );
    }
    
    /**
     * Добавление мета-бокса к требуемым типам записей
     */
    public function add_meta_boxes()
    {
        foreach ($this->screen as $single_screen) {
            add_meta_box(
                self::METABOX_ID,
                __( 'Хлебные крошки', YSCB ),
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
        _e ( 'Установка произвольных хлебных крошек для этого элемента', YSCB );
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
}
