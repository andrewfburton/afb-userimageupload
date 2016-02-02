<?php
/*
Plugin Name: AFB User Image Upload
Plugin URI: http://techpress.rocks
Description: Let your web site visitors upload images, directly into your image gallery
Author: Tareq Hasan & AFB
Version: 1.0.1
Author URI: http://techpress.rocks
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
if ( !class_exists( 'WeDevs_Settings_API' ) ) {
    require_once dirname( __FILE__ ) . '/lib/class.settings-api.php';
}
require_once 'auiu-functions.php';
require_once 'admin/settings-options.php';
require_once 'admin/form-builder.php';

if ( is_admin() ) {
    require_once 'admin/settings.php';
    require_once 'admin/custom-fields.php';
    require_once 'admin/taxonomy.php';
	
	require 'plugin-update-checker/plugin-update-checker.php';
	$className = PucFactory::getLatestClassVersion('PucGitHubChecker');
	$myUpdateChecker = new $className( 'https://github.com/andrewfburton/afb-userimageupload/',__FILE__,'master');
 }
require_once 'auiu-add-post.php';
require_once 'auiu-ajax.php';

class AUIU_Main {

    function __construct() {
        register_activation_hook( __FILE__, array($this, 'install') );
        register_deactivation_hook( __FILE__, array($this, 'uninstall') );
        add_action( 'admin_init', array($this, 'block_admin_access') );
        add_action( 'init', array($this, 'load_textdomain') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
    }
    /**
     * Create tables on plugin activation
     *
     * @global object $wpdb
     */
    function install() {
        global $wpdb;

        flush_rewrite_rules( false );
        $sql_custom = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}auiu_customfields (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `field` varchar(30) NOT NULL,
         `type` varchar(20) NOT NULL,
         `values` text NOT NULL,
         `label` varchar(200) NOT NULL,
         `desc` varchar(200) NOT NULL,
         `required` varchar(5) NOT NULL,
         `region` varchar(20) NOT NULL DEFAULT 'top',
         `order` int(1) NOT NULL,
         PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

        $wpdb->query( $sql_custom );
    }

    function uninstall() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}auiu_customfields" );
    }
    /**
     * Enqueues Styles and Scripts when the shortcodes are used only
     *
     * @uses has_shortcode()
     * @since 0.2
     */
    function enqueue_scripts() {
        $path = plugins_url('', __FILE__ );
        //for multisite upload limit filter
        if ( is_multisite() ) {
            require_once ABSPATH . '/wp-admin/includes/ms.php';
        }
        require_once ABSPATH . '/wp-admin/includes/template.php';

        wp_enqueue_style( 'auiu', $path . '/css/auiu.css' );

        if ( auiu_has_shortcode( 'afb_uploadform' )  ) {
            wp_enqueue_script( 'plupload-handlers' );
        }
 
        wp_enqueue_script( 'auiu', $path . '/js/auiu.js', array('jquery') );
		
		wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js' );

        $posting_msg = auiu_get_option( 'updating_label', 'auiu_labels' );
        $feat_img_enabled = ( auiu_get_option( 'enable_featured_image', 'auiu_frontend_posting' ) == 'yes') ? true : false;
		$size_limit = (int) (auiu_get_option( 'attachment_max_size', 'auiu_frontend_posting' ));
        wp_localize_script( 'auiu', 'auiu', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'postingMsg' => $posting_msg,
            'confirmMsg' => __( 'Are you sure?', 'auiu' ),
            'nonce' => wp_create_nonce( 'auiu_nonce' ),
            'featEnabled' => $feat_img_enabled,
            'plupload' => array(
                'runtimes' => 'html5,silverlight,flash,html4',
                'browse_button' => 'auiu-ft-upload-pickfiles',
				'drop_element' => 'auiu-ft-upload-container',
                'container' => 'auiu-ft-upload-container',
                'file_data_name' => 'auiu_featured_img',
                'max_file_size' => $size_limit . 'kb',
                'url' => admin_url( 'admin-ajax.php' ) . '?action=auiu_featured_img&nonce=' . wp_create_nonce( 'auiu_featured_img' ),
                'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
                'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
                'filters' => array(array('title' => __( 'Allowed Files' ), 'extensions' => '*')),
                'multipart' => true,
                'urlstream_upload' => true,
            )
        ) );
    }
    /**
     * Block user access to admin panel for specific roles
     *
     * @global string $pagenow
     */
    function block_admin_access() {
        global $pagenow;
        // bail out if we are from WP Cli
        if ( defined( 'WP_CLI' ) ) {
            return;
        }

        $access_level = auiu_get_option( 'admin_access', 'auiu_others', 'read' );
        $valid_pages = array('admin-ajax.php', 'async-upload.php', 'media-upload.php');

        if ( !current_user_can( $access_level ) && !in_array( $pagenow, $valid_pages ) ) {
            wp_die( __( 'Access Denied. Your site administrator has blocked your access to the WordPress back-office.', 'auiu' ) );
        }
    }
    /**
     * Load the translation file for current language.
     *
     * @since version 0.7
     * @author Tareq Hasan
     */
    function load_textdomain() {
        $locale = apply_filters( 'auiu_locale', get_locale() );
        $mofile = dirname( __FILE__ ) . "/languages/auiu-$locale.mo";

        if ( file_exists( $mofile ) ) {
            load_textdomain( 'auiu', $mofile );
        }
    }
    /**
     * The main logging function
     *
     * @uses error_log
     * @param string $type type of the error. e.g: debug, error, info
     * @param string $msg
     */
    public static function log( $type = '', $msg = '' ) {
        if ( WP_DEBUG == true ) {
            $msg = sprintf( "[%s][%s] %s\n", date( 'd.m.Y h:i:s' ), $type, $msg );
            error_log( $msg, 3, dirname( __FILE__ ) . '/log.txt' );
        }
    }
}
$auiu = new AUIU_Main();