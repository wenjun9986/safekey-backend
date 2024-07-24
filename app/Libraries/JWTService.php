<?php namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService {
    private $key;
    private $alg;

    public function __construct() {
        $this->key = env('JWT_SECRET_KEY');
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
