<?php
include_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'interface.php' );

class WC_Gateway_Fyfy_Service_FyfyX implements WC_Gateway_Fyfy_Validation_Service_Interface {
    /**
     * Initializes the validation service
     * @param {WC_Gateway_Fyfy} $gateway - A WC_Gateway_Fyfy class instance
     * @return {void}
     */
    public function __construct( $gateway ) {
        $this->transaction = null;
        $this->head_height = null;

        if ( $gateway->get_option( 'network' ) !== 'main' ) {
            throw new Exception( __( 'FyfyX can only be used for mainnet.', 'wc-gateway-fyfy' ) );
        }

        $this->api_key = $gateway->get_option( 'fyfy_api_key' );
        if ( empty( $this->api_key ) ) {
            throw new Exception( __( 'API key not set.', 'wc-gateway-fyfy' ) );
        }
        if ( !ctype_xdigit( $this->api_key ) ) {
            throw new Exception( __( 'Invalid API key.', 'wc-gateway-fyfy' ) );
        }
    }

    /**
     * Retrieves the current blockchain head height
     * @return {number|WP_Error}
     */
    public function blockchain_height() {
        if ( !empty( $this->head_height ) ) {
            return $this->head_height;
        }

        $api_response = wp_remote_get( $this->makeUrl( 'network-stats' ) );

        if ( is_wp_error( $api_response ) ) {
            return $api_response;
        }

        $network_stats = json_decode( $api_response[ 'body' ] );

        if ( $network_stats->error ) {
            return new WP_Error( 'service', $network_stats->error );
        }

        $this->head_height = $network_stats->height;
        return $this->head_height;
    }

    /**
     * Loads a transaction from the service
     * @param {string} $transaction_hash - Transaction hash as HEX string
     * @param {WP_Order} $order
     * @param {WC_Gateway_Fyfy} $gateway
     * @return {'NOT_FOUND'|'PAID'|'OVERPAID'|'UNDERPAID'|WP_Error}
     */
    public function load_transaction( $transaction_hash, $order, $gateway ) {
        $this->transaction = null;

        // Automatic transaction finding is not yet available for Fyfy
        if ( empty( $transaction_hash ) ) return 'NOT_FOUND';

        if ( !ctype_xdigit( $transaction_hash ) ) {
            return new WP_Error('service', __( 'Invalid transaction hash.', 'wc-gateway-fyfy' ) );
        }

        $head_height = $this->blockchain_height();
        if ( is_wp_error( $head_height ) ) {
            return $head_height;
        }

        $api_response = wp_remote_get( $this->makeUrl( 'transaction/' . $transaction_hash ) );

        if ( is_wp_error( $api_response ) ) {
            return $api_response;
        }

        $transaction = json_decode( $api_response[ 'body' ] );

        if ( $transaction->error ) {
            return new WP_Error( 'service', $transaction->error );
        }

        $this->transaction = $transaction;
        return $this->transaction_found() ? 'PAID' : 'NOT_FOUND';
    }

    /**
     * Returns if transaction was found or not
     * @return {boolean}
     */
    public function transaction_found() {
        return !!$this->transaction;
    }

    /**
     * Returns any error that the service returned
     * @return {string|false}
     */
    public function error() {
        return $this->transaction->error ?: false;
    }

    /**
     * Returns the userfriendly address of the transaction sender
     * @return {string}
     */
    public function sender_address() {
        return $this->transaction->from_address;
    }

    /**
     * Returns the userfriendly address of the transaction recipient
     * @return {string}
     */
    public function recipient_address() {
        return $this->transaction->to_address;
    }

    /**
     * Returns the value of the transaction in the smallest unit
     * @return {string}
     */
    public function value() {
        return strval( $this->transaction->value );
    }

    /**
     * Returns the data (message) of the transaction in plain text
     * @return {string}
     */
    public function message() {
        $extraData = base64_decode( $this->transaction->data );
        return mb_convert_encoding( $extraData, 'UTF-8' );
    }

    /**
     * Returns the height of the block containing the transaction
     * @return {number}
     */
    public function block_height() {
        return $this->transaction->height;
    }

    /**
     * Returns the confirmations of the transaction
     * @return {number}
     */
    public function confirmations() {
        return $this->blockchain_height() + 1 - $this->block_height();
    }

    private function makeUrl( $path ) {
        return 'https://api.fyfy.com/' . $path . '?api_key=' . $this->api_key;
    }
}

$services['fyfy'] = new WC_Gateway_Fyfy_Service_FyfyX( $gateway );
