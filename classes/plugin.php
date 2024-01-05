<?php
/**
 * Класс основного плагина
 */
namespace YSCB;

class Plugin {
    /**
     * Папка плагина
     * @var string
     */
    private $folder;

    /**
     * Мета-бокс произвольных крошек
     */
    private $metabox;


    /**
     * Конструктор класса
     * Установка обработчиков хуков
     * 
     * @param string $mainFile  Основной файл плагина
     */
    public function __construct( $mainFile ) {
        /* Рабочая папка плагина */
        $this->folder = dirname( plugin_basename( $mainFile ) );
        /* Мета-бокс */
        $this->metabox = new Metabox();

        // Хуки
        add_action( 'plugins_loaded', array( $this, 'load_lang' ) );
        add_filter( 'wpseo_breadcrumb_links', array( $this, 'get_crumbs' ) );
        
    } 

    /**
     * Загрузка файлов локализации
     */
    public function load_lang() {
        load_plugin_textdomain( YSCB, false, $this->folder . '/languages/');
    }

    /**
     * Обработка массива хлебных крошек
     * 
     * @param mixed $crumbs Массив хлебных крошек, сформированный Yoast SEO
     * @return mixed        Результирующий массив хлебных крошек
     */
    public function get_crumbs( $crumbs ) {
        /* Текущий объект и установленный параметр крошек */
        $post_id = get_queried_object_id();
        $crumbs_menu = get_post_meta( $post_id, Metabox::META_FIELD, true );

        /* Формируем массив крошек */
        if ( $crumbs_menu && $crumbs_menu != $this->metabox->get_default() ) {
            /* Формируем хлебные крошки */
            $crumbs = array();
            $menu_items = wp_get_nav_menu_items( $crumbs_menu );
            foreach ( $menu_items as $menu_item ) {
                $crumbs[] = array(
                    'url'   => $menu_item->url,
                    'text'  => $menu_item->title
                );
            }
            /* Добавим последний элемент -- ссылку на себя */
            $post = get_post($post_id);
            $crumbs[] = array(
                'text'  => $post->post_title
            );            
        }

        return $crumbs;
    }
}