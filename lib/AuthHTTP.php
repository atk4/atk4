<?
class AuthHTTP {
    /*
     * This class will add authentication to your web application. All you need to do is:
     * $this->api->add('AuthWeb');
     */
    public $api;
    public $owner;
    public $dq;
    public $auth_data;
    
    function init(){
        $this->api->addHook('post-init',array($this,'authenticate'));
    }
    function setSource($table,$login='login',$password='password'){
        $this->dq=$this->api->db->dsql()
            ->table($table)
            ->field($login)
            ->field($password);
    }
    function authenticate(){
		if(!isset($_SERVER['PHP_AUTH_USER'])||(!isset($_SERVER['PHP_AUTH_PW']))){
			header('WWW-Authenticate: Basic realm="Private"');
			header('HTTP/1.0 401 Unauthorized');
		}else{
			//checking user
            $this->auth_data = $this->dq
                ->where('name',$_SERVER['PHP_AUTH_USER'])
                ->do_getHash();

			$this->authenticated = $this->auth_data['password'] == $_SERVER['PHP_AUTH_PW'];
                    // TODO - add cryptfunc support
            unset($this->auth_data['password']);
		}
		if(!$this->authenticated)throw new AuthException('Authorization Required');
	}
}
