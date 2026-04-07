<?php

namespace App\Utils;

use Eher\OAuth\SignatureMethod;

class HmacSha256 extends SignatureMethod
{
    public function get_name()
    {
        return 'HMAC-SHA256';
    }

    public function build_signature($request, $consumer, $token)
    {
        $base_string = $request->get_signature_base_string();
        $key_parts = [
            $consumer->secret,
            ($token) ? $token->secret : ''
        ];

        $key_parts = array_map('rawurlencode', $key_parts);
        $key = implode('&', $key_parts);

        return base64_encode(hash_hmac('sha256', $base_string, $key, true));
    }
}