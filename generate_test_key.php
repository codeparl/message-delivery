<?php

$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'config' => 'C:/xampp/php/extras/openssl.cnf',
];

$key = @openssl_pkey_new($config);

// Fallback: try without config
if (!$key) {
    $key = @openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
}

if (!$key) {
    echo "ERROR: " . openssl_error_string();
    exit(1);
}

openssl_pkey_export($key, $privateKey, null, $config);
echo $privateKey;
