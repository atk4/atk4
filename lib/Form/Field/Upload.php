<?php // vim:ts=4:sw=4:et:fdm=marker
/*
 * Undocumented
 *
 * @link http://agiletoolkit.org/
*//*
==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
 =====================================================ATK4=*/
/**
 *
 * This class is for uploading files. It supports 3 types of upload:
 * - regular upload. No javascript is involved. When form is being submitted, data is collected
 * - javascript iframe upload. Upon selection of the file, it will start uploading it right away,
 *    and will display additional file upload field, so that you can pick another file.
 * - flash upload. Upload will be carried through by flash.

 * Mode can be set by
 *  setMode('plain')  - plain mode is not available with AJAX form
 *  setMode('iframe')
 *  setMode('flash')
 *  
 * by Default mode is iframe.



 * allowMultiple(boolean=true)
 *  this function will allow you to specify whether you want user to upload multiple files.
 * 
 * by default, single mode is used



 * Mode Support.
 *  You can use setModel('filestore/File'); This will use the model for file upload handling. You
 *  can specify your own model, which derives from either filestore\Model_File or filestore\Model_Image
 *  Field value will always contain "id" of uploaded file. If multiple file upload is permitted, field
 *  will contain comma-separated list of IDs.

 * Example1: Simple use with model

 $upl=$form->addField('Upload','myfile_id')
     ->setModel('filestore/File');

 * Example2: Customizing field

 $upl=$form->addField('Upload','photo_id','Photo')
     ->setController('filestore/Image')
     ->allowMultiple(false)
       ;
 $upl->template->set('after_field','Max size: 500k');


 * Example3: Specifying inside Model
 
$model=$this->add('Model_Book');
$model->add('filestore/Field_Image','picture_id');

$this->add('Form')->setModel($model);

 * Example4: Use of your custom model
 
$this->app->stickyGET('user_id');
$myfile=$this->add('filestore/Image');
$myfile->join('user_images.file_id')->addCondition('user_id',$_GET['user_id']);
$form->addField('Upload','photo')
    ->setNoSave()->setModel($myfile);

This last example will implement many-to-many relationship between file object
and user_id. This is implemented through intermediate table user_images, which
is joined with an image model.


 */


class Form_Field_Upload extends Form_Field {
    public $max_file_size=null;
    public $mode='iframe';
    public $multiple=false;
    public $debug=false;
    public $format_files_template="view/uploaded_files";

    function init(){
        parent::init();
        $this->owner->template->set('enctype', "enctype=\"multipart/form-data\"");
        $this->attr['type']='file';

        $max_post=$this->convertToBytes(ini_get('post_max_size'))/2;
        $max_upload=$this->convertToBytes(ini_get('upload_max_filesize'));

        $this->max_file_size = min($max_upload, $max_post);

        /*
           if($_POST[$this->name.'_token']){
           $t=json_decode(stripslashes($_POST[$this->name.'_token']));
           $_FILES[$this->name]=array(
           'name'=>$t->fileInfo->name,
           'tmp_name'=>'upload/temp/'.$t->filename,
           );
           }
         */
    }
    function allowMultiple($multiple=50){
        // Allow no more than $multiple files to be present in the table
        $this->multiple=$multiple;
        return $this;
    }
    function setFormatFilesTemplate($template){
        $this->format_files_template=$template;
        return $this;
    }
    function setMode($mode){
        $this->mode=$mode;
        return $this;
    }
    function loadPOST(){
        parent::loadPOST();
        if($_GET[$this->name.'_upload_action']){
            // This is JavaScript upload. We do not want to trigger form submission event
            $_POST=array();
        }
        if($_GET[$this->name.'_upload_action'] || $this->isUploaded()){

            if($this->model){
                try{

                    if(!file_exists($this->getFilePath())){
                        throw $this->exception('File upload was blocked by the webserver (post: '.json_encode($_POST).
                            ', get: '.json_encode($_GET).')','ForUser');
                    }

                    $model=$this->model;
                    $model->set($this->getVolumeIDFieldName(),$model->getAvailableVolumeID());
                    $model->set($this->getOriginalFilenameFieldName(),$this->getOriginalName());
                    $model->set($this->getTypeIDFieldName(),$model->getFiletypeID($this->getOriginalType(),$model->policy_add_new_type));
                    $model->import($this->getFilePath());
                    $model->save();
                }catch(Exception $e){
                    $this->app->logger->logCaughtException($e);

                    if($e instanceof Exception_ForUser){
                        // nicer error for user
                        $this->uploadFailed($e->getMessage());
                        //.', error: '.$this->getFileError()); //more user friendly
                    }

                    if(is_subclass_of($e, 'BaseException')){
                        $e->addMoreInfo('upload_error',$this->getFileError());
                    }

                    echo '<script>$=window.top.$;';
                    $_POST['ajax_submit']=1;
                    $this->app->addHook('post-js-execute',function(){ 
                        echo ';</script>';
                    });
                    $this->app->caughtException($e);

                }

                $this->uploadComplete($model->get());
            }else{
            }
        }
        if($_POST[$this->name.'_token']){
            $a=explode(',',$_POST[$this->name.'_token']);$b=array();
            foreach($a as $val)if($val)$b[]=$val;
            $this->set(join(',',filter_var_array($b,FILTER_VALIDATE_INT)));
        }
        else $this->set($this->default_value);
    }
    function uploadComplete($data=null){
        echo "<html><head><script>window.top.$('#".
            $_GET[$this->name.'_upload_action']."').atk4_uploader('uploadComplete',".
            json_encode($data).");</script></head></html>";
        exit;
    }
    function uploadFailed($message){

        $d='';
        if($this->debug)$d=','.json_encode($_FILES[$this->name]);

        echo "<html><head><script>window.top.$('#".
            $_GET[$this->name.'_upload_action']."').atk4_uploader('uploadFailed',".
            json_encode($message).
            $d.
            ");</script></head></html>";
        exit;
    }

