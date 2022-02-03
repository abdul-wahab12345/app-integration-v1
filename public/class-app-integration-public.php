<?php

/**
* The public-facing functionality of the plugin.
*
* @link       https://abdulwahab.live/
* @since      1.0.0
*
* @package    App_Integration
* @subpackage App_Integration/public
*/

/**
* The public-facing functionality of the plugin.
*
* Defines the plugin name, version, and two examples hooks for how to
* enqueue the public-facing stylesheet and JavaScript.
*
* @package    App_Integration
* @subpackage App_Integration/public
* @author     Abdul Wahab <rockingwahab9@gmail.com>
*/
class App_Integration_Public {

	/**
	* The ID of this plugin.
	*
	* @since    1.0.0
	* @access   private
	* @var      string    $plugin_name    The ID of this plugin.
	*/
	private $plugin_name;

	/**
	* The version of this plugin.
	*
	* @since    1.0.0
	* @access   private
	* @var      string    $version    The current version of this plugin.
	*/
	private $version;

	/**
	* Initialize the class and set its properties.
	*
	* @since    1.0.0
	* @param      string    $plugin_name       The name of the plugin.
	* @param      string    $version    The version of this plugin.
	*/
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}



	public function template_redirect(){

		/*
		*Auto Login on Checkout and payment methode addition
		*/

		if(isset($_GET['aw_user_id']) && $_GET['aw_secure_hash']){

			$user_id = $_GET['aw_user_id'];
			$token = $_GET['aw_secure_hash'];
			$web_token = get_user_meta($user_id,'secure_token',true);

			if($web_token && $token == $web_token){
				wp_clear_auth_cookie();
				wp_set_current_user ( $user_id );
				wp_set_auth_cookie  ( $user_id );
			}else{
				wp_clear_auth_cookie();
			}

		}

		/*
		*Add product to cart using url prams and redirect to checkout webview
		*/

		if(isset($_GET['aw_add_to_cart1'])){
			$product_id = $_GET['aw_add_to_cart1'];
			$variation_id = $_GET['aw_variation_id'];


			$data = array();

			if(isset($_GET['planId']) && $_GET['planId'] != "0" && $_GET['planId'] != "false"){
				$aw_switch_id = $_GET['planId'];
				$data = array('aw_switch_id' => $aw_switch_id );
			}

			WC()->cart->add_to_cart( $product_id ,1,	$variation_id,array(), $data);
			
			wp_redirect(wc_get_checkout_url());
			exit;

		}
	}

	/*
	*Rest Api end points for app
	*/

	public function rest_api_callback(){
		register_rest_route( 'meal-prep/v1', '/user-meals', array(
			'methods' => "POST",
			'callback' => array($this,'meals_callback'),
		) );

		register_rest_route( 'meal-prep/v1', '/user-login', array(
			'methods' => "POST",
			'callback' => array($this,'user_login'),
		) );

		register_rest_route( 'meal-prep/v1', '/dislike-meal', array(
			'methods' => "POST",
			'callback' => array($this,'dislike_meal'),
		) );

		register_rest_route( 'meal-prep/v1', '/like-meal', array(
			'methods' => 'GET',
			'callback' => array($this,'like_meal'),
		) );

		register_rest_route( 'meal-prep/v1', '/unlike-meal', array(
			'methods' => 'GET',
			'callback' => array($this,'unlike_meal'),
		) );

		register_rest_route( 'meal-prep/v1', '/products', array(
			'methods' => 'GET',
			'callback' => array($this,'get_products'),
		) );

		register_rest_route( 'meal-prep/v1', '/make-default-payment-method', array(
			'methods' => 'POST',
			'callback' => array($this,'make_default'),
		) );

	}

	public function make_default($data){

		$token_id = absint( $data['id']);
		$token    = WC_Payment_Tokens::get( $token_id );


		WC_Payment_Tokens::set_users_default( $token->get_user_id(), intval( $token_id ) );
		return __( 'This payment method was successfully set as your default.');

	}

	public function meals_callback($req){
		$user_id = $req['user_id'];
		if($user_id){

			$json_data = [];

			$current_week = get_field("aw_current_week","option");


			$current_time = current_time('timestamp');

			$aw_current_week_date = get_option("aw_current_week_date");

			$week_index = get_option("aw_week_index");
			if(!$aw_current_week_date){
				$counter = 1;

				while (have_rows("aw_weeks","option")) {
					the_row();
					$week = get_sub_field("week");
					if($current_week == $week){
						$week_index = $counter;
					}
					$counter++;

				}
				update_option('aw_week_index',$week_index);

				$sunday = new DateTime(date("Y-m-d",$current_time));
				$sunday->modify("Next Sunday");
				$sunday->setTime(23,59,59);

				update_option("aw_current_week_date",$sunday->format("Y-m-d"));
			}else{
				$aw_current_week_number = get_option("aw_current_week_date");

				if(date("Y-m-d",$current_time) > $aw_current_week_number){

					$sunday = new DateTime(date("Y-m-d",$current_time));
					$sunday->modify("Next Sunday");
					$sunday->setTime(23,59,59);

					update_option("aw_current_week_date",$sunday->format("Y-m-d"));

					$counter = 1;
					$week_index = $week_index + 1;

					while (have_rows("aw_weeks","option")) {
						the_row();
						$week = get_sub_field("week");
						if($counter == ($week_index)){
							update_field("aw_current_week",$week,"option");
						}
						$counter++;

					}

					update_option('aw_week_index',$week_index);

				}

			}
			$all_weeks = get_field("aw_weeks","option");

			if(count($all_weeks) > 0 && count($all_weeks) < $week_index){
				$week_index = 1;
				update_option('aw_week_index',$week_index);

				$counter = 1;

				while (have_rows("aw_weeks","option")) {
					the_row();
					$week = get_sub_field("week");
					if($counter == 1){
						update_field("aw_current_week",$week,"option");
					}
					$counter++;

				}

			}
			//$user_id = get_current_user_id();
			$current_week = get_field("aw_current_week","option");

			if($current_week){


				$user_fav_post = get_user_meta($user_id,"aw_fav_post",true);
				$user_fav = [];
				if($user_fav_post){
					$user_fav = get_post_meta($user_fav_post,'aw_fav_meal');
					$temp_fav = [];
					if($user_fav){
						foreach ($user_fav as $key => $value) {
							$temp_fav[] = $value;
						}

						$user_fav = $temp_fav;
					}
				}

				$user_subscriptions = wcs_get_users_subscriptions($user_id);
				$user_products = [];

				foreach ($user_subscriptions as $subscription) {
					if ($subscription->get_status() != "active") {
						continue;
					}
					$products = $subscription->get_items();
					foreach ($products as $item) {
						$product_id = $item->get_product_id();
						$user_products[] = $product_id;
					}
				}

				$all_meals = [];

				while (have_rows('weeks_planner',$current_week)) {
					the_row();
					$planner = get_sub_field('planner_product');
					//check if user have this product in subscription

					if (!in_array($planner,$user_products)) {
						continue;
					}

					foreach (get_sub_field('meals') as $key => $value) {
						$all_meals[] = $value;
					}

				}


				foreach($all_meals as $value){
					$meal = ['id' => $value,'title' => get_the_title($value),'subTitle' => get_field('sub_heading',$value),'product_id' => get_field('product_planner',$value),'imageUrl' => get_the_post_thumbnail_url($value),'isFavorite' => in_array($value,$user_fav)];

					$ingredients = [];
					$carbs = [];
					while (have_rows('ingredients_admin', $value)) {
						the_row();
						$ing = explode(":",get_sub_field('name'));
						$ingredients[] = $ing[0];

						$unit_weights = get_sub_field("unit_weights");
						if(!empty($unit_weights)){
							$unit_weights = json_decode($unit_weights,true);
							foreach ($unit_weights['nutrition']['nutrients'] as $key3 => $value3) {
								$carbs[$value3['name']][] = $value3;
							}
						}

					}

					$calories = ['weight' => 0,'unit' => '','type' => 'kcal'];
					$Carbohydrates = ['weight' => 0,'unit' => '','type' => 'Carb'];
					$Fat = ['weight' => 0,'unit' => '','type' => 'Fat'];
					$Protein = ['weight' => 0,'unit' => '','type' => 'Protein'];

					foreach ($carbs as $key2 => $value1) {
						foreach ($value1 as $key1 => $value2) {

							switch($key2){
								case 'Calories':
								$calories['weight'] = $calories['weight'] + $value2['amount'];
								$calories['unit'] = $value2['unit'];
								break;
								case 'Carbohydrates':
								$Carbohydrates['weight'] = $Carbohydrates['weight'] + $value2['amount'];
								$Carbohydrates['unit'] = $value2['unit'];

								break;
								case 'Fat':
								$Fat['weight'] = $Fat['weight'] + $value2['amount'];
								$Fat['unit'] = $value2['unit'];

								break;
								case 'Protein':
								$Protein['weight'] = $Protein['weight'] + $value2['amount'];
								$Protein['unit'] = $value2['unit'];

								break;
							}
						}

					}




					if($calories['weight'] > 1000){
						$calories['weight'] = round($calories['weight'] / 1000,1);
						$calories['weight'] = $calories['weight'] . "k";
					}else{
						$cal = $calories['weight'];
						$calories['weight'] = $cal > 1?round($cal):round($cal,2);
					}

					if($Carbohydrates['weight'] > 1000){
						$Carbohydrates['weight'] = round($Carbohydrates['weight'] / 1000,1);
						$Carbohydrates['weight'] = $Carbohydrates['weight'] . "k";
					}else{
						$carb = $Carbohydrates['weight'];
						$Carbohydrates['weight'] = $carb > 1?round($carb):round($carb,2);

					}

					if($Fat['weight'] > 1000){
						$Fat['weight'] = round($Fat['weight'] / 1000,1);
						$Fat['weight'] = $Fat['weight'] . "k";
					}else{

						$F = $Fat['weight'];
						$Fat['weight'] = $F > 1?round($F):round($F,2);
					}

					if($Protein['weight'] > 1000){
						$Protein['weight'] = round($Protein['weight'] / 1000,1);
						$Protein['weight'] = $Protein['weight'] . "k";
					}else{
						$pro = $Protein['weight'];
						$Protein['weight'] = $pro > 1?round($pro):round($pro,2);

					}

					$Carbohydrates['weight'] = $Carbohydrates['weight'] . "g";
					$Protein['weight'] = $Protein['weight'] . "g";
					$Fat['weight'] = $Fat['weight'] . "g";
					$calories['weight'] = $calories['weight'] . "";


					$meal['calories'] = $calories;
					$meal['carbohydrates'] = $Carbohydrates;
					$meal['fat'] = $Fat;
					$meal['protein'] = $Protein;

					$terms = get_the_terms( $value, 'badges' );
					$badges = [];

					foreach($terms as $badge){
						$badges[] = $badge->name;
					}
					$meal['badges'] = $badges;
					$meal['ingredients'] = implode(", ",$ingredients);

					$json_data[] = $meal;

				}

				//nocache_headers();

				$result = new WP_REST_Response($json_data, 200);
				$result->set_headers(array('Cache-Control' => 'no-cache'));

				return $result;

			}
		}
	}

	public function like_meal(){
		$user_id = $_GET['user_id'];
		$id = $_GET['meal_id'];
		if($user_id){
			$user = get_userdata($user_id);

			$user_fav_post = get_user_meta($user_id,"aw_fav_post",true);

			if($user_fav_post){
				add_post_meta($user_fav_post,'aw_fav_meal',$id);
			}else{

				$post = wp_insert_post(['post_type' => "user_favourite",'post_title' => "#$user_id - $user->display_name",'post_status' => "publish"]);
				update_user_meta($user_id,"aw_fav_post",$post);
				add_post_meta($post,'aw_fav_meal',$id);

			}
		}
	}

	public function unlike_meal(){
		$user_id = $_GET['user_id'];
		$id = $_GET['meal_id'];
		if($user_id){
			$user_fav_post = get_user_meta($user_id,"aw_fav_post",true);

			if($user_fav_post){
				delete_post_meta($user_fav_post,'aw_fav_meal',$id);
			}
		}
	}

	public function user_login($data){
		$username = $data['username'];
		$password = $data["password"];

		$user = wp_authenticate($username, $password);
		$response = ['status' => 'unknown','data' => [],'message' => ''];
		if(!is_wp_error($user)) {
			$first_name = $user->first_name;

			$token = openssl_random_pseudo_bytes(16);

			$token = bin2hex($token) . $user->ID;

			update_user_meta($user->ID,'secure_token',$token);

			$subscription_obj = new Subscription();


			$response = ['status' => 'success','data' => [
				'ID' => $user->ID,
				'fullName' => $user->display_name
				,'hash' => $token],'profile_data' => $subscription_obj->user_profile(['user_id' => $user->ID])];
			} else {
				$response = ['status' => 'error','data' => [],'message' => $user->get_error_code()];
			}

			return $response;
		}


		public function dislike_meal($data){
			$sms = $data['aw_sms'];
			$user_id = $data['user_id'];
			$id = $data['id'];

			$user = get_userdata($user_id);

			$user_fav_post = get_user_meta($user_id,"aw_fav_post",true);

			if($user_fav_post){
				add_post_meta($user_fav_post,'aw_dislike_meal',['meal' => $id,'sms' => $sms]);
			}else{

				$post = wp_insert_post(['post_type' => "user_favourite",'post_title' => "#$user_id - $user->display_name",'post_status' => "publish"]);
				update_user_meta($user_id,"aw_fav_post",$post);
				add_post_meta($post,'aw_dislike_meal',['meal' => $id,'sms' => $sms]);

			}
			return true;
		}



		public function get_products(){

			$products = wc_get_products(['status' => "publish",'return' => 'objects','visibility' => 'catalog']);


			$aw_week_cutoff = get_option('aw_week_cutoff')?get_option('aw_week_cutoff'):"Wednesday";
			$current_time = current_time('timestamp');


			$cuttofftime_start = new Datetime(date("Y-m-d H:i:s",$current_time));
			$cuttofftime_start->modify("Last {$aw_week_cutoff} + 1 days");
			$cuttofftime_start->setTime(00,00,00);

			$sunday = new Datetime(date("Y-m-d H:i:s",$current_time));
			$sunday->modify("Last {$aw_week_cutoff}");
			$sunday->modify("Next Sunday");
			$sunday->setTime(23, 59,59);

			if(date('l',$current_time) == $aw_week_cutoff){

				$cuttofftime_start = new Datetime(date("Y-m-d H:i:s",$current_time));
				$cuttofftime_start->modify("+ 1 days");
				$cuttofftime_start->setTime(00,00,00);

				$sunday = new Datetime(date("Y-m-d H:i:s",$current_time));
				$sunday->modify("Next Sunday");
				$sunday->setTime(23, 59,59);

			}

			$today = new DateTime(date("Y-m-d H:i:s",$current_time));

			$delivery_date = new DateTime(date("Y-m-d H:i:s",$current_time));
			$delivery_date->modify("Next Sunday");

			$aw_products = [];

			foreach ($products as $key => $product) {
				$today = new DateTime(date("Y-m-d H:i:s",$current_time));
				$product_id = $product->get_id();
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), array( 100, 100)  );

				$p_name = get_the_title($product_id);

				$price = $product->get_price();

				$variations = [];

				if($product->is_type('variable')){

					$price = $product->get_variation_price();
					$variations_ids = $product->get_children();


					foreach ($variations_ids as $key => $value) {
						$v = wc_get_product($value);
						$v_name = str_replace($p_name . " - ","",$v->get_name());
						$variations[] = ['id' => $value,
						'title' => $v_name,
						'price' => $v->get_price(),
						'product_id' => $product_id,
					];
				}

			}


			$aw_products[] = ['id' => $product_id,'title'=> $p_name , 'delivery_date' =>$delivery_date->format("m/d/Y") ,'imageUrl' => $image[0]?$image[0]:wc_placeholder_img_src(),'price' => $price,'variations' => $variations];
			// where we need to add in array

		}
		return $aw_products;

	}


}


// add_filter('get_avatar_data', 'ow_change_avatar', 100, 2);
// function ow_change_avatar($args, $user_data) {
//     if(is_object($user_data)){
//         $user_id = $user_data->user_id;
//     } else{
//         $user_id = $user_data;
//     }
//     if($user_id){
//         $author_pic = get_user_meta($user_id, 'author_pic', true);
//         if($author_pic){
//             $args['url'] = $author_pic;
//         } else {
//             $args['url'] = 'http://u1s.ee6.myftpupload.com/wp-content/uploads/2021/11/Dish-8-2.png';
//         }
//     } else {
//         $args['url'] = 'guast user img url';
//     }
//     return $args;
// }
