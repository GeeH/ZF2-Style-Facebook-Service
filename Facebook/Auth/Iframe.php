<?php
namespace Spabby\Facebook\Auth;
use Spabby\Facebook\Exception;

class Iframe implements \Spabby\Facebook\Auth
{    
    /**
     * Facebook secret for application
     * @var string
     */
    protected $fbSecret; 
    /**
     * Signed Request passed by Facebook
     * @var string
     */
    protected $signedRequest;
    /**
     * Decoded Facebook sigs
     * @var array
     */
    protected $decodedSigs;
    /**
     * Constructor
     * @param type string
     * @param type string
     */
    public function __construct($fbSecret, $signedRequest = null)
    {
        $this->fbSecret = $fbSecret;
        $this->signedRequest = $signedRequest;
    }
    
    /**
     * Gets the signed request
     * @return string
     */
    public function getSignedRequest()
    {
        return $this->signedRequest;
    }
    
    /**
     * Returns the decoded facebook sigs
     * @throws Spabby\Facebook\AuthException
     * @return array
     */
    public function getDecodedSigs()
    {
        if(!is_array($this->decodedSigs))
        {
            $this->decodedSigs = $this->parseSignedRequest();
        }
        if(!is_array($this->decodedSigs))
        {
            throw new AuthException("Invalid decoded sigs");
        }
        $decodedSigs = $this->decodedSigs;
        if(!array_key_exists('expires', $decodedSigs)
                || !array_key_exists('oauth_token', $decodedSigs)) 
        {
            throw new AuthException("Token details do not exist");
        }
        if($decodedSigs['expires'] < time())
        {
            throw new AuthException("Token has expired");
        }
        return $this->decodedSigs;
    }
    
    /**
     * Returns the Facebook oauth_token from the decoded sigs
     * @throws Spabby\Facebook\AuthException
     * @return string
     */
    public function getToken()
    {
        $decodedSigs = $this->getDecodedSigs();
        return (string) $decodedSigs['oauth_token'];
    }
    
    /**
     * Returns auth'd user's Facebook ID
     * @return string
     */
    public function getFacebookID()
    {
        $decodedSigs = $this->getDecodedSigs();
        return (string) $decodedSigs['user_id'];            
    }
    
    /**
     * Decodes signed request
     * @throws Spabby\Facebook\AuthException
     * @return array 
     */
    protected function parseSignedRequest()
    {
        // Check vars
        if (!is_string($this->signedRequest) || empty($this->signedRequest))
        {
            throw new AuthException('Invalid Signed Request');
        }
        if (!is_string($this->fbSecret) || empty($this->fbSecret))
        {
            throw new AuthException('Invalid Facebook Secret');
        }
        list($encoded_sig, $payload) = \explode('.', $this->signedRequest, 2);

        // Decode the data
        $sig = $this->base64UrlDecode($encoded_sig);
        $data = \json_decode($this->base64UrlDecode($payload), true);
        if (\strtoupper($data['algorithm']) !== 'HMAC-SHA256')
        {
            throw new AuthException('Invalid Signed Request');
        }

        // Check sig
        $expected_sig = \hash_hmac('sha256', $payload, $this->fbSecret, $raw = true);
        if ($sig !== $expected_sig) 
        {
            throw new AuthException('Invalid Signed Request');
        }

        return $data;

    }

    /**
     * Base 64 Url Decode string
     * @param string $input
     * @return string
     */
    protected function base64UrlDecode($input)
    {
        return \base64_decode(\strtr($input, '-_', '+/'));
    }
}

?>
