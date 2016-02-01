<?php
/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
class AUIU_Settings {
    private $settings_api;
    function __construct() {
        $this->settings_api = new WeDevs_Settings_API();
        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }
    function admin_init() {
        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );
        //initialize settings
        $this->settings_api->admin_init();
    }
    /**
     * Register the admin menu
     *
     * @since 1.0
     */
    function admin_menu() {
        add_menu_page( __( 'AFB User Image Upload', 'auiu' ), __( 'AFB User Image Upload', 'auiu' ), 'activate_plugins', 'auiu-admin-opt', array($this, 'plugin_page'), plugins_url( 'images/techpress-rocks-16px.png' , dirname(__FILE__) ) );
        add_submenu_page( 'auiu-admin-opt', __( 'Custom Fields', 'auiu' ), __( 'Custom Fields', 'auiu' ), 'activate_plugins', 'auiu_custom_fields', 'auiu_custom_fields' );
    }
    /**
     * AVUU Settings sections
     *
     * @since 1.0
     * @return array
     */
    function get_settings_sections() {
        return auiu_settings_sections();
    }
    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        return auiu_settings_fields();
    }
    function plugin_page() {
        ?>
        <div class="wrap">
			<a href="http://techpress.rocks" target="_blank">
                <img src="<?php echo plugins_url( '', dirname( __FILE__ ) ); ?>/images/banner-auiu.png" alt="techpress.rocks" title="AFB User Image Upload Plugin">
            </a>
            <div class="clear"></div>
            <?php
            settings_errors();
            screen_icon( 'options-general' );
            $this->settings_api->show_navigation();
            $this->settings_api->show_forms();
            ?>
        </div>
        <?php
    }
}
$auiu_settings = new AUIU_Settings();