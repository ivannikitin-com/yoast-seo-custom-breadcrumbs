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
     * Мета для таксономий
     */
    private $taxonomyMeta;

    /**
     * Текущий запрошенный объект WP 
     */
    public $current_obj = null;

    /**
     * Конструктор класса
     * Установка обработчиков хуков
     * 
     * @param string $mainFile  Основной файл плагина
     */
    public function __construct( $mainFile ) {
        /* Рабочая папка плагина */
        $this->folder = dirname( plugin_basename( $mainFile ) );

        /* Объекты интеграции */
        $this->metabox = new Metabox( $this );
        $this->TaxonomyMeta = new TaxonomyMeta( $this );

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
     * Сервисный метод логирования
     */
    public function log( $objs ) {
        if ( ! defined( 'YSCB_DEBUG' ) || ! YSCB_DEBUG ) return;

        if ( is_scalar( $objs ) ) {
            $messages = array();
            $messages[] = $objs;
        }
        else {
            $messages = $objs;
        }

        $message = '[' . date('d-M-Y H:i:s') . '] YSCB: ';
        foreach ( $messages as $obj ) {
            if (is_scalar($obj)) {
                $message .= $obj . ' ';
            } else {
                $message .= var_export($obj, true);
            }
            $message .= PHP_EOL;
        }

        error_log($message);
    }

    // -----------------------------------------------------------------------------

    /**
     * Возвращает текущий объект WP
     */
    public function get_current_obj() {
        if ( $this->current_obj ) return $this->current_obj;

        // Если объект еще не получен, получаем его
        try {
            $this->current_obj = get_queried_object();
        }
        catch (Exception $e) {
            $this->log( 'Текущий объект запроса еще не существует!' );
            $this->current_obj = null;
        }

        return $this->current_obj;
    }

    /**
     * Возвращает значение пункта выбора по умолчанию
     * Нужно для согласованной работы классов Plugin и Metabox
     * 
     * @return string
     */
    public function get_default() {
        return __( 'Default', YSCB );
    }

    /**
     * Список названий меню для хлебных крошек
     */
    private $menus = array();

    /** 
     * Возвращает массив названий меню для хлебных крошек
     */
    public function get_menu_names() {
        /* Если уже вычисляли, возвращаем вычисленные значения */
        if ( $this->menus ) {
            return $this->menus;
        }

        /* Формируем список меню */
        $this->menus = array( $this->get_default() );
        foreach (wp_get_nav_menus() as $menu ){
            $this->menus[] = $menu->name;
        }

        return $this->menus;
    }
    // -----------------------------------------------------------------------------

    /**
     * Обработка массива хлебных крошек
     * 
     * @param mixed $crumbs Массив хлебных крошек, сформированный Yoast SEO
     * @return mixed        Результирующий массив хлебных крошек
     */
    public function get_crumbs( $crumbs ) {

        // Установленное меню текущей страницы
        $crumbs_menu = $this->metabox->get_current_menu();
        $current_obj_name = $this->metabox->get_current_obj_name();

        // Если данных нет, читаем объект таксономии
        if (! $crumbs_menu ) {
            $crumbs_menu = $this->TaxonomyMeta->get_current_menu();
            $current_obj_name = $this->TaxonomyMeta->get_current_obj_name();
        }
        $this->log( ['Выбранное меню', $crumbs_menu] ); 

        if ( ! $crumbs_menu ) {
            return $crumbs;
        }

        /* Формируем массив крошек */
        if ( $crumbs_menu && $crumbs_menu != $this->get_default() ) {
            /* Формируем хлебные крошки */
            $crumbs = array();
            $menu_items = wp_get_nav_menu_items( $crumbs_menu );
            foreach ( $menu_items as $menu_item ) {
                $crumbs[] = array(
                    'url'   => $menu_item->url,
                    'text'  => $menu_item->title
                );
            }

            // Последний элемент -- текущая страница
            $crumbs[] = array(
                'text'  => $current_obj_name
            );         
        }

        return $crumbs;
    }
}