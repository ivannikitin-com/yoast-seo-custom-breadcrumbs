<?php
/**
 * Класс добавляет мета-данные к таксономиям
 */
namespace YSCB;

class TaxonomyMeta {
    /**
     * Объект плагина
     */
    private $plugin = null; 

    /**
     * Типы таксономий, для которых устанавливаются хлебные крошки
     */
    private $screen = array(
        'product'
    );

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
    }

    /**
     * Возвращает значение пункта выбора по умолчанию
     * Нужно для согласованной работы классов Plugin и Metabox
     * 
     * @return string
     */
    // TODO: Вынести в Plugin
    public function get_default() {
        return __( 'Default', YSCB );
    }    

    /**
     * Инициализация
     */
    public function init() {
        /* Список меню */
        // TODO: Сохранить его где-нибудь в классе
        $menus = array( $this->get_default() );
        foreach (wp_get_nav_menus() as $menu ){
            $menus[] = $menu->name;
        }
    }



}