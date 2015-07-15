<?php

/*
Plugin Name: Contact Form 7 - City Field Extension
Plugin URI: http://cfe.wp-themes.it
Description: Provides a text input field for city search, based on Google Place Autocomplete library.  Requires Contact Form 7.
Version: 1.1
Author: Pasquale Bucci
Author URI: http://wp-themes.it/
License: GPL2
*/

/*  Copyright 2014 - 2015 Pasquale Bucci (email : paky.bucci@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
* Loads Scripts
*/
function pcfe_load_scripts() {
	wp_enqueue_script( 'pcfe-google-places-api', '//maps.googleapis.com/maps/api/js?v=3.exp&libraries=places' );
	wp_enqueue_script( 'pcfe-plugin-script', plugins_url( '/js/script.js', __FILE__ ));
}
add_action( 'wp_enqueue_scripts', 'pcfe_load_scripts' );

function load_pcfe_wp_admin_style() {
    wp_enqueue_style( 'pcfe-plugin-style', plugins_url( '/css/pakystyle.css', __FILE__ ));
}
add_action( 'admin_enqueue_scripts', 'load_pcfe_wp_admin_style' );

/*
* A base module for [cityfieldtext], [cityfieldtext*]
*/
function wpcf7_cityfieldtext_init(){
	if(function_exists('wpcf7_add_shortcode')){
		/* Shortcode handler */		
		wpcf7_add_shortcode( 'cityfieldtext', 'wpcf7_cityfieldtext_shortcode_handler', true );
		wpcf7_add_shortcode( 'cityfieldtext*', 'wpcf7_cityfieldtext_shortcode_handler', true );
	
	}
	add_filter( 'wpcf7_validate_cityfieldtext', 'wpcf7_cityfieldtext_validation_filter', 10, 2 );
	add_filter( 'wpcf7_validate_cityfieldtext*', 'wpcf7_cityfieldtext_validation_filter', 10, 2 );
}
add_action( 'plugins_loaded', 'wpcf7_cityfieldtext_init' , 20 );

/*
* CityFieldText Shortcode
*/
function wpcf7_cityfieldtext_shortcode_handler( $tag ) {
	
$wpcf7_contact_form = WPCF7_ContactForm::get_current();

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';
	$aria='';

	$class_att .= ' wpcf7-text';
	$id_att = 'autocomplete';

	if ( 'cityfieldtext*' == $type ) {
		$class_att .= ' wpcf7-validates-as-required';
		$aria="true";
	}

	foreach ( $options as $option ) {
		if ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';


	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) && $wpcf7_contact_form->is_posted() ) {
		if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['ok'] )
			$value = '';
		else
			$value = stripslashes_deep( $_POST[$name] );
	} else {
		$value = isset( $values[0] ) ? $values[0] : '';
	}
	
	$scval = do_shortcode('['.$value.']');
	if($scval != '['.$value.']') $value = $scval;
	
	$readonly = '';
	if(in_array('uneditable', $options)){
		$readonly = 'readonly="readonly"';
	}

	$html = '<input type="text" aria-required="' . $aria . '" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $atts . ' '. $readonly.' />';

	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

/*
* CityFieldText Validation filter
*/
function wpcf7_cityfieldtext_validation_filter( $result, $tag ) {

	$wpcf7_contact_form = WPCF7_ContactForm::get_current();

	$type = $tag['type'];
	$name = $tag['name'];
	$value = isset( $_POST[$name] ) ? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) ) : '';

	if ( 'cityfieldtext*' == $type ) {
		if ( '' == $value ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		}
	}

	return $result;
}


/*
* CityFieldText Tag generator
*/
add_action( 'admin_init', 'wpcf7_add_tag_generator_cityfieldtext', 45 );

function wpcf7_add_tag_generator_cityfieldtext() {
	if (class_exists('WPCF7_TagGenerator')) {
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'cityfieldtext', __( 'City Text Field', 'contact-form-7' ), 'wpcf7_tg_pane_cityfieldtext' );
	} else if (function_exists('wpcf7_add_tag_generator')) {
		wpcf7_add_tag_generator( 'cityfieldtext', __( 'City Text Field', 'wpcf7' ),	'wpcf7_tg_pane_cityfieldtext', 'wpcf7_tg_pane_cityfieldtext' );
	}
}

function wpcf7_tg_pane_cityfieldtext($contact_form, $args = '') {
	$args = wp_parse_args( $args, array() );

	$description = __( "Generate a form tag for an autocomplete text field that returns place predictions in the form of a dropdown pick list. For more details, see %s.", 'contact-form-7' );
	$desc_link = wpcf7_link( __( 'https://wordpress.org/plugins/contact-form-7-city-field-extension/', 'contact-form-7' ), __( 'the plugin page on WordPress.org', 'contact-form-7' ), array('target' => '_blank' ) );
?>
<div class="control-box">
	<fieldset>
		<legend><?php printf( esc_html( $description ), $desc_link ); ?></legend>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
							<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
					<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
				</tr>

				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class (optional)', 'contact-form-7' ) ); ?></label></th>
					<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
				</tr>
			</tbody>
		</table>
	</fieldset>
</div>
	<div class="insert-box">
		<input type="text" name="cityfieldtext" class="tag code" readonly="readonly" onfocus="this.select()" />

		<div class="submitbox">
			<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
		</div>

		<br class="clear" />

		<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
	</div>
<?php
}

/*
* CityFieldText Welcome panel
*/
add_action( 'wpcf7_admin_notices', 'wpcf17_welcome_panel', 2 );
function wpcf17_welcome_panel() {
	global $plugin_page;

	if ( 'wpcf7' != $plugin_page || ! empty( $_GET['post'] ) ) {
		return;
	}

	$classes = 'welcome-panel';

	$vers = (array) get_user_meta( get_current_user_id(),
		'wpcf7_hide_welcome_panel_on', true );

	if ( wpcf7_version_grep( wpcf7_version( 'only_major=1' ), $vers ) ) {
		$classes .= ' hidden';
	}

?>
<div id="welcome-panel" class="<?php echo esc_attr( $classes ); ?>">
	<?php wp_nonce_field( 'wpcf7-welcome-panel-nonce', 'welcomepanelnonce', false ); ?>
	<a class="welcome-panel-close" href="<?php echo esc_url( menu_page_url( 'wpcf7', false ) ); ?>"><?php echo esc_html( __( 'Dismiss', 'contact-form-7' ) ); ?></a>

	<div class="welcome-panel-content">
		<div class="welcome-panel-container">
			<div class="welcome-panel-column">
				<h4><?php echo esc_html( __( 'City Field Extension for Contact Form 7 Needs Your Support', 'contact-form-7' ) ); ?></h4>
				<p class="message"><?php echo esc_html( __( "If you enjoy using City Field Extension for Contact Form 7 and find it useful, please consider making a donation.", 'contact-form-7' ) ); ?></p>
				<p><a href="<?php echo esc_url( __( 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PKMBP2CF3M8SQ', 'contact-form-7' ) ); ?>" class="button button-paky" target="_blank"><?php echo esc_html( __( 'Donate', 'contact-form-7' ) ); ?></a></p>
			</div>

		</div>
	</div>
</div>
<?php
}
