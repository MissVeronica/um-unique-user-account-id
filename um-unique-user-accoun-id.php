<?php
/**
 * Plugin Name:     Ultimate Member - Unique User Account ID
 * Description:     Extension to Ultimate Member for setting a prefixed Unique User Account ID per UM Registration Form.
 * Version:         2.2.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.8.3
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

class UM_Unique_Account_ID {

    public $um_unique_account_meta_key = false;

    function __construct() {

        add_action( 'um_user_register',      array( $this, 'um_user_register_with_unique_account_id' ), -1, 2 );
        add_filter( 'um_settings_structure', array( $this, 'um_settings_structure_unique_account_id' ), 10, 1 );
    }

    public function unique_account_id_exists( $value ) {

        global $wpdb;

        return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = '{$this->um_unique_account_meta_key}' AND meta_value = '{$value}' " );
    }

    public function um_user_register_with_unique_account_id( $user_id, $args ) {

        if ( isset( $args['form_id'] ) && ! empty( $args['form_id'] ) && is_int( $user_id ) && (int)$user_id > 0 ) {

            $um_unique_account_id = array_map( 'sanitize_text_field', array_map( 'trim', explode( "\n", UM()->options()->get( 'um_unique_account_id' ))));
            if ( is_array( $um_unique_account_id )) {

                foreach( $um_unique_account_id as $form_prefix ) {

                    $array = array_map( 'trim', array_map( 'sanitize_text_field', explode( ':', $form_prefix )));

                    if ( isset( $array[0] ) && ! empty( $array[0] )) {

                        if ( $args['form_id'] == $array[0] && isset( $array[1] ) && ! empty( $array[1] )) {

                            $digits = absint( UM()->options()->get( 'um_unique_account_id_digits' ));
                            if ( empty( $digits )) {
                                $digits = 5;
                            }

                            $this->um_unique_account_meta_key = sanitize_key( UM()->options()->get( 'um_unique_account_id_meta_key' ));
                            if ( empty( $this->um_unique_account_meta_key )) {
                                $this->um_unique_account_meta_key = 'um_unique_account_id';
                            }

                            $prefix = '';
                            $string_pad = '';

                            if ( $array[1] == 'meta_key' && isset( $array[2] ) && ! empty( $array[2] )) {

                                if ( isset( $args[$array[2]] ) && ! empty( $args[$array[2]] )) {

                                    $prefix = sanitize_text_field( $args[$array[2]] );
                                    if ( isset( $array[3] ) && ! empty( $array[3] ) && mb_strlen( $array[3] ) == 1 ) {
                                        $prefix .= $array[3];
                                    }

                                    if ( isset( $array[4] ) && $array[4] == 'random' ) {

                                        $string_pad = str_pad( rand( 0, pow( 10, $digits ) -1 ), $digits, '0', STR_PAD_LEFT );

                                        while( $this->unique_account_id_exists( $prefix . $string_pad )) {
                                            $string_pad = str_pad( rand( 0, pow( 10, $digits ) -1 ), $digits, '0', STR_PAD_LEFT );
                                        }                                    

                                    } else {

                                        $string_pad = str_pad( strval( $user_id ), $digits, '0', STR_PAD_LEFT );

                                        $i = 1;
                                        $string_pad_saved = $string_pad;

                                        while( $this->unique_account_id_exists( $prefix . $string_pad )) {
                                            $string_pad = $string_pad_saved . '-' . str_pad( strval( $i++ ), 3, '0', STR_PAD_LEFT );
                                        }
                                    }
                                }

                            } else {

                                $prefix = $array[1];

                                if ( isset( $array[2] ) && $array[2] == 'random' ) {

                                    $string_pad = str_pad( rand( 0, pow( 10, $digits ) -1 ), $digits, '0', STR_PAD_LEFT );

                                    while( $this->unique_account_id_exists( $prefix . $string_pad )) {
                                        $string_pad = str_pad( rand( 0, pow( 10, $digits ) -1 ), $digits, '0', STR_PAD_LEFT );
                                    }

                                } else {

                                    $string_pad = str_pad( strval( $user_id ), $digits, '0', STR_PAD_LEFT );

                                    $i = 1;
                                    $string_pad_saved = $string_pad;

                                    while( $this->unique_account_id_exists( $prefix . $string_pad )) {
                                        $string_pad = $string_pad_saved . '-' . str_pad( strval( $i++ ), 3, '0', STR_PAD_LEFT );
                                    }
                                }

                            }

                            if ( ! empty( $prefix ) && ! empty( $string_pad )) {

                                $um_unique_account_id = $prefix . $string_pad;
                                update_user_meta( $user_id, $this->um_unique_account_meta_key, $um_unique_account_id );
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public function um_settings_structure_unique_account_id( $settings_structure ) {

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['unique_account_id']['title']       = __( 'Unique User Account ID', 'ultimate-member' );
        $settings_structure['appearance']['sections']['registration_form']['form_sections']['unique_account_id']['description'] = __( 'Plugin version 2.2.0 - tested with UM 2.8.3', 'ultimate-member' );

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['unique_account_id']['fields'][] = array(
                        'id'          => 'um_unique_account_id',
                        'type'        => 'textarea',
                        'label'       => __( "Form ID:prefix or meta_key format", 'ultimate-member' ),
                        'description' => __( "Enter the UM Registration Form ID and the Unique User Account ID Prefix or meta_key format one setting per line.", 'ultimate-member' ),
                        'args'        => array( 'textarea_rows' => 6 ));

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['unique_account_id']['fields'][] = array(
                        'id'          => 'um_unique_account_id_digits',
                        'type'        => 'number',
                        'label'       => __( "Number of digits", 'ultimate-member' ),
                        'description' => __( "Enter the number of digits in the Unique User Account ID. Default value is 5.", 'ultimate-member' ),
                        'size'        => 'small' );

        $settings_structure['appearance']['sections']['registration_form']['form_sections']['unique_account_id']['fields'][] = array(
                        'id'          => 'um_unique_account_id_meta_key',
                        'type'        => 'text',
                        'label'       => __( "meta_key", 'ultimate-member' ),
                        'description' => __( "Enter the meta_key name of the Unique User Account ID field. Default name is 'um_unique_account_id'", 'ultimate-member' ),
                        'size'        => 'small' );

        return $settings_structure;
    }
}

new UM_Unique_Account_ID ();

