<?php

/**
 * Plugin Name: Free Shipping Banner
 * Plugin URI: https://pothirajblog.wordpress.com/
 * Description: Display a Free Shipping Banner at the top of your website.
 * Version: 1.0.0
 * Author: Pothiraj
 * Author URI: https://pothirajblog.wordpress.com/
 * License: GPL2
 *
 * @package Free Shipping Banner
 * @version 1.0.0
 * @author Pothiraj 
 */


register_activation_hook(__FILE__, 'shipping_banner_activate');
function shipping_banner_activate()
{
	add_action('admin_menu', 'shipping_banner_menu');
}

// Disabled Pages/Posts functionns
function get_disabled_pages_array()
{
	return array_filter(explode(',', get_option('disabled_pages_array')));
}
function get_post_object()
{
	return get_posts(array('include' => array(get_the_ID())));
}
function get_is_current_page_a_post()
{
	return !empty(get_post_object());
}
function get_disabled_on_posts()
{
	return get_option('disabled_on_posts');
}
function get_disabled_on_current_page()
{
	$disabled_on_current_page = (!empty(get_disabled_pages_array()) && in_array(get_the_ID(), get_disabled_pages_array()))
		|| (get_disabled_on_posts() && get_is_current_page_a_post());
	return $disabled_on_current_page;
}


add_action('wp_enqueue_scripts', 'shipping_banner');
function shipping_banner()
{
	// Enqueue the style
	wp_register_style('shipping-banner-style',  plugin_dir_url(__FILE__) . 'shipping-banner.css', '');
	wp_enqueue_style('shipping-banner-style');
	// Set Script parameters
	$disabled_on_current_page = get_disabled_on_current_page();
	$script_params = array(
		// script specific parameters
		'hide_shipping_banner' => get_option('hide_shipping_banner'),
		'shipping_banner_position' => get_option('shipping_banner_position'),
		'wholesale_amount' => get_option('wholesale_amount'),
		'retail_amount' => get_option('retail_amount'),
		'disabled_on_current_page' => $disabled_on_current_page,
		// debug specific parameters
		'debug_mode' => get_option('debug_mode'),
		'id' => get_the_ID(),
		'disabled_pages_array' => get_disabled_pages_array(),
		// 'post_object' => get_post_object(),
		'is_current_page_a_post' => get_is_current_page_a_post(),
		'disabled_on_posts' => get_disabled_on_posts(),
		'shipping_banner_font_size' => get_option('shipping_banner_font_size'),
		'shipping_banner_color' => get_option('shipping_banner_color'),
		'shipping_banner_text_color' => get_option('shipping_banner_text_color'),
		'shipping_banner_text' => $disabled_on_current_page ? '' : get_option('shipping_banner_text'),
		'shipping_banner_text2' => $disabled_on_current_page ? '' : get_option('shipping_banner_text2'),
		'shipping_banner_text3' => $disabled_on_current_page ? '' : get_option('shipping_banner_text3'),
		'shipping_banner_scrolling_custom_css' => get_option('shipping_banner_scrolling_custom_css'),

		'wp_body_open_enabled' => get_option('wp_body_open_enabled'),
		'wp_body_open' => function_exists('wp_body_open'),

	);
}

// Use `wp_body_open` action
if (function_exists('wp_body_open') && get_option('wp_body_open_enabled')) {
	add_action('wp_body_open', 'shipping_banner_body_open');
}
function shipping_banner_body_open()
{
	// if not disabled use wp_body_open
	$user_id = get_current_user_id();
	$user = new WP_User($user_id);
	$disabled_on_current_page = get_disabled_on_current_page();
	global $woocommerce;
	$carttotal = $woocommerce->cart->cart_contents_total;
	$wholesaleamount =  get_option('wholesale_amount');
	$retailamount = get_option('retail_amount');
	$user = wp_get_current_user();
	$userrole = $user->roles;
	$iswholesaler = current_user_can('wwp_wholesaler') && ($userrole);
	$goalvalue = ($iswholesaler ? $wholesaleamount : $retailamount);
	$messagetext = 0;
	if ($carttotal == 0) {
		$messagetext = 1;
	} elseif ($carttotal >= $goalvalue) {
		$messagetext = 3;
	} elseif ($goalvalue > $carttotal) {
		$messagetext = 2;
	}
	if (!$disabled_on_current_page) {
		if ($messagetext == 3) {
			echo '<div id="shipping-banner" class="shipping-banner"><div class="shipping-banner-text"><span>'
				. '<span id="currency" style="padding-right: 10px">'
				. get_option('shipping_banner_text3')
				. '</span>'
				. '</span></div></div>';
		} elseif ($messagetext == 2) {
			echo '<div id="shipping-banner" class="shipping-banner"><div class="shipping-banner-text"><span>'
				. '<span id="currency" style="padding-right: 10px">'
				. get_woocommerce_currency_symbol()
				. ($goalvalue - $carttotal)
				. '</span>'
				. get_option('shipping_banner_text2')
				. '</span></div></div>';
		} else {
			echo '<div id="shipping-banner" class="shipping-banner"><div class="shipping-banner-text"><span>'
				. '<span id="currency" style="padding-right: 10px">'
				. get_woocommerce_currency_symbol()
				. $goalvalue
				. '</span>'
				. get_option('shipping_banner_text')
				. '</span></div></div>';
		}
	}
}