    function convertToBytes($val){
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch($last) {
            case 'g':
                $size = $val * 1024 * 1024 * 1024;
                break;
            case 'm':
                $size = $val * 1024 * 1024;
                break;
            case 'k':
                $size = $val * 1024;
                break;
            default:
                $size = (int) $val;
        }
        return $size;

    }

    // TODO:
    // add functions for file type and extension validation
    // those can be done in flash thingie as well

    function getUploadedFiles(){
        if($c=$this->model){

            $a=explode(',',$this->value);$b=array();
            foreach($a as $val)if($val)$b[]=$val;
            $files=join(',',filter_var_array($b,FILTER_VALIDATE_INT));
            if($files){
                $c->addCondition('id','in',($files?$files:0));
                $data=$c->getRows(array('id','url','thumb_url',$this->getOriginalFilenameFieldName(),$this->getFilesizeFieldName()));
            }else $data=array();
            return $this->formatFiles($data);
        }
    }
    function formatFiles($data){
        $this->js(true)->atk4_uploader('addFiles',$data);
        $o = $this->add('GiTemplate')->loadTemplate($this->format_files_template)->render();
        return $o;
    }


    function getInput(){
        if($id=$_GET[$this->name.'_delete_action']){
            // this won't be called in post unfortunatelly, because ajaxec does not send POST data
            // This is JavaScript upload. We do not want to trigger form submission event
            if($c=$this->model){
                try {
                    $c->tryLoad($id);
                    $c->delete();
                    $this->js()->_selector('[name='.$this->name.']')->atk4_uploader('removeFiles',array($id))->execute();
                } catch (Exception $e){
                    $this->js()->univ()->alert("Could not delete image - " . $e->getMessage())->execute();
                }
                //$this->js(true,$this->js()->_selector('#'.$this->name.'_token')->val(''))->_selectorRegion()->closest('tr')->remove()->execute();
            }
        }

        if($id=$_GET[$this->name.'_save_action']){
            // this won't be called in post unfortunatelly, because ajaxec does not send POST data
            // This is JavaScript upload. We do not want to trigger form submission event
            if($c=$this->model){
                $c->tryLoad($id);
                $f=$c;
                $mime = $f->ref($this->getTypeIDFieldName())->get('mime_type');
                $path = $f->getPath();
                $name = $f->get($this->getOriginalFilenameFieldName());
                $len = $f->get($this->getFilesizeFieldName());
                header("Content-type: $mime");
                header("Content-legnth: $len");
                if($_GET["redirect"]){
                    /* it should be possible to use redirect method as well */
                    header("HTTP/1.1 301 Moved Permanently"); 
                    header("Location: $path");
                } else {
                    if(!$_GET['view']){
                        header("Content-disposition: attachment; filename=\"$name\"");
                    }
                    print(file_get_contents($path));
                }
                exit;

                $this->js()->_selector('[name='.$this->name.']')->atk4_uploader('removeFiles',array($id))->execute();
                //$this->js(true,$this->js()->_selector('#'.$this->name.'_token')->val(''))->_selectorRegion()->closest('tr')->remove()->execute();
            }
        }
        if($_GET[$this->name.'_upload_action'] && !$_POST){
            if(!$this->model){
                $this->uploadFailed('You are not using model with upolad field. Please use "isUploaded" and uploadComplete() methods in form submit handler');

            }
            $this->uploadFailed('Webserver settings have blocked this file. Perhaps post_max_size should incleased ('.
                $_SERVER['CONTENT_LENGTH'].' sent, '.ini_get('post_max_size').' max)(post: '.json_encode($_POST).
                            ', get: '.json_encode($_GET).')');
        }
        $o='';

        $options=array('size_limit'=>$this->max_file_size);

        switch($this->mode){
            case'simple':break;
            case'iframe':
                     $options['iframe']=$this->name.'_iframe';
                     break;
            case'flash':
                     $options['flash']=true;
                     break;

        }
        if($this->multiple){
            $options['multiple']=$this->multiple;
        }
        if($this->mode!='simple'){
            $options['form']=$this->owner;
            $this->js(true)->_load('ui.atk4_uploader')->atk4_uploader($options);
        }

        // First - output list of files wi already have uploaded
        $o.='<div id="'.$this->name.'_files" class="uploaded_files">'.
            $this->getUploadedFiles().
            '</div>';

        $o.=
            $this->getTag('input',array(
                        'type'=>'hidden',
                        'name'=>'MAX_FILE_SIZE',
                        'value'=>$this->max_file_size
                        ));
        $o.=
            $this->getTag('input',array(
                        'type'=>'hidden',
                        'name'=>$this->name.'_token',
                        'value'=>$this->value,
                        'id'=>$this->name.'_token',
                        ));


        $o.=parent::getInput();
        return $o;
    }
    function isUploaded(){
        return isset($_FILES[$this->name]) && $_FILES[$this->name]['name'];
    }
    function getOriginalName(){
        return $_FILES[$this->name]['name'];
    }
    function getOriginalType(){
        // detect filetype instead of relying on uploaded type
        if(function_exists('mime_content_type'))return mime_content_type($this->getFilePath());
        return $_FILES[$this->name]['type'];
    }
    function getFilePath(){
        return $_FILES[$this->name]['tmp_name'];
    }
    function getFileError(){
        $error=$_FILES[$this->name]['error'];
        switch ($error) {
        case UPLOAD_ERR_OK:
            $response = 'File was uploaded properly.';
            break;
        case UPLOAD_ERR_INI_SIZE:
            $response = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $response = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $response = 'The uploaded file was only partially uploaded.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $response = 'No file was uploaded.';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $response = 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $response = 'Failed to write file to disk. Introduced in PHP 5.1.0.';
            break;
        case UPLOAD_ERR_EXTENSION:
            $response = 'File upload stopped by extension. Introduced in PHP 5.2.0.';
            break;
        default:
            $response = 'Unknown error';
            break;
        }
        return $response;
    }
    function getFile(){
        return file_get_contents($this->getFilePath());
    }
    function getFileSize(){
        return filesize($this->getFilePath());
    }

    function saveInto($directory){
        // Moves file into a directory.
        // TODO: we should make sure we are not overwriting anything here
        if(!move_uploaded_file($_FILES[$this->name],$directory)){
            throw new BaseException('Unable to save uploaded file into '.$directory);
        }
    }
    function displayUploadInfo(){
        $this->owner->info('Was file uploaded: '.($this->isUploaded()?'Yes':'No'));
        $this->owner->info('Filename: '.$this->getFilePath());
        $this->owner->info('Size: '.$this->getFileSize());
        $this->owner->info('Original Name: '.$this->getOriginalName());
    }
    function debug(){
        $this->debug=true;
        return $this;
    }

    /*  reimplement these functions in your *_Upload class   */
    function getVolumeIDFieldName() {
        return 'filestore_volume_id';
    }
    function getTypeIDFieldName() {
        return 'filestore_type_id';
    }
    function getOriginalFilenameFieldName() {
        return 'original_filename';
    }
    function getFilesizeFieldName() {
        return 'filesize';
    }
}
