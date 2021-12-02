<?php
/**
 *
 * Field: backup
 *
 * @link       https://shapedplugin.com/
 *
 * @package    Logo_Carousel_Free
 * @subpackage Logo_Carousel_Free/sp-framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! class_exists( 'SPLC_FREE_Field_backup' ) ) {
	/**
	 *
	 * Field: backup
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLC_FREE_Field_backup extends SPLC_FREE_Fields {

			/**
			 * The class constructor.
			 *
			 * @param array  $field The field type.
			 * @param string $value The values of the field.
			 * @param string $unique The unique ID for the field.
			 * @param string $where To where show the output CSS.
			 * @param string $parent The parent args.
			 */
		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		/**
		 * The render method.
		 *
		 * @return void
		 */
		public function render() {

			$unique = $this->unique;
			$nonce  = wp_create_nonce( 'splogocarousel_backup_nonce' );
			$export = add_query_arg(
				array(
					'action' => 'splogocarousel-export',
					'unique' => $unique,
					'nonce'  => $nonce,
				),
				admin_url( 'admin-ajax.php' )
			);

			echo wp_kses_post( $this->field_before() );

			echo '<textarea name="splogocarousel_import_data" class="splogocarousel-import-data"></textarea>';
			echo '<button type="submit" class="button button-primary splogocarousel-confirm splogocarousel-import" data-unique="' . esc_attr( $unique ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Import', 'splogocarousel' ) . '</button>';
			echo '<hr />';
			echo '<textarea readonly="readonly" class="splogocarousel-export-data">' . esc_attr( wp_json_encode( get_option( $unique ) ) ) . '</textarea>';
			echo '<a href="' . esc_url( $export ) . '" class="button button-primary splogocarousel-export" target="_blank">' . esc_html__( 'Export & Download', 'splogocarousel' ) . '</a>';
			echo '<hr />';
			echo '<button type="submit" name="splogocarousel_transient[reset]" value="reset" class="button splogocarousel-warning-primary splogocarousel-confirm splogocarousel-reset" data-unique="' . esc_attr( $unique ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Reset', 'splogocarousel' ) . '</button>';

			echo wp_kses_post( $this->field_after() );

		}

	}
}
