<?php
namespace Spabby;
use Zend\Http\Client;

/**
 * Container class for Facebook integration, contains automatic authentication
 * @todo Add extended permissions requests
 * @todo Add update/delete methods for graph
 * @todo Add FQL handler https://developers.facebook.com/docs/reference/fql/ 
 * @todo Add proxy methods to Access https://developers.facebook.com/docs/reference/api/
 * @todo View helpers for the fb js class https://developers.facebook.com/docs/reference/javascript/
 * @todo View helpers for social plugins https://developers.facebook.com/docs/plugins/
 * @todo Meta tag generation https://developers.facebook.com/docs/reference/plugins/like/
 * @todo View helpers for the FB Dialog system https://developers.facebook.com/docs/reference/dialogs/
 * @todo Facebook Credits API integration (may be seperate module)
 */
class Facebook 
{
    /** 
     * @var string 
     **/
    protected $signedRequest;
    /**
     * @var string
     **/
    protected $fbSecret;
    /** 
     * @var string 
     **/
    protected $fbClientId;
    /** 
     * @var Spabby\Facebook\Auth 
     **/
    protected $auth;
    /** 
     * @var Access; 
     **/
    protected $api;
    /**
     * Configuration array, set using the constructor or using ::setConfig()
     *
     * @var array
     */
    protected $config = array(
        'useiframeauth' =>      true,
        'useconnectauth' =>     true
    );
    
    /**
     * Constructor
     * @param string $fbSecret  Facebook API Secret token
     * @param int $fbClientId  Facebook Client ID
     * @param string $signedRequest  Signed request string posted from Facebook (optional)
     * @param string $fbCode  Access code passed from Facebook (optional)
     * @param array $config  Configuration array (optional)
     */
    public function __construct($fbSecret, $fbClientId, 
            $signedRequest=null, $fbCode=null, array $config=null)
    {
        $this->fbSecret = $fbSecret;
        $this->fbClientId = $fbClientId;
        $this->signedRequest = $signedRequest;
        $this->fbCode = $fbCode;
        if ($config !== null) {
            $this->setConfig($config);
        }
    }
    
    /**
     * Set configuration parameters for this HTTP client
     *
     * @param  Config|array $config
     * @return Facebook
     * @throws Facebook\Exception
     */
    public function setConfig($config = array())
    {
        if ($config instanceof Config) {
            $config = $config->toArray();

        } elseif (!is_array($config)) {
            throw new InvalidArgumentException('Config parameter is not valid');
        }
        /** Config Key Normalization */
        foreach ($config as $k => $v) {
            $this->config[str_replace(array('-', '_', ' ', '.'), '', strtolower($k))] = $v; // replace w/ normalized
        }        
        return $this;
    }
    
    /**
     * Returns valid Facebook Auth object (if authentication is successful)
     * @return Facebook\Auth
     * @throws Facebook\Exception\AuthException
     */
    public function getAuth()
    {        
        // If no auth set, we can use Iframe auth, and the sigs are set, do it!
        if($this->auth instanceof Facebook\Auth === false
                && is_string($this->signedRequest) 
                && $this->config['useiframeauth'])
        {
            $this->auth = new Facebook\Auth\Iframe($this->fbSecret, $this->signedRequest);
        }
        // If no auth set, and we can use Connect auth, do it!
        if($this->auth instanceof Facebook\Auth === false
                && $this->config['useconnectauth'])
        {
            $this->auth = new Facebook\Auth\Connect(
                    $this->fbClientId, 
                    $this->fbSecret,
                    new \Zend\Http\Client(), 
                    $this->fbCode);
            $this->auth->getToken();
        }
        if($this->auth instanceof Facebook\Auth === false)
        {
            throw new Facebook\Exception\AuthException('No valid Auth adapter found');
        }
        return $this->auth;
    }
    
    /**
     * Returns the Facebook Access Object
     * @return Spabby\Facebook\Access
     */
    public function api()
    {
        if(!is_a($this->api, 'Access'))
        {
            $this->api = new Facebook\Access($this->getAuth(), 
                    new Client());            
        }
        return $this->api;
    }
        
}