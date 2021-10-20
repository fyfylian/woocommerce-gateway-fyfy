<?php

$woo_fyfy_has_site_icon = !empty( get_site_icon_url() );
$woo_fyfy_has_https     = (!empty($_SERVER[ 'HTTPS' ]) && $_SERVER[ 'HTTPS' ] !== 'off') || $_SERVER[ 'SERVER_PORT' ] === 443;
$woo_fyfy_has_extension = function_exists('\gmp_init') || function_exists('\bcmul');

/* translators: %s: Full cryptocurrency name, 'Bitcoin' or 'Ethereum' */
$woo_fyfy_no_extension_error = __( 'You must install & enable either the <code>php-bcmath</code> or <code>php-gmp</code> extension to accept %s with <strong>Fyfy Pay Checkout</strong>.', 'wc-gateway-fyfy' );

$woo_fyfy_redirect_behaviour_options = [ 'popup' => 'Popup' ];
if ( $woo_fyfy_has_https ) {
    $woo_fyfy_redirect_behaviour_options['redirect'] = 'Redirect';
}

// List available price services here. The option value must match the file name.
$woo_fyfy_price_services = [
    'coingecko' => 'Coingecko',
    // 'fyfy'    => 'Fyfy (Fyfy only)',
];
$woo_fyfy_price_service_default = 'coingecko';
if ( in_array( get_option( 'woocommerce_currency' ), [ 'EUR', 'USD' ] ) ) {
    $woo_fyfy_price_services['fastspot'] = 'Fastspot (' . __( 'also estimates fees', 'wc-gateway-fyfy' ) . ')';
    $woo_fyfy_price_service_default = 'fastspot';
}

