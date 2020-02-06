<?php

defined( 'ABSPATH' ) || exit;

return array(
    'enabled' => array(
        'title' => __('Enable / Disable', 'chimera_gateway'),
        'label' => __('Enable this payment gateway', 'chimera_gateway'),
        'type' => 'checkbox',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'chimera_gateway'),
        'type' => 'text',
        'desc_tip' => __('Payment title the customer will see during the checkout process.', 'chimera_gateway'),
        'default' => __('Chimera Gateway', 'chimera_gateway')
    ),
    'description' => array(
        'title' => __('Description', 'chimera_gateway'),
        'type' => 'textarea',
        'desc_tip' => __('Payment description the customer will see during the checkout process.', 'chimera_gateway'),
        'default' => __('Pay securely using Chimera. You will be provided payment details after checkout.', 'chimera_gateway')
    ),
    'discount' => array(
        'title' => __('Discount for using Chimera', 'chimera_gateway'),
        'desc_tip' => __('Provide a discount to your customers for making a private payment with Chimera', 'chimera_gateway'),
        'description' => __('Enter a percentage discount (i.e. 5 for 5%) or leave this empty if you do not wish to provide a discount', 'chimera_gateway'),
        'type' => __('number'),
        'default' => '0'
    ),
    'valid_time' => array(
        'title' => __('Order valid time', 'chimera_gateway'),
        'desc_tip' => __('Amount of time order is valid before expiring', 'chimera_gateway'),
        'description' => __('Enter the number of seconds that the funds must be received in after order is placed. 3600 seconds = 1 hour', 'chimera_gateway'),
        'type' => __('number'),
        'default' => '3600'
    ),
    'confirms' => array(
        'title' => __('Number of confirmations', 'chimera_gateway'),
        'desc_tip' => __('Number of confirms a transaction must have to be valid', 'chimera_gateway'),
        'description' => __('Enter the number of confirms that transactions must have. Enter 0 to zero-confim. Each confirm will take approximately four minutes', 'chimera_gateway'),
        'type' => __('number'),
        'default' => '10'
    ),
    'turtlecoin_address' => array(
        'title' => __('Chimera Address', 'chimera_gateway'),
        'label' => __('Public Chimera Address'),
        'type' => 'text',
        'desc_tip' => __('Chimera Wallet Address (CMRA)', 'chimera_gateway')
    ),
    'daemon_host' => array(
        'title' => __('Chimera-Service Host/IP', 'chimera_gateway'),
        'type' => 'text',
        'desc_tip' => __('This is the chimera-service Host/IP to authorize the payment with', 'chimera_gateway'),
        'default' => '127.0.0.1',
    ),
    'daemon_port' => array(
        'title' => __('Chimera-Service Port', 'chimera_gateway'),
        'type' => __('number'),
        'desc_tip' => __('This is the chimera-service port to authorize the payment with', 'chimera_gateway'),
        'default' => '8070',
    ),
    'daemon_password' => array(
        'title' => __('Chimera-Service Password', 'chimera_gateway'),
        'type' => 'text',
        'desc_tip' => __('This is the chimera-service password to authorize the payment with', 'chimera_gateway'),
        'default' => '',
    ),
    'show_qr' => array(
        'title' => __('Show QR Code', 'chimera_gateway'),
        'label' => __('Show QR Code', 'chimera_gateway'),
        'type' => 'checkbox',
        'description' => __('Enable this to show a QR code after checkout with payment details.'),
        'default' => 'no'
    ),
    'use_chimera_price' => array(
        'title' => __('Show Prices in Chimera', 'chimera_gateway'),
        'label' => __('Show Prices in Chimera', 'chimera_gateway'),
        'type' => 'checkbox',
        'description' => __('Enable this to convert ALL prices on the frontend to Chimera (experimental)'),
        'default' => 'no'
    ),
    'use_chimera_price_decimals' => array(
        'title' => __('Display Decimals', 'chimera_gateway'),
        'type' => __('number'),
        'description' => __('Number of decimal places to display on frontend. Upon checkout exact price will be displayed.'),
        'default' => 2,
    ),
);
