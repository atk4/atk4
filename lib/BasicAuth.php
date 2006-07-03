<?
/*
 * Mandatory Authorization module. Once you add this to your API, it will protect
 * it without any further actions.
 */
class BasicAuth extends AbstractController {
    public $info=false;

    protected $password=null;     // this is password to let people in

    protected $form;

    function init(){
        parent::init();
        $this->api->auth=$this;
        $this->info=$this->recall('info',false);
        if($this->api->page=='Logout'){
            $this->forget('info');
            setcookie($this->name."_user",null);
            setcookie($this->name."_password",null);
            $this->info=false;
            $this->api->redirect('Index');
        }
    }
    function setPassword($password){
        $this->password=$password;
        return $this;
    }
    function check(){
        if(!$this->isLoggedIn()){
            // verify if cookie is present
            if(isset($_COOKIE[$this->name."_user"]) && isset($_COOKIE[$this->name."_password"])){
                if($this->verifyCredintals(
                            $_COOKIE[$this->name."_user"],
                            $_COOKIE[$this->name."_password"]
                           )){
                    // cookie login was successful
                    $this->loggedIn();
                    return;
                }
            }
            $this->processLogin();
        }
    }
    function isLoggedIn(){
        if($this->info)return true;
    }
    function verifyCredintals($user,$password){
        return $user.'123'==$password;
    }
    function loggedIn(){
        $this->info=array('auth'=>true);
        $this->memorize('info',$this->info);
        if($this->form && $this->form->get('memorize')){
            setcookie($this->name."_user",$this->form->get('username'),time()+60*60*24*30*6);
            setcookie($this->name."_password",$this->form->get('password'),time()+60*60*24*30*6);

        }
        unset($_GET['submit']);
        unset($_GET['page']);
    }
    function processLogin(){
        // this function is called when authorization is not found. 
        // You should return true, if login process was successful.

        // Initialize an empty page
        $p=$this->add('Page');
        $p->template->loadTemplate('empty');
        $this->form=$p->frame('Content','Authentication')
            ->add('Form',null,'content');

        $this->form
            ->addComment('Access to this resource is only allowed if you know a secret phrase. Enter it here:')
            ->addField('Line','username','Username')
            ->addField('Password','password','Password')
            ->addField('Checkbox','memorize','Remember on this computer')
            ->addSubmit('Login');

        if($this->form->isSubmitted()){
            if($this->verifyCredintals($this->form->get('username'),$this->form->get('password'))){
                $this->loggedIn();
                $this->api->redirect(null,$_GET);
            }
            $this->form->getElement('password')->displayFieldError('Incorrect login information');
        }

        $p->downCall('render');
        echo $p->template->render();
        exit;
    }
}
