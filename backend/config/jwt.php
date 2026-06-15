<?php

define('JWT_SECRET', 'pc_store_secret_key_change_in_production');
define('JWT_EXPIRY', 86400); // 24 hours in seconds

function jwtEncode(array $payload): string {
    $header = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

    $payload['exp'] = time() + JWT_EXPIRY;
    $body = base64UrlEncode(json_encode($payload));

    $signature = base64UrlEncode(
        hash_hmac('sha256', "$header.$body", JWT_SECRET, true)
    );

    return "$header.$body.$signature";
}

function jwtDecode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $body, $signature] = $parts;

    $expectedSig = base64UrlEncode(
        hash_hmac('sha256', "$header.$body", JWT_SECRET, true)
    );

    if (!hash_equals($expectedSig, $signature)) return null;

    $payload = json_decode(base64UrlDecode($body), true);
    if (!$payload || !isset($payload['exp'])) return null;

    if ($payload['exp'] < time()) return null;

    return $payload;
}

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}
