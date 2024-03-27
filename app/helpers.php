<?php

use App\Models\CategorySupplier;

if (!function_exists('getSupplierName')) {
    function getSupplierName($id) {
        $supplierName = CategorySupplier::where('id',$id)->value('supplier_name');
        return  $supplierName;
    }
}

if (!function_exists('encryptData')) {
    // Encryption function
    function encryptData($data, $key, $salt) {
        $method = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        return base64_encode($salt . $iv . $encrypted);
    }
}

if (!function_exists('decryptData')) {
    // Decryption function
    function decryptData($data, $key) {
        $method = 'aes-256-cbc';
        $data = base64_decode($data);
        $salt = substr($data, 0, 16); // Extract salt
        $iv = substr($data, 16, 16); // Extract IV
        $encrypted = substr($data, 32); // Extract encrypted data
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }
}