<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly


/**
 * Class DT_Webform_Utilities
 */
class DT_Webform_Utilities {

    public static function get_form_meta( $token ) {

        if ( empty( $token ) ) {
            return false;
        }
        $meta = wp_cache_get( 'get_form_meta', $token );
        if ( $meta) {
            return maybe_unserialize( $meta );
        }

        global $wpdb;

        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1", $token ) );
        $meta = dt_get_simple_post_meta( $post_id );

        if ( isset( $meta['_edit_last'] ) ) {
            unset( $meta['_edit_last'] );
        }
        if ( isset( $meta['_edit_lock'] ) ) {
            unset( $meta['_edit_lock'] );
        }

        wp_cache_set( 'get_form_meta', $meta, $token );

        return $meta;
    }

    public static function get_custom_css( $token ) {

        if ( empty( $token ) ) {
            return false;
        }

        $meta = wp_cache_get( 'get_custom_css', $token );
        if ( $meta ) {
            return $meta;
        }

        global $wpdb;
        $css = $wpdb->get_var( $wpdb->prepare( "
            SELECT meta_value
            FROM $wpdb->postmeta
            WHERE post_id = ( SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %s AND meta_key = 'token' LIMIT 1 )
            AND meta_key = 'custom_css'
            LIMIT 1", $token ) );

        wp_cache_set( 'get_custom_css', $css, $token );

        return $css;
    }

    public static function order_custom_field_array( $custom_fields ) {
        // reorder
        $order = [];
        foreach ( $custom_fields as $value ) {
            $value = maybe_unserialize( $value );
            if ( ! isset( $value['order'] ) || $value['order'] < 1 ) {
                $value['order'] = 1;
            }
            if ( ! isset( $order[$value['order']] ) ) {
                $order[$value['order']] = [];
            }
            $order[$value['order']][$value['key']] = $value;
        }
        ksort( $order );

        $ordered_fields = [];
        foreach ( $order as $value ) {
            foreach ( $value as $k => $v ) {
                $ordered_fields[$k] = $v;
            }
        }

        return $ordered_fields;
    }

    public static function get_contact_defaults( $force = false ) {

        if ( is_dt() ) {
            Disciple_Tools::instance();
            return DT_Posts::get_post_settings( 'contacts' );
        }

        // caching
        $contact_defaults = get_transient( 'dt_webform_contact_defaults' );
        if ( $contact_defaults && ! $force ) {
            return $contact_defaults;
        }

        $site_id = dt_get_webform_site_link();
        if ( ! $site_id ) {
            return new WP_Error( __METHOD__, 'Not site link set.' );
        }

        $site = Site_Link_System::get_site_connection_vars( $site_id );
        if ( ! $site ) {
            return new WP_Error( __METHOD__, 'Missing site to site data' );
        }

        $args = [
            'method' => 'GET',
            'body' => [],
            'headers' => [
                'Authorization' => 'Bearer ' . $site['transfer_token'],
            ],
        ];

        $result = wp_remote_post( 'https://' . trailingslashit( $site['url'] ) . 'wp-json/dt-posts/v2/contacts/settings', $args );
        if ( is_wp_error( $result ) ) {
            return new WP_Error( __METHOD__, $result->get_error_message() );
        }

        $contact_defaults = json_decode( $result['body'], true );
        if ( isset( $contact_defaults['sources'] ) ) {
            set_transient( 'dt_webform_contact_defaults', $contact_defaults, 60 *60 *24 );
            return $contact_defaults;
        } else {
            return new WP_Error( __METHOD__, 'Remote response from DT server malformed.' );
        }

    }

    public static function get_theme( string $theme = 'wide-heavy', string $token = null ) {

        $meta = self::get_form_meta( $token );
        if ( empty( $meta ) ) {
            $meta = [];
        }

        // Unique styles
        switch ( $theme ) {
            case 'simple':
                $css = '
                    #wrapper {
                        margin: auto;
                        max-width: 400px;
                    }
                    #contact-form {}
                    .input-text {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-date {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-textarea {
                        height:80px;
                        padding: .2em;
                        font-size: .8em;
                        border: .5px solid #ccc;
                    }
                    .input-checkbox {}
                    .input-multi_radio {
                        padding: .2em;
                        font-size: .8em;
                    }
                    .span-radio {
                        float:left;
                        padding:5px;
                    }
                    .input-multi_select {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-key_select {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-dropdown {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-tel {
                       padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-email {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    .input-note {
                        padding: .2em;
                        font-size: .8em;
                        width: 100%;
                    }
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }

                    .hr {}
                    .hr-divider {}
                    .offlineWarning {
                        color: #9F6000;
                        background-color: #FEEFB3;
                        padding: 1em;
                        font-size: 1.2em;
                        margin: 1em 0;
                        border-top: 1em solid HSLA(47, 100%, 48%, 1.00);
                    },
                    label.error {
                        color: red;
                        font-size: .8em;
                    }
                    .input-label {
                        font-size: 1em;

                    }
                    .label-dropdown {}
                    .label-multi_radio {
                        margin-bottom:10px;
                    }
                    #title {
                        font-size: 1.2em;
                        font-weight:bold;
                        padding: .5em 0;
                    }
                    #description {
                        padding-bottom: .8em;
                        font-size: .9em;

                    }
                    .section {
                        padding: 10px 0;
                        width: 100%;
                    }
                    .section-dropdown {}
                    .section-multi_radio {}
                    .section-checkbox {
                        padding:0;
                    }
                    .section-header {
                       font-size: 1.2em;
                        font-weight:bold;
                        padding: .5em 0;
                    }
                    .section-description {
                        padding-bottom: .8em;
                        font-size: .9em;

                    }
                    .section-map {
                        margin: 10px 0 ;
                        padding: 10px 0;
                    }
                    ';
                break;
            case 'heavy':
                $css = '
                    #wrapper {
                        margin: auto;
                        max-width: 400px;
                    }
                    #contact-form {}
                    .input-text {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-date {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-textarea {
                        height:80px;
                        padding: .5em;
                        font-size: 1.2em;
                        border: .5px solid #ccc;
                    }
                    .input-checkbox {}
                    .input-multi_radio {
                        font-size:1.1em;
                    }
                    .span-radio {
                        float:left;
                        padding:5px;

                    }
                    .input-multi_select {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-key_select {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-dropdown {
                        font-size: 1.2em;
                        width: 50%;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                        appearance: none;
                        padding: 5px;
                    }
                    .input-tel {
                        padding: .5em;
                        font-size: 1.2em;
                        line-height: 1.5em;
                        width: 100%;
                    }
                    .input-email {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-note {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }
                    .offlineWarning {
                        color: #9F6000;
                        background-color: #FEEFB3;
                        padding: 1em;
                        font-size: 1.2em;
                        margin: 1em 0;
                        border-top: 1em solid HSLA(47, 100%, 48%, 1.00);
                    },
                    .hr {}
                    .hr-divider {}

                    label.error {
                        color: red;
                        font-size: .8em;
                    }

                    .input-label {
                        font-size: 1.2em;

                    }
                    .label-dropdown {}
                    .label-multi_radio {
                        margin-bottom:10px;
                    }

                    .section {
                        padding: 10px 0;
                        width: 100%;
                    }
                    .section-dropdown {}
                    .section-multi_radio {}
                    .section-checkbox {
                        padding:0;
                    }
                    .section-header {
                        font-size: 2em;
                        font-weight: bolder;
                        padding-top: .5em;
                    }
                    .section-map {
                        margin: 10px 0 ;
                    }

                    #title {
                        font-size: 2em;
                        font-weight: bolder;
                        padding-top: .5em;
                    }
                    #description {
                        padding-bottom: 1em;
                    }
                    ';
                break;
            case 'none':
                $css = '';
                break;
            case 'minimum':
                $css = '
                    // FORM WRAPPER
                    #wrapper {}
                    #contact-form {}

                    // CORE SECTION AND INPUTS
                    #title {}
                    #description {}
                    #section-name {}
                    #section-phone {}
                    #section-email {}
                    #name {}
                    #phone {}
                    #email {}

                    // SECTION CLASSES
                    .section {}
                    .section-dropdown {}
                    .section-key_select {}
                    .section-multi_select {}
                    .section-multi_radio {}
                    .section-checkbox {}
                    .section-tel {}
                    .section-email {}
                    .section-text {}
                    .section-header {}
                    .section-description {}
                    .section-note {}
                    .section-map {}
                    .section-custom_label {}

                    // INPUT CLASSES
                    .input-text {}
                    .input-textarea {}
                    .input-checkbox {}
                    .input-multi_radio {}
                    .input-key_select {}
                    .input-multi_select {}
                    .span-radio {}
                    .input-tel {}
                    .input-email {}
                    .input-dropdown {}
                    .input-note {}
                    button.submit-button {}

                    // LABELS
                    .input-label {}
                    .label-dropdown {}
                    .label-multi_radio {}
                    .label-checkbox {}
                    .label-tel {}
                    .label-email {}
                    .label-text {}
                    .label-note {}
                    .label-map {}
                    .label-map-instructions {}

                    // DIVIDER CLASSES
                    .hr {}
                    .hr-divider {}

                    // ERROR CLASSES
                    label.error {}
                    .offlineWarning {}
                    // EXTRA SECTIONS AND INPUTS
                    ';
                break;
            case 'wide-heavy':
                $css = '
                    #wrapper {
                        margin: auto;
                        max-width: 1000px;
                    }
                    #contact-form {}

                    #title {
                        font-size: 2em;
                        font-weight: bolder;
                        padding: .5em 0;
                    }
                   #description {
                        padding: .5em 0;
                    }
                    #section-name {}
                    #section-phone {}
                    #section-email {}
                    #name {}
                    #phone {}
                    #email {}

                    .section {
                        padding: 10px 0;
                        width: 100%;
                    }
                    .section-multi_select {}
                    .section-key_select {}
                    .section-dropdown {}
                    .section-multi_radio {}
                    .section-checkbox {
                        padding:0;
                    }
                    .section-tel {}
                    .section-email {}
                    .section-text {}
                    .section-date {}
                    .section-header {
                        font-size: 2em;
                        font-weight: bolder;
                        padding-top: .5em;
                    }
                    .section-description {}
                    .section-note {}
                    .section-map {
                        padding-bottom: 10px;
                    }
                    .section-custom_label {
                        font-size: 1.2em;
                    }

                    .input-text {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-date {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-textarea {
                        height:80px;
                        padding: .5em;
                        font-size: 1.5em;
                        font-family: Arial;
                        border: .5px solid #ccc;
                    }
                    .input-checkbox {}
                    .input-multi_radio {
                        font-size:1.1em;
                    }
                    .span-radio {
                        float:left;
                        padding:5px;
                    }
                    .input-tel {
                       padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-email {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-multi_select {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-key_select {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-dropdown {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-note {
                        padding: .5em;
                        font-size: 1.2em;
                        font-family: Arial;
                        width: 100%;
                        border: .5px solid #ccc;
                    }
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }
                    .input-label {
                        font-size: 1.2em;
                    }
                    .label-dropdown {}
                    .label-multi_radio {
                        margin-bottom:10px;
                    }
                    .label-checkbox {}
                    .label-tel {}
                    .label-email {}
                    .label-text {}
                    .label-note {}
                    .label-map {}
                    .label-map-instructions {
                        color: grey;
                    }
                    .offlineWarning {
                        color: #9F6000;
                        background-color: #FEEFB3;
                        padding: 1em;
                        font-size: 1.2em;
                        margin: 1em 0;
                        border-top: 1em solid HSLA(47, 100%, 48%, 1.00);
                    },
                    .hr {}
                    .hr-divider {}

                    label.error {
                        color: red;
                        font-size: .8em;
                    }
                    ';
                break;
            default:
                $css = '
                    #wrapper {
                        margin: auto;
                        max-width: 1000px;
                    }
                    #contact-form {}

                    #title {
                        font-size: 2em;
                        font-weight: bolder;
                        padding: .5em 0;
                    }
                   #description {
                        padding: .5em 0;
                    }
                    #section-name {}
                    #section-phone {}
                    #section-email {}
                    #name {}
                    #phone {}
                    #email {}

                    .section {
                        padding: 10px 0;
                        width: 100%;
                    }
                    .section-multi_select {}
                    .section-key_select {}
                    .section-dropdown {}
                    .section-multi_radio {}
                    .section-checkbox {
                        padding:0;
                    }
                    .section-tel {}
                    .section-email {}
                    .section-text {}
                    .section-date {}
                    .section-header {
                        font-size: 2em;
                        font-weight: bolder;

                        padding-top: .5em;
                    }
                    .section-description {}
                    .section-note {}
                    .section-map {
                        padding-bottom: 10px;
                    }
                    .section-custom_label {
                        font-size: 1.2em;

                    }

                    .input-text {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-date {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-textarea {
                        height:80px;
                        padding: .5em;
                        font-size: 1.2em;
                        border: .5px solid #ccc;
                    }
                    .input-checkbox {}
                    .input-multi_radio {
                        font-size:1.1em;
                    }
                    .span-radio {
                        float:left;
                        padding:5px;
                    }
                    .input-tel {
                       padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-email {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    .input-multi_select {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-key_select {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-dropdown {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                        border: 1px solid lightgray;
                    }
                    .input-note {
                        padding: .5em;
                        font-size: 1em;
                        width: 100%;
                    }
                    button.submit-button {
                        padding: 1em;
                        font-weight: bolder;
                    }
                    .input-label {
                        font-size: 1.2em;
                    }
                    .label-dropdown {}
                    .label-multi_radio {
                        margin-bottom:10px;
                    }
                    .label-checkbox {}
                    .label-tel {}
                    .label-email {}
                    .label-text {}
                    .label-note {}
                    .label-map {}
                    .label-map-instructions {
                        color: grey;
                    }
                    .offlineWarning {
                        color: #9F6000;
                        background-color: #FEEFB3;
                        padding: 1em;
                        font-size: 1.2em;
                        margin: 1em 0;
                        border-top: 1em solid HSLA(47, 100%, 48%, 1.00);
                    },
                    .hr {}
                    .hr-divider {}

                    label.error {
                        color: red;
                        font-size: .8em;
                    }
                    ';

                $ids = '';
                foreach ( $meta as $key => $value ) {
                    if ( substr( $key, 0, 5 ) === 'field' ) {
                        if ( empty( $value['labels'] ) ) {
                            $value['labels'] = 'Divider';
                        }
                        if ( ! is_array( $value['labels'] ) ) {
                            $ids .= '// ' . esc_html( $value['labels'] ) . PHP_EOL;
                            $ids .= '#section-' . esc_attr( $key ) . ' {}' . PHP_EOL;
                            $ids .= '#' . esc_attr( $key ) . ' {}' . PHP_EOL . PHP_EOL;
                        }
                    }
                }

                return $css . $ids;
                break;


        }

        /**
         * Location Styles
         */
        $location_styles = '';
        foreach ( $meta as $key => $value ) {
            if ( substr( $key, 0, 5 ) === 'field' && $value['type'] === 'location' ) {
                $location_styles = '
                #geocoder {
                    padding-bottom: 10px;
                }
                #map {
                    width:66%;
                    height:400px;
                    float:left;
                }
                #list {
                    width:33%;
                    float:right;
                }
                #selected_values {
                    width:66%;
                    float:left;
                }
                .selection-container {
                    padding: 15px 10px;
                    border: 1px solid lightgray;
                    margin: 5px;
                    font-weight: bold;
                    float:left;
                }
                .selection-remove {
                    padding-left:10px;
                    color: red;
                    cursor: pointer;
                }
                .results-button-column {
                    width:10px;
                    padding-right: 10px;
                    vertical-align: top;
                }
                .results-add-button {

                }
                .results-title-column {}
                .results-title {
                    font-size:1.2em;
                    font-weight:bold;
                }
                .results-row {
                    padding-bottom:5px;
                }
                .results-table {

                }
                .results-table td {
                    padding-bottom: 15px;
                }

                /* mapbox autocomplete elements*/
                #mapbox-search {
                    margin:0;
                }
                #mapbox-search-wrapper {
                    margin: 0 0 1rem;
                }
                .mapbox-autocomplete {
                    /*the container must be positioned relative:*/
                    position: relative;
                }
                .mapbox-autocomplete-items {
                    position: absolute;
                    border: 1px solid #e6e6e6;
                    border-bottom: none;
                    border-top: none;
                    z-index: 99;
                    /*position the autocomplete items to be the same width as the container:*/
                    top: 100%;
                    left: 0;
                    right: 0;
                }
                .mapbox-autocomplete-items div {
                    padding: 10px;
                    cursor: pointer;
                    background-color: #fff;
                    border-bottom: 1px solid #e6e6e6;
                }
                .mapbox-autocomplete-items div:hover {
                    /*when hovering an item:*/
                    background-color: #00aeff;
                    color: #ffffff;
                }
                .mapbox-autocomplete-active {
                    /*when navigating through the items using the arrow keys:*/
                    background-color: #00aeff !important;
                    color: #ffffff;
                }
                #mapbox-spinner-button {
                    border-radius:0;
                    display:none;
                }
                /* end mapbox elements*/
            ';
            }
        }

        /**
         * Custom CSS
         */
        $custom_css = '';
        if ( isset( $meta['custom_css'] ) && ! empty( $meta['custom_css'] ) ) {
            $custom_css = $meta['custom_css'];
        }

        $css = $location_styles . $css . $custom_css;
        $css = trim( str_replace( PHP_EOL, '', str_replace( '  ', '', $css ) ) );

        return $css;
    }

    /**
     * Trigger transfer of new leads
     *
     * @param array $selected_records
     *
     * @return bool|\WP_Error
     */
//    public static function trigger_transfer_of_new_leads( $selected_records = [] ) {
//        dt_write_log( __METHOD__ );
//
//        $transfer_records = [];
//
//        $site_transfer_post_id = get_option( 'dt_webform_site_link' );
//        if ( empty( $site_transfer_post_id ) ) {
//            return new WP_Error( 'no_site_transfer_setting', 'No site to site transfer defined.' );
//        }
//        $transfer_vars = Site_Link_System::get_site_connection_vars( $site_transfer_post_id );
//
//        // get entire record from selected records
//        foreach ( $selected_records as $record ) {
//            $array = dt_get_simple_post_meta( $record );
//            if ( isset( $array['token'] ) ) {
//                $transfer_records[] = dt_get_simple_post_meta( $record );
//            }
//        }
//        if ( empty( $transfer_records ) ) {
//            return false;
//        }
//
//
//        // Send remote request
//        $args = [
//            'method' => 'GET',
//            'body' => [
//                'transfer_token' => $transfer_vars['transfer_token'],
//                'selected_records' => $transfer_records,
//            ]
//        ];
//
//        dt_write_log( $args );
//
//        $result = wp_remote_get( 'https://' . $transfer_vars['url'] . '/wp-json/dt-public/v1/webform/transfer_collection', $args );
//        if ( is_wp_error( $result ) ) {
//            dt_write_log( $result );
//            return new WP_Error( 'failed_remote_get', $result->get_error_message() );
//        }
//
//        if ( isset( $result['body'] ) && ! empty( $result['body'] ) ) {
//            $records = json_decode( $result['body'] );
//
//            if ( is_array( $records ) ) {
//                foreach ( $records as $record ) {
//                    wp_delete_post( $record, true );
//                }
//            }
//        }
//
//        return true;
//    }

    /**
     * Create Contact via Disciple Tools System
     *
     * @param $new_lead_id
     *
     * @return bool|\WP_Error
     */
//    public static function create_contact_record( $new_lead_id ) {
//        dt_write_log( __METHOD__ );
//
//        // set vars
//        $check_permission = false;
//        $fields = [];
//        $notes = [];
//        $new_lead_meta = dt_get_simple_post_meta( $new_lead_id );
//
//        dt_write_log( 'new_lead_meta' );
//        dt_write_log( $new_lead_meta );
//
//        // check required fields
//        if ( ! isset( $new_lead_meta['token'] ) || empty( $new_lead_meta['token'] ) ) {
//            return new WP_Error( 'missing_contact_info', 'Missing token' );
//        }
//        if ( ! isset( $new_lead_meta['name'] ) || empty( $new_lead_meta['name'] ) ) {
//            return new WP_Error( 'missing_contact_info', 'Missing name' );
//        }
//
//        // get form data: remote verse local form
//        $form_meta = [];
//        if ( isset( $new_lead_meta['form_meta'] ) ) {
//            $form_meta = $new_lead_meta['form_meta'];
//        }
//        dt_write_log( 'form_meta' );
//        dt_write_log( $form_meta );
//
//        // name
//        $fields['title'] = $new_lead_meta['name'];
//
//        // phone
//        if ( isset( $new_lead_meta['phone'] ) && ! empty( $new_lead_meta['phone'] ) ) {
//            $fields['contact_phone'] = [ [ "value" => $new_lead_meta['phone'] ] ];
//        }
//
//        // email
//        if ( isset( $new_lead_meta['email'] ) && ! empty( $new_lead_meta['email'] ) ) {
//            $fields['contact_email'] = [ [ "value" => $new_lead_meta['email'] ] ];
//        }
//
//        // locations
//        $locations = [ "values" => [] ];
//        $coordinates = [ "values" => [] ];
//        foreach ( $new_lead_meta as $lead_key => $lead_value ) {
//            if ( 'location_lnglat_' === substr( $lead_key, 0, 16 ) ) {
//                $array = explode( ',', $lead_value );
//
//                $longitude = $array[0] ?? '';
//                $latitide = $array[1] ?? '';
//                $grid_id = $array[2] ?? '';
//
//                $locations['values'][] = [
//                    'value' => $grid_id
//                ];
//
//                $coordinates['values'][] = [
//                    'value' => [
//                        'lng' => $longitude,
//                        'lat' => $latitide,
//                        'grid_id' => $grid_id,
//                        'level' => '',
//                        'place_name' => ''
//                    ]
//                ];
//            }
//        }
//        if ( isset( $locations['values'] ) && ! empty( $locations['values'] ) ) {
//            $fields['location_grid'] = $locations;
//        }
//        if ( isset( $coordinates['values'] ) && ! empty( $coordinates['values'] ) ) {
//            $fields['location_lnglat'] = $coordinates;
//        }
//
//        $contact_fields = Disciple_Tools_Contact_Post_Type::instance()->get_custom_fields_settings();
//
//        // custom fields
//        foreach ( $new_lead_meta as $lead_key => $lead_value ) {
//            if ( 'field_' === substr( $lead_key, 0, 6 ) && ! empty( $lead_value ) ) {
//
//                // unserialize post meta
//                if ( isset( $form_meta[$lead_key] ) ) {
//                    $field = maybe_unserialize( $form_meta[$lead_key] );
//                } else {
//                    continue;
//                }
//
//                if ( ! isset( $field['type'] ) ) {
//                    continue;
//                }
//
//                // prepare note
//                $label = ucfirst( $field['type'] );
//                $notes[$lead_key] = $label . ': ' . $lead_value;
//
//                // prepare mapped fields
//                if ( isset( $field['dt_field'] ) && ! empty( $field['dt_field'] ) ) {
//                    // set field value to custom field
//                    switch ( $field['type'] ) {
//                        case 'checkbox':
//                            if ( ! isset( $fields[$field['dt_field']] ) ) {
//                                $fields[$field['dt_field']] = [ 'values' => [] ];
//                            }
//                            $fields[$field['dt_field']]['values'][] = [ 'value' => $field['values'] ];
//                            break;
//
//                        case 'multi_radio':
//                        case 'dropdown':
//                        case 'tel':
//                        case 'email':
//                        case 'text':
//                            if ( isset( $contact_fields[$field['dt_field']]["type"] ) && $contact_fields[$field['dt_field']]["type"] === "multi_select" ) {
//                                $fields[$field['dt_field']]['values'][] = [ 'value' => $lead_value ];
//                            } else {
//                                $fields[$field['dt_field']] = $lead_value;
//                            }
//                            break;
//                        case 'note':
//                            $notes[$lead_key] = $field['label'] . ': ' . esc_html( $lead_value );
//                            break;
//                        default:
//                            continue 2;
//                            break;
//                    }
//                }
//            }
//        }
//
//        // source
//        if ( ! empty( $form_meta['source'] ) ) {
//            if ( ! isset( $fields['sources'] ) ) {
//                $fields['sources'] = [ "values" => [] ];
//            }
//            $fields['sources']['values'] = [ [ "value" => $form_meta['source'] ] ];
//        }
//
//        // ip address
//        if ( ! empty( $new_lead_meta['ip_address'] ) ) {
//            $notes['ip_address'] = __( 'IP Address: ', 'dt_webform' ) . $new_lead_meta['ip_address'];
//        }
//
//        // form source
//        if ( ! isset( $new_lead_meta['form_title'] ) || empty( $new_lead_meta['form_title'] ) ) {
//            $notes['form_title'] = __( 'Source Form: Unknown (token: ', 'dt_webform' ) . $new_lead_meta['token'] . ')';
//        } else {
//            $notes['form_title'] = __( 'Source Form: ', 'dt_webform' )  . $new_lead_meta['form_title'];
//        }
//
//        $fields['notes'] = $notes;
//
//        // assign user
//        if ( isset( $form_meta['assigned_to'] ) && ! empty( $form_meta['assigned_to'] ) ) {
//            $fields['assigned_to'] = $form_meta['assigned_to'];
//            $fields['overall_status'] = 'assigned';
//        }
//
//        // Post to contact
//        if ( ! class_exists( 'Disciple_Tools_Contacts' ) ) {
//            return new WP_Error( 'disciple_tools_missing', 'Disciple Tools is missing.' );
//        }
//
//        dt_write_log( $fields );
//
//        // Create contact
//        $result = Disciple_Tools_Contacts::create_contact( $fields, $check_permission );
//
//        if ( is_wp_error( $result ) ) {
//            return new WP_Error( 'failed_to_insert_contact', $result->get_error_message() );
//        }
//
//        // Delete new lead after success
//        $delete_result = wp_delete_post( $new_lead_id, true );
//        if ( is_wp_error( $delete_result ) ) {
//            return new WP_Error( 'failed_to_delete_contact', $result->get_error_message() );
//        }
//
//        return $result;
//    }

    /**
     * Insert Post
     *
     * @return int|\WP_Error
     */
//    public static function insert_post( $params ) {
//        dt_write_log( __METHOD__ );
//
//        $params = array_filter( $params );
//        dt_write_log( $params );
//
//        // Prepare Insert
//        $args = [
//            'post_type' => 'dt_webform_new_leads',
//            'post_title' => sanitize_text_field( wp_unslash( $params['name'] ) ),
//            'comment_status' => 'closed',
//            'ping_status' => 'closed',
//        ];
//
//        foreach ( $params as $key => $value ) {
//            $key = sanitize_text_field( wp_unslash( $key ) );
//            if ( is_array( $value ) ) {
//                $value = dt_sanitize_array( $value );
//            }
//            else {
//                $value = sanitize_text_field( wp_unslash( $value ) );
//            }
//
//            $args['meta_input'][$key] = $value;
//        }
//
//        // get form_meta
//        $form_meta = [];
//        if ( ! empty( $params['token'] ) ) {
//            $form_meta = maybe_unserialize( self::get_form_meta( $params['token'] ) );
//        }
//
//        // add form_title
//        if ( ! isset( $args['meta_input']['form_title'] ) && ! empty( $form_meta ) ) {
//            $form_title = DT_Webform_Active_Form_Post_Type::get_form_title_by_token( $params['token'] );
//            $args['meta_input']['form_title'] = $form_title;
//        }
//
//        // add full form meta
//        if ( ! isset( $args['meta_input']['form_meta'] ) && ! empty( $form_meta ) ) {
//            $args['meta_input']['form_meta'] = self::get_form_meta( $params['token'] );
//        }
//
//        // add assigned to
//        if ( ! isset( $args['meta_input']['assigned_to'] ) && ! empty( $form_meta ) && isset( $form_meta['assigned_to'] ) ) {
//            $args['meta_input']['assigned_to'] = $form_meta['assigned_to'];
//        }
//
//        // add source
//        if ( ! isset( $args['meta_input']['source'] ) && ! empty( $form_meta ) && isset( $form_meta['source'] ) ) {
//            $args['meta_input']['source'] = $form_meta['source'];
//        }
//
//        // Add plugin status
//        if ( ! isset( $args['meta_input']['form_state'] ) || empty( $args['meta_input']['form_state'] ) ) {
//            $args['meta_input']['form_state'] = get_option( 'dt_webform_state' );
//        }
//
//        // Insert
//        $status = wp_insert_post( $args, true );
//        return $status;
//    }

}

