<?php
namespace Spabby;
use Zend\Http\Client;


class Facebook 
{
    /** @var string **/
    protected $signedRequest;
    /** @var string **/
    protected $fbSecret;
    /** @var string **/
    protected $fbClientId;
    /** @var Spabby\Facebook\Auth **/
    protected $auth;
    /** @var Access; **/
    protected $fbAccess;
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
     * @param string $fbSecret Facebook API Secret token
     * @param string $signedRequest Signed request string posted from Facebook
     */
    public function __construct($fbSecret, $fbClientId, 
            $signedRequest=null, $fbCode=null, $config=null)
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
     * @return Client
     * @throws Client\Exception
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
    protected function getAccess()
    {
        if(!is_a($this->fbAccess, 'Access'))
        {
            $this->fbAccess = new Facebook\Access($this->getAuth(), 
                    new Client());            
        }
        return $this->fbAccess;
    }
    
    /**
     * Proxy to the get data from the graph api
     * @param string $method Method to get in camel case
     * @return \stdClass
     */
    public function __get($method)
    {
        $method = 'get'.\ucfirst($method);
        if(!\method_exists($this->getAccess(), $method))
        {
            throw new AccessException("No such method {$method}");
        }
        return $this->getAccess()->$method();
    }
}