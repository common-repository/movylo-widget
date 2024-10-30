<?php

/*
 * This is a PHP Movylo APIs wrapper, an implementation of some of the Movylo V3 APIs
 * described at https://app.swaggerhub.com/apis-docs/movylo/movylo-api/
 * 
 *  Current version 3.4.0.230614
 *  
 * 	Version history:
 * 
 * 		version 3.2.3.230620
 * 			added methods 'get_merchant_by_username' and 'get_api_credentials'
 * 
 * 		version 3.2.2.230614
 * 			added methods 'set_coupon_status' and 'get_coupon_status'
 * 
 * 		version 3.2.1.220111
 * 			added method 'get_plan'
 * 
 * 		version 3.2.0.210412
 * 			added methods 'set_reseller', 'update_merchant', 'check_code', 'get_plans', 'get_ip_data', 'recover_login, 'store_login', 'close_store', 'del_store_by_ext_id', 'close_store_by_ext_id', 'set_store_bo'
 *		  	method 'merchant_login' now accepts 'partner_code' and login via password
 *		  	method 'set_merchant' now accepts param 'json_params'
 *		  	method 'set_store' now accepts param 'plan_id' and 'json_params'
 *		  	modified the environment management
 * 
 * 		version 3.1.1.191010
 * 			added methods 'get_coupons_stats', 'get_store_stats', 'get_reviews' and 'get_feedback'
 * 
 * 		version 3.1.0.190206
 * 			added methods 'del_store' and 'merchant_login'
 * 			deprecated method 'merchant_authentication'
 * 			new version number related to the API version on SwaggherHub
 * 
 *  	version 1.181130
 *  		added new methods 'set_plan', 'set_customers_limit_offset' and 'set_sms_amount_offset'
 * 
 *  	version 1.181127
 *  		added 'phone' as second input params in the method set_merchant
 *  		added 'username' as last input params in the method set_merchant taht can now manage username different from the email
 *  		methods create_store and create_merchat return the array with the data of the created object
 *  
 *  	version 1.181120
 *  		all errors are managed throwing Exceptions
 *  
 */


class MovyloApiV3{
	
	const ENV_PROD = "amazon-movylo";
	const ENV_TEST = "amazon-test";
	const ENV_DEV = "amazon-dev";
	const ENV_LOCAL = "local-movylo";
	const API_URL_LIST = [
		self::ENV_PROD => "https://api.movylo.com",
		self::ENV_TEST => "https://api.sandbox.movylo.com",
		self::ENV_DEV => "https://api.dev.movylo.com",
		self::ENV_LOCAL => "http://api.movylocom.local"];
	private $api_url;
	private $access_token;
	private $last_error_message;
	private $last_error_response;
	private $client_id;
	private $client_secret;
	
	public function __construct($client_id, $client_secret, $env=null, $clear_token=false) {
		if(!array_key_exists($env, self::API_URL_LIST)) $env=self::ENV_PROD;
		$this->api_url = self::API_URL_LIST[$env]."/v3/";
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		if($clear_token) $this->access_token=$_SESSION['movylo_api_access_token']=null;
		self::logme("client_id = '{$client_id}', client_secret = '{$client_secret}', api_url = '{$this->api_url}'".(is_array($_SESSION)&&array_key_exists('movylo_api_access_token', $_SESSION)?", SESSION[movylo_api_access_token]='{$_SESSION['movylo_api_access_token']}'":""));
		if(is_array($_SESSION) && array_key_exists('movylo_api_access_token', $_SESSION) && $_SESSION['movylo_api_access_token']){
			self::logme(__FUNCTION__." => Using session token");
			$this->access_token = $_SESSION['movylo_api_access_token'];
		}else{
			self::logme(__FUNCTION__." => Generating token");
			$result = $this->authenticate($client_id, $client_secret);
			$this->access_token = $result['access_token'];
			if(is_array($_SESSION)) $_SESSION['movylo_api_access_token'] = $this->access_token;
		}
		self::logme(__FUNCTION__." => Set token to {$this->access_token}");
		return;
	}
	
