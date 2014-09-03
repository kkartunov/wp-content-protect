<?php
/*
Plugin Name: Content Protect By Time Lock
Description: Content display management for site's visitors regulated by time locks.
Version: 0.0.1
Author: Kiril Kartunov
Author URI: mailto:kiri4a@gmail.com?Subject=ContentProtect-plugin
Author Email: kiri4a@gmail.com
Plugin URI: http://wordpress.org/plugins/content-protect-by-time-lock/
License:

                    GNU GENERAL PUBLIC LICENSE
                       Version 3, 29 June 2007

 Copyright (C) 2007 Free Software Foundation, Inc. [http://fsf.org/]
 Everyone is permitted to copy and distribute verbatim copies
 of this license document, but changing it is not allowed.

*/
if( !class_exists( 'WP_ContentProtect' ) && defined( 'ABSPATH' ) ){

	register_activation_hook( __FILE__, array( 'WP_ContentProtect', 'activate' ) );
	if( function_exists( 'register_uninstall_hook' ) )
		register_uninstall_hook( __FILE__, array( 'WP_ContentProtect', 'uninstall' ) );

	class WP_ContentProtect {

		/*--------------------------------------------*
		 * Plugin variables
		 *--------------------------------------------*/
        // Refers to self.
        // Used to prevent multiple instances of the plugin.
        private static $instance;
        //Plugin DIR name.
        private $DIR;
        // Plugin base name.
        private $p_basename;
        // Plugin settings
        private $settings;
        // Post's meta key name used for protection filtering.
        const meta_protected_until = '_WPCP_protected_until';
        // Post's ,eta key name used to store plugin internal data.
        const meta_wpcp = '_WPCP_data';

		/*--------------------------------------------*
		 * Singleton instance factory
		 *--------------------------------------------*/
		public static function getInstance(){
			if (!isset(self::$instance)) {
				$className = __CLASS__;
				self::$instance = new $className;
			}
			return self::$instance;
		}

		/*--------------------------------------------*
		 * Constructor
		 *--------------------------------------------*/
		protected function __construct(){
			// Get and set plugin DIR and base name.
			$this -> p_basename = plugin_basename( __FILE__ );
			$this -> DIR = dirname( $this -> p_basename );
			//Load plugin settings.
			$this -> settings = get_option('WP_ContentProtect_settings');
            // Load translation.
            add_action('init', array($this, 'load_translation'));
            // Specific behavior for admin and front-end areas.
            if ( is_admin() ){
                // Admin area.
                // Add plugin's metabox in edit post/page GUI.
                add_action('add_meta_boxes', array($this, 'editGUI_meta_box'));
                // Hook on save post event to update plugin internal data.
                add_action('save_post', array($this, 'updateWPCP_asset'));
                // Future to publish trasitions
                add_action('future_to_publish', array($this, 'future_to_publish'));
                // Manage plugin styles/scripts under the admin area.
                add_action('admin_init', array($this, 'manageAssets'));
                // Add custom column to post/pages lists to show protected posts.
                if( $this -> settings['listings_col'] ){
                    add_filter('manage_posts_columns', array($this, 'addProtectedColumn'));
                    add_action('manage_posts_custom_column', array($this, 'fillProtectedColumn'), 10, 2);
                    add_filter('manage_pages_columns', array($this, 'addProtectedColumn'));
                    add_action('manage_pages_custom_column', array($this, 'fillProtectedColumn'), 10, 2);
                }
				//settings page functionality
				add_action('admin_menu', array( $this, 'add_settings_page' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'load_settings_page_css' ) );
				add_action('admin_init', array( $this, 'init_settings_page' ) );
            }else{
                // Front-end area.
                add_filter('template_include', array($this, 'preventDirectContAccess'), 1);
                add_action('pre_get_posts', array($this, 'apply_query_filtering'));
                add_filter('wp_get_nav_menu_items', array($this, 'apply_navmenu_filtering'));
                add_filter('get_pages', array($this, 'apply_links_filtering'));
                add_filter('getarchives_where', array($this, 'apply_archives_filtering'));
                add_action('future_to_publish', array($this, 'future_to_publish'));
            }

        }

		/*--------------------------------------------*
		 * on Activate event
		 *--------------------------------------------*/
		public static function activate( $network_wide ){
			// Make sure the user is allowed to activate this plugin.
			if ( ! current_user_can( 'activate_plugins' ) )
				wp_die(
                __('Ops, you are not allowed to activate plugins', 'WP_ContentProtect_Textdomain'),
                __('Unauthorized plugin activation', 'WP_ContentProtect_Textdomain'),
                array('back_link'=>true)
            );
			// Create or overwrite the default plugin's settings.
			update_option('WP_ContentProtect_settings', array(
                // Time in seconds to protect content from anonym users.
                // After this time content will be considered open for public.
                // Default: 6h
				'protect_for' => '+6 hours',
                // Datetime formating used in plugin's GUIs.
                'time_format' => 'F j, Y, G:i',
                // Custom column in listings.
                'listings_col' => true,
                // Protect by default.
                'protect_by_default' => true
			), '', 'no' );
        }

		/*--------------------------------------------*
		 * on Uninstall event
		 *--------------------------------------------*/
		public static function uninstall( $network_wide ){
            delete_option('WP_ContentProtect_settings');
        }

		/*--------------------------------------------*
		 * Loads plugin translation.
		 *--------------------------------------------*/
		public function load_translation(){
            load_plugin_textdomain('WP_ContentProtect_Textdomain', false, $this -> DIR.'/lang/');
        }

		/*--------------------------------------------*
		 * Compares WP version to some version string.
		 *--------------------------------------------*/
		public function minVer($compare_with, $operator = '>='){
            include preg_replace('/wp-content$/', 'wp-includes', WP_CONTENT_DIR).'/version.php';
            return version_compare($wp_version, $compare_with, $operator);
        }

		/*--------------------------------------------*
		 * Adds plugin settings page link
		 to settings menu in admin area
		 *--------------------------------------------*/
		public function add_settings_page(){
			add_options_page('Content Protect - settings', 'Content Protect', 'manage_options', 'Wp_ContentProtect', array( $this, 'print_settings_page' ));
		}

		/*--------------------------------------------*
		 * Echoes the contents of the settings page
		 *--------------------------------------------*/
		 public function print_settings_page(){
             include 'admin/settings_page.php';
         }

		/*--------------------------------------------*
		 * Adds settings page css for loading
		 only on our settings page
		 *--------------------------------------------*/
		public function load_settings_page_css($hook){
			if ( isset($_GET['page']) && $_GET['page'] == 'Wp_ContentProtect' ){
				wp_register_style( 'Wp_ContentProtect-settings', plugins_url() . '/' . $this->DIR . '/admin/css/settings_page.css', false );
				wp_enqueue_style( 'Wp_ContentProtect-settings' );
			}
		}

		/*--------------------------------------------*
		 * Initializes the plugin options page
		 *--------------------------------------------*/
		public function init_settings_page(){
			//section and storage
			add_settings_section(
				'Wp_ContentProtect_settings',
				__('General', 'WP_ContentProtect_Textdomain'),
				null,
				'Wp_ContentProtect'	//our page name in settings menu (from add_options_page())
			);
			//protected for
			add_settings_field(
				'protect_for',
				__('Restrict content', 'WP_ContentProtect_Textdomain'),
				array($this, 'settings_field_protect_for' ),
				'Wp_ContentProtect',
				'Wp_ContentProtect_settings',
				array(
					'<a href="http://php.net/manual/en/datetime.formats.relative.php" target="_blank">'.__('Help', 'WP_ContentProtect_Textdomain').'</a>'
				)
			);
			//time format
			add_settings_field(
				'time_format',
				__('Datetime formating', 'WP_ContentProtect_Textdomain'),
				array($this, 'settings_field_time_format' ),
				'Wp_ContentProtect',
				'Wp_ContentProtect_settings',
				array(
					'<a href="http://php.net/manual/en/function.date.php" target="_blank">'.__('Help', 'WP_ContentProtect_Textdomain').'</a>'
				)
			);
			//display in listings
			add_settings_field(
				'listings_col',
				__('Custom column', 'WP_ContentProtect_Textdomain'),
				array($this, 'settings_field_listings_col' ),
				'Wp_ContentProtect',
				'Wp_ContentProtect_settings',
				array(
					__('Display custom column with content status in listings', 'WP_ContentProtect_Textdomain')
				)
			);
			//protect by default toggler
			add_settings_field(
				'protect_by_default',
				__('Protect by default', 'WP_ContentProtect_Textdomain'),
				array($this, 'settings_field_protect_by_default' ),
				'Wp_ContentProtect',
				'Wp_ContentProtect_settings',
				array(
					__('Protect new, pending and future content by default', 'WP_ContentProtect_Textdomain')
				)
			);
			//register the settings
			register_setting(
				'Wp_ContentProtect_settings',
				'Wp_ContentProtect_settings',
				array( $this, 'settings_post_validation' )
			);
		}

		/*--------------------------------------------*
		 * Settings page post validation
		 *--------------------------------------------*/
		public function settings_post_validation($input){
			//validate
			$validated = array(
				'protect_for' => $this->settings['protect_for'],
				'time_format' => $this->settings['time_format'],
				'listings_col' => (isset($input['listings_col']) && $input['listings_col']==1)? 1:0,
                'protect_by_default' => (isset($input['protect_by_default']) && $input['protect_by_default']==1)? 1:0
			);

            //
            if( isset($input['protect_for']) &&
               !empty($input['protect_for']) &&
               strtotime($input['protect_for'], current_time('timestamp')) > current_time('timestamp')
              ){
                $validated['protect_for'] = $input['protect_for'];
            }

            //
            if( isset($input['time_format']) &&
               !empty($input['time_format']) &&
               strtotime(date($input['time_format']))
              ){
                $validated['time_format'] = $input['time_format'];
            }

            return $validated;
        }

		/*--------------------------------------------*
		 * Settings page protect for def value
		 *--------------------------------------------*/
		public function settings_field_protect_for($args){
			$html = '<input type="text" id="protect_for" class="regular-text" name="Wp_ContentProtect_settings[protect_for]" value="'.$this->settings['protect_for'].'"/>';
			$html .= '<label for="protect_for"> '  . $args[0] . '</label>';
			echo $html;
		}
		/*--------------------------------------------*
		 * Settings page timeformating def value
		 *--------------------------------------------*/
		public function settings_field_time_format($args){
			$html = '<input type="text" id="time_format" class="regular-text" name="Wp_ContentProtect_settings[time_format]" value="'.$this->settings['time_format'].'"/>';
			$html .= '<label for="time_format"> '  . $args[0] . '</label>';
			echo $html;
		}
		/*--------------------------------------------*
		 * Settings page listings_col checkbox
		 *--------------------------------------------*/
		public function settings_field_listings_col($args){
			$html = '<input type="checkbox" id="listings_col" name="Wp_ContentProtect_settings[listings_col]" value="1" ' . checked($this->settings['listings_col'], 1, false) . '/>';
			$html .= '<label for="listings_col"> '  . $args[0] . '</label>';
			echo $html;
		}
		/*--------------------------------------------*
		 * Settings page protect_by_default checkbox
		 *--------------------------------------------*/
		public function settings_field_protect_by_default($args){
			$html = '<input type="checkbox" id="protect_by_default" name="Wp_ContentProtect_settings[protect_by_default]" value="1" ' . checked($this->settings['protect_by_default'], 1, false) . '/>';
			$html .= '<label for="protect_by_default"> '  . $args[0] . '</label>';
			echo $html;
		}

		/*--------------------------------------------*
		 * Handles plugin script and styles loading
         * under the admin area.
		 *--------------------------------------------*/
        public function manageAssets(){
            global $pagenow;
            if( $pagenow == 'post-new.php' || $pagenow == 'post.php' ){
                // Load control GUI assets.
				wp_register_style( 'WPCP_controlGUI', plugins_url() . '/' . $this->DIR . '/admin/css/controlGUI.css', false );
				wp_enqueue_style( 'WPCP_controlGUI' );
            }
            if( $pagenow == 'edit.php'){
                // Table related CSS
				wp_register_style( 'WPCP_listinsCSS', plugins_url() . '/' . $this->DIR . '/admin/css/listingsCSS.css', false );
				wp_enqueue_style( 'WPCP_listinsCSS' );
            }
        }

		/*--------------------------------------------*
		 * Add plugin's control box
         * into edit post/page GUIs.
		 *--------------------------------------------*/
        public function editGUI_meta_box($post_type){
            if( $post_type != 'attachment' )
                add_meta_box(
                    'WPCP_ctrl',
                    __('Content Protect', 'WP_ContentProtect_Textdomain'),
                    array($this, 'renderer_editGUI_meta_box'),
                    $post_type,
                    'side'
                );
        }

		/*--------------------------------------------*
		 * Actual renderer for the control meta box
         * displayed on edit post/page GUIs.
		 *--------------------------------------------*/
        public function renderer_editGUI_meta_box($post){
            // Add security nonce to our control box.
            wp_nonce_field('editGUI_meta_box', 'editGUI_meta_box_nonce');
            // Get plugin internals for content.
            $meta_wpcp = get_post_meta( $post -> ID, self::meta_wpcp, true);
            $meta_protected_until = get_post_meta( $post -> ID, self::meta_protected_until, true);

            // Render the control GUI.
            include 'admin/control_metabox.php';
        }

		/*--------------------------------------------*
		 * After content(post/page/custom) was saved to the db event.
         * Can be triggered not only from the edit page!
         * So plugin posted form may not exists!
		 *--------------------------------------------*/
        public function updateWPCP_asset($post_id){
            // Check if this came from plugin screen because this hook
            // can be triggered at other times.
            if( ! isset($_POST['editGUI_meta_box_nonce']) )
                // It was not, just pass further...
                return;
            // Verify if the plugin nonce presented here is valid.
            if( ! wp_verify_nonce($_POST['editGUI_meta_box_nonce'], 'editGUI_meta_box') )
                // Ops, it is not. Pass furhter...
                return;
            // When doing autosave the plugin form won't be submitted either.
            // Thus again we do nothing.
            if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return;
            // If this is just a revision, again pass further.
            if( wp_is_post_revision($post_id) )
                return;

            // When still here let's do some work...
            $post = get_post($post_id);
            $the_time_is = current_time('timestamp');
            $meta_protected_until = get_post_meta( $post -> ID, self::meta_protected_until, true);
            $meta_wpcp = get_post_meta( $post -> ID, self::meta_wpcp, true);
            $meta_wpcp = is_array($meta_wpcp)? $meta_wpcp:array();
            $meta_wpcp = array_merge($meta_wpcp, array(
                'last_edited_by' => wp_get_current_user() -> user_login
            ));

            // Process the `protect_for` input if present.
            if( isset($_POST['wpcp_protect_for']) ){
                $protect_util = strtotime( sanitize_text_field($_POST['wpcp_protect_for']), $the_time_is );
                if( ! $protect_util || $protect_util == -1 || $protect_util <= $the_time_is ){
                    // Processing failed due to invalid input or time value in the past given by the user.
                    // Thus just use the default value from the config.
                    $protect_util = strtotime( $this -> settings['protect_for'], $the_time_is );
                    $in_protect_for = $this -> settings['protect_for'];
                }else{
                    // Processing is valid.
                    $in_protect_for = sanitize_text_field($_POST['wpcp_protect_for']);
                }
                // Add it for saving/updating.
                $meta_wpcp = array_merge($meta_wpcp, array('protect_for' => $in_protect_for));
            }

            // On any save...
            if( isset($protect_util) && $post -> post_status == 'publish' ){
                if( isset($_POST['wpcp_remove_protect']) ){
                    $meta_wpcp = array_merge($meta_wpcp, array('protect_removed_at' => $the_time_is));
                    delete_post_meta($post_id, self::meta_protected_until);
                }else if( ! isset($meta_wpcp['protected_at']) || isset($_POST['wpcp_add_protect']) || isset($_POST['wpcp_protect_indef']) ){
                    $meta_wpcp = array_merge($meta_wpcp, array('protected_at' => $the_time_is));
                    update_post_meta($post_id, self::meta_protected_until, isset($_POST['wpcp_protect_indef'])? PHP_INT_MAX:$protect_util);
                }
            }
            update_post_meta($post_id, self::meta_wpcp, $meta_wpcp);
        }

		/*--------------------------------------------*
		 * Futured to publish status handler.
		 *--------------------------------------------*/
        public function future_to_publish($post){
            // Get plugin internals for content.
            $meta_wpcp = get_post_meta( $post -> ID, self::meta_wpcp, true);
            // Calc protect end time.
            $protect_until = strtotime($meta_wpcp['protect_for'], current_time('timestamp'));
            if($protect_until){
                // Protect it.
                update_post_meta($post -> ID, self::meta_protected_until, $protect_until);
                // Update internals.
                $meta_wpcp['protected_at'] = current_time('timestamp');
                update_post_meta($post -> ID, self::meta_wpcp, $meta_wpcp);
            }
        }

        // FILTERING HANDLERS
        // ---

		/*--------------------------------------------*
		 * The actual filtering of content hooks before
         * query is executed.
		 *--------------------------------------------*/
        public function apply_query_filtering($query){
            if( !is_user_logged_in() && !is_singular() )
                $query -> set('meta_query', array(
                    'relation' => 'OR',
                    array(
                        'key' => self::meta_protected_until,
                        'value' => 'bug#23268',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => self::meta_protected_until,
                        'value' => current_time('timestamp'),
                        'compare' => '<'
                    )
                ));
        }

		/*--------------------------------------------*
		 * Filtering of ptotected items in wp nav menu
		 *--------------------------------------------*/
        public function apply_navmenu_filtering($items){
            if( is_user_logged_in() ) return $items;
            foreach($items as $key => $item){
                if( get_post_meta( $item -> object_id, self::meta_protected_until, true) > current_time('timestamp') )
                    unset($items[$key]);
            }
            return $items;
        }

		/*--------------------------------------------*
		 * Apply filtyering to the archives results.
		 *--------------------------------------------*/
        public function apply_archives_filtering($where){
            if( is_user_logged_in() ) return $where;
            global $wpdb;
            $tbl_name = $wpdb->prefix.'postmeta';
            return $where." AND ID NOT IN (SELECT $tbl_name.post_id FROM $tbl_name WHERE $tbl_name.meta_key = '".self::meta_protected_until."' AND $tbl_name.meta_value  > '".current_time('timestamp')."')";
        }

		/*--------------------------------------------*
		 * Apply filtering to pages collections
		 *--------------------------------------------*/
        public function apply_links_filtering($items){
            if( is_user_logged_in() ) return $items;
            foreach($items as $key => $item){
                if( get_post_meta( $item -> ID, self::meta_protected_until, true) > current_time('timestamp') )
                    unset($items[$key]);
            }
            return $items;
        }

		/*--------------------------------------------*
		 * Fills the custom column in the post/page listings with data
		 *--------------------------------------------*/
        public function fillProtectedColumn($column, $post_id){
            if($column == 'WPCP_protected'){
                $meta_protected_until = get_post_meta( $post_id, self::meta_protected_until, true);
                if($meta_protected_until > current_time('timestamp')){
                    if( $this->minVer('3.8') )
                        echo '<i class="dashicons dashicons-lock" title="',
                                sprintf(__('Protected for %suntil %s', 'WP_ContentProtect_Textdomain'),
                                    human_time_diff($meta_protected_until, current_time('timestamp'))."\n",
                                    date($this -> settings['time_format'], $meta_protected_until)
                               ),
                            '"></i>';
                    else{
                        // Handle case when dashicons not available.

                    }
                }
            }
        }

		/*--------------------------------------------*
		 * Adds a custom column to the post/page listings
		 *--------------------------------------------*/
        public function addProtectedColumn($columns){
            return
                array_slice($columns, 0, 1, true) +
                array('WPCP_protected' => '') +
                array_slice($columns, 1, count($columns)-1, true);
        }

		/*--------------------------------------------*
         * Plugin's last stand of defence.
		 *--------------------------------------------*/
        public function preventDirectContAccess($template){
            // Trigger only for anonymous users
            // as we want to redirect them to login page if they try to direct access content.
            if( ! is_user_logged_in() ){
                // Conditions and content types to redirect for.
                if( is_singular() ){
                    // Get the current post object.
                    $post = get_post();
                    // Check for protection enabled.
                    $meta_protected_until = get_post_meta( $post -> ID, self::meta_protected_until, true);
                    if( $meta_protected_until > current_time('timestamp') ){
                        // Yes protected.
                        auth_redirect();
                    }
                }
            }
            // Otherwise just do nothing.
            return $template;
        }

    }

	//main entry point
	//create instance of WP_ContentProtect
	WP_ContentProtect::getInstance();
};