// Add custom CSS/JS
add_action('wp_head', 'shipping_banner_custom_options');
function shipping_banner_custom_options()
{
	$disabled_on_current_page = get_disabled_on_current_page();
	$banner_is_disabled = $disabled_on_current_page || get_option('hide_shipping_banner') == "yes";

	if (!$banner_is_disabled && get_option('wholesale_amount') != "") {
		echo '<style id="shipping-banner-header-margin" type="text/css">header{margin-top:' . get_option('wholesale_amount') . ';}</style>';
	}

	if (!$banner_is_disabled && get_option('retail_amount') != "") {
		echo '<style id="shipping-banner-header-padding" type="text/css" >header{padding-top:' . get_option('retail_amount') . ';}</style>';
	}
	if (get_option('shipping_banner_font_size') != "") {
		echo '<style type="text/css">.shipping-banner .shipping-banner-text{font-size:' . get_option('shipping_banner_font_size') . ';}</style>';
	}

	if (get_option('shipping_banner_color') != "") {
		echo '<style type="text/css">.shipping-banner{background:' . get_option('shipping_banner_color') . ';}</style>';
	} else {
		echo '<style type="text/css">.shipping-banner{background: #024985;}</style>';
	}

	if (get_option('shipping_banner_text_color') != "") {
		echo '<style type="text/css">.shipping-banner .shipping-banner-text{color:' . get_option('shipping_banner_text_color') . ';}</style>';
	} else {
		echo '<style type="text/css">.shipping-banner .shipping-banner-text{color: #ffffff;}</style>';
	}
}

add_action('admin_menu', 'shipping_banner_menu');
function shipping_banner_menu()
{
	$manage_shipping_banner = 'manage_shipping_banner';
	$manage_options = 'manage_options';
	// Add admin access
	$admin = get_role('administrator');
	if ($admin) {
		$admin->add_cap($manage_shipping_banner);
	}

	$permissions_array = get_option('permissions_array');

	// Add permissions for other roles
	foreach (get_editable_roles() as $role_name => $role_info) {
		if ($role_name !== 'administrator') {
			if (in_array($role_name, explode(",", $permissions_array))) {
				$add_role = get_role($role_name);
				$add_role->add_cap($manage_shipping_banner);
				$add_role->add_cap($manage_options);
			} else {
				$remove_role = get_role($role_name);
				// only remove capabilities if they were previously added
				if ($remove_role->has_cap($manage_shipping_banner)) {
					$remove_role->remove_cap($manage_shipping_banner);
					$remove_role->remove_cap($manage_options);
				}
			}
		}
	}

	add_menu_page('Free Shipping Banner Settings', 'Free Shipping Banner', $manage_shipping_banner, 'shipping-banner-settings', 'shipping_banner_settings_page', plugins_url('/shipping-banner/img/icon.png'));
}


//script input sanitization function
function theme_slug_sanitize_js_code($input)
{
	return base64_encode($input);
}


//output escape function    
function theme_slug_escape_js_output($input)
{
	return esc_textarea(base64_decode($input));
}

add_action('admin_init', 'shipping_banner_settings');
function shipping_banner_settings()
{
	register_setting(
		'shipping-banner-settings-group',
		'hide_shipping_banner',
		array(
			'sanitize_callback' => 'wp_filter_nohtml_kses'
		)
	);
	register_setting(
		'shipping-banner-settings-group',
		'shipping_banner_font_size',
		array(
			'sanitize_callback' => 'wp_filter_nohtml_kses'
		)
	);
	register_setting(
		'shipping-banner-settings-group',
		'shipping_banner_color',
		array(
			'sanitize_callback' => 'wp_filter_nohtml_kses'
		)
	);
	register_setting(
		'shipping-banner-settings-group',
		'shipping_banner_text_color',
		array(
			'sanitize_callback' => 'wp_filter_nohtml_kses'
		)
	);
	register_setting(
		'shipping-banner-settings-group',
		'shipping_banner_text',
		array(
			'sanitize_callback' => 'wp_kses_post'
		)
	);
	register_setting(
		'shipping-banner-settings-group',
		'shipping_banner_text2',
		array(
			'sanitize_callback' => 'wp_kses_post'
		)
	);
	register_setting(
		'shipping-banner-settings-group',
		'shipping_banner_text3',
		array(
			'sanitize_callback' => 'wp_kses_post'
		)
	);
	register_setting(
		'shipping-banner-settings-group',
		'wholesale_amount',
		array(
			'sanitize_callback' => 'wp_filter_nohtml_kses'
		)
	);
	register_setting(
		'shipping-banner-settings-group',
		'retail_amount',
		array(
			'sanitize_callback' => 'wp_filter_nohtml_kses'
		)
	);
}

