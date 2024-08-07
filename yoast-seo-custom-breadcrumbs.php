<?php
/*
 * Plugin Name:       Yoast SEO Custom Breadcrumbs
 * Plugin URI:        https://github.com/ivannikitin-com/yoast-seo-custom-breadcrumbs
 * Description:       This plugin allows for custom breadcrumbs to be set up for any individual page/post/product in Wordpress.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            IvanNikitin and Co
 * Author URI:        https://ivannikitin.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/ivannikitin-com/yoast-seo-custom-breadcrumbs/releases/latest
 * Text Domain:       yoast-seo-custom-breadcrumbs
 * Domain Path:       /languages
 * 
 * Yoast SEO Custom Breadcrumbs is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Yoast SEO Custom Breadcrumbs is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Yoast SEO Custom Breadcrumbs. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */
defined( 'ABSPATH' ) or die( -1 );
define( 'YSCB', 'yoast-seo-custom-breadcrumbs' );
define( 'YSCB_DEBUG', false );

/* Plugin files */
require( 'classes/plugin.php' );
require( 'classes/metabox.php' );
require( 'classes/taxonomy-meta.php' );

/* Запуск */
new \YSCB\Plugin(
    __FILE__    // Основной файл плагина
);
