<?php

namespace NetellerAPI;

class NetellerAPI{
    var $baseUrl;
    var $clientId;
    var $clientSecret;
    var $executionErrors = array();
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function getIP(){
        return file_get_contents('http://whatismyip.akamai.com');
    }
    
    public function get($path, $queryParams = array(), $headers){
        return $this->makeHttpRequest("get", $path, $queryParams, $headers);
    }

    public function post($path, $queryParams = array(), $headers, $requestParams = array()){
        return $this->makeHttpRequest("post", $path, $queryParams, $headers, $requestParams);
    }

    public function put($path, $queryParams = array(), $headers, $requestParams = array()){
        return $this->makeHttpRequest("put", $path, $queryParams, $headers, $requestParams);
    }

    public function delete($path, $queryParams = array(), $headers, $requestParams = array()){
        return $this->makeHttpRequest("delete", $path, $queryParams, $headers, $requestParams);
    }

    public function getUrl($url){
        $token = $this->getToken_ClientCredentials();
        
        if($token == false)
        {
            return false;
        }
        
        $path = str_replace($this->baseUrl, "/", $url);
        
        $queryParams = array();
        
        $headers = array
            (
                "Content-type" => "application/json",
                "Authorization" => "Bearer ". $token
            );
        
        $response = $this->makeHttpRequest("get", $path, $queryParams, $headers);
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                    'http_status_code' => $responseInfo['http_code'],
                    'api_error_code' => $responseBody->error->code,
                    'api_error_message' => $responseBody->error->message,
                    'api_resource_used' => 'GET {url}'
            );
            
