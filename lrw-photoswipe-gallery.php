<?php
/**
 * Plugin Name:       LRW PhotoSwipe Gallery
 * Plugin URI:        https://github.com/luizrw
 * Description:       Plugin for implement PhotoSwipe JS plugin in default galleries WordPress.
 * Version:           1.0.0
 * Author:            LRW
 * Author URI:        https://github.com/luizrw
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lrw-photoswipe-gallery
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'LRW_Photoswipe_Gallery' ) ) :

    /**
     * Main class
     */
    class LRW_Photoswipe_Gallery {
        /**
         * Instance of this class.
         *
         * @var object
         */
        private static $instance = null;

        protected $photoswipe_settings;

        /**
         * Return an instance of this class.
         *
         * @return object A single instance of this class.
         */
        public static function get_instance() {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }

        /**
         * Initialize the plugin public actions.
         */
        private function __construct() {
            add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

            $this->do_plugin_settings();

            add_action( 'admin_init', array( &$this, 'admin_init' ) );
            add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'plugin_action_links' ) );

            // Apply filter to default gallery shortcode
    		add_filter( 'wp_get_attachment_link', array( &$this, 'data_size' ), 10, 6 );
            add_filter( 'post_gallery', array( &$this, 'photoswipe_gallery' ), 10, 2 );
            add_filter( 'wp_enqueue_scripts', array( &$this, 'setup_scripts' ) );
            add_filter( 'wp_footer', array( &$this, 'photoswipe_html_footer' ) );
            add_filter( 'wp_footer', array( &$this, 'photoswipe_ui_options' ) );
        }

        /**
         * Load the plugin text domain for translation.
         */
        public function load_plugin_textdomain() {
            $domain = 'lrw-photoswipe-gallery';
            $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

            load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . 'lrw-photoswipe-gallery/lrw-photoswipe-gallery-' . $locale . '.mo' );
            load_plugin_textdomain( 'lrw-photoswipe-gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }

        /**
         *
         */
        public function do_plugin_settings() {
            if ( false == get_option( 'photoswipe_settings' ) ) {

                add_option( 'photoswipe_settings' );

                $default = array(
                    'active_conditional'    => 1,
                    'loop_images'           => 1,
                    'masonry_active'        => '',
                    'close_button'          => 1,
                    'fullscreen_button'     => 1,
                    'zoom_button'           => 1,
                    'share_button'          => 1,
                    'image_counter'         => 1,
                    'arrow_button'          => 1,
                    'image_preloader'       => 1,
                );

                update_option( 'photoswipe_settings', $default );
            }

            $this->photoswipe_settings = get_option( 'photoswipe_settings' );
        }

        /**
         * Plugin settings form fields.
         */
        function admin_init() {
            add_settings_section(
                'general_settings',
                __( 'General settings', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_section_text' ),
                'lrw-photoswipe-gallery'
            );

            add_settings_field(
                'active_conditional',
                __( 'Active only in singular posts:', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_active_conditional' ),
                'lrw-photoswipe-gallery',
                'general_settings'
            );

            add_settings_section(
                'ui_settings',
                __( 'Setup UI PhotoSwipe', 'lrw-photoswipe-gallery' ),
                '__return_false',
                'lrw-photoswipe-gallery'
            );

            add_settings_field(
                'close_button',
                __( 'Display close button:', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_close_button' ),
                'lrw-photoswipe-gallery',
                'ui_settings'
            );

            add_settings_field(
                'fullscreen_button',
                __( 'Display fullscreen button:', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_fullscreen_button' ),
                'lrw-photoswipe-gallery',
                'ui_settings'
            );

            add_settings_field(
                'zoom_button',
                __( 'Display zoom button:', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_zoom_button' ),
                'lrw-photoswipe-gallery',
                'ui_settings'
            );

            add_settings_field(
                'share_button',
                __( 'Display share button:', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_share_button' ),
                'lrw-photoswipe-gallery',
                'ui_settings'
            );

            add_settings_field(
                'image_counter',
                __( 'Display image counter:', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_counter' ),
                'lrw-photoswipe-gallery',
                'ui_settings'
            );

            add_settings_field(
                'arrow_button',
                __( 'Display arrow navigation:', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_arrow' ),
                'lrw-photoswipe-gallery',
                'ui_settings'
            );

            add_settings_field(
                'image_preloader',
                __( 'Display image preloader:', 'lrw-photoswipe-gallery' ),
                array( &$this, 'html_input_preloader' ),
                'lrw-photoswipe-gallery',
                'ui_settings'
            );

            register_setting(
                'lrw-photoswipe-gallery',
                'photoswipe_settings'
            );
        }

        /**
         * Section callback
         */
        public function html_input_section_text() {
            $html = '<p>' . __( 'This configurations are optionals, fell free to change.', 'lrw-photoswipe-gallery' ) . '</p>';

            echo $html;
        }

        /**
         * Loop images
         */
        public function html_input_active_conditional() {
            // $checked = ( isset ( $this->photoswipe_settings['loop_images'] ) ) ? ' checked="checked" ' : '';
            $html = '<label><input type="checkbox" id="active_conditional" name="photoswipe_settings[active_conditional]" value="1"' . checked( 1, isset( $this->photoswipe_settings['active_conditional'] ), false ) . ' />' . __( 'If true, the plugin scripts enqueue only in singular posts. If not, enqueue in all site.', 'lrw-photoswipe-gallery' ) . '</label>';

            echo $html;
        }

        /**
         * Close button
         */
        public function html_input_close_button() {
            $html = '<label><input type="checkbox" id="close_button" name="photoswipe_settings[close_button]" value="1" ' . ( checked( 1, isset( $this->photoswipe_settings['close_button'] ), false ) ). ' /></label>';

            echo $html;
        }

        /**
         * Fullscreen button
         */
        public function html_input_fullscreen_button() {
            $html = '<label><input type="checkbox" id="fullscreen_button" name="photoswipe_settings[fullscreen_button]" value="1" ' . ( checked( 1, isset( $this->photoswipe_settings['fullscreen_button'] ), false ) ). ' /></label>';

            echo $html;
        }

        /**
         * Zoom
         */
        public function html_input_zoom_button() {
            $html = '<label><input type="checkbox" id="zoom_button" name="photoswipe_settings[zoom_button]" value="1" ' . ( checked( 1, isset( $this->photoswipe_settings['zoom_button'] ), false ) ). ' /></label>';

            echo $html;
        }

        /**
         * Share
         */
        public function html_input_share_button() {
            $html = '<label><input type="checkbox" id="share_button" name="photoswipe_settings[share_button]" value="1" ' . ( checked( 1, isset( $this->photoswipe_settings['share_button'] ), false ) ). ' /></label>';

            echo $html;
        }

        /**
         * Image counter
         */
        public function html_input_counter() {
            $html = '<label><input type="checkbox" id="image_counter" name="photoswipe_settings[image_counter]" value="1" ' . ( checked( 1, isset( $this->photoswipe_settings['image_counter'] ), false ) ). ' /></label>';

            echo $html;
        }

        /**
         * Arrow nav
         */
        public function html_input_arrow() {
            $html = '<label><input type="checkbox" id="arrow_button" name="photoswipe_settings[arrow_button]" value="1" ' . ( checked( 1, isset( $this->photoswipe_settings['arrow_button'] ), false ) ). ' /></label>';

            echo $html;
        }

        /**
         * Image preloader
         */
        public function html_input_preloader() {
            $html = '<label><input type="checkbox" id="image_preloader" name="photoswipe_settings[image_preloader]" value="1" ' . ( checked( 1, isset( $this->photoswipe_settings['image_preloader'] ), false ) ). ' /></label>';

            echo $html;
        }

        /**
         * Add the settings page.
         */
        function admin_menu(){
            add_submenu_page(
                'options-general.php',
                __( 'LRW PhotoSwipe Gallery', 'lrw-photoswipe-gallery' ),
                __( 'LRW PhotoSwipe Gallery', 'lrw-photoswipe-gallery' ),
                'administrator',
                'lrw-photoswipe-gallery',
                array( &$this, 'html_form_settings' )
            );
        }

        /**
         * Action links.
         *
         * @param  array $links
         *
         * @return array
         */
        public function plugin_action_links( $links ) {
            $plugin_links   = array();
            $plugin_links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=lrw-photoswipe-gallery' ) ) . '">' . __( 'Settings', 'lrw-photoswipe-gallery' ) . '</a>';
            return array_merge( $plugin_links, $links );
        }

        /**
         * Render the settings page for this plugin.
         */
        public function html_form_settings(){
        ?>
            <div class="wrap">
                <div id="icon-options-general" class="icon32"></div>
                <h2><?php _e( 'Gallery Settings' ); ?></h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields( 'lrw-photoswipe-gallery' );
                    do_settings_sections( 'lrw-photoswipe-gallery' );
                    submit_button();
                    ?>
                </form>
            </div>
        <?php
        }

        /**
         * Enqueue scripts
         */
        function setup_scripts() {
            $options = get_option('photoswipe_settings');

            if ( $options['active_conditional'] == 1 ) {
                if ( is_singular() ) {
                    wp_enqueue_script( 'photoswipe-lib', plugin_dir_url( __FILE__ ) . 'assets/js/photoswipe.min.js', array() );
                    wp_enqueue_script( 'photoswipe', plugin_dir_url( __FILE__ ) . 'assets/js/photoswipe-init.js', array( 'photoswipe-lib', 'jquery' ) );
                    wp_enqueue_style( 'photoswipe-lib', plugin_dir_url( __FILE__ ) . 'assets/css/photoswipe.css', false );
                    wp_enqueue_style( 'photoswipe-default-skin', plugin_dir_url( __FILE__ ) . 'assets/css/default-skin/default-skin.css ', false );
                }
            }
        }

        /**
         * Include default ui settings for PhotoSwipe
         */
        function photoswipe_ui_options() {
            include_once( 'includes/photoswipe-ui-default.php' );
        }

        /**
         * Add HTML necessary for galleries
         */
        function photoswipe_html_footer() {
            ?>
            <!-- Root element of PhotoSwipe. Must have class pswp. -->
            <div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
                <!-- Background of PhotoSwipe.
                It's a separate element, as animating opacity is faster than rgba(). -->
                <div class="pswp__bg"></div>

                <!-- Slides wrapper with overflow:hidden. -->
                <div class="pswp__scroll-wrap">

                    <!-- Container that holds slides. PhotoSwipe keeps only 3 slides in DOM to save memory. -->
                    <!-- don't modify these 3 pswp__item elements, data is added later on. -->
                    <div class="pswp__container">
                        <div class="pswp__item"></div>
                        <div class="pswp__item"></div>
                        <div class="pswp__item"></div>
                    </div>

                    <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
                    <div class="pswp__ui pswp__ui--hidden">
                        <div class="pswp__top-bar">

                            <!--  Controls are self-explanatory. Order can be changed. -->
                            <div class="pswp__counter"></div>
                            <button class="pswp__button pswp__button--close" title="<?php _e( 'Close (Esc)', 'lrw-photoswipe-gallery' ); ?>"></button>
                            <button class="pswp__button pswp__button--share" title="<?php _e( 'Share', 'lrw-photoswipe-gallery' ); ?>"></button>
                            <button class="pswp__button pswp__button--fs" title="<?php _e( 'Toggle fullscreen', 'lrw-photoswipe-gallery' ); ?>"></button>
                            <button class="pswp__button pswp__button--zoom" title="<?php _e( 'Zoom in/out', 'lrw-photoswipe-gallery' ); ?>"></button>

                            <!-- Preloader demo http://codepen.io/dimsemenov/pen/yyBWoR -->
                            <!-- element will get class pswp__preloader--active when preloader is running -->
                            <div class="pswp__preloader">
                                <div class="pswp__preloader__icn">
                                  <div class="pswp__preloader__cut">
                                    <div class="pswp__preloader__donut"></div>
                                  </div>
                                </div>
                            </div>
                        </div>

                        <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                            <div class="pswp__share-tooltip"></div>
                        </div>

                        <button class="pswp__button pswp__button--arrow--left" title="<?php _e( 'Previous (arrow left)', 'lrw-photoswipe-gallery' ); ?>">
                        </button>

                        <button class="pswp__button pswp__button--arrow--right" title="<?php _e( 'Next (arrow right)', 'lrw-photoswipe-gallery' ); ?>">
                        </button>

                        <div class="pswp__caption">
                            <div class="pswp__caption__center"></div>
                        </div>

                      </div>

                </div>
            </div>
            <!-- End gallery markup -->
            <?php
        }

        /**
         * Filter image attributes to include data-size element
         */
        function data_size ( $html, $id, $size, $permalink, $icon, $text ) {
            if ( $permalink ) {
                return $html;
            }

            $image_attributes = wp_get_attachment_image_src( $id, 'full' );
            if ( $image_attributes ) {
                $html = preg_replace( "/<a/","<a data-size=\"" . $image_attributes[1] . "x" . $image_attributes[2] . "\"", $html, 1 );
            }

            return $html;
        }

        /**
         * Custom filter function to modify default gallery shortcode output
         */
    	function photoswipe_gallery( $output, $attr ) {

    		// Initialize
    		global $post, $wp_locale;

    		// Gallery instance counter
    		static $instance = 0;
    		$instance++;

    		// Validate the author's orderby attribute
    		if ( isset( $attr['orderby'] ) ) {
    			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
    			if ( ! $attr['orderby'] ) unset( $attr['orderby'] );
    		}

    		// Get attributes from shortcode
    		extract(
    			shortcode_atts(
    				array(
    					 'order'      => 'ASC',
    					 'orderby'    => 'menu_order ID',
    					 'id'         => $post->ID,
    					 'itemtag'    => 'figure',
    					 'icontag'    => 'dt',
    					 'captiontag' => 'figcaption',
    					 'columns'    => 3,
    					 'size'       => 'thumbnail',
    					 'include'    => '',
    					 'exclude'    => ''
    				 ), $attr
    				)
    			);

    		// Initialize
    		$id = intval( $id );
    		$attachments = array();
    		if ( $order == 'RAND' ) $orderby = 'none';

    		if ( ! empty( $include ) ) {

    			// Include attribute is present
    			$include = preg_replace( '/[^0-9,]+/', '', $include );
    			$_attachments = get_posts( array( 'include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby ) );

    			// Setup attachments array
    			foreach ( $_attachments as $key => $val ) {
    				$attachments[ $val->ID ] = $_attachments[ $key ];
    			}

    		} else if ( ! empty( $exclude ) ) {

    			// Exclude attribute is present
    			$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );

    			// Setup attachments array
    			$attachments = get_children( array( 'post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby ) );
    		} else {
    			// Setup attachments array
    			$attachments = get_children( array( 'post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby ) );
    		}

    		if ( empty( $attachments ) ) return '';

    		// Filter gallery differently for feeds
    		if ( is_feed() ) {
    			$output = "\n";
    			foreach ( $attachments as $att_id => $attachment ) $output .= wp_get_attachment_link( $att_id, $size, true ) . "\n";
    			return $output;
    		}

    		// Filter tags and attributes
    		$itemtag = tag_escape( $itemtag );
    		$captiontag = tag_escape( $captiontag );
    		$columns = intval( $columns );
    		$itemwidth = $columns > 0 ? floor( 100 / $columns ) : 100;
    		$float = is_rtl() ? 'right' : 'left';
    		$selector = "gallery-{$instance}";

    		// Filter gallery CSS
    		$output = apply_filters( 'gallery_style', "
    			<style type='text/css'>
    				#{$selector} {
    					/* Masonry container */
    					-moz-column-gap: 1em;
    					-webkit-column-gap: 1em;
    					column-gap: 1em;
    				}

    				#{$selector} .gallery-item {
    					display: inline-block;
    					margin: 0 0 1em;
        				width: 100%;
    				}

    				#{$selector} .gallery-item img{
    					width: 100%;
    					height: auto;
    				}

    				/* #2- Portrait tablet to landscape and desktop */
    				@media (min-width: 768px){
    					#{$selector} {
    						-moz-column-count: {$columns} !important;
    						-webkit-column-count: {$columns} !important;
    						column-count: {$columns} !important;
    					}
    				}

    				/* #3- Landscape phone to portrait tablet */
    				@media (min-width: 481px) and (max-width: 767px) {
    					#{$selector} {
    						-moz-column-count: 3 !important;
    						-webkit-column-count: 3 !important;
    						column-count: 3 !important;
    					}
    				}

    				/* #4- Landscape phones and down */
    				@media (max-width: 480px) {
    					#{$selector} {
    						-moz-column-count: 2 !important;
    						-webkit-column-count: 2 !important;
    						column-count: 2 !important;
    					}
    				}
    			</style>
    			<!-- see gallery_shortcode() in wp-includes/media.php -->
    			<div id='$selector' class='gallery lrw-photoswipe-gallery galleryid-{$id}' itemscope itemtype='http://schema.org/ImageGallery'>"
    		);

    		// Iterate through the attachments in this gallery instance
    		$i = 0;
    		foreach ( $attachments as $id => $attachment ) {

    			// Attachment link
    			$link = isset( $attr['link'] ) && 'file' == $attr['link'] ? wp_get_attachment_link( $id, $size, false, false ) : wp_get_attachment_link( $id, $size, true, false );

    			// Start itemtag
    			$output .= "<{$itemtag} class='gallery-item' itemscope itemtype='http://schema.org/ImageObject'>";

    			// icontag
    			$output .= $link;

    			if ( $captiontag && trim( $attachment->post_excerpt ) ) {

    				// captiontag
    				$output .= "
    				<{$captiontag} class='gallery-caption'>
    					" . wptexturize($attachment->post_excerpt) . "
    				</{$captiontag}>";
    			}

    			// End itemtag
    			$output .= "</{$itemtag}>";
    		}

    		// End gallery output
    		$output .= "</div>\n";

    		return $output;
    	}

    }

    add_action( 'plugins_loaded', array( 'LRW_Photoswipe_Gallery', 'get_instance' ), 0 );

endif;