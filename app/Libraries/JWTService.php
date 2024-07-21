<?php namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService {
    private $key;
    private $alg;

    public function __construct() {
        //TODO: Switch to ENV KEY
        $this->key = 'fUt5w4vDoAvCjsKDw5HDtFHCnsKBwrDCqcKqwptKwpk+wrNhe01nw50iw4c=';
        $this->alg = 'HS256';
    }

    public function encode(array $data, int $expirationSec = 3600): string
    {
        $time = time();
        $payload  = array(
            "iss" => "http://api.safekey.local",
            "aud" => "http://api.safekey.local",
            "iat" => $time,
            "nbf" => $time,
            "exp" => $time + $expirationSec,
            "data" => $data
        );
        return JWT::encode($payload, $this->key, $this->alg);
    }

    public function decode($jwt): array|bool
    {
        try {
            $decoded = JWT::decode($jwt, new Key($this->key, $this->alg));
            return (array) $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }
}
