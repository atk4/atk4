<?
class Page_Welcome extends Page {
    function call_render(){
        $this->owner->rendered['sub-elements'][]= "
            <center><h3>Welcome to AModules-3 Demonstration</h3></center>
            <p>The 3rd version of AModules libraries is yet another huge step to flexible and powerfull
            administration system library. As already started in 2nd version, this version makes your
            application much understandable and modular. AModules-3 is divided into much more number of
            classes and features new concept of administration system planing and design.
            <p>AModules-3 provides a building blocks for your administration system which you can put in
            any way you want it. It's now possible to describe the whole system in one file only, you
            just have to specify what kind of components participate in your application.
            ";
    }
}
?>
