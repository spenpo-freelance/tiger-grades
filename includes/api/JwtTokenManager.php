<?php
namespace Spenpo\TigerGrades\API;
class JwtTokenManager {
    private $encryption_key;
    private $token_prefix;
    
    public function __construct($token_prefix = 'default_api') {
        $this->encryption_key = defined('JWT_ENCRYPTION_KEY') ? JWT_ENCRYPTION_KEY : getenv('JWT_ENCRYPTION_KEY');
        $this->token_prefix = sanitize_key($token_prefix);
    }
    
    private function get_token_key() {
        return "{$this->token_prefix}_jwt_token";
    }
    
    private function get_refresh_token_key() {
        return "{$this->token_prefix}_refresh_token";
    }
    
    public function store_token($token, $expires_in = 3600) {
        if (empty($token)) {
            return false;
        }
        
        $encrypted = $this->encrypt($token);
        return set_transient($this->get_token_key(), $encrypted, $expires_in);
    }
    
    public function get_token() {
        $encrypted = get_transient($this->get_token_key());
        if (!$encrypted) {
            return false;
        }
        
        return $this->decrypt($encrypted);
    }
    
    public function store_refresh_token($token) {
        if (empty($token)) {
            return false;
        }
        
        $encrypted = $this->encrypt($token);
        return update_option($this->get_refresh_token_key(), $encrypted, true);
    }
    
    public function get_refresh_token() {
        $encrypted = get_option($this->get_refresh_token_key());
        if (!$encrypted) {
            return false;
        }
        
        return $this->decrypt($encrypted);
    }
    
    private function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $this->encryption_key,
            0,
            $iv
        );
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    private function decrypt($encrypted_data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_data), 2);
        
        return openssl_decrypt(
            $encrypted_data,
            'aes-256-cbc',
            $this->encryption_key,
            0,
            $iv
        );
    }
    
    public function delete_tokens() {
        delete_transient($this->get_token_key());
        delete_option($this->get_refresh_token_key());
    }
}