<?php

/*
 * Implementation of Rest-full API connectivity using JSON. Define your 
 * model with the methods you wish and then set this controller. You 
 * need to specify URL as a table. When you perform interaction with 
 * your model, it will automatically communicate with the remote server.
 *
 * You can call custom methods through $controller->request($model,'method',arguments);
 * Those will not change the state of your model and will simply return 
 * decoded JSON back.
 *
 * NOTE: This is pretty awesome controller!
 */
class Controller_Data_RestAPI extends Controller_Data {
    /* Sends Generic Request */
    function request_get($method,$object=null){
        $payload=json_encode($data);
 
        $submit_url = "https://agiletoolkit.org/api/v1/".$req;
 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $submit_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode($payload));

        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
}
