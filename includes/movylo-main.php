<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once "MovyloApiV3.php";

if(!class_exists('MOVYLO_main')){
	class MOVYLO_main {
		private $movylo_api_url;
		private $env = MovyloApiV3::ENV_PROD;
		// private $env = MovyloApiV3::ENV_LOCAL;
	
		// const DEVMODE = true;
		const RANDOM_USERNAME = false;

		public function __construct(){
			add_action( 'admin_menu', array($this, 'movylo_settings_page') );
			add_action( 'admin_enqueue_scripts', array($this, 'movylo_enqueue_admin_script'), 101 );
			add_action( 'wp_head', array($this, 'movylo_embed_code'), 1 );
			$this->movylo_api_url = MovyloApiV3::get_api_url($this->env);
		}

		public function movylo_embed_code(){
			$movylo_disabled = get_option('movylo_disabled');
			if(empty($movylo_disabled)){
				$movylo_embed_code = get_option('movylo_embed_code');
				if(!empty($movylo_embed_code)){
					echo '<script async type="application/javascript" src="'.esc_url($movylo_embed_code).'"></script>';
				}
			}
		}

		public function movylo_enqueue_admin_script($hook_suffix) {
			$screen = get_current_screen();
			if($screen->id == "toplevel_page_movylo-settings"){
				wp_enqueue_style( 'movylo-css', MOVYLO_URL . '/css/admin.css', array(), time());
				// wp_enqueue_script( "movylo-js", MOVYLO_URL.'/js/admin.js', array('jquery'), time() );
			}
		}

		public function movylo_settings_page(){
			add_menu_page( 
				'Movylo', 
				'Movylo', 
				'manage_options', 
				'movylo-settings', 
				array($this, 'do_action_and_print_setting_page'),
				'/wp-content/plugins/movylo-widget/movylo-ico.png'
			);
		}

		public function do_action_and_print_setting_page(){
			$current_user = wp_get_current_user();
			$blog_name = get_bloginfo('name');
			?>
			<div id="wpwrap">
				<div class="wrap shortcuts-manager">
<?php				$movylo_data = $this->movylo_do_action();
					if(!empty($movylo_data)){
						echo '<div class="notice notice-'.esc_attr($movylo_data['status']).'">'.esc_attr($movylo_data['message']).'</div>';
					} ?>
					<div>
						<div class="info_subtitle">
							<h2><b>Movylo and WordPress, quick recap.</b></h2>
						</div>
						<p>Movylo will add a contact form to your website to start capturing clicks and convert them into customers.<br>
						You can also add other floating icons (Booking icon, Contact Us icon, ...).</p>
						<p>Movylo is not only a contact form: when the customer list ramps up the Movylo AutoPilot will kick-in and staart engaging automatocally with the customers in order to help you close more sales.</p>
						<div>
							<h3><b>1#</b> Built and grow your customer list</h3>
							<p>Add a form to capture the clicks of your website and start signing up customers everyday.<br>Optionally you can also connect your social media accounts, use flyers and Qrcodes and much more!</p>
						</div>	
						<div>
							<h3><b>2#</b> Convert the customer list into real sales</h3>
							<p>Optionally start engaging with the customers in the list by sending them automated messages with the Movylo AutoPilot.<br>
							Messages sent by the AutoPilot can include a bonus for the customers, this will help you close more sales!</p>
						</div>	
					</div>	

					<?php if(!get_option('movylo_api_id')){ ?>
						<h2 class="settings wp-heading-inline;" style="text-transform: uppercase"><b>Now, connect your website to Movylo</b></h2>
						
						<div id="accountCreation" class="settings">
							<h2 onclick="toggleCreation()" class="section-title">
								Need a Movylo account?<br>
								Create a free account and connect it to your website
							</h2>
						
							<div id="accountCreationForm" class="import swk_admin_card" style="display:none;margin:20px 0 20px">
								<div class="swk_admin_body">
									<form method="post" action="">
										<div class="field">
											<label for="">First name</label>
											<input type="text" id="f_name" name="f_name" class="" placeholder="First name" value="<?=$_POST['f_name']??$current_user->user_nicename?>">
										</div>
										<div class="field">
											<label for="">Last name</label>
											<input type="text" id="l_name" name="l_name" class="" placeholder="Last name" value="<?=$_POST['l_name']?>">
										</div>
										<div class="field">
											<label for="">Business name</label>
											<input type="text" id="business_name" name="business_name" class="" placeholder="Business name" value="<?=$_POST['business_name']??$blog_name?>" required>
										</div>
										<div class="field">
											<label for="">Email</label>
											<input type="email" name="username" class="" placeholder="Email" value="<?=$_POST['username']??$current_user->user_email?>" required>
										</div>
										<div class="field">
											<label for="">Password</label>
											<input type="password" name="password" class="" placeholder="Password" required>		
										</div>
										<div class="">
											<input type="submit" class="button button-primary" value="Create a Movylo account and connect it to your website">
										</div>
										<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('movylo_nonce'); ?>">
										<input type="hidden" name="movylo_create_account" value="1">
									</form>
								</div>
							</div><!-- End of Sticky wrap -->
						</div>
						
						<div id="accountConnection" class="settings">
							<h2 onclick="toggleConnection()" class="section-title">
								Already have a Movylo account?<br>
								Connect Movylo to your website
							</h2>
							<div id="accountConnectionForm" class="import swk_admin_card" style="display:none;margin:20px 0 20px">
								<ol>
									<li>Log in into your Movylo account and click on "Find new customers" => "Via Website" and get your API credentials;</li>
									<li>Enter the credentials below to connect the Movylo web widget to your WordPress site;</li>
									<li>You are all set!</li>
								</ol>
								<div style="margin-top:20px">
									<div class="import swk_admin_card">
										<div class="swk_admin_body">
											<form method="post" action="">
												<div class="field">
													<label>API Id</label> 
													<input type="text" name="movylo_api_id" value="<?php echo esc_attr(get_option('movylo_api_id')); ?>" placeholder="API Id">
												</div>
												<div class="field">
													<label>API Secret</label>
													<input type="password" name="movylo_api_secret" value="<?php echo esc_attr(get_option('movylo_api_secret')); ?>" placeholder="API Secret">
												</div>
												<div class="field">
													<label>Store Id</label>
													<input type="text" name="movylo_store_id" value="<?php echo esc_attr(get_option('movylo_store_id')); ?>" placeholder="Store Id">
												</div>
												<div class="field">
													<input type="submit" class="button button-primary" value="Connect Movylo to your website">
													<!-- <input type="button" class="button button-secondary rf-token" value="Refresh token"> -->
												</div>
												<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('movylo_nonce'); ?>">
												<!-- <input type="hidden" id="refresh_token" name="movylo_refresh_token" value="yes"> -->
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>

					<?php }else{ ?>

						<div id="accountActions" class="settings">
							<h2><a onclick="toggleActions()" class="section-title">More actions</a></h2>
							<div id="accountActionsForm" class="import swk_admin_card" style="display:none;margin:20px 0 20px">
								<div class="import swk_admin_card">
									<div class="swk_admin_body">
										<form method="post" action="" style="display:inline-block;margin-right:20px">
											<div class="field">
												<input type="submit" class="button button-secondary" value="Clear all data">
											</div>
											<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('movylo_nonce'); ?>">
											<input type="hidden" name="movylo_delete_data" value="yes">
										</form>
										<form method="post" action="" style="display:inline-block;margin-right:20px">
											<div class="field">
												<input type="submit" class="button button-secondary" value="Disable the widget">
											</div>
											<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('movylo_nonce'); ?>">
											<input type="hidden" name="movylo_disabled" value="yes">
										</form>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>

			<script>
				function toggleCreation(){
					var x = document.getElementById("accountCreationForm");
					if (x.style.display === "none") x.style.display = "block";
					else x.style.display = "none";
				}
				function hideCreation(){
					if(document.getElementById("accountCreation")) document.getElementById("accountCreation").style.display = "none";
				}
				function showCreation(){
					if(document.getElementById("accountCreation")) document.getElementById("accountCreation").style.display = "block";
				}
				function toggleConnection(){
					var x = document.getElementById("accountConnectionForm");
					if (x.style.display === "none") x.style.display = "block";
					else x.style.display = "none";
				}
				function hideConnection(){
					if(document.getElementById("accountCreation")) document.getElementById("accountConnection").style.display = "none";
				}
				function showConnection(){
					if(document.getElementById("accountCreation")) document.getElementById("accountConnection").style.display = "block";
				}
				function toggleActions(){
					var x = document.getElementById("accountActionsForm");
					if (x.style.display === "none") x.style.display = "block";
					else x.style.display = "none";
				}
				function hideActions(){
					if(document.getElementById("accountCreation")) document.getElementById("accountActions").style.display = "none";
				}
				function showActions(){
					if(document.getElementById("accountCreation")) document.getElementById("accountActions").style.display = "block";
				}
			</script>
		
	<?php }

		public function movylo_do_action(){
			if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST["movylo_api_id"]) && wp_verify_nonce($_POST["_wpnonce"],"movylo_nonce")) {
				// connessione movylo wp
				$movylo_api_id = sanitize_text_field( $_POST['movylo_api_id'] );
				$movylo_api_secret = sanitize_text_field( $_POST['movylo_api_secret'] );
				$movylo_store_id = sanitize_text_field( $_POST['movylo_store_id'] );
				// $movylo_refresh_token = sanitize_text_field( $_POST['movylo_refresh_token'] );
				update_option('movylo_api_id', $movylo_api_id);
				update_option('movylo_api_secret', $movylo_api_secret);
				update_option('movylo_store_id', $movylo_store_id);
				update_option('movylo_disabled', '');
				// update_option('movylo_refresh_token', $movylo_refresh_token);
				$data = $this->movylo_fetch_access_token();
				return $data;
			}
			if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST["movylo_create_account"]) && wp_verify_nonce($_POST["_wpnonce"],"movylo_nonce")) {
				$creation = $this->create_movylo_account();
				if($creation['success']){
					$api_credentials = $creation['api_credentials'];
					$movylo_api_id = $api_credentials['client_id'];
					$movylo_api_secret = $api_credentials['client_secret'];
					$movylo_store_id = $api_credentials['store_id'];
					update_option('movylo_api_id', $movylo_api_id);
					update_option('movylo_api_secret', $movylo_api_secret);
					update_option('movylo_store_id', $movylo_store_id);
					update_option('movylo_disabled', '');
					$data = $this->movylo_fetch_access_token();
				}else{
					$data['status'] = 'error';
					$data['message'] = $creation['message'];
				}
			}
			if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST["movylo_delete_data"]) && wp_verify_nonce($_POST["_wpnonce"],"movylo_nonce")) {
				$data = $this->delete_data();
			}
			if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST["movylo_disabled"]) && wp_verify_nonce($_POST["_wpnonce"],"movylo_nonce")) {
				$data = $this->disable_widget();
			}
			return $data;
		}
		
		public function delete_data(){
			update_option('movylo_api_id', '');
			update_option('movylo_api_secret', '');
			update_option('movylo_store_id', '');
			update_option('movylo_disabled', 'yes');
			$data['status'] = 'success';
			$data['message'] = 'Data has been deleted and the widget removed. Connect Movylo to show the widge again.';
			return $data;
		}
		public function disable_widget(){
			update_option('movylo_disabled', 'yes');
			$data['status'] = 'success';
			$data['message'] = 'Movylo has been disabled. Connect Movylo to show the widge again.';
			return $data;
		}

		public function create_movylo_account(){
			// client_id linked to movyloshop brand
			$CLIENT_ID = "wpmvlplgn230906";
			$CLIENT_SECRET = "372087D16ABF6F94339C68ECEBE6D9925809CCE56C3E3FAAD1E30AC25927AD94";
			// Set the partner code (provided by Movylo to the partner)
			$PARTNER_CODE = "wpmvlplgn";
			// $activation_code = $PARTNER_CODE;

			$current_user_email = $_POST['username'];
			// if(self::RANDOM_USERNAME) $current_user_email = rand(100000, 999999).'@example.com';

			// $partner_business_uid = $current_user_email; // => an ID that identify the partner's SMB customer on partner side
			$partner_business_email = $current_user_email;    // => the email of the SMB user that will receive platform communications
			$partner_business_username = $current_user_email; // must be unique on movylo side, if not set will be used the email
			$partner_business_name = $_POST['business_name'];    // => the name of the SMB
			$partner_customer_first_name = $_POST['f_name'];
			$partner_customer_last_name = $_POST['l_name'];
			
			try{
				$MovyloApiV3Obj = new MovyloApiV3($CLIENT_ID, $CLIENT_SECRET, $this->env);
				MovyloApiV3::logme("SCRIPT => MovyloApiV3 object created");
			}catch(Exception $e){
				$err_msg = "Error inizializing connection: ".$e->getMessage();
				MovyloApiV3::logme("SCRIPT => ".$err_msg);
				return ['success' => false, 'message' => $err_msg];
			}

			// check if the username already exists on Movylo side
			try{
				// check if already exists by the partner's SMB customer ID with the defined partner code
				// $merchant_data = $MovyloApiV3Obj->get_merchant_by_ext_id($partner_business_uid, $PARTNER_CODE);
				$merchant_data = $MovyloApiV3Obj->get_merchant_by_username($partner_business_email);
				// $merchant_data contains the data defined here https://app.swaggerhub.com/apis-docs/movylo/movylo-api/3.1.0#model-Merchant
				MovyloApiV3::logme("SCRIPT => ACCOUNT ALREADY EXISTS get_merchant_by_ext_id returns ".print_r($merchant_data, 1));
				
				// if the method call returns data and doesn't throw a exception means that the user already exists on Movylo side
				$movylo_account_id = $merchant_data['account_id'];
				if($movylo_account_id){
					$err_msg = "Error creating account: Email address '{$partner_business_email}' has already been used";
					MovyloApiV3::logme("SCRIPT => ".$err_msg);
					return ['success' => false, 'message' => $err_msg];
				}
				
			}catch(Exception $e){
				// if an exception is thrown we need to check the error code
				if($e->getCode()==ErrorCodes::APP_MerchantNotFound){
					// if the code is this it means the merchant account was not found and we need to create it
					MovyloApiV3::logme("SCRIPT => ACCOUNT NOT EXISTS, must create it");
				}else{
					// a different error occurred
					$err_msg = "Error checking account: ".$e->getMessage();
					MovyloApiV3::logme("SCRIPT => ".$err_msg);
					return ['success' => false, 'message' => $err_msg];
				}
			}
				
			$ret = null;
			
			// The user is using the Marketing Engine for the first time so we need to create/activate the account on Movylo
			try{
				
				// Creating the new merchant account
				// all available data are defined at https://app.swaggerhub.com/apis-docs/movylo/movylo-api/3.1.0#model-Merchant
				if($partner_business_uid && $PARTNER_CODE){
					$customer_info = [	"external_account_id" => $partner_business_uid,
										"partner_code" => $PARTNER_CODE];
				}
				if($partner_business_username) $customer_info['username'] = $partner_business_username;
				if($partner_business_email) $customer_info['email'] = $partner_business_email;
				if($partner_customer_first_name) $customer_info['first_name'] = $partner_customer_first_name;
				if($partner_customer_last_name) $customer_info['last_name'] = $partner_customer_last_name;
				if($partner_business_name) $customer_info['business_name'] = $partner_business_name;
				$merchant_data = $MovyloApiV3Obj->create_merchant($customer_info);
				MovyloApiV3::logme("SCRIPT => merchant created, data: ".print_r($merchant_data, 1));
				$movylo_account_id = $merchant_data['account_id'];
				
				// Creating the new store
				// all available data are defined at https://app.swaggerhub.com/apis-docs/movylo/movylo-api/3.1.0#model-StoreInfo
				$store_data = [	"account_id" => $movylo_account_id,
								"store_name" => $partner_business_name,
								// "external_store_id" => $partner_store_uid,
								// "code" => $activation_code,
								"partner_code" => $PARTNER_CODE];
				$store_data = $MovyloApiV3Obj->create_store($store_data);
				$store_movylo_id = $store_data['store_id'];
				MovyloApiV3::logme("SCRIPT => store created (#$store_movylo_id), data: ".print_r($store_data, 1));
				// $user_exists_on_movylo = TRUE;
				
			}catch(Exception $e){
				$err_msg = "Error creating account: ".$e->getMessage();
				MovyloApiV3::logme("SCRIPT => ".$err_msg);
				return ['success' => false, 'message' => $err_msg];
			}
			
			// Get the api credentials
			try{
				$ret = $MovyloApiV3Obj->get_api_credentials($store_movylo_id);
				MovyloApiV3::logme("SCRIPT => get_api_credentials returns ".print_r($ret, 1));
			}catch(Exception $e){
				$err_msg = "Error getting the api credentials: ".$e->getMessage();
				MovyloApiV3::logme("SCRIPT => ".$err_msg);
				return ['success' => false, 'message' => $err_msg];
			}
			
			return $ret;
		}

		public function movylo_fetch_access_token(){
			$return_data = array();
			$movylo_access_token = get_option('movylo_access_token');
			// $movylo_refresh_token = get_option('movylo_refresh_token');
			$movylo_store_id = get_option('movylo_store_id');
			$movylo_api_id = get_option('movylo_api_id');
			$movylo_api_secret = get_option('movylo_api_secret');
			if(
				!empty($movylo_api_id)
				&& !empty($movylo_api_secret)
			) {
				$data = array(
					'client_id' => $movylo_api_id,
					'client_secret' => $movylo_api_secret,
					'grant_type' => 'client_credentials',
				);
				$access_token_data = $this->movylo_access_token($this->movylo_api_url().'/v3/Authentication/', $data);
				//echo "<pre>"; print_r($access_token_data); "</pre>"; exit;
				if($access_token_data['code'] == "200"){
					update_option('movylo_access_token', $access_token_data['data']->access_token);
					update_option('movylo_refresh_token', "no");
					$data = array(
						'store_id' => $movylo_store_id,
					);
					$widget_code_data = $this->movylo_widget_code($this->movylo_api_url().'/v3/Store/'.$movylo_store_id.'/WidgetCode/GWL/', $access_token_data['data']->access_token);
					if($widget_code_data['code'] == "200"){
						update_option('movylo_embed_code', $widget_code_data['data']->js_url);
						$return_data['status'] = "success";
						$return_data['message'] = "Movylo has been connected!";
					} else {
						$return_data['status'] = "warning";
						$return_data['message'] = $widget_code_data['code'] . ': '. $widget_code_data['message'];
					}
				} else {						
					$return_data['status'] = "warning";
					$return_data['message'] = $access_token_data['code'] . ': '. $access_token_data['message'];
				}
			}
			return $return_data;
		}

		public function movylo_api_url(){
			return $this->movylo_api_url;
		}

		public function movylo_access_token($url, $data){
			$args = array(
				'method'      => 'POST',
				'timeout'     => 45,
				'sslverify'   => false,
				'headers'     => array(
					'accept'  => 'application/json',
					'Content-Type'  => 'application/json',
				),
				'body' => json_encode($data),
			);

			$request = wp_remote_post( $url, $args );
			$result['data'] = json_decode($request['body']);
			$result['message'] = $request['response']['message'];
			$result['code'] = $request['response']['code'];
			
			return $result;
		}
		
		public function movylo_widget_code($url, $access_token){
			$args = array(
				'method'      => 'GET',
				'timeout'     => 45,
				'sslverify'   => false,
				'headers'     => array(
					'Authorization' => 'Bearer '.$access_token,
					'accept'  => 'application/json',
					'Content-Type'  => 'application/json',
				),
			);

			$request = wp_remote_get( $url, $args );
			$result['data'] = json_decode($request['body']);
			$result['message'] = $request['response']['message'];
			$result['code'] = $request['response']['code'];
			return $result;
		}

	}

	new MOVYLO_main();
}
