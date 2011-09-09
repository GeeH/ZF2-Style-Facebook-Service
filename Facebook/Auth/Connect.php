<?php
namespace Spabby\Facebook\Auth;
use Spabby\Facebook\Exception;
use Zend\Session\Container as Session;

class Connect implements \Spabby\Facebook\Auth
{    
    /**
     * Facebook Client ID
     * @var string
     */
    protected $fbClientId; 
    /**
     * Facebook Secret
     * @var string
     */
    protected $fbSecret;
    /**
     * Facebook handshake code
     * @var string
     */
    protected $fbCode;    
    /** 
     * Http Client
     * @var \Zend\Http\Client
     */
    protected $http;
    /**
     * Session Container
     * @var \Zend\Session\Container
     */
    protected $session;
    
    const FACEBOOK_REDIRECT_URI = 'https://www.facebook.com/dialog/oauth';    
    const FACEBOOK_AUTH_URI = 'https://graph.facebook.com/oauth/access_token';
    
    public function __construct(
            $fbClientId, 
            $fbSecret, 
            \Zend\Http\Client $http,            
            $fbCode=null)
    {
        $this->fbClientId = $fbClientId;
        $this->fbSecret = $fbSecret;
        $this->http = $http;
        $this->fbCode = $fbCode;
        $this->session = new Session('Spabby_Facebook');        
    }
    
    public function getCode()
    {        
        if(!is_string($this->fbCode) && !$this->session->fbCode)
        {
            $uri = self::FACEBOOK_REDIRECT_URI
                    .'?client_id='.$this->fbClientId
                    .'?&redirect_uri=http://'.$_SERVER['HTTP_HOST'].'/'
                    .'&scope=';            
            header('location: '.$uri);
        }        
        if($this->fbCode)
        {            
            $this->session->fbCode = $this->fbCode;          
            header('location: http://'.$_SERVER['HTTP_HOST']);
        }        
        return $this->session->fbCode;
    }
    
    public function getToken() 
    {        
        if($this->session->fbToken && $this->session->fbTokenExpires)
        {
            if (time() <= $this->session->fbTokenExpires)
            {
                return $this->session->fbToken;
            }
        }
        $code = $this->getCode();
        $this->http->setUri(self::FACEBOOK_AUTH_URI);
        $this->http->setParameterGet(array(
            'client_id' => $this->fbClientId,
            'redirect_uri' => 'http://'.$_SERVER['HTTP_HOST'].'/',
            'client_secret' => $this->fbSecret, 
            'code' => $code
        ));                
        $response =  $this->http->send();        
        if($response->getStatusCode() != \Zend\Http\Response::STATUS_CODE_200)
        {
            throw new Exception\AuthException('Bad response from Facebook OAuth');
        }        
        
        parse_str($response->getBody(), $parsedResponse);
        
        if(!array_key_exists('access_token', $parsedResponse) || !array_key_exists('expires', $parsedResponse))
        {
            throw new Exception\AuthException('Bad parse from Facebook OAuth');
        } 
        
        $this->session->fbToken = $parsedResponse['access_token'];         
        $this->session->fbTokenExpires = time()+$parsedResponse['expires'];
        $this->session->offsetUnset('fbCode');
        return $this->session->fbToken;
    }                
}
