<?php
/**
 * Класс добавляет мета-данные к таксономиям
 */
namespace YSCB;

class TaxonomyMeta {
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
     * Объект плагина
     */
    private $plugin = null; 

    /**
     * Типы таксономий, для которых устанавливаются хлебные крошки
     */
    private $taxonomies = array(
        'product_cat'
    );

    /**
     * Конструктор класса
     */
    public function __construct( $plugin )
    {
        // Плагин
        $this->plugin = $plugin;

        // Хуки
        foreach ($this->taxonomies as $taxonomy) {
            add_action( $taxonomy . '_add_form_fields', array( $this, 'add_select_menu_form' ) );
            add_action( $taxonomy . '_edit_form_fields', array( $this, 'edit_select_menu_form' ), 10, 2 );
            add_action( 'created_' . $taxonomy, array( $this, 'save' ) );
            add_action( 'edited_' . $taxonomy, array( $this, 'save' ) );            
        }
        
    }

    /**
     * Возвращает выбранное меню для текущей страницы
     */
    public function get_current_menu() {
        $current_obj = $this->plugin->get_current_obj();
        $this->plugin->log( ['Текущий объект', $current_obj] );

        // Есть ли запрашиваемый объект
        if ( ! $current_obj ) return null;

        // Это таксономия?
        if ( ! $current_obj instanceof \WP_Term ) return null;

        // Это наша таксономия?
        if ( ! in_array( $current_obj->taxonomy, $this->taxonomies ) ) return null;

        // Текущее значение мета-поля
        $current_menu = get_term_meta( $current_obj->term_id, self::META_FIELD, true );
        $this->plugin->log( ['Текущее значение мета-поля', $current_menu] );

        // Оно установлено?
        if ( $current_menu ) {
            return $current_menu;
        }

        return null;
    }

    /**
     * Метод возвращает название текущего объекта для хлебных крошек 
     */
    public function get_current_obj_name() {
        // Если текущий объект не определен, пусто
        if ( ! $this->plugin->get_current_obj() ) return '';

        return $this->plugin->get_current_obj()->name;
    }

    /* ------------------------------------------------------------------------------------------------ */

    /**
     * Форма выбора меню хлебных крошек при добавлении нового термина
     */
    public function add_select_menu_form( $taxonomy ) {
        echo '<div class="form-field">
                <label for="' . self::METABOX_ID . '">' . __('Select breadcrumbs', YSCB ) . '</label>', PHP_EOL;
        $this->show_select();
        echo '</div>', PHP_EOL;        
    }

    /**
     * Форма выбора меню хлебных крошек при добавлении нового термина
     */
    public function edit_select_menu_form( $term, $taxonomy ) {
        // Значение мета-поля
        $current_menu = get_term_meta( $term->term_id, self::META_FIELD, true );
        echo '<tr class="form-field">
                <th>
                    <label for="' . self::METABOX_ID . '">' . __('Select breadcrumbs', YSCB ) . '</label>
                </th>
            <td>', PHP_EOL; 
        $this->show_select( $current_menu );
        echo '</td></tr>', PHP_EOL;       
    }


    /**
     * Вывод select со списком меню
     */
    private function show_select( $current_value = null ) {
        $select_id = self::METABOX_ID;
        echo '<select name="' . $select_id . '" id="' . $select_id . '">', PHP_EOL;
        foreach ($this->plugin->get_menu_names() as $menu) {
            echo '<option value="' . $menu . '" ' . selected( $current_value, $menu, false ) . '>' . $menu . '</option>', PHP_EOL;
        }
        echo '</select>', PHP_EOL;
    }

    /**
     * Сохранение данных в мета-поле
     */
    public function save( $term_id ) {
        if( isset( $_POST[ self::METABOX_ID ] ) ) {
            update_term_meta( $term_id, self::META_FIELD, sanitize_text_field( $_POST[ self::METABOX_ID ] ) );
        } 
        else {
            delete_term_meta( $term_id, self::META_FIELD );
        }
    }

}