            return false;
        }
        else{
            return false;
        }
    }
    
    public function setApiCredentials($baseUrl, $clientId, $clientSecret){
        $this->baseUrl = $baseUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }
    
    public function getToken_ClientCredentials(){
        $queryParams = array("grant_type" => "client_credentials");
        $headers = array
            (
                "Content-type" => "application/json",
                "Authorization" => "Basic ". base64_encode( $this->clientId . ":" . $this->clientSecret )
            );
        $response = $this->post("v1/oauth2/token", $queryParams, $headers, array());
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);
        
        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody->accessToken;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                    'http_status_code' => $responseInfo['http_code'],
                    'api_error_code' => $responseBody->error,
                    'api_error_message' => '',
                    'api_resource_used' => 'v1/oauth2/token'
                );
            return false;
        }
        else{
            return false;
        }
    }

    public function getToken_AuthCode($authCode, $redirectUri){
        $queryParams = array
            (
                "grant_type" => "authorization_code",
                "code" => $authCode,
                "redirect_uri" => $redirectUri
            );
        $headers = array
            (
                "Content-type" => "application/json",
                "Authorization" => "Basic ". base64_encode( $this->clientId . ":" . $this->clientSecret )
            );
        $response = $this->post("v1/oauth2/token", $queryParams, $headers, array());
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);
        
        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody->accessToken;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error,
                'api_error_message' => '',
                'api_resource_used' => 'v1/oauth2/token/{authCode}'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getToken_RefreshToken($refreshToken){
        $queryParams = array
        (
            "grant_type" => "refresh_token",
            "refresh_token" => $refreshToken
        );
        
        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Basic ". base64_encode( $this->clientId . ":" . $this->clientSecret )
        );
        
        $response = $this->post("v1/oauth2/token", $queryParams, $headers, array());
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);
        
        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody->accessToken;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error,
                'api_error_message' => '',
                'api_resource_used' => 'v1/oauth2/token/{refreshToken}'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    protected function makeHttpRequest($method, $path, $queryParams = array(), $headers, $requestParams = array()){
        $ch	= curl_init();
        //set the timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        //do not attempt to validate SSL certificates
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        //return the data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //return the response headers
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // curl_setopt($ch, CURLOPT_VERBOSE, true);

        //method && request params:
        switch (strtolower($method))
        {
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (count($requestParams) > 0)
        		{
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestParams));
				}
                break;
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				if (count($requestParams) > 0)
                {
    				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestParams));			
				}
                break;
            case 'post':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
				if (count($requestParams) > 0)
                {
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestParams));
				}
                break;
            case 'get':
                break;
        }

        //headers
        $_headers = array();

        foreach ($headers as $key => $value)
        {
            $_headers[] = "$key: $value";
        }

        if (count($_headers) > 0)
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        }

        //query params
        $url = $this->baseUrl . $path;
        if (count($queryParams) > 0)
        {
            $url .= '?' ;
            foreach ($queryParams as $key => $value)
            {
                $url .= $key.'='.rawurlencode($value).'&';
            }
            $url = rtrim($url, '&');
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        //response data
        $data			= curl_exec($ch);
        $info			= curl_getinfo($ch);
		
        $response_headers = substr($data, 0, $info['header_size']);
        $response_body = substr($data, $info['header_size']);

		curl_close($ch);
        
        $response = array
        (
            'headers'	=> $response_headers,
            'body'		=> $response_body,
            'info'		=> $info,
        );

        return $response;
    }

    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class RequestPayment extends NetellerAPI{
    var $paymentMethodValue;
    var $transactionMerchantRefId;
    var $transactionAmount;
    var $transactionCurrency;
    var $verificationCode;

    var $expandObjects;
    var $executionErrors = array();

    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }

    public function setPaymentMethodValue($paymentMethodValue){
        $this->paymentMethodValue = $paymentMethodValue;
        return $this;
    }

    public function setTransactionMerchantRefId($transactionMerchantRefId){
        $this->transactionMerchantRefId = $transactionMerchantRefId;
        return $this;
    }

    public function setTransactionAmount($transactionAmount){
        $this->transactionAmount = $transactionAmount;
        return $this;
    }

    public function setTransactionCurrency($transactionCurrency){
        $this->transactionCurrency = $transactionCurrency;
        return $this;
    }

    public function setVerificationCode($verificationCode){
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function setExpand($expandObjects){
        $this->expandObjects = $expandObjects;
        return $this;
    }

    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
        
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $requestParams = array
        (
            "paymentMethod" => array
            (
                "type" => "neteller",
                "value" => $this->paymentMethodValue
            ),
            "transaction" => array
            (
                "merchantRefId" => $this->transactionMerchantRefId,
                "amount" => $this->transactionAmount,
                "currency" => $this->transactionCurrency
            ),
            "verificationCode" => $this->verificationCode
        );

        if(isset($this->expandObjects)){
            $queryParams = array('expand' => $this->expandObjects);
        }
        else{
            $queryParams = array();
        }
        
        $response = $this->post("v1/transferIn", $queryParams, $headers, $requestParams);
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                    'http_status_code' => $responseInfo['http_code'],
                    'api_error_code' => $responseBody->error->code,
                    'api_error_message' => $responseBody->error->message,
                    'api_resource_used' => 'POST /v1/transferIn'
                );
            return false;
        }
        else{
            return false;
        }
    }

    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class CreatePayment extends NetellerAPI{
    var $payeeProfileEmail;
    var $transactionAmount;
    var $transactionCurrency;
    var $transactionMerchantRefId;
    var $message;
    var $expandObjects;

    var $executionErrors = array();

    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }

    public function setPayeeProfileEmail($payeeProfileEmail){
        $this->payeeProfileEmail = $payeeProfileEmail;
        return $this;
    }

    public function setTransactionAmount($transactionAmount){
        $this->transactionAmount = $transactionAmount;
        return $this;
    }

    public function setTransactionCurrency($transactionCurrency){
        $this->transactionCurrency = $transactionCurrency;
        return $this;
    }

    public function setTransactionMerchantRefId($transactionMerchantRefId){
        $this->transactionMerchantRefId = $transactionMerchantRefId;
        return $this;
    }

    public function setMessage($message){
        $this->message = $message;
        return $this;
    }

    public function setExpand($expandObjects){
        $this->expandObjects = $expandObjects;
        return $this;
    }

    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
        
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $requestParams = array
        (
            "payeeProfile" => array
            (
                "email" => $this->payeeProfileEmail
            ),
            "transaction" => array
            (
                "amount" => $this->transactionAmount,
                "currency" => $this->transactionCurrency,
                "merchantRefId" => $this->transactionMerchantRefId
            ),
            "message" => $this->message
        );

        if(isset($this->expandObjects)){
            $queryParams = array('expand' => $this->expandObjects);
        }
        else{
            $queryParams = array();
        }
        $response = $this->post("v1/transferOut", $queryParams, $headers, $requestParams);
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'POST /v1/transferOut'
            );
            return false;
        }
        else{
            return false;
        }
    }

    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class LookupPayment extends NetellerAPI{
    var $transactionId;
    var $merchantRefId;

    var $expandObjects;
    var $executionErrors;

    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }

    public function setTransactionId($transactionId){
        $this->transactionId = "$transactionId";
        return $this;
    }

    public function setMerchantRefId($merchantRefId){
        $this->merchantRefId = $merchantRefId;
        return $this;
    }

    public function setExpand($expandObjects){
        $this->expandObjects = $expandObjects;
        return $this;
    }

    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        if(isset($this->expandObjects)){
            $queryParams = array('expand' => $this->expandObjects);
        }
        else{
            $queryParams = array();
        }

        if(isset($this->transactionId)){
            $response = $this->get("v1/payments/".$this->transactionId, $queryParams, $headers, array());
        }

        if(isset($this->merchantRefId)){
            $queryParams['refType'] = 'merchantRefId';
            $response = $this->get("v1/payments/".$this->merchantRefId, $queryParams, $headers, array());
        }
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/payments/{}'
            );
            return false;
        }
        else{
            return false;
        }
    }

    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class CreateOrder extends NetellerAPI{
    var $orderId;
    var $orderMerchantRefId;
    var $orderTotalAmount;
    var $orderCurrency;
    var $orderLang;
    var $orderCustomerIp;
    var $items = array();
    var $fees = array();
    var $taxes = array();
    var $redirects = array();
	var $paymentMethods = array();
    var $billingDetailsEmail = "";
    var $billingDetailsCountry = "";
    var $billingDetailsFirstName;
    var $billingDetailsLastName = "";
    var $billingDetailsAddress1 = "";
    var $billingDetailsAddress2 = "";
    var $billingDetailsAddress3 = "";
    var $billingDetailsCity = "";
    var $billingDetailsCountrySubdivisionCode;
    var $billingDetailsPostCode;
    var $billingDetailsLang;
        
    var $attributes = array();
    
    var $redirectUrl;
    var $executionErrors = array();

    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }

    public function setOrderMerchantRefId($orderMerchantRefId){
        $this->orderMerchantRefId = $orderMerchantRefId;
        return $this;
    }

    public function setOrderTotalAmount($orderTotalAmount){
        $this->orderTotalAmount = $orderTotalAmount;
        return $this;
    }

    public function setOrderCurrency($orderCurrency){
        $this->orderCurrency = $orderCurrency;
        return $this;
    }

    public function setOrderLang($orderLang){
        $this->orderLang = $orderLang;
        return $this;
    }
    
    public function setOrderCustomerIp($orderCustomerIp){
        $this->orderCustomerIp = $orderCustomerIp;
        return $this;
    }
    
    public function setItems($item){
        $this->items[] = $item;
        return $this;
    }

    public function setFees($fee){
        $this->fees[] = $fee;
        return $this;
    }
    
    public function setTaxes($tax){
        $this->taxes[] = $tax;
        return $this;
    }

	public function setPaymentMethods($paymentMethod){
        $this->paymentMethods[] = $paymentMethod;
        return $this;
    }
    public function setRedirectOnSuccess($url){
        $redirect = array
        (
            "rel" => "on_success",
            "returnKeys" => array
            (
                "id"
            ),
            "uri" => $url
        );
        $this->redirects[] = $redirect;
        return $this;
    }

    public function setRedirectOnCancel($url){
        $redirect = array
        (
            "rel" => "on_cancel",
            "returnKeys" => array
            (
                "id"
            ),
            "uri" => $url
        );
        $this->redirects[] = $redirect;
        return $this;
    }
    
    public function setBillingDetailsEmail($billingDetailsEmail){
        $this->billingDetailsEmail = $billingDetailsEmail;
        return $this;
    }
    
    public function setBillingDetailsFirstName($billingDetailsFirstName){
        $this->billingDetailsFirstName = $billingDetailsFirstName;
        return $this;
    }
    
    public function setBillingDetailsLastName($billingDetailsLastName){
        $this->billingDetailsLastName = $billingDetailsLastName;
        return $this;
    }
    
    public function setBillingDetailsCountry($billingDetailsCountry){
        $this->billingDetailsCountry = $billingDetailsCountry;
        return $this;
    }
    
    public function setBillingDetailsCity($billingDetailsCity){
        $this->billingDetailsCity = $billingDetailsCity;
        return $this;
    }
    
    public function setBillingDetailsAddress1($billingDetailsAddress1){
        $this->billingDetailsAddress1 = $billingDetailsAddress1;
        return $this;
    }
	
    public function setBillingDetailsAddress2($billingDetailsAddress2){
        $this->billingDetailsAddress2 = $billingDetailsAddress2;
        return $this;
    }    
    
    public function setBillingDetailsAddress3($billingDetailsAddress3){
        $this->billingDetailsAddress3 = $billingDetailsAddress3;
        return $this;
    }
    
    public function setBillingDetailsCountrySubDivisionCode($countrySubDivisionCode){
        $this->countrySubDivisionCode = $countrySubDivisionCode;
        return $this;
    }
    
    public function setBillingDetailsPostCode($billingDetailsPostCode){
        $this->billingDetailsPostCode = $billingDetailsPostCode;
        return $this;
    }
    
    public function setBillingDetailsLang($billingDetailsLang){
        $this->billingDetailsLang = $billingDetailsLang;
        return $this;
    }
    
    public function setAttributes($attribute){
        $this->attributes[] = $attribute;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array();
        
        $requestParams = array
        (
            "order" => array
            (
                "merchantRefId" => $this->orderMerchantRefId,
                "totalAmount" => $this->orderTotalAmount,
                "currency" => $this->orderCurrency,
                "lang" => $this->orderLang,
                "items" => $this->items,
                "fees" => $this->fees,
                "taxes" => $this->taxes,
				"paymentMethods" =>$this->paymentMethods,
                "redirects" => $this->redirects,
            ),
            "billingDetails" => array(array()),
            "attributes" => $this->attributes
        );

        if($this->orderCustomerIp != null){
            $requestParams['order']['customerIp'] = $this->orderCustomerIp;
        }
        
        if($this->billingDetailsEmail != null){
            $requestParams['billingDetails'][0]['email'] = $this->billingDetailsEmail;
        }
        
        if($this->billingDetailsCountry != null){
            $requestParams['billingDetails'][0]['country'] = $this->billingDetailsCountry;
        }
        
        if($this->billingDetailsFirstName != null){
            $requestParams['billingDetails'][0]['firstName'] = $this->billingDetailsFirstName;
        }
        
        if($this->billingDetailsLastName != null){
            $requestParams['billingDetails'][0]['lastName'] = $this->billingDetailsLastName;
        }
        
        if($this->billingDetailsCity != null){
            $requestParams['billingDetails'][0]['city'] = $this->billingDetailsCity;
        }
        
        if($this->billingDetailsAddress1 != null){
            $requestParams['billingDetails'][0]['address1'] = $this->billingDetailsAddress1;
        }
        
        if($this->billingDetailsAddress2 != null){
            $requestParams['billingDetails'][0]['address2'] = $this->billingDetailsAddress2;
        }
        
        if($this->billingDetailsAddress3 != null){
            $requestParams['billingDetails'][0]['address3'] = $this->billingDetailsAddress3;
        }
        
        if($this->billingDetailsCountrySubdivisionCode != null){
            $requestParams['billingDetails'][0]['countrySubDivisionCode'] = $this->billingDetailsCountrySubdivisionCode;
        }
        
        if($this->billingDetailsPostCode != null){
            $requestParams['billingDetails'][0]['postCode'] = $this->billingDetailsPostCode;
        }
        
        if($this->billingDetailsLang != null){
            $requestParams['billingDetails'][0]['lang'] = $this->billingDetailsLang;
        }
        
        # Don't send billingDetails if we don't have any
        if(is_array($requestParams['billingDetails']) && count($requestParams['billingDetails']) == 1 &&
            array_key_exists(0, $requestParams['billingDetails']) && is_array($requestParams['billingDetails'][0]) &&
            count($requestParams['billingDetails'][0]) == 0) {
            unset($requestParams['billingDetails']);
        }
        
        $response = $this->post("v1/orders", $queryParams, $headers, $requestParams);
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            $this->orderId = $responseBody->orderId;
            foreach($responseBody->links as $struct) {
                if ($struct->rel == "hosted_payment") {
                    $this->redirectUrl = $struct->url;
                    break;
                }
            }
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->orderId = null;
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'POST v1/orders'
            );
            return false;
        }
        else{
            return false;
        }
    }

    public function getOrderId() {
        return $this->orderId;
    }
    
    public function getRedirectUrl(){
        return $this->redirectUrl;
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class LookupOrder extends NetellerAPI{
    var $orderId;

    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setOrderId($orderId){
        $this->orderId = $orderId;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array();
        
        $response = $this->get("v1/orders/".$this->orderId, $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/orders/{}'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class LookupOrderInvoice extends NetellerAPI{
    var $orderId;

    var $expandObjects;
    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setOrderId($orderId){
        $this->orderId = $orderId;
        return $this;
    }
    
    public function setExpand($expandObjects){
        $this->expandObjects = $expandObjects;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        if(isset($this->expandObjects)){
            $queryParams = array('expand' => $this->expandObjects);
        }
        else{
            $queryParams = array();
        }
        
        $response = $this->get("v1/orders/".$this->orderId."/invoice", $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/order/{}/invoice'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class CreateCustomer extends NetellerAPI{
    var $email;
    var $firstName;
    var $lastName;
    var $address1;
    var $address2;
    var $address3;
    var $city;
    var $country;
    var $countrySubDivisionCode;
    var $postCode;
    var $gender;
    var $dobDay;
    var $dobMonth;
    var $dobYear;
    var $currency;
    var $language;
    var $contactDetail = array();
    var $btag;
    var $redirectUrl;
    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setEmail($email){
        $this->email = $email;
        return $this;
    }
    
    public function setFirstName($firstName){
        $this->firstName = $firstName;
        return $this;
    }
    
    public function setLastName($lastName){
        $this->lastName = $lastName;
        return $this;
    }
    
    public function setAddress1($address1){
        $this->address1 = $address1;
        return $this;
    }
    
    public function setAddress2($address2){
        $this->address2 = $address2;
        return $this;
    }
    
    public function setAddress3($address3){
        $this->address3 = $address3;
        return $this;
    }
    
    public function setCity($city){
        $this->city = $city;
        return $this;
    }
    
    public function setCountry($country){
        $this->country = $country;
        return $this;
    }
    
    public function setCountrySubDivisionCode($countrySubDivisionCode){
        $this->countrySubDivisionCode = $countrySubDivisionCode;
        return $this;
    }
    
    public function setPostCode($postCode){
        $this->postCode = $postCode;
        return $this;
    }
    
    public function setGender($gender){
        $this->gender = $gender;
        return $gender;
    }
    
    public function setDobDay($dobDay){
        $this->dobDay = $dobDay;
        return $this;
    }
    
    public function setDobMonth($dobMonth){
        $this->dobMonth = $dobMonth;
        return $this;
    }
    
    public function setDobYear($dobYear){
        $this->dobYear = $dobYear;
        return $this;
    }
    
    public function setLanguage($language){
        $this->language = $language;
        return $this;
    }
    
    public function setCurrency($currency){
        $this->currency = $currency;
        return $this;
    }
    
    public function setBtag($btag){
        $this->btag = $btag;
        return $this;
    }
    
    public function setMobile($mobile){
        $this->contactDetail[] = array
        (
            "type" => "mobile", 
            "value" => $mobile
        );
        return $this;
    }
    
    public function setLandLine($landLine){
        $this->contactDetail[] = array
        (
            "type" => "landLine", 
            "value" => $landLine
        );
        return $this;
    }
    
    public function setLinkBackUrl($linkBackUrl){
        $this->linkBackUrl = $linkBackUrl;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
        
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );
        
        $requestParams = array
        (
            "accountProfile" => array
            (
                "firstName" => $this->firstName,
                "lastName" => $this->lastName,
                "email" => $this->email,
                "address1" => $this->address1,
                "address2" => $this->address2,
                "address3" => $this->address3,
                "city" => $this->city,
                "country" => $this->country,
                "countrySubdivisionCode" => $this->countrySubdivisionCode,
                "postCode" => $this->postCode,
                "contactDetails" => $this->contactDetails,
                "gender" => $this->gender,
                "dateOfBirth" => array
                (
                    "year" => $this->dobYear,
                    "month" => $this->dobMonth,
                    "day" => $this->dobDay
                ),
                "accountPreferences" => array
                (
                    "lang" => $this->language,
                    "currency" => $this->currency
                )
            ),
            "linkbackurl" => $this->linkBackUrl,
            "btag" => $this->btag
        );
        
        $queryParams = array();
        
        $response = $this->post("v1/customers", $queryParams, $headers, $requestParams);
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            foreach($responseBody->links as $struct) {
                if ($struct->rel == "member_signup_redirect") {
                    $this->redirectUrl = $struct->url;
                    break;
                }
            }
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'POST /v1/customers'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getRedirectUrl(){
        return $this->redirectUrl;
    }
        
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class LookupCustomer extends NetellerAPI{
    var $accountId;
    var $customerId;
    var $email;
    var $authCode;
    var $refreshToken;

    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setAccountId($accountId){
        $this->accountId = $accountId;
        return $this;
    }
    
    public function setCustomerId($customerId){
        $this->customerId = $customerId;
        return $this;
    }
    
    public function setEmail($email){
        $this->email = $email;
        return $this;
    }
    
    public function setAuthCode($authCode){
        $this->authCode = $authCode;
        return $this;
    }
    
    public function setRefreshToken($refreshToken){
        $this->refreshToken = $refreshToken;
        return $this;
    }
    
    public function doRequest(){
        if(isset($this->authCode)){
            $token = $this->getToken_AuthCode($this->authCode);
        }
        
        if(isset($this->refreshToken)){
            $token = $this->getToken_RefreshToken($this->refreshToken);
        }
        
        if(!isset($this->authCode) AND !isset($this->refreshToken)){
            $token = $this->getToken_ClientCredentials();
        }

        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );
        
        if(isset($this->customerId)){
            $queryParams = array();
            $response = $this->get("v1/customers/".$this->customerId, $queryParams, $headers, array());
        }
        
        if(isset($this->accountId)){
            $queryParams = array
            (
                "accountId" => $this->accountId
            );
            $response = $this->get("v1/customers/", $queryParams, $headers, array());
        }
        
        if(isset($this->email)){
            $queryParams = array
            (
                "email" => $this->email
            );
            $response = $this->get("v1/customers/", $queryParams, $headers, array());
        }
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/customers/{}'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class CreatePlan extends NetellerAPI{
    var $planId;
    var $planName;
    var $interval;
    var $intervalType;
    var $intervalCount;
    var $amount;
    var $currency;

    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setPlanId($planId){
        $this->planId = $planId;
        return $this;
    }
    
    public function setPlanName($planName){
        $this->planName = $planName;
        return $this;
    }
    
    public function setInterval($interval){
        $this->interval = $interval;
        return $this;
    }
    
    public function setIntervalType($intervalType){
        $this->intervalType = $intervalType;
        return $this;
    }
    
    public function setIntervalCount($intervalCount){
        $this->intervalCount = $intervalCount;
        return $this;
    }
    
    public function setAmount($amount){
        $this->amount = $amount;
        return $this;
    }
    
    public function setCurrency($currency){
        $this->currency = $currency;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
        
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );
        
        $requestParams = array
        (
            "planId" => $this->planId,
            "planName" => $this->planName,
            "interval" => $this->interval,
            "intervalType" => $this->intervalType,
            "intervalCount" => $this->intervalCount,
            "amount" => $this->amount,
            "currency" => $this->currency
        );
        
        $queryParams = array();
        
        $response = $this->post("v1/plans", $queryParams, $headers, $requestParams);
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'POST v1/plans'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class LookupPlan extends NetellerAPI{
    var $planId;

    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setPlanId($planId){
        $this->planId = $planId;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array();
        
        $response = $this->get("v1/plans/".$this->planId, $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/plans/{}'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class CancelPlan extends NetellerAPI{
    var $planId;

    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setPlanId($planId){
        $this->planId = $planId;
        return $this;
    }

    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array();
        
        $response = $this->post("v1/plans/".$this->planId."/cancel", $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'POST /v1/plans/{}/cancel'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class DeletePlan extends NetellerAPI{
    var $planId;

    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setPlanId($planId){
        $this->planId = $planId;
        return $this;
    }

    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array();
        
        $response = $this->delete("v1/plans/".$this->planId, $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'DELETE /v1/plans/{}'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class ListPlans extends NetellerAPI{
    var $executionErrors;
    var $limit;
    var $offset;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setLimit($limit){
        $this->limit = $limit;
        return $this;
    }
    
    public function setOffset($offset){
        $this->offset = $offset;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array
        (
            "limit" => $this->limit,
            "offset" => $this->offset
        );
        
        $response = $this->get("v1/plans", $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/plans'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class CreateSubscription extends NetellerAPI{
    var $authCode;
    var $refreshToken;
    var $redirectUri;
    
    var $planId;
    var $customerId;
    var $startDate;
    
    var $expandObjects;
    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setPlanId($planId){
        $this->planId = $planId;
        return $this;
    }
    
    public function setCustomerId($customerId){
        $this->customerId = $customerId;
        return $this;
    }
    
    public function setStartDate($startDate){
        $this->startDate = $startDate;
        return $this;
    }
    
    public function setRefreshToken($refreshToken){
        $this->refreshToken = $refreshToken;
        return $this;
    }
    
    public function setAuthCode($authCode){
        $this->authCode = $authCode;
        return $this;
    }
    
    public function setRedirectUri($redirectUri){
        $this->redirectUri = $redirectUri;
        return $this;
    }
    
    public function setExpand($expandObjects){
        $this->expandObjects = $expandObjects;
        return $this;
    }
    
    public function doRequest(){
        if(isset($this->authCode)){
            $token = $this->getToken_AuthCode($this->authCode, $this->redirectUri);
        }
        elseif(isset($this->refreshToken)){
            $token = $this->getToken_RefreshToken($this->refreshToken);
        }
        else{
            $this->executionErrors[] = array('POST /v1/oauth2/token' => "Either Auth code or Refresh Token must be provided");
            return false;
        }
        
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );
        
        $requestParams = array
        (
            "planId" => $this->planId,
            "customerId" => $this->customerId,
            "startDate" => $this->startDate
        );
        
        if(isset($this->expandObjects)){
            $queryParams = array('expand' => $this->expandObjects);
        }
        else{
            $queryParams = array();
        }
        $response = $this->post("v1/subscriptions", $queryParams, $headers, $requestParams);
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'POST /v1/subscriptions'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class LookupSubscription extends NetellerAPI{
    var $subscriptionId;

    var $expandObjects;
    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setSubscriptionId($subscriptionId){
        $this->subscriptionId = $subscriptionId;
        return $this;
    }
    
    public function setExpand($expandObjects){
        $this->expandObjects = $expandObjects;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        if(isset($this->expandObjects)){
            $queryParams = array('expand' => $this->expandObjects);
        }
        else{
            echo "expand is not set!";
            $queryParams = array();
        }
        
        $response = $this->get("v1/subscriptions/".$this->subscriptionId, $queryParams, $headers);
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/subscriptions/{}'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class CancelSubscription extends NetellerAPI{
    var $subscriptionId;

    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setSubscriptionId($subscriptionId){
        $this->subscriptionId = $subscriptionId;
        return $this;
    }

    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array();
        
        $response = $this->post("v1/subscriptions/".$this->subscriptionId."/cancel", $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'POST /v1/subscriptions/{}/cancel'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    } 
}

class ListSubscriptions extends NetellerAPI{
    var $limit;
    var $offset;
    
    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setLimit($limit){
        $this->limit = $limit;
        return $this;
    }
    
    public function setOffset($offset){
        $this->offset = $offset;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array
        (
            "limit" => $this->limit,
            "offset" => $this->offset
        );
        
        $response = $this->get("v1/subscriptions", $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/subscriptions'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class LookupSubscriptionInvoice extends NetellerAPI{
    var $subscriptionId;
    var $invoiceNumber;

    var $expandObjects;
    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setSubscriptionId($subscriptionId){
        $this->subscriptionId = $subscriptionId;
        return $this;
    }
    
    public function setInvoiceNumber($invoiceNumber){
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }
    
    public function setExpand($expandObjects){
        $this->expandObjects = $expandObjects;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        if(isset($this->expandObjects)){
            $queryParams = array('expand' => $this->expandObjects);
        }
        else{
            $queryParams = array();
        }
        
        $response = $this->get("v1/subscriptions/".$this->subscriptionId."/invoices/".$this->invoiceNumber, $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/subscriptions/{subscriptionId}/invoices/{invoiceNumber}'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class LookupAllSubscriptionInvoices extends NetellerAPI{
    var $subscriptionId;
    var $limit;
    var $offset;

    var $expandObjects;
    var $executionErrors;
    
    public function __construct(){
        $this->setApiCredentials(NETELLER_BASE_URL,NETELLER_CLIENT_ID,NETELLER_CLIENT_SECRET);
    }
    
    public function setSubscriptionId($subscriptionId){
        $this->subscriptionId = $subscriptionId;
        return $this;
    }
    
    public function setLimit($limit){
        $this->limit = $limit;
        return $this;
    }
    
    public function setOffset($offset){
        $this->offset = $offset;
        return $this;
    }
    
    public function doRequest(){
        $token = $this->getToken_ClientCredentials();
    
        if($token == false){
            return false;
        }

        $headers = array
        (
            "Content-type" => "application/json",
            "Authorization" => "Bearer ". $token
        );

        $queryParams = array
        (
            "limit" => $this->limit,
            "offset" => $this->offset
        );
        
        $response = $this->get("v1/subscriptions/".$this->subscriptionId."/invoices", $queryParams, $headers, array());
        
        $responseInfo = $response['info'];
        $responseBody = json_decode($response['body']);

        if($responseInfo['http_code'] == 200){
            $this->executionErrors = array();
            return $responseBody;
        }
        elseif($responseInfo['http_code'] >= 400){
            $this->executionErrors = array(
                'http_status_code' => $responseInfo['http_code'],
                'api_error_code' => $responseBody->error->code,
                'api_error_message' => $responseBody->error->message,
                'api_resource_used' => 'GET /v1/subscriptions/{subscriptionId}/invoices'
            );
            return false;
        }
        else{
            return false;
        }
    }
    
    public function getExecutionErrors(){
        return $this->executionErrors;
    }
}

class WebhookHandler extends NetellerAPI{
    
    public function handleRequest(){
        if(isset($_POST)){
            $webhookData = file_get_contents("php://input");
            $webhookData = json_decode($webhookData);

            if (function_exists($webhookData->eventType)) {
                call_user_func($webhookData->eventType, $webhookData);
            }

            header('X-PHP-Response-Code: 200', true, 200);
        }
        
    }
}

?>
