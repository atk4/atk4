<?
/*
 * Mandatory Authorization module. Once you add this to your API, it will protect
 * it without any further actions.
 */
class BasicAuth extends AbstractController {
    public $info=false;

    protected $password=null;     // this is password to let people in

    function init(){
        parent::init();
        $this->api->auth=$this;
        $this->info=$this->recall('info',false);
        if($this->api->page=='Logout'){
            $this->forget('info');
            $this->info=false;
            $this->api->redirect('Index');
        }
    }
    function setPassword($password){
        $this->password=$password;
        return $this;
    }
    function check(){
        if(!$this->isLoggedIn())$this->processLogin();
    }
    function isLoggedIn(){
        if($this->info)return true;
    }
    function loggedIn(){
        $this->info=array('auth'=>true);
        $this->memorize('info',$this->info);
        unset($_GET['submit']);
        unset($_GET['page']);
        $this->api->redirect(null,$_GET);
    }
    function processLogin(){
        // this function is called when authorization is not found. 
        // You should return true, if login process was successful.

        // Initialize an empty page
        $p=$this->add('Page');
        $p->template->loadTemplate('empty');
        $f=$p->frame('Content','Authentication')
            ->add('Form',null,'content');

        $f
            ->addComment('Access to this resource is only allowed if you know a secret phrase. Enter it here:')
            ->addField('Password','secret')
            ->addSubmit('Save');

        if($f->isSubmitted()){
            if($f->get('secret')==$this->password && $this->password){
                $this->loggedIn();
            }
            // display error here :)
        }

        $p->downCall('render');
        echo $p->template->render();
        exit;
    }
}
