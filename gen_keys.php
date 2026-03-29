<?php
$config = [
    'private_key_bits' => 4096,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];
$res = openssl_pkey_new($config);
if (!$res) {
    die("Failed to generate private key.");
}
openssl_pkey_export($res, $privkey, 'change_me_in_production');
$pubkey = openssl_pkey_get_details($res)['key'];
if (!is_dir(__DIR__ . '/config/jwt')) {
    mkdir(__DIR__ . '/config/jwt', 0777, true);
}
file_put_contents(__DIR__ . '/config/jwt/private.pem', $privkey);
file_put_contents(__DIR__ . '/config/jwt/public.pem', $pubkey);
echo "Keys generated successfully.\n";