$woo_fyfy_checkout_settings = [
    'shop_logo_url' => [
        'title'       => __( 'Shop Logo', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Display your logo in Fyfy Checkout by entering a URL to an image file here. ' .
                             'The file must be on the same domain as your webshop. ' .
                             'The image should be quadratic for best results.', 'wc-gateway-fyfy' ),
        'placeholder' => $woo_fyfy_has_site_icon
            ? __( 'Enter URL or leave empty to use your WordPress\'s site icon.', 'wc-gateway-fyfy' )
            : __( 'Enter URL to display your logo during checkout.', 'wc-gateway-fyfy' ),
        'desc_tip'    => true,
        'custom_attributes' => [
            'data-site-icon' => get_site_icon_url() ?: get_site_url() . '/wp-content/plugins/woocommerce-gateway-fyfy/assets/icon.svg',
        ],
    ],

    'instructions' => [
        'title'       => __( 'Email Instructions', 'wc-gateway-fyfy' ),
        'type'        => 'textarea',
        'description' => __( 'Instructions that will be added to the thank-you page and emails.', 'wc-gateway-fyfy' ),
        'default'     => __( 'You will receive email updates after your payment has been confirmed and when your order has been shipped.', 'wc-gateway-fyfy' ),
        'desc_tip'    => true,
    ],

    'section_fyfy' => [
        'title'       => 'Fyfy',
        'type'        => 'title',
        /* translators: %s: Full cryptocurrency name, e.g. 'Fyfy', 'Bitcoin' or 'Ethereum' */
        'description' => sprintf( __( 'All %s-related settings', 'wc-gateway-fyfy' ), 'Fyfy'),
        'class'       => 'section-fyfy',
    ],

    'fyfy_address' => [
        'title'       => __( 'Wallet Address', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'The Fyfy address that your customers will pay to.', 'wc-gateway-fyfy' ),
        'placeholder' => 'FY...',
        'desc_tip'    => true,
        'class'       => 'required',
    ],

    'message' => [
        'title'       => __( 'Transaction Message', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Enter a message that should be included in every transaction. 50 characters maximum.', 'wc-gateway-fyfy' ),
        'default'     => __( 'Thank you for shopping with us!', 'wc-gateway-fyfy' ),
        'desc_tip'    => true,
    ],

    'validation_service_fyfy' => [
        'title'       => __( 'Chain Monitoring Service', 'wc-gateway-fyfy' ),
        'type'        => 'select',
        'description' => __( 'Which service should be used for monitoring the Fyfy blockchain.', 'wc-gateway-fyfy' ),
        'default'     => 'fyfy_watch',
        'options'     => [
            // List available validation services here. The option value must match the file name.
            'fyfy_watch'  => 'FYFYIO.WATCH (Testnet & Mainnet)',
            'json_rpc_fyfy' => 'Fyfy JSON-RPC API (Network configured by Fyfy node)',
            'fyfy'       => 'Fyfy (Mainnet)',
        ],
        'desc_tip'    => true,
    ],

    'jsonrpc_fyfy_url' => [
        'title'       => __( 'JSON-RPC URL', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Full URL (including port) of the Fyfy JSON-RPC server used to monitor the Fyfy blockchain.', 'wc-gateway-fyfy' ),
        'default'     => 'http://localhost:8648',
        'placeholder' => __( 'This field is required when accepting Ethereum.', 'wc-gateway-fyfy' ),
        'desc_tip'    => true,
        'class'       => 'required',
    ],

    'jsonrpc_fyfy_username' => [
        'title'       => __( 'JSON-RPC Username', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Username for the protected JSON-RPC service. (Optional)', 'wc-gateway-fyfy' ),
        'desc_tip'    => true,
    ],

    'jsonrpc_fyfy_password' => [
        'title'       => __( 'JSON-RPC Password', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Password for the protected JSON-RPC service. (Optional)', 'wc-gateway-fyfy' ),
        'desc_tip'    => true,
    ],

    'fyfy_api_key' => [
        'title'       => __( 'Fyfy API Key', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Key for accessing the Fyfy exchange rate and chain monitoring service. Visit fyfy.io to sign up for a key.', 'wc-gateway-fyfy' ),
        'placeholder' => __( 'This field is required.', 'wc-gateway-fyfy' ),
        'desc_tip'    => true,
        'class'       => 'required',
    ],

    'section_bitcoin' => [
        'title'       => 'Bitcoin',
        'type'        => 'title',
        'description' => $woo_fyfy_has_extension
            ? sprintf( __( 'All %s-related settings', 'wc-gateway-fyfy' ), 'Bitcoin')
            : sprintf( $woo_fyfy_no_extension_error, 'Bitcoin' ),
        'class'       => $woo_fyfy_has_extension ? 'section-bitcoin' : 'section-bitcoin-disabled',
    ],

    'bitcoin_xpub' => [
        'title'       => __( 'Wallet Account Public Key', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Your Bitcoin xpub/zpub/tpub/vpub "Master Public Key" from which payment addresses are derived.', 'wc-gateway-fyfy' ),
        'placeholder' => 'xpub...',
        'desc_tip'    => true,
    ],

    'bitcoin_xpub_type' => [
        'title'       => __( 'Public Key Type', 'wc-gateway-fyfy' ),
        'type'        => 'select',
        'description' => __( 'The derivation type of the public key. Usually, you do not have to change this. But there are wallets such as Coinomi that will show a field called "Derivation" or "BIP32" that looks similar to the values in the select box, in that case, pick the value that matches the one shown in your wallet.', 'wc-gateway-fyfy' ),
        'default'     => 'bip-44',
        'options'     => [
            'bip-44'  => __('Legacy', 'wc-gateway-fyfy') . ' (m/44\'/0\'/0\')',
            // 'bip-49'  => __('SegWit Compat', 'wc-gateway-fyfy') . ' (m/49\'/0\'/0\')', // Not yet supported by fyfy/xpub
            'bip-84'  => __('Native SegWit', 'wc-gateway-fyfy') . ' (m/84\'/0\'/0\')',
        ],
        'desc_tip'    => true,
    ],

    'validation_service_btc' => [
        'title'       => __( 'Chain Monitoring Service', 'wc-gateway-fyfy' ),
        'type'        => 'select',
        'description' => __( 'Which service should be used for monitoring the Bitcoin blockchain.', 'wc-gateway-fyfy' ),
        'default'     => 'blockstream',
        'options'     => [
            // List available validation services here. The option value must match the file name.
            'blockstream'  => 'Blockstream.info (testnet & mainnet)',
        ],
        'desc_tip'    => true,
    ],

    'section_ethereum' => [
        'title'       => 'Ethereum',
        'type'        => 'title',
        'description' => $woo_fyfy_has_extension
            ? sprintf( __( 'All %s-related settings', 'wc-gateway-fyfy' ), 'Ethereum')
            : sprintf( $woo_fyfy_no_extension_error, 'Ethereum' ),
        'class'       => $woo_fyfy_has_extension ? 'section-ethereum' : 'section-ethereum-disabled',
    ],

    'ethereum_xpub' => [
        'title'       => __( 'Wallet Account Public Key', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Your Ethereum xpub "Account Public Key" from which payment addresses are derived.', 'wc-gateway-fyfy' ),
        'placeholder' => 'xpub...',
        'desc_tip'    => true,
    ],

    'reuse_eth_addresses' => [
        // 'title'       => '',
        'type'        => 'checkbox',
        'description' => __( 'Re-using addresses reduces your shop\'s privacy but gives you the comfort of having payments distributed over less addresses.', 'wc-gateway-fyfy' ),
        'label'       => __( 'Re-use Addresses', 'wc-gateway-fyfy' ),
        'default'     => 'no',
        // 'desc_tip'    => true,
    ],

    'validation_service_eth' => [
        'title'       => __( 'Chain Monitoring Service', 'wc-gateway-fyfy' ),
        'type'        => 'select',
        'description' => __( 'Which service should be used for monitoring the Ethereum blockchain.', 'wc-gateway-fyfy' ),
        'default'     => 'etherscan',
        'options'     => [
            // List available validation services here. The option value must match the file name.
            'etherscan'  => 'Etherscan.io (testnet & mainnet)',
        ],
        'desc_tip'    => true,
    ],

    'etherscan_api_key' => [
        'title'       => __( 'Etherscan.io API Key', 'wc-gateway-fyfy' ),
        'type'        => 'text',
        'description' => __( 'Token for accessing the Etherscan chain monitoring service.', 'wc-gateway-fyfy' ),
        'placeholder' => __( 'This field is required.', 'wc-gateway-fyfy' ),
        'desc_tip'    => true,
        'class'       => 'required',
    ],

    'section_advanced' => [
        'title'       => 'Advanced',
        'type'        => 'title',
        'description' => 'Settings for advanced users. Only touch if you know what you are doing.',
        'class'       => 'section-advanced'
    ],

    'network' => [
        'title'       => __( 'Network Mode', 'wc-gateway-fyfy' ),
        'type'        => 'select',
        'description' => __( 'Which network to use: Testnet for testing, Mainnet when the shop is running live.', 'wc-gateway-fyfy' ),
        'default'     => 'main',
        'options'     => [ 'main' => 'Mainnet', 'test' => 'Testnet' ],
        'desc_tip'    => true,
    ],

    'price_service' => [
        'title'       => __( 'Exchange Rate service', 'wc-gateway-fyfy' ),
        'type'        => 'select',
        'description' => __( 'Which service to use for fetching price information for currency conversion.', 'wc-gateway-fyfy' ),
        'default'     => $woo_fyfy_price_service_default,
        'options'     => $woo_fyfy_price_services,
        'desc_tip'    => true,
    ],

    'fee_fyfy' => [
        'title'       => __( 'FYFY Fee per Byte [Luna]', 'wc-gateway-fyfy' ),
        'type'        => 'number',
        'description' => __( 'Lunas per byte to be applied to transactions.', 'wc-gateway-fyfy' ),
        /* translators: %1$d: Amount, %2$s: Unit of amount */
        'placeholder' => sprintf( __( 'Optional - Default: %1$d %2$s', 'wc-gateway-fyfy' ), 1, 'Luna' ),
        'desc_tip'    => true,
    ],

    'fee_btc' => [
        'title'       => __( 'BTC Fee per Byte [Sat]', 'wc-gateway-fyfy' ),
        'type'        => 'number',
        'description' => __( 'Satoshis per byte to be applied to transactions.', 'wc-gateway-fyfy' ),
        'placeholder' => sprintf( __( 'Optional - Default: %1$d %2$s', 'wc-gateway-fyfy' ), 40, 'Satoshi' ),
        'desc_tip'    => true,
    ],

    'fee_eth' => [
        'title'       => __( 'ETH Gas Price [Gwei]', 'wc-gateway-fyfy' ),
        'type'        => 'number',
        'description' => __( 'Gas price in Gwei to be applied to transactions.', 'wc-gateway-fyfy' ),
        'placeholder' => sprintf( __( 'Optional - Default: %1$d %2$s', 'wc-gateway-fyfy' ), 8, 'Gwei' ),
        'desc_tip'    => true,
    ],

    'margin' => [
        'title'       => __( 'Margin Percentage', 'wc-gateway-fyfy' ),
        'type'        => 'number',
        'description' => __( 'A margin to apply to crypto payments, in percent. Can also be negative.', 'wc-gateway-fyfy' ),
        'placeholder' => 'Optional - Default: 0%',
        'desc_tip'    => true,
        'custom_attributes' => [
            'step'=> 0.1,
        ],
    ],

    'validation_interval' => [
        'title'       => __( 'Validation Interval [minutes]', 'wc-gateway-fyfy' ),
        'type'        => 'number',
        'description' => __( 'Interval between validating transactions, in minutes. If you change this, disable and enable this plugin to apply the new interval.', 'wc-gateway-fyfy' ),
        /* translators: %d: Number of minutes */
        'placeholder' => sprintf( __( 'Optional - Default: %d minutes', 'wc-gateway-fyfy' ), 5 ),
        'desc_tip'    => true,
    ],

    'rpc_behavior' => [
        'title'       => __( 'Checkout Behavior', 'wc-gateway-fyfy' ),
        'type'        => 'select',
        'description' => __( 'How should the user be forwarded to Fyfy Checkout to finalize the payment process, as a popup or by being redirected?', 'wc-gateway-fyfy' ),
        'default'     => 'popup',
        'options'     => $woo_fyfy_redirect_behaviour_options,
        'desc_tip'    => true,
    ],

    'tx_wait_duration' => [
        'title'       => __( 'Payment Timeout', 'wc-gateway-fyfy' ),
        'type'        => 'number',
        'description' => __( 'How many minutes to wait for a payment transaction before considering the order to have failed.', 'wc-gateway-fyfy' ),
        'placeholder' => sprintf( __( 'Optional - Default: %d minutes', 'wc-gateway-fyfy' ), 120 ),
        'desc_tip'    => true,
    ],

    'confirmations_fyfy' => [
        /* translators: %s: Cryptocurrency name */
        'title'       => sprintf( __( 'Required confirmations for %s', 'wc-gateway-fyfy' ), 'Fyfy'),
        'type'        => 'number',
        'description' => __( 'The number of confirmations required to accept a Fyfy transaction. Each confirmation takes 1 minute on average.', 'wc-gateway-fyfy' ),
        /* translators: %d: Number of blocks */
        'placeholder' => sprintf( __( 'Optional - Default: %d blocks', 'wc-gateway-fyfy' ), 10 ),
        'desc_tip'    => true,
    ],

    'confirmations_btc' => [
        'title'       => sprintf( __( 'Required confirmations for %s', 'wc-gateway-fyfy' ), 'Bitcoin'),
        'type'        => 'number',
        'description' => __( 'The number of confirmations required to accept a Bitcoin transaction. Each confirmation takes 10 minutes on average.', 'wc-gateway-fyfy' ),
        'placeholder' => sprintf( __( 'Optional - Default: %d blocks', 'wc-gateway-fyfy' ), 2 ),
        'desc_tip'    => true,
    ],

    'confirmations_eth' => [
        'title'       => sprintf( __( 'Required confirmations for %s', 'wc-gateway-fyfy' ), 'Ethereum'),
        'type'        => 'number',
        'description' => __( 'The number of confirmations required to accept an Ethereum transaction. Each confirmation takes 15 seconds on average.', 'wc-gateway-fyfy' ),
        'placeholder' => sprintf( __( 'Optional - Default: %d blocks', 'wc-gateway-fyfy' ), 45 ),
        'desc_tip'    => true,
    ],

    // 'current_address_index_btc' => [
    //     'title'       => __( '[BTC Address Index]', 'wc-gateway-fyfy' ),
    //     'type'        => 'number',
    //     'min'    => '-1',
    //     'description' => __( 'DO NOT CHANGE! The current BTC address derivation index.', 'wc-gateway-fyfy' ),
    //     'default'     => -1,
    //     'desc_tip'    => true,
    // ],

    // 'current_address_index_eth' => [
    //     'title'       => __( '[ETH Address Index]', 'wc-gateway-fyfy' ),
    //     'type'        => 'number',
    //     'min'    => '-1',
    //     'description' => __( 'DO NOT CHANGE! The current ETH address derivation index.', 'wc-gateway-fyfy' ),
    //     'default'     => -1,
    //     'desc_tip'    => true,
    // ],
];
