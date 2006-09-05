<?
class RPC extends AbstractController {
    /*
     * RPC class implements remote method call. It's very similar to
     * XML-RPC, but it does not use XML, but uses serialize. Also it
     * will work perfectly with AModules3
     *
     * You must use ApiRPC on the other side of the request
     */
    var $destination_url;    // where requests will be sent
    var $security_key=null;


    function __call($method,$arguments){
        if($this->security_key){
            // if security key is specified there will be 3 elements in top-array
            // where 3rd will contain checksum
            $data = serialize(
                    array(
                        $method,
                        $arguments,
                        md5(
                            serialize(
                                array(
                                    $method,
                                    $arguments,
                                    $this->security_key
                                    )
                                )
                            )
                        )
                    );
        }else{
            $data = serialize(array($method,$arguments));
        }

        curl_setopt($ch, CURLOPT_URL, $this->destination_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "SERWEB full_access version 0.1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".$data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // need these if we dont have cert
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        $response = curl_exec ($ch);
        curl_close ($ch);

        if($response!=serialize(false)){
            // we won't be sure if it was false returned or if there was error, so we
            // unserialize it
            return false;
        }
        @$response=unserialize($response);
        if($response===false){
            // it was really an error
            throw new RPCException("Couldn't connect to handler URL");
        }
        if($response instanceof BaseException){
            throw $response;    // if exception was raised on other end - we just raise it again
        }
        return $response;
    }
}