	public function authenticate($client_id, $client_secret){
		$request_type = "POST";
		$api = "Authentication";
		$data = array("client_id" => $client_id, "client_secret" => $client_secret, "grant_type" => "client_credentials");
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public static function get_api_url($env=null){
		if(!$env) $env=self::get_env();
		return self::API_URL_LIST[$env];
	}
	
	public function get_access_token(){
		if($this->access_token) return $this->access_token;
		return false;
	}
	
	public function access_token_set(){
		if($this->access_token) return true;
		return false;
	}
	
	public function check_access_token(){
		// returns parameters described here https://app.swaggerhub.com/apis-docs/movylo/movylo-api/3.1.0#model-StoreInfo
		$request_type = "GET";
		$api = "isAlive";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}

	public function set_merchant_with_just_partner_id($ext_acc_id=null, $partner_code=null){
		$username = $partner_code.'_'.$ext_acc_id;
		$email = $username.'@example.com';
		return $this->set_merchant($email, null, null, null, null, null, null, null, null, null, null, null, $ext_acc_id, $partner_code, $username);
	}
	
	public function set_merchant($email, $phone=null, $f_name=null, $l_name=null, $bus_name=null, $cf=null, $vat=null, $address=null, $city=null, $state=null, $zip=null, $country=null, $ext_acc_id=null, $partner_code=null, $username=null, $pw='', $json_params=null, $enabled_notify=null){
		$data = [	"username" => $username?$username:$email,
					"password" => $pw,
					"email" => $email,
					"phone" => $phone,
					"first_name" => $f_name,
					"last_name" => $l_name,
					"business_name" => $bus_name,
					"fiscal_code" => $cf,
					"vat_number" => $vat,
					"address" => $address,
					"city" => $city,
					"state" => $state,
					"zip" => $zip,
					"country" => $country,
					"external_account_id" => $ext_acc_id,
					"partner_code" => $partner_code,
					"json_params" => $json_params ? json_encode($json_params) : null
		];
		if($enabled_notify !== null) $data['enabled_notify']=$enabled_notify;
		
		return $this->create_merchant($data);
	}

	public function set_reseller($email, $phone=null, $f_name=null, $l_name=null, $ext_acc_id=null, $partner_code=null){
		$data = [	"username" => $ext_acc_id,
					"email" => $email,
					"phone" => $phone,
					"first_name" => $f_name,
					"last_name" => $l_name,
					"external_account_id" => $ext_acc_id,
					"partner_code" => $partner_code
		];
		$request_type = "POST";
		$api = "Reseller";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function update_merchant($data){
		$request_type = "PUT";
		$api = "Merchant";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function create_merchant($data){
		$request_type = "POST";
		$api = "Merchant";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function create_store($data){
		$request_type = "POST";
		$api = "Store";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function set_store_bo($id, $data){
		$request_type = "POST";
		$api = "Store/{$id}/SetBo";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function set_store($account_id, $store_name, $code=null, $currency=null, $country=null, $ext_sto_id=null, $partner_code=null, $plan_id=null, $json_params=null){
		self::logme(__FUNCTION__." => HERE 1");
		$data = [	"account_id" => $account_id,
					"store_name" => $store_name,
					"code" => $code,
					"currency" => $currency,
					"country" => $country,
					"external_store_id" => $ext_sto_id,
					"partner_code" => $partner_code,
					"plan_id" => $plan_id,
					"json_params" => $json_params];
		return $this->create_store($data);
	}
	
	public function get_store($id){
		// returns parameters described here https://app.swaggerhub.com/apis-docs/movylo/movylo-api/3.1.0#model-StoreInfo
		$request_type = "GET";
		$api = "Store/{$id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function del_store($id){
		$request_type = "DELETE";
		$api = "Store/{$id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function del_store_by_ext_id($ext_id, $partner_code){
		$ret = $this->get_store_by_ext_id($ext_id, $partner_code);
		if($ret["store_id"])
			return $this->del_store($ret["store_id"]);
		else
			return $ret;
	}
	
	public function close_store($store_id){
		$request_type = "PUT";
		$api = "/Store/{$store_id}/";
		$data = array("expiration_date" => date("Y-m-d"));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function close_store_by_ext_id($ext_id, $partner_code){
		$ret = $this->get_store_by_ext_id($ext_id, $partner_code);
		if($ret["store_id"]){
			$request_type = "PUT";
			$api = "/Store/{$ret["store_id"]}/";
			$data = array("expiration_date" => date("Y-m-d"));
			return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
		}else
			return $ret;
	}
	
	public function get_store_info_extended($id){
		// returns parameters described here https://app.swaggerhub.com/apis-docs/movylo/movylo-api/3.1.0#model-StoreInfoExtended
		$request_type = "GET";
		$api = "Store/{$id}/InfoExtended";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_customer_coupons($sid, $aid){
		$request_type = "GET";
		$api = "Store/{$sid}/Customer/{$aid}/Coupons/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_feedback($id, $from=null, $to=null){
		$request_type = "GET";
		$api = "Store/{$id}/Feedback/?from_date={$from}&to_date={$to}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_reviews($id, $from=null, $to=null){
		$request_type = "GET";
		$api = "Store/{$id}/Reviews/?from_date={$from}&to_date={$to}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_store_by_ext_id($ext_id, $partner_code){
		$request_type = "GET";
		$api = "Store/{$ext_id}";
		$data = array("partner_code" => $partner_code);
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function update_store($id, $data){
		// returns parameters described here https://app.swaggerhub.com/apis-docs/movylo/movylo-api/3.1.0#model-StoreInfo
		$request_type = "PUT";
		$api = "Store/{$id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function update_store_extended($id, $data){
		$request_type = "PUT";
		$api = "Store/{$id}/InfoExtended";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function get_store_info_pages($id){
		$request_type = "GET";
		$api = "Store/{$id}/InfoPages";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function set_store_info_pages($id, $data){
		$request_type = "PUT";
		$api = "Store/{$id}/InfoPages";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	/* DEPRECATED in v 3.1.0 use merchant_login instead */
	public function merchant_authentication($id){
		$request_type = "POST";
		$api = "Merchant/Authentication";
		$data = array("account_id" => $id);
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function merchant_login($id, $partner_code=null, $pass=null){
		$request_type = "GET";
		$api = "Merchant/Login/{$id}";
		$data = [];
		if($partner_code) $data["partner_code"] = $partner_code;
		if($pass) $data["password"] = $pass;
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function recover_login($id, $partner_code=null){
		$request_type = "PUT";
		$api = "Merchant/Login/Recover/{$id}";
		$data = [];
		if($partner_code) $data["partner_code"] = $partner_code;
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function store_login($id, $partner_code){
		$request_type = "GET";
		$api = "Store/Login/{$id}";
		$data = [];
		if($partner_code) $data=array("partner_code" => $partner_code);
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function get_merchant($id){
		$request_type = "GET";
		$api = "Merchant/{$id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function del_merchant($id){
		$request_type = "DELETE";
		$api = "Merchant/{$id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_merchant_by_username($username){
		$request_type = "GET";
		$api = "Merchant/Search";
		$data = array("username" => $username);
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function get_merchant_by_ext_id($ext_id, $partner_code){
		$request_type = "GET";
		$api = "Merchant/{$ext_id}";
		$data = array("partner_code" => $partner_code);
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function del_merchant_by_ext_id($ext_id, $partner_code){
		$ret = $this->get_merchant_by_ext_id($ext_id, $partner_code);
		if($ret["account_id"])
			return $this->del_merchant($ret["account_id"]);
		else
			return $ret;
	}
	
	
	/*
	 * Marketing automation methods
	 */
	
	public function set_autopilot_bonus($id, $data){
		$request_type = "POST";
		$api = "Store/{$id}/AutoPilot/Bonus";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function get_mkauto_status($id){
		$request_type = "GET";
		$api = "Store/{$id}/MarketingAutomation";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function set_mkauto_status($id, $status=TRUE){
		$request_type = "PUT";
		$api = "Store/{$id}/MarketingAutomation/".intval($status);
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function set_mkauto_settings($id, $data){
		$request_type = "PUT";
		$api = "Store/{$id}/MarketingAutomation/1";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	
	
	/*
	 * Customers methods
	 */
	
	public function get_customers($id, $search_text=null){
		$request_type = "GET";
		$api = "Store/{$id}/Customer";
		if($search_text) $data = array("search_string" => $search_text);
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function get_customer_by_ext_id($id, $ext_acc_id){
		$request_type = "GET";
		$api = "Store/{$id}/Customer/extId/{$ext_acc_id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function set_customer($id, $data){
		// $data contains parameters described here https://app.swaggerhub.com/apis-docs/movylo/movylo-api/3.1.0#model-Customer
		$request_type = "POST";
		$api = "Store/{$id}/Customer";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function update_customer($id, $account_id, $data){
		$request_type = "PUT";
		$api = "Store/{$id}/Customer/{$account_id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function del_customer($id, $account_id){
		$request_type = "DELETE";
		$api = "Store/{$id}/Customer/{$account_id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function del_customer_by_ext_id($id, $ext_acc_id){
		$request_type = "DELETE";
		$api = "Store/{$id}/Customer/extId/{$ext_acc_id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_customer_stats($id, $account_id){
		$request_type = "GET";
		$api = "Store/{$id}/Customer/{$account_id}/Stats";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function set_store_style($id, $logo=null, $bgc=null, $btnc=null){
		$request_type = "POST";
		$api = "Store/{$id}/Style/";
		$data = ["id" => $id, "logo_url" => $logo, "bg_color" => $bgc, "btn_color" => $btnc];
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function set_generic_data($id, $key, $data, $exp=null){
		$request_type = "POST";
		$api = "Generic/Data";
		$data = ["id" => $id, "key" => $key, "data" => $data, "exp" => $exp];
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function get_generic_data($id, $key=null){
		$request_type = "GET";
		if($id) $api = "Generic/Data/Id/".$id;
		if($key) $api = "Generic/Data/Key/".$key;
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function del_generic_data($id, $key=null){
		$request_type = "DELETE";
		if($id) $api = "Generic/Data/Id/".$id;
		if($key) $api = "Generic/Data/Key/".$key;
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	
	
	/*
	 * Coupons methods
	 */
	
	
	public function get_coupon_status($id, $code){
		$request_type = "GET";
		$api = "Store/{$id}/Coupon/{$code}/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function set_coupon_status($id, $code, $amount=null){
		$request_type = "PUT";
		$api = "Store/{$id}/Coupon/{$code}/".($amount?'?amount='.$amount:'');
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	/*
	 * Stats methods
	 */
	
	public function get_store_stats($id, $from=null, $to=null){
		$request_type = "GET";
		$api = "Store/{$id}/Stats/?from_date={$from}&to_date={$to}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_coupons_stats($id, $from=null, $to=null){
		$request_type = "GET";
		$api = "Store/{$id}/Coupons/Stats/?from_date={$from}&to_date={$to}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	

	/*
	 * Plans methods
	 */
	
	public function get_plan($plan_id){
		$request_type = "GET";
		$api = "Billing/Plan/Id/{$plan_id}/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}	
	public function get_plans(){
		$request_type = "GET";
		$api = "Billing/Plans/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function set_plan($id, $plan_id, $new_expiring_date=null){
		$request_type = "PUT";
		$api = "Store/{$id}/Plan/{$plan_id}";
		$data = ["new_expiring_date" => $new_expiring_date];
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function set_plan_by_ext_id($id, $plan_ext_id, $partner_code, $new_expiring_date=null){
		$request_type = "PUT";
		$api = "Store/{$id}/Plan/{$plan_ext_id}";
		$data = ["partner_code" => $partner_code, "new_expiring_date" => $new_expiring_date];
		self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function set_customers_limit_offset($id, $num_customers){
		$request_type = "PUT";
		$api = "Store/{$id}/CustomersLimit/Offset/{$num_customers}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function set_sms_amount_offset($id, $sms){
		$request_type = "PUT";
		$api = "Store/{$id}/SmsAmount/Offset/{$sms}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	


	/*
	* Other methods
	*/

	public function get_consumer_platform_messages($store_id){
		$request_type = "GET";
		$api = "Platform/Messages/Store/{$store_id}/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	public function get_store_merchant_messages($store_id){
		$request_type = "GET";
		$api = "Store/{$store_id}/MerchantMessages/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	public function get_pre_board_questions($lang=null, $country=null){
		$request_type = "GET";
		$api = "Merchant/PreBoard/lang/{$lang}/country/{$country}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	public function save_pre_board($data){
		$request_type = "POST";
		$api = "Merchant/PreBoard";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function save_to_board($data){
		$request_type = "POST";
		$api = "Merchant/ToBoard";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function send_templated_email($tit, $text, $to){
	    $request_type = "POST";
	    $api = "Generic/Send/Email/";
	    $data = ["title"=>$tit, "text"=>$text, "to"=>$to];
	    self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
	    return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function send_email_template($to, $template, $sbj=null){
	    $request_type = "POST";
	    $api = "Generic/Send/Template/Email/";
	    $data = ["to"=>$to, "template"=>$template, "sbj"=>$sbj];
	    self::logme(__FUNCTION__." => Calling Api '{$api}' with data: ".print_r($data,1));
	    return self::my_json_decode($this->call_api($api, $request_type, $data), TRUE);
	}
	
	public function get_ip_data($ip){
		$request_type = "GET";
		$api = "Ip/Data/{$ip}/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function check_code($code){
		$request_type = "GET";
		$api = "Code/{$code}/Check/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}

	public function get_code_info($code){
		$request_type = "GET";
		$api = "Code/{$code}/Info/";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_page_url($t){
		$request_type = "GET";
		$api = "Generic/Page/?types[]=$t";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	public function get_api_credentials($store_id){
		$request_type = "GET";
		$api = "ApiCredentials/{$store_id}";
		self::logme(__FUNCTION__." => Calling Api '{$api}'");
		return self::my_json_decode($this->call_api($api, $request_type), TRUE);
	}
	
	private function call_api($api, $request_type, $data=''){
		list($httpcode, $res) = $this->send_curl($api, $request_type, $data);
		self::logme(__FUNCTION__." => call returns code '{$httpcode}' with data ".print_r($res,1)."\n");
		$this->last_error_message = FALSE;
		$this->last_error_response = FALSE;
		$app_error_code = null;
		if($httpcode<200 || $httpcode>299){
 			$err_msg  = "Unable to complete the operation.\nHttp code: '{$httpcode}'\nResponse: '{$res}'";
 			$app_error_code = $httpcode;
			if($res){
				try{
					$res_obj = json_decode($res, 1);
					// $app_error_code = is_array($res_obj['error'])?$res_obj['error']['code']:'';
					$app_error_code = $res_obj['error']['code'];
				}catch(Exception $e){
					self::logme(__FUNCTION__." => damn it wasn't json. It was: '{$res}'");
				}
			}
 			if($httpcode==ErrorCodes::HTTP_Unauthorized && $app_error_code==ErrorCodes::APP_InvalidToken){
 				self::logme(__FUNCTION__." => exipired token, try generationg a new one");
 				$_SESSION['movylo_api_access_token'] = $this->access_token = null;
				// Generate a new token
				try{
					$result = $this->authenticate($this->client_id, $this->client_secret);
					$_SESSION['movylo_api_access_token'] = $this->access_token = $result['access_token'];
				}catch(Exception $e){
					$err_code = $e->getCode();
					self::logme(__FUNCTION__." => Error '{$err_code}' generating a new token:\n".$e->getMessage());
					// if credentials are wrong
					if($err_code==ErrorCodes::APP_InvalidCredentials){
						// stop doingcalls
						throw new Exception($e->getMessage(), $err_code);
					}
				}
				if($this->access_token){
					self::logme(__FUNCTION__." => Re do the same call with the new token");
					$res = $this->call_api($api, $request_type, $data);
				}
 			}else{
 				self::logme(__FUNCTION__." => App error code: ".$app_error_code);
 				$this->last_error_message = $err_msg;
 				$this->last_error_response = $res;
 				throw new Exception($err_msg, $app_error_code);
 			}
 		}
		return $res;
	}
	
	private function send_curl($api, $request_type, $data=[]){
		$curl = curl_init();
		$api_url= $this->api_url.$api;
		if(substr($api_url, -1)!='/' && !strpos($api_url, '?') ) $api_url .= '/';
		$httpheader = [];		
		self::logme(__FUNCTION__." => {$request_type} to '{$api_url}'");
		if($data && ($request_type=="POST" || $request_type=="PUT") ){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			array_push($httpheader, "Content-Type: application/x-www-form-urlencoded");
			self::logme("\t\t with data: ".http_build_query($data));
		}else{
			if($data) $api_url .= '?'.http_build_query($data);
		}
		self::logme("\t\t with header => ".print_r($httpheader, 1));
		if($this->access_token) array_push($httpheader, "Authorization: Bearer ".$this->access_token);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($curl, CURLOPT_URL, $api_url); 
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request_type);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if($e=self::get_env()==self::ENV_LOCAL){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}
		$res = curl_exec($curl);
		$info = curl_getinfo($curl);
		$httpcode = array_key_exists("http_code", $info) ? $info["http_code"] : 0;
		if($httpcode==0) $res="Curl info: ".print_r($info,1)."\nError: ".curl_error($curl);
		curl_close($curl);
		return [$httpcode, $res];
	}
	
	public function get_last_error(){
		return $this->last_error_message;
	}
	public function get_last_error_response(){
		return $this->last_error_response;
	}
	
	public static function my_json_decode($json){
		$d = json_decode($json, TRUE);
		if(is_null($d)){
			// bad json
			mail('admin@movylo.com', "json decoding issue", "Received bad json\n\n".$json, "From: mail@movylo.com");
			return null;
		}
		return $d;
	}
	
	public static function logme($txt){
		$log_dir = "/var/log/movylo/";
		if(!(is_dir($log_dir))) $log_dir = "/tmp/";
		$myfile = fopen($log_dir."MovyloApiV3.log", "a");
		if($myfile){
			fwrite($myfile, "\n".date('ymd-His')."[".get_called_class()."] {$txt}\n");
			fclose($myfile);
		}
	}
	
	public static function get_env(){
		$current_environment = getenv('APPLICATION_ENV');
		if(!$current_environment){
			$current_environment = get_cfg_var('movylo.application_env');
		}
		return $current_environment;
	}
	
}

class ErrorCodes {
	const HTTP_Unauthorized = 401;
	const APP_MerchantNotFound = 1101;
	const APP_InvalidToken = 1041;
	const APP_InvalidCredentials = 1042;
}
