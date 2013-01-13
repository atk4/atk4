<?php
class PathFinder_Location extends AbstractModel {
    /*
       Represents a location, which contains number of sub-locations. Each
       of which may contain certain type of data
     */


    public $parent_location=null;

    public $contents=array();
    // contains list of 'type'=>'subdir' which lists all the
    // resources which can be found in this directory


    public $relative_path=null;
    // Path to relative file within this resource

    public $base_url=null;
    public $base_path=null;

    public $auto_track_element=true;


    function init(){
        parent::init();

        $this->relative_path=$this->short_name;

        if($this->short_name[0]=='/' || (strlen($this->short_name)>1 && $this->short_name[1]==':')){
            // Absolute path. Independent from base_location

        }else{
            $this->setParent($this->owner->base_location);
        }
    }
    function setRelativePath($path){
        $this->relative_path = $path;
        return $this;
    }
    function setParent($parent){
        $this->parent_location=$parent;
        return $this;
    }

    function __toString(){
        // this is our path
        $s=(isset($this->parent_location)?
                ((string)$this->parent_location):'');
        if($s && substr($s,-1)!='/' && $this->relative_path)$s.='/';
        $s.=$this->relative_path;
        return $s;
    }

    function getURL($file_path=null){
        // Returns how this location or file can be accessed through web
        // base url + relative path + file_path

        $url='';
        if($this->base_url)$url=$this->base_url;else
            if($this->parent_location){
                $url=$this->parent_location->getURL();
                if(substr($url,-1)!='/')$url.='/';
                $url.=$this->relative_path;
            }else
                throw new BaseException('Unable to determine URL');

        if($file_path){
            if(substr($url,-1)!='/')$url.='/';
            $url.=$file_path;
        }
        $url=str_replace('/./','/',$url);
        return $url;
    }

    function getPath($file_path=null){
        // Returns how this location or file can be accessed through filesystem

        $path='';
        if($this->base_path)$path=$this->base_path;else
            if($this->parent_location){
                $path=$this->parent_location->getPath();
                if(substr($path,-1)!='/')$path.='/';
                $path.=$this->relative_path;
            }else
                throw new BaseException('Unable to determine Path for '.$this.', parent='.$this->parent_location);

            if($file_path){
                if(substr($path,-1)!='/')$path.='/';
                $path.=$file_path;
            }
            return $path;
    }

    function setBaseURL($url){
        /*
           something like /my/app
         */
        $this->base_url=$url;
        return $this;
    }
    function setBasePath($path){
        /*
           something like /home/web/public_html
         */
        $this->base_path=$path;
        return $this;
    }
    function defineContents($contents){
        if($contents==='all'){
            $contents=array('all'=>'all');
        }
        if(is_string($contents)){
            $contents=array($contents=>'.');
        }
        $this->contents=array_merge_recursive($this->contents,$contents);
        return $this;
    }

    function locate($type,$filename,$return='relative'){
        // Locates the file and if found - returns location,
        // otherwise returns array of attempted locations

        // specify empty filename to find location

        $attempted_locations=array();
        $locations=array();
        $location=null;

        // first - look if type is explicitly defined in
        if(isset($this->contents[$type])){
            if(is_array($this->contents[$type])){
                $locations=$this->contents[$type];
            }else{
                $locations=array($this->contents[$type]);
            }
            // next - look if locations claims to have all resource types
        }elseif(isset($this->contents['all'])){
            $locations=array($type);
            echo (string)$this;
        }

        foreach($locations as $path){
            $f=$this->getPath($pathfile=$path.'/'.$filename);
            if(file_exists($f)){
                if(!is_readable($f)){
                    throw new PathFinder_Exception($type,$filename,$f,'File found but it is not readable');
                }

                if($return=='location')return $this;
                if($return=='relative')return $pathfile;
                if($return=='url')return $this->getURL($pathfile);
                if($return=='path')return $f;

                throw new BaseException('Wrong return type for locate()');

            }else $attempted_locations[]=$f;
        }

        return $attempted_locations;
    }
}
