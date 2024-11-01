<?php
/*
Plugin Name: smoove connector for Elementor forms
Description: Sends Elementor forms submitted contacts into your smoove account.
Version: 3.0.3
Author: smoove
Author URI: https://www.smoove.io
Stable tag: 3.0.3
Requires at least: 5.5.3
Requires PHP: 7.1
License: GPLv2 or later
Text Domain: smoove-elementor
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'SMEF_DEV_MODE', false );
define( 'SMEF_PLUGIN_FILE', __FILE__ );
define( 'SMEF_PLUGIN_REL_PATH', dirname( plugin_basename( __FILE__ ) ) );
define( 'SMEF_PLUGIN_DIR', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SMEF_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'SMEF_FILE_VER', SMEF_DEV_MODE ? time() : '1.0.7' );

add_action( 'plugins_loaded', 'smef_plugin_init', 1 );

function smef_plugin_init() {
	load_plugin_textdomain( 'smoove-elementor', false, SMEF_PLUGIN_REL_PATH . '/languages' );

    if ( ! function_exists( 'is_plugin_active' ) ) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
	
	if ( !is_plugin_active( 'elementor/elementor.php' ) ) {
		add_action( 'admin_notices', 'smef_elementor_deactivated' );
		return;
	}

	smef_include_folder_files( SMEF_PLUGIN_PATH . '/inc/modules' );
	smef_include_folder_files( SMEF_PLUGIN_PATH . '/inc/helpers' );

	if( is_admin() ){
		smef_include_folder_files( SMEF_PLUGIN_PATH . '/inc/admin' );
	}
}

function smef_register_form_actions( $form_actions_registrar ) {
	smef_include_folder_files( SMEF_PLUGIN_PATH . '/inc/form-actions' );

	$form_actions_registrar->register( new \SMEF\Form_Actions\Smoove_Action_After_Submit() );
}

add_action( 'elementor_pro/forms/actions/register', 'smef_register_form_actions' );

function smef_elementor_deactivated() {
	echo '<div class="notice notice-warning">';
		/* translators: %1$s: current plugin name, required plugin name */
		echo '<p>' . sprintf( esc_html__( '%1$s requires %2$s to be installed and active.', 'smoove-elementor' ), '<strong>' . esc_html__('smoove connector for Elementor forms', 'smoove-elementor') . '</strong>', '<a href="https://elementor.com/" target="_blank">Elementor</a>' ) . '</p>';
	echo '</div>';
}

function smef_include_folder_files( $folder ){
	foreach( glob("{$folder}/*.php") as $filepath ){
		if( $filepath && is_readable( $filepath ) ){
			require_once $filepath;
		}
	}
}

function smef_plugin_activated(){
	smef_include_folder_files( SMEF_PLUGIN_PATH . '/inc/helpers' );

	\SMEF\Helpers\DB::create_api_log_table();
}

register_activation_hook( SMEF_PLUGIN_FILE, 'smef_plugin_activated' );

// Hook into the plugin update process
add_action('upgrader_process_complete', 'plugin_update_notice_after_update_multilang', 10, 2);

function plugin_update_notice_after_update_multilang($upgrader_object, $options) {
    // Check if the current process is for a plugin update
    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        // Get the plugin being updated
        $updated_plugins = $options['plugins'];
        
        // Check if the updated plugin is the one you want to show the message for
        if (in_array(plugin_basename(__FILE__), $updated_plugins)) {
            // Trigger a custom admin notice
            add_action('admin_notices', 'plugin_show_update_notice_multilang');
        }
    }
}

function plugin_show_update_notice_multilang() {
    // Get the site locale
    $locale = get_locale();
    
    // Define the notice message based on the locale
    if ($locale == 'he_IL') {
        // Hebrew message
        $message = 'עדכון חשוב: העדכון הזה דורש תשומת לב. אנא קרא בעיון את הערות העדכון.';
        $notice = '<strong>הודעת עדכון חשובה:</strong> שימו לב! לאחר עדכון הגרסה יש להוסיף לכל הטפסים שדה אישור דיוור וסמס (שדות מסוג acceptance או checkbox), אחרת אנשי הקשר יכנסו ללא הרשאות דיוור וסמס.';
    } else {
        // Default to English message
        $message = 'Important: This update requires some attention. Please read the update notes carefully.';
        $notice = "<strong>Important Update Notice:</strong> Pay attention! If you don't add email and SMS acceptance fields to all forms after the version update, all contacts will be transmitted without mailing permissions.";
    }
    
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo $notice; ?></p>
    </div>
    <script type="text/javascript">
        alert('<?php echo $message; ?>');
    </script>
    <?php
}