function shipping_banner_settings_page()
{
?>

	<style type="text/css" id="settings_stylesheet">
		.shipping-banner-settings-form th {
			width: 30%;
		}
	</style>

	<div class="wrap">
		<div style="display: flex;justify-content: space-between;">
			<h2>Free Shipping Banner Settings</h2>
		</div>
		<!-- Settings Form -->
		<form class="shipping-banner-settings-form" method="post" action="options.php">
			<?php settings_fields('shipping-banner-settings-group'); ?>
			<?php do_settings_sections('shipping-banner-settings-group'); ?>

			<table class="form-table">
				<!-- Hide -->
				<tr valign="top">
					<th scope="row">
						Hide Free Shipping Banner
					</th>
					<td style="vertical-align:top;">
						<!-- -->
						<input type="radio" id="yes" name="hide_shipping_banner" value="yes" <?php echo ((get_option('hide_shipping_banner') == 'yes') ? 'checked' : ''); ?>>
						<label for="yes">yes</label>
						<!-- -->
						<input type="radio" id="no" name="hide_shipping_banner" value="no" <?php echo ((get_option('hide_shipping_banner') == 'yes') ? '' : 'checked'); ?>>
						<label for="no">no</label>
						<!-- -->
					</td>
				</tr>
				<!-- Font Size -->
				<tr valign="top">
					<th scope="row">
						Free Shipping Banner Font Size
					</th>
					<td style="vertical-align:top;">
						<input type="text" id="shipping_banner_font_size" name="shipping_banner_font_size" placeholder="font-size" value="<?php echo esc_attr(get_option('shipping_banner_font_size')); ?>" />
						<span>e.g. 16px</span>
					</td>
				</tr>
				<!-- Background Color -->
				<tr valign="top">
					<th scope="row">
						Free Shipping Background Color
					</th>
					<td style="vertical-align:top;">
						<input type="text" id="shipping_banner_color" name="shipping_banner_color" placeholder="Hex value" value="<?php echo esc_attr(get_option('shipping_banner_color')); ?>" />
					</td>
				</tr>
				<!-- Text Color -->
				<tr valign="top">
					<th scope="row">
						Free Shipping Text Color
					</th>
					<td style="vertical-align:top;">
						<input type="text" id="shipping_banner_text_color" name="shipping_banner_text_color" placeholder="Hex value" value="<?php echo esc_attr(get_option('shipping_banner_text_color')); ?>" />
					</td>
				</tr>

				<!-- Text Contents -1 -->
				<tr valign="top">
					<th scope="row">
						Free Shipping Banner Text
					</th>
					<td>
						<textarea id="shipping_banner_text" class="large-text code" style="height: 150px;width: 97%;" name="shipping_banner_text"><?php echo esc_textarea(get_option('shipping_banner_text')); ?></textarea>
					</td>
				</tr>
				<!-- Text Contents-2 -->
				<tr valign="top">
					<th scope="row">
						Free Shipping Banner Text 2
					</th>
					<td>
						<textarea id="shipping_banner_text2" class="large-text code" style="height: 150px;width: 97%;" name="shipping_banner_text2"><?php echo esc_textarea(get_option('shipping_banner_text2')); ?></textarea>
					</td>
				</tr>
				<!-- Text Contents-3 -->
				<tr valign="top">
					<th scope="row">
						Free Shipping Banner Text 3
					</th>
					<td>
						<textarea id="shipping_banner_text3" class="large-text code" style="height: 150px;width: 97%;" name="shipping_banner_text3"><?php echo esc_textarea(get_option('shipping_banner_text3')); ?></textarea>
					</td>
				</tr>

				<!-- Wholesale Offer Amount -->
				<tr valign="top">
					<th scope="row">
						Wholesale Offer Amount
					</th>
					<td style="vertical-align:top;">
						<input type="text" id="wholesale_amount" name="wholesale_amount" placeholder="Wholesale Offer Amount" value="<?php echo esc_attr(get_option('wholesale_amount')); ?>" />
						<span>e.g. 1000</span>
					</td>
				</tr>
				<!--  Retail Offer Amount -->
				<tr valign="top">
					<th scope="row">
						Retail Offer Amount
					</th>
					<td style="vertical-align:top;">
						<input type="text" id="retail_amount" name="retail_amount" placeholder="Retail Offer Amount" value="<?php echo esc_attr(get_option('retail_amount')); ?>" />
						<span>e.g. 1000</span>
					</td>
				</tr>

			</table>
			<!-- Save Changes Button -->
			<?php submit_button(); ?>
		</form>
	</div>

	<!-- Script to apply styles to Preview Banner -->
	<script type="text/javascript">
		// Free Shipping Banner Default Stylesheet
		var shipping_banner_css = document.createElement('link');
		shipping_banner_css.id = 'shipping-banner-stylesheet';
		shipping_banner_css.rel = 'stylesheet';
		shipping_banner_css.href = "<?php echo plugin_dir_url(__FILE__) . 'shipping-banner.css' ?>";
		document.getElementsByTagName('head')[0].appendChild(shipping_banner_css);



		var style_font_size = document.createElement('style');
		var style_background_color = document.createElement('style');
		var style_text_color = document.createElement('style');

		// Banner Text
		var hrefRegex = /href\=[\'\"](?!http|https)(.*?)[\'\"]/gsi;
		var scriptStyleRegex = /<(script|style)[^>]*?>.*?<\/(script|style)>/gsi;

		function stripBannerText(string) {
			let strippedString = string;
			while (strippedString.match(scriptStyleRegex)) {
				strippedString = strippedString.replace(scriptStyleRegex, '')
			};
			return strippedString.replace(hrefRegex, "href=\"https://$1\"");
		}

		// Font Size
		style_font_size.type = 'text/css';
		style_font_size.appendChild(document.createTextNode('.shipping-banner .shipping-banner-text{font-size:' + (document.getElementById('shipping_banner_font_size').value || '1em') + '}'));
		document.getElementsByTagName('head')[0].appendChild(style_font_size);

		document.getElementById('shipping_banner_font_size').onchange = function(e) {

			if (child) {
				child.innerText = "";
				child.id = '';
			}

			var style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';

			style_dynamic.appendChild(
				document.createTextNode(
					'.shipping-banner .shipping-banner-text{font-size:' + (document.getElementById('shipping_banner_font_size').value || '1em') + '}'
				)
			);
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		};

		// Background Color
		style_background_color.type = 'text/css';
		style_background_color.appendChild(document.createTextNode('.shipping-banner{background:' + (document.getElementById('shipping_banner_color').value || '#024985') + '}'));
		document.getElementsByTagName('head')[0].appendChild(style_background_color);

		document.getElementById('shipping_banner_color').onchange = function(e) {
			document.getElementById('shipping_banner_color_show').value = e.target.value || '#024985';
			if (child) {
				child.innerText = "";
				child.id = '';
			}

			var style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.appendChild(
				document.createTextNode(
					'.shipping-banner{background:' + (document.getElementById('shipping_banner_color').value || '#024985') + '}'
				)
			);
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		};
		document.getElementById('shipping_banner_color_show').onchange = function(e) {
			document.getElementById('shipping_banner_color').value = e.target.value;
			document.getElementById('shipping_banner_color').dispatchEvent(new Event('change'));
		};

		// Text Color
		style_text_color.type = 'text/css';
		style_text_color.appendChild(document.createTextNode('.shipping-banner .shipping-banner-text{color:' + (document.getElementById('shipping_banner_text_color').value || '#ffffff') + '}'));
		document.getElementsByTagName('head')[0].appendChild(style_text_color);

		document.getElementById('shipping_banner_text_color').onchange = function(e) {
			document.getElementById('shipping_banner_text_color_show').value = e.target.value || '#ffffff';
			if (child) {
				child.innerText = "";
				child.id = '';
			}

			var style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.appendChild(
				document.createTextNode(
					'.shipping-banner .shipping-banner-text{color:' + (document.getElementById('shipping_banner_text_color').value || '#ffffff') + '}'
				)
			);
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		};
		document.getElementById('shipping_banner_text_color_show').onchange = function(e) {
			document.getElementById('shipping_banner_text_color').value = e.target.value;
			document.getElementById('shipping_banner_text_color').dispatchEvent(new Event('change'));
		};

		// remove banner text newlines on submit
		document.getElementById('submit').onclick = function(e) {
			document.getElementById('shipping_banner_text').value = document.getElementById('shipping_banner_text').value.replace(/\n/g, "");
		};
	</script>
<?php
}
?>