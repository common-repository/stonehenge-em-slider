<?php
/************************************************************
* Plugin Name:			Events Manager - Events / Locations Slider
* Description:			Create a dynamic Slider (carousel or fade) for Events and Locations with EM arguments you already know, using a simple shortcode.
* Version:				1.8.7
* Author:  				Stonehenge Creations
* Author URI: 			https://www.stonehengecreations.nl/
* Plugin URI: 			https://www.stonehengecreations.nl/creations/stonehenge-em-slider
* License URI: 			https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: 			stonehenge-em-slider
* Domain Path: 			/languages
* Requires at least: 	5.3
* Tested up to: 		5.9
* Requires PHP:			7.3
* Network:				false
************************************************************/
if( !defined('ABSPATH') ) exit;
include_once(ABSPATH.'wp-admin/includes/plugin.php');


#===============================================
function stonehenge_em_slider() {
	$wp 	= get_plugin_data( __FILE__ );
	$plugin = array(
		'name' 		=> $wp['Name'],
		'short' 	=> 'EM - Events Slider',
		'icon' 		=> '&#8646;',
		'slug' 		=> 'stonehenge_em_slider',
		'version' 	=> $wp['Version'],
		'text' 		=> $wp['TextDomain'],
		'class' 	=> 'Stonehenge_EM_Slider',
		'base' 		=> plugin_basename(__DIR__),
		'prio' 		=> 25,
	);
	$plugin['url'] 		= admin_url().'admin.php?page='.$plugin['slug'];
	$plugin['options'] 	= get_option( $plugin['slug'] );
	return $plugin;
}


#===============================================
add_action('plugins_loaded', function() {
	if( !function_exists('stonehenge')) { require_once('stonehenge/init.php'); }

	$plugin = stonehenge_em_slider();
	if( start_stonehenge($plugin) ) {
		new Stonehenge_EM_Slider();
	}
}, 22);


if( !class_exists('Stonehenge_EM_Slider')) :
Class Stonehenge_EM_Slider {

	var $plugin;
	var $text;

	#===============================================
	public function __construct() {
		$plugin = self::plugin();
		$slug 	= $plugin['slug'];
		$this->plugin 	= $plugin;
		$this->text 	= $plugin['text'];
		$this->is_ready = is_array($plugin['options']) ? true : false;

		add_filter("{$slug}_options", array($this, 'define_events_options'), 10);
		add_filter("{$slug}_options", array($this, 'define_locations_options'), 11);
		add_filter("{$slug}_options", array($this, 'define_general_options'), 12);

		if( $this->is_ready ) {
			add_shortcode('events_slider', array($this, 'show_events_slider'));
			add_shortcode('locations_slider', array($this, 'show_locations_slider'));
		}
	}


	#===============================================
	public static function plugin() {
		return stonehenge_em_slider();
	}


	#===============================================
	public static function dependency() {
		$dependency = array(
			'events-manager/events-manager.php' => 'Events Manager',
		);
		return $dependency;
	}


	#===============================================
	public static function plugin_updated() {
		return;
	}


	#===============================================
	public static function register_assets() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_style( 'em-slider-css', plugins_url( "assets/jquery.bxslider{$suffix}.css", __FILE__ ), null, '4.2.14', 'all' );
		wp_register_script( 'em-slider-js', plugins_url( "assets/jquery.bxslider{$suffix}.js", __FILE__ ), array( 'jquery' ), '4.2.14', true );
	}


	#===============================================
	public static function load_admin_assets() {
		return;
	}


	#===============================================
	public function load_public_assets() {
		wp_enqueue_style('em-slider-css');
		wp_enqueue_script('em-slider-js');
	}


	#===============================================
	public function define_events_options( $sections = array() ) {
		$sections['events'] = array(
			'id' 		=> 'events',
			'label'		=> 'Events Slider',
			'fields' 	=> array(
				array(
					'id' 		=> 'example',
					'label' 	=> __('Example', $this->text),
					'type' 		=> 'span',
					'default' 	=> '<b>[events_slider scope="future" category="-7"]</b>',
					'helper' =>
sprintf( __('This shortcode works almost exactly the same as %2$s, but shows a slider instead of a list. You can use the same <a href=%1$s> search attributes</a> in this shortcode.', $this->text), 'https://wp-events-plugin.com/documentation/event-search-attributes/', '<code>[events_list]</code>'),
				),
				array(
					'id' 		=> 'format',
					'label' 	=> __('Single Slide Format', $this->text),
					'type' 		=> 'textarea',
					'helper' 	=> sprintf( __('Define the layout of one single slide (one %s). It will be adapted to all other slides.'), __('event', 'events-manager') ) .' '. $this->events_placeholder_tip(),
					'required' 	=> true,
				),
				array(
					'id' 		=> 'mode',
					'label' 	=> __('Animation Mode', $this->text),
					'type' 		=> 'dropdown',
					'choices' 	=> array(
						'fade' 			=> __('Cross Fade', $this->text),
						'horizontal'	=> __('Horizontal Slide', $this->text),
						'vertical' 		=> __('Vertical Slide', $this->text),
					),
					'required' 	=> true,
					'helper' 	=> __('Type of transition between slides.', $this->text),
				),
				array(
					'id' 		=> 'speed',
					'label' 	=> __('Animation Speed', $this->text),
					'type' 		=> 'number',
					'after' 	=> 'ms',
					'required' 	=> true,
					'helper' 	=> '1 ms = 0.001 sec',
					'min' 		=> '500',
					'max' 		=> '120000',
				),
				array(
					'id' 		=> 'delay',
					'label' 	=> __('Animation Delay', $this->text),
					'type' 		=> 'number',
					'after' 	=> 'ms',
					'required' 	=> true,
					'helper' 	=> '1 ms = 0.001 sec',
					'min' 		=> '500',
					'max' 		=> '120000',
				),
				array(
					'id' 		=> 'autostart',
					'label' 	=> __('Auto Start', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'default' 	=> 'true',
				),
				array(
					'id' 		=> 'loop',
					'label' 	=> __('Infinite Loop', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'default' 	=> 'true',
				),
				array(
					'id' 		=> 'dots',
					'label' 	=> __('Show Dots', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'default' 	=> 'true',
				),
				array(
					'id' 		=> 'arrows',
					'label' 	=> __('Show Arrows', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'default' 	=> 'true',
				),
				array(
					'id' 		=> 'pause',
					'label' 	=> __('Pause on Hover', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'after' 	=> '<span class="description">' . __('On MouseOver only.', $this->text) .'</span>',
					'default' 	=> 'true',
				),
				array(
					'id'  		=> 'touch',
					'label' 	=> __('Enable Swipe', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'after' 	=> '<span class="description">' . __('If set, the slider will allow touch swipe transitions', $this->text) .'</span>',
					'default' 	=> 'true',
				),
			)
		);
		return $sections;
	}


	#===============================================
	public function define_locations_options( $sections = array() ) {
		$sections['locations'] = array(
			'id' 		=> 'locations',
			'label'		=> 'Locations Slider',
			'fields' 	=> array(
				array(
					'id' 		=> 'example',
					'label' 	=> __('Example', $this->text),
					'type' 		=> 'span',
					'default' 	=> '<b>[locations_slider limit="15" eventful="1"]</b>',
					'helper' => sprintf( __('This shortcode works almost exactly the same as %s, but shows a slider instead of a list.', $this->text), '<code>[locations_list]</code>') .' '. sprintf( __('You can use the same <a href=%s> search attributes</a> in this shortcode.', $this->text), 'https://wp-events-plugin.com/documentation/event-search-attributes/'),
				),
				array(
					'id' 		=> 'format',
					'label' 	=> __('Single Slide Format', $this->text),
					'type' 		=> 'textarea',
					'helper' 	=> sprintf( __('Define the layout of one single slide (one %s). It will be adapted to all other slides.'), __('location', 'events-manager') ) .' '. $this->locations_placeholder_tip(),
					'required' 	=> true,
				),
				array(
					'id' 		=> 'mode',
					'label' 	=> __('Animation Mode', $this->text),
					'type' 		=> 'dropdown',
					'choices' 	=> array(
						'fade' 			=> __('Cross Fade', $this->text),
						'horizontal'	=> __('Horizontal Slide', $this->text),
						'vertical' 		=> __('Vertical Slide', $this->text),
					),
					'required' 	=> true,
					'helper' 	=> __('Type of transition between slides.', $this->text),
				),
				array(
					'id' 		=> 'speed',
					'label' 	=> __('Animation Speed', $this->text),
					'type' 		=> 'number',
					'after' 	=> 'ms',
					'required' 	=> true,
					'helper' 	=> '1 ms = 0.001 sec',
					'min' 		=> '500',
					'max' 		=> '120000',
				),
				array(
					'id' 		=> 'delay',
					'label' 	=> __('Animation Delay', $this->text),
					'type' 		=> 'number',
					'after' 	=> 'ms',
					'required' 	=> true,
					'helper' 	=> '1 ms = 0.001 sec',
					'min' 		=> '500',
					'max' 		=> '120000',
				),
				array(
					'id' 		=> 'autostart',
					'label' 	=> __('Auto Start', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'default' 	=> 'true',
				),
				array(
					'id' 		=> 'loop',
					'label' 	=> __('Infinite Loop', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'default' 	=> 'true',
				),
				array(
					'id' 		=> 'dots',
					'label' 	=> __('Show Dots', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'default' 	=> 'true',
				),
				array(
					'id' 		=> 'arrows',
					'label' 	=> __('Show Arrows', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'default' 	=> 'true',
				),
				array(
					'id' 		=> 'pause',
					'label' 	=> __('Pause on Hover', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'after' 	=> '<span class="description">' . __('On MouseOver only.', $this->text) .'</span>',
					'default' 	=> 'true',
				),
				array(
					'id'  		=> 'touch',
					'label' 	=> __('Enable Swipe', $this->text),
					'type' 		=> 'toggle',
					'choices' 	=> array(
						'false'		=> __('No'),
						'true' 		=> __('Yes'),
					),
					'required' 	=> true,
					'after' 	=> '<span class="description">' . __('If set, the slider will allow touch swipe transitions', $this->text) .'</span>',
					'default' 	=> 'true',
				),
			)
		);
		return $sections;
	}


	#===============================================
	public function define_general_options( $sections = array() ) {
		$sections['general'] = array(
			'id' 		=> 'general',
			'label'		=> esc_attr__('Plugin Settings', $this->text),
			'fields' 	=> array(
				array(
					'id' 		=> 'delete',
					'label' 	=> __('Delete Data', $this->text),
					'type' 		=> 'toggle',
					'required'	=> true,
					'helper' 	=> __('Automatically delete all data from your database when you uninstall this plugin?', $this->text),
					'default' 	=> 'no',
				),
			)
		);
		return $sections;
	}


	#===============================================
	private function events_placeholder_tip() {
		$events_placeholders = sprintf('<a href=%s target="_blank">%s</a>',
			'https://wp-events-plugin.com/documentation/placeholders/',
			__('Event Related Placeholders', $this->text)
		);
		$locations_placeholders = sprintf('<a href=%s target="_blank">%s</a>',
			'https://wp-events-plugin.com/documentation/placeholders/',
			__('Location Related Placeholders', $this->text)
		);
		$events_placeholder_tip = " ". sprintf(__('This accepts %s and %s.', $this->text),$events_placeholders, $locations_placeholders);
		return $events_placeholder_tip;
	}


	#===============================================
	private function locations_placeholder_tip() {
		$locations_placeholders = sprintf('<a href=%s target="_blank">%s</a>',
			'https://wp-events-plugin.com/documentation/placeholders/',
			__('Location Related Placeholders', $this->text)
		);
		$locations_placeholder_tip = " ". sprintf(__('This accepts %s.', $this->text), $locations_placeholders);
		return $locations_placeholder_tip;
	}


	#===============================================
	public function process_events_options() {
		$saved 	= $this->plugin['options']['events'];
		ob_start();
		?><script>
		jQuery(document).ready(function($){
			$('#em-events-slider').bxSlider({
				mode: '<?php echo $saved['mode']; ?>',
				speed: '<?php echo $saved['speed']; ?>',
				pause: '<?php echo $saved['delay']; ?>',
				pager: <?php echo $saved['dots']; ?>,
				pagerType: 'full',
				infiniteLoop: '<?php echo $saved['loop']; ?>',
				controls: <?php echo $saved['arrows']; ?>,
				autoHover: <?php echo $saved['pause']; ?>,
				auto: '<?php echo $saved['autostart']; ?>',
				autoStart:  <?php echo $saved['autostart']; ?>,
				autoDirection: 'next',
				stopAutoOnClick: false,
				autoDelay: '0',
				autoSlideForOnePage: false,
				useCSS: 'false',
				touchEnabled: <?php echo $saved['touch']; ?>,
				swipeThreshold: '60',
				oneToOneTouch: false,
			});
		});
		</script><?php
		$script = ob_get_clean();
		$script = stonehenge()->minify_js($script);
		echo $script;
	}


	#===============================================
	public function show_events_slider( $args ) {
		if( !$this->is_ready ) {
			return;
		}

		$saved 	= $this->plugin['options']['events'];

		if(	empty($args) ) {
			$args = array();
		}

		unset( $args['pagination'] );
		$Events = EM_Events::get($args, $count = false);
		if( count( (array) $Events) === '0' ) {
			$return = __('No events found matching your criteria.', $this->text);
		}
		else {
			ob_start();
			echo '<div class="bxslider" id="em-events-slider">';
			foreach( $Events as $Event ) {
				$EM_Event 	= new EM_Event($Event->event_id);
				$content 	= wpautop( $EM_Event->output( $saved['format'] ) );
				echo "<div>{$content}</div>";
			}
			echo '</div>';
			add_action('wp_footer', array($this, 'load_public_assets'), 15);
			add_action('wp_footer', array($this, 'process_events_options'), 30);
			$result = ob_get_clean();
		}
		return $result;
	}


	#===============================================
	public function process_locations_options() {
		$saved 	= $this->plugin['options']['locations'];

		ob_start();
		?><script>
		jQuery(document).ready(function($){
			$('#em-locations-slider').bxSlider({
				mode: '<?php echo $saved['mode']; ?>',
				speed: '<?php echo $saved['speed']; ?>',
				pause: '<?php echo $saved['delay']; ?>',
				pager: <?php echo $saved['dots']; ?>,
				pagerType: 'full',
				infiniteLoop: '<?php echo $saved['loop']; ?>',
				controls: <?php echo $saved['arrows']; ?>,
				autoHover: '<?php echo $saved['pause']; ?>',
				auto: '<?php echo $saved['autostart']; ?>',
				autoStart:  <?php echo $saved['autostart']; ?>,
				autoDirection: 'next',
				stopAutoOnClick: false,
				autoDelay: '0',
				autoSlideForOnePage: false,
				useCSS: 'false',
				touchEnabled: <?php echo $saved['touch']; ?>,
				swipeThreshold: '60',
				oneToOneTouch: false,
			});
		});
		</script><?php
		$script = ob_get_clean();
		$script = stonehenge()->minify_js($script);
		echo $script;
	}


	#===============================================
	public function show_locations_slider( $args ) {
		if( !$this->is_ready ) {
			return;
		}

		$saved 	= $this->plugin['options']['locations'];

		if(	empty($args)) {
			$args = array();
		}

		unset( $args['pagination'] );	// No EM pagination in the slider.
		$Locations = EM_Locations::get($args, $count = false);

		if( count( (array) $Locations) === '0' ) {
			$return = __('No locations found matching your criteria.', $this->text);
		}

		else {
			ob_start();
			echo '<div class="bxslider" id="em-locations-slider">';
			foreach( $Locations as $Location ) {
				$EM_Location 	= new EM_Location($Location->location_id);
				$content 		= wpautop( $EM_Location->output( $saved['format'] ) );
				echo "<div>{$content}</div>";
			}
			echo '</div>';
			add_action('wp_footer', array($this, 'load_public_assets'), 15);
			add_action('wp_footer', array($this, 'process_locations_options'), 30);
			$result = ob_get_clean();
		}
		return $result;
	}

} // End class.
endif;

