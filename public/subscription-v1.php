<?php

class Subscription{

  public function __construct(){

  }

  /*
  *Subscription Api end points
  */

  public function rest_api_callback(){

    register_rest_route( 'meal-prep/v1', '/user-subscriptions', array(
      'methods' => "POST",
      'callback' => array($this,'user_subscriptions'),
    ) );

    register_rest_route( 'meal-prep/v1', '/pause-subscription', array(
      'methods' => "POST",
      'callback' => array($this,'pause_subscription'),
    ) );

    register_rest_route( 'meal-prep/v1', '/add-note', array(
      'methods' => "POST",
      'callback' => array($this,'aw_add_note'),
    ) );

    register_rest_route( 'meal-prep/v1', '/user-profile', array(
      'methods' => "POST",
      'callback' => array($this,'user_profile'),
    ) );

    register_rest_route( 'meal-prep/v1', '/change-password', array(
      'methods' => "POST",
      'callback' => array($this,'change_password'),
    ) );

    register_rest_route( 'meal-prep/v1', '/signup', array(
      'methods' => "POST",
      'callback' => array($this,'signup'),
    ) );

    register_rest_route( 'meal-prep/v1', '/forget-password', array(
      'methods' => "POST",
      'callback' => array($this,'forget_password'),
    ) );

    register_rest_route( 'meal-prep/v1', '/reactivate-subscription', array(
      'methods' => "POST",
      'callback' => array($this,'reactivate_subscription'),
    ) );

    register_rest_route( 'meal-prep/v1', '/un-pause-subscription', array(
      'methods' => "POST",
      'callback' => array($this,'un_pause'),
    ) );

    register_rest_route( 'meal-prep/v1', '/verify-code', array(
      'methods' => "POST",
      'callback' => array($this,'verify_code'),
    ) );

    register_rest_route( 'meal-prep/v1', '/forget-change-password', array(
      'methods' => "POST",
      'callback' => array($this,'aw_change_password'),
    ) );

    register_rest_route( 'meal-prep/v1', '/add-delivery-note', array(
      'methods' => "POST",
      'callback' => array($this,'aw_add_delivery_note'),
    ) );


    register_rest_route( 'meal-prep/v1', '/add-allergies', array(
      'methods' => "POST",
      'callback' => array($this,'aw_add_alllergy'),
    ) );


    register_rest_route( 'meal-prep/v1', '/app-settings', array(
      'methods' => "POST",
      'callback' => array($this,'aw_app_settings'),
    ) );

    register_rest_route( 'meal-prep/v1', '/verify-token', array(
      'methods' => "POST",
      'callback' => array($this,'aw_verify_token'),
    ) );

    register_rest_route( 'meal-prep/v1', '/change-profile-image', array(
      'methods' => "POST",
      'callback' => array($this,'upload_profile_image'),
    ) );



  }

  /*
  *Get user Subscriptions
  */

  public function user_subscriptions($data){
    ob_start();
    $current_time = current_time('timestamp');
    $user_id = $data['user_id'];
    $subscriptions = wcs_get_users_subscriptions($user_id);
    $subscriptions_s = [];
    foreach ($subscriptions as $key => $subscription) {

      $aw_pause_date = get_post_meta($subscription->get_id(),'aw_pause_date',true);
      $sub_next_delivery = get_post_meta($subscription->get_id(),'aw_next_delivery',true);

      $calculated_next_date = '';

      $aw_status_date = false;

      $paused = false;
      $status = "";

      if($subscription->get_status() == "active"){
        $status = "Active";
      }elseif($subscription->get_status() == "on-hold"){
        $status = "Inactive";
      }else{
        $status = $subscription->get_status();
      }

      if($subscription->get_status() == "active" || $subscription->get_status() == "on-hold"){

      }else{
        continue;
      }


      $aw_last_payment = $subscription->get_date('date_paid');

      $aw_next_payment = $subscription->get_date('next_payment');

      if(!$aw_next_payment){
        $aw_next_payment = $subscription->get_date('end');
      }

      if($subscription->has_status("active")){

        if($sub_next_delivery && $sub_next_delivery != "false" && $sub_next_delivery > date("Y-m-d H:i:s",$current_time)){
          echo 2222;
          $calculated_next_dateobj = new DateTime($sub_next_delivery);

          $calculated_next_date = $calculated_next_dateobj->format("m/d/Y");


          $aw_status_date = $sub_next_delivery;

          if($aw_next_payment){
            $aw_next_payment_ob = new Datetime($aw_next_payment);

            $today = new DateTime(date("Y-m-d H:i:s",$current_time));

            $dif =   $aw_next_payment_ob->diff($today);

            if($dif->days > 7){
              $status = "Paused";
            }else{
              $status = "Active";
            }




          }

        }else{
          echo 123;
          $next_delivery = new Datetime(date("Y-m-d H:i:s",$current_time));
          $next_delivery->modify("Next Sunday");

          $calculated_next_date = $next_delivery->format("m/d/Y");
          if($aw_next_payment){
            echo 123;
            $aw_last_payment = new Datetime($aw_last_payment);
            $aw_last_payment->modify("Next Sunday");
            $next_obj = new DateTime($aw_next_payment);
            $today = new Datetime(date("Y-m-d H:i:s",$current_time));

            $diff = $next_obj->diff($today);

            if( $diff->days > 7 && $next_obj->format("Y-m-d H:i:s") >= date("Y-m-d H:i:s",$current_time)){
              $next_obj->modify("Next Sunday");

              $aw_status_date =  $next_obj->format("Y-m-d H:i:s");
              $calculated_next_date = $next_obj->format("m/d/Y");

            }

          }

        }



      }




      $single = [];
      $single['diff'] = $dif->days;

      $single['ID'] = $subscription->get_id();
      $single['status'] = $status;
      $single['next_delivery'] = $calculated_next_date;

      $actions = wcs_get_all_user_actions_for_subscription( $subscription, $user_id );

      $single['actions'] = $actions;

      $name = [];
      $aw_product_id = 0;

      $products=[];




      foreach ( $subscription->get_items() as $item_id => $item ) {
        $product_id = $item->get_product_id();
        $products[]=$product_id;
        $variation_id = $item->get_variation_id();
        if($aw_product_id == 0){
          $aw_product_id = $product_id;
        }

        $product = $item->get_product();
        $product_name = $item->get_name();

        $name[] = $product_name;

        $quantity = $item->get_quantity();
        $subtotal = $item->get_subtotal();
        $total = $item->get_total();
      }

      $single['products']=$products;
      $single['next_delivery11'] = $sub_next_delivery;

      $image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), array( 100, 100)  );

      $single['title'] = implode("\n",$name);
      $single['imageUrl'] = $image[0]?$image[0]:wc_placeholder_img_src();
      $single['end_date'] = $subscription->get_date('end');

      $single['payment_status'] = $this->get_payment_status($subscription->get_id());

      $subscriptions_s[] = $single;
    }
    ob_clean();


    return $subscriptions_s;
  }

  /*
  *Pause Subscription
  */

  public function pause_subscription($data){

    $type = $_POST['type'];

    if($type == "indf"){
      return $this->pause_indefinitly($_POST);
    }else{
      return $this->pause_subscription_for_period($_POST);
    }

  }

  public function pause_subscription_for_period($post){
    $return_note = '';
    $charged = $post['aw_charged'];
    $reason = $post['reason'];
    $id = $post['id'];
    $user_id = $post['user_id'];
    $next_delivery_user = $post['next_delivery'];
    $current_time = current_time('timestamp');
    $subscription = wcs_get_subscription($id);

    $note = __("Subscriptions has been paused due to '{$reason}'");
    update_post_meta($id,'aw_pause_date',date("Y-m-d",	$current_time));


    $return_note = $note . ". Your next delivery date is {$next_delivery_user}.You will recieve current week meals on this sunday.";
    
    update_post_meta($id,'aw_next_delivery',$next_delivery_user . " " . date("H:i:s",$current_time));

    $next_payment = new DateTime($next_delivery_user);

    $next_payment->modify("-4 days");
    $dates_to_update = array('next_payment' => $next_payment->format("Y-m-d H:i:s"));

    $subscription->update_dates($dates_to_update);



    return $return_note;
  }

  public function pause_indefinitly($post){
    $return_note = '';
    $charged = $post['aw_charged'];
    $reason = $post['reason'];
    $id = $post['id'];
    $user_id = $post['user_id'];
    $current_time = current_time('timestamp');

    $note = __("Subscriptions has been paused due to '{$reason}' for Indefinitely time");

    $subscription = wcs_get_subscription($id);

    delete_post_meta($id,'aw_pause_date');

    if(isset($post['want_meal'])){

      $want_meal = $post['want_meal'];

      if(	$charged  == "true"){
        //charged

        if($want_meal == "no"){

          //process refund, don't want meals

          $this->handle_refund($subscription);
          $subscription->update_status('on-hold');
          $return_note = $note . ". Your amount is refunded.";
          $note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8', false);
          $this->add_hubspot_note($user_id,$note);
          $subscription->add_order_note( $note );

        }else{
          // "yes want meal";

          $subscription->update_status('on-hold');
          $return_note = $note . ". You will receive your meals on sunday.";
          $note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8', false);
          $this->add_hubspot_note($user_id,$note);
          $subscription->add_order_note( $note );

        }

      }else{
        // Not charged
        if($want_meal == "no"){
          // "don't want meal";
          $subscription->update_status('on-hold');
          $return_note = $note;
          $note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8', false);
          $this->add_hubspot_note($user_id,$note);
          $subscription->add_order_note( $note );

        }else{
          //must be renewed, want meal

          $return_note = $this->handle_renewal($subscription,$note,$user_id);

        }

      }

    }else{


      $subscription->update_status('on-hold');
      $return_note = $note;
      $note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8', false);
      $this->add_hubspot_note($user_id,$note);
      $subscription->add_order_note( $note );


    }


    return $return_note;
  }


  //handle renew

  public function handle_renewal($subscription,$note,$user_id){
    $return_note = '';

    WCS_Admin_Meta_Boxes::process_renewal_action_request($subscription);

    $subscription = wcs_get_subscription($subscription->get_id());
    if($subscription->get_status() == "active"){

      $subscription->update_status('on-hold');
      $return_note = $note . ". We are processing your renewal, after payment capture you will receive your meals on sunday.";
      $note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8', false);
      $this->add_hubspot_note($user_id,$note);
      $subscription->add_order_note( $note );
    }else{
      $return_note = "We are unable to process your renewal, make sure you have active card attached or contact us for details.";
    }
    return $return_note;

  }

  public function handle_renewal_for_pause($subscription,$note,$user_id){
    $return_note = '';
    ob_start();
    WCS_Admin_Meta_Boxes::process_renewal_action_request($subscription);
    //sleep(3);
    $subscription = wcs_get_subscription($subscription->get_id());
    if($subscription->get_status() == "active"){

      $return_note = $note . ". We are processing your renewal, after payment capture you will receive your meals on sunday.";

      $note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8', false);
      $this->add_hubspot_note($user_id,$note);
      $subscription->add_order_note( $note );
    }else{
      $return_note = "We are unable to process your renewal, make sure you have active card attached or contact us for details.";
    }
    ob_clean();
    return $return_note;
  }

  public function handle_refund($subscription){
    $relared_orders_ids_array = $subscription->get_related_orders();
    $i = 0;
    foreach ($relared_orders_ids_array as $key => $value) {
      $order = wc_get_order($value);
      if($order->get_status() == "processing" && $i == 0){
        //	echo "process" . $value . "<br>";
        $this->refund($order);
        break;
      }
      $i++;
    }
  }

  public function refund($order){
    $order_id = $order->get_id();
    $max_refund = wc_format_decimal($order->get_total() - $order->get_total_refunded());
    if (!$max_refund) {
      return;
    }
    $refund = wc_create_refund(array('amount' => $max_refund, 'reason' => __('Order Fully Refunded, customer pause subscription and don\'t want meals on sunday.', 'woocommerce'), 'order_id' => $order_id, 'line_items' => array()));
    wc_delete_shop_order_transients($order_id);

  }

  public function add_hubspot_note($user_id,$note){
    ob_start();
    $hub_user_id = get_user_meta($user_id,'hubwoo_user_vid',true);
    $aw_hubspotapikey = get_option('aw_hubspotapikey');

    if(!$user_id || !$aw_hubspotapikey){
      return;
    }

    $headers = array(
      "Content-Type:application/json",
    );

    $time = round(microtime(1) * 1000);

    $fields = '{
      "engagement": {
        "active": true,
        "type": "NOTE",
        "timestamp": '.$time.'
      },
      "associations": {
        "contactIds": ['.$hub_user_id.']
      },
      "metadata": {
        "body": "'.$note.'"
      }
    }';




    $url = "https://api.hubapi.com/engagements/v1/engagements?hapikey={$aw_hubspotapikey}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

    $response = curl_exec($ch);
    curl_close($ch);
    ob_clean();

  }

  /*
  *Check payment status and cutoff time
  */

  public function get_payment_status($id){

    $result = ['show_options' => false];

    $subscription  = wcs_get_subscription($id);

    $aw_week_cutoff = get_option('aw_week_cutoff')?get_option('aw_week_cutoff'):"Wednesday";
    $current_time = current_time('timestamp');
    $cuttofftime = new Datetime(date("Y-m-d H:i:s",  $current_time));
    $cuttofftime->modify("Next {$aw_week_cutoff}");
    $cuttofftime->setTime(23,59,59);

    $cuttofftime_last = new Datetime(date("Y-m-d H:i:s",  $current_time));
    $cuttofftime_last->modify("Last {$aw_week_cutoff} +1 days");
    $cuttofftime_last->setTime(00,00,00);

    $monday = new Datetime(date("Y-m-d H:i:s",  $current_time));
    $monday->modify("Next {$aw_week_cutoff}");
    $monday->modify('Last Monday');
    $monday->setTime(00,00,00);


    $today = new Datetime(date("Y-m-d H:i:s",  $current_time));

    if(date('l',$current_time) == $aw_week_cutoff){
      $cuttofftime = new Datetime(date("Y-m-d H:i:s",  $current_time));
      $cuttofftime->setTime(23,59,59);


      $monday = new Datetime(date("Y-m-d H:i:s",  $current_time));
      $monday->modify('Last Monday');
      $monday->setTime(00,00,00);
    }

    $last_payment =$subscription->get_date('last_payment');

    $last_payment = new Datetime($last_payment);

    $charged = false;

    if($last_payment >= $cuttofftime_last && $last_payment <= $cuttofftime){
      $charged = true;
    }



    if($today >= $monday && $today <= $cuttofftime){
      $result['show_options'] = true;

    }else{
      if($charged){
        $result['show_options'] = true;

      }
    }

    $result['charged'] = $charged;
    return $result;

  }

  /*
  *Add note
  */

  public function aw_add_note(){

    $note = $_POST['note'];
    $sub = $_POST['aw_subscription_id'];
    $subscription = wcs_get_subscription($sub);
    $orders = $subscription->get_related_orders();

    foreach ($orders as $key => $value) {
      $order = wc_get_order($value);

      if(isset($_POST['note_type'])){
        //	$order->add_order_note($note);

        $pr_note = $order->get_customer_note();
        $pr_note .=   "<br>" . $note;
        $order->set_customer_note($pr_note);
        $order->save();

      }else{

        $delivery_note = get_post_meta($value,'delivery_note',true);

        if ($delivery_note) {
          $delivery_note .= "<br>" . $note;
          update_post_meta($value,'delivery_note',$delivery_note);

        }else{
          update_post_meta($value,'delivery_note',$note);
        }

      }

    }


    return true;
  }


  /*
  *Add note
  */

  public function aw_add_delivery_note($data){

    $note = $_POST['note'];

    $user_id = $data['user_id'];
    $subscriptions = wcs_get_users_subscriptions($user_id);

    foreach ($subscriptions as $key => $subscription) {

      $sub = $subscription->get_id();
      $subscription = wcs_get_subscription($sub);
      $orders = $subscription->get_related_orders();

      foreach ($orders as $key => $value) {
        $order = wc_get_order($value);

        // $delivery_note = get_post_meta($value,'delivery_note',true);

        // if ($delivery_note) {
        //   $delivery_note .= "\n," . $note;
        //   update_post_meta($value,'delivery_note',$delivery_note);

        // }else{
        update_user_meta($user_id,'aw_delivery_note',$note);
        update_post_meta($value,'delivery_note',$note);
        //  }

      }
    }

    return true;
  }

  /*
  *add allergy
  */

  public function aw_add_alllergy($data){
    $note = $data['allergy'];
    $dislikes = $data['dislikes'];

    $user_id = $data['user_id'];
    $subscriptions = wcs_get_users_subscriptions($user_id);

    foreach ($subscriptions as $key => $subscription) {

      //$sub = $subscription->get_id();
      //$subscription = wcs_get_subscription($sub);
      $orders = $subscription->get_related_orders();

      foreach ($orders as $key => $value) {

        // var_dump($value);
        //   $order = wc_get_order($value);

        $order = get_post($value);

        $old_note = get_user_meta($user_id,'aw_allergy_note',true);
        $old_dislikes =  get_user_meta($user_id,'aw_dislikes',true);

        $pr_note = $order->post_excerpt;
        $pr_note = str_replace("\n,".$old_note,'',$pr_note);
        $pr_note = str_replace("\n,".$old_dislikes,'',$pr_note);
        $pr_note .=   "\n," . $note;
        $pr_note .=   "\n," . $dislikes;

        $the_post = array(
          'ID'           => $value,//the ID of the Post
          'post_excerpt' => $pr_note,
        );
        wp_update_post( $the_post );




      }
    }

    update_user_meta($user_id,'aw_allergy_note',$note);
    update_user_meta($user_id,'aw_dislikes',$dislikes);

    return true;
  }

  /*
  *User Profile
  */

  public function user_profile($data){
    $user_id = $data['user_id'];
    $user_info = get_userdata($user_id);
    $first_name = get_user_meta($user_id,'billing_first_name',true);
    $last_name = get_user_meta($user_id,'billing_last_name',true);
    $user_email = $user_info->user_email;
    $image = get_user_meta($user_id,'user_image_url',true);
    if(!$image){
      $image = get_avatar_url( $user_id );
    }

    $user_img = get_user_meta($user_id,'aw_user_profile_image',true);

    if($user_img){
      $image = site_url('wp-content/uploads/userimages/'.$user_id.'.jpg');
    }

    /*
    *Payment Methods
    */

    $saved_methods = wc_get_customer_saved_methods_list( $user_id );

    /*
    *Address Info
    */

    $billing = [
      'address_1' => get_user_meta($user_id,'billing_address_1',true),
      'address_2' => get_user_meta($user_id,'billing_address_2',true),
      'city' => get_user_meta($user_id,'billing_city',true),
      'state' => get_user_meta($user_id,'billing_state',true),
      'postcode' => get_user_meta($user_id,'billing_postcode',true),
    ];

    $shipping = [
      'address_1' => get_user_meta($user_id,'shipping_address_1',true),
      'address_2' => get_user_meta($user_id,'shipping_address_2',true),
      'city' => get_user_meta($user_id,'shipping_city',true),
      'state' => get_user_meta($user_id,'shipping_state',true),
      'postcode' => get_user_meta($user_id,'shipping_postcode',true),
    ];

    $note = get_user_meta($user_id,'aw_allergy_note',true);
    $dislikes =  get_user_meta($user_id,'aw_dislikes',true);
    $current_time = current_time('timestamp');
    $next_delivery = new Datetime(date("Y-m-d H:i:s",$current_time));
    $next_delivery->modify("Next Sunday");

    $calculated_next_date = $next_delivery->format("m/d/Y");

    $data = [
      'next_delivery' => $calculated_next_date,
      'name' => $first_name ." " .$last_name,
      'image' => $image,
      'email' => $user_email,
      'allergies' => $note,
      'dislikes' => $dislikes,
      'payment_methods' => $saved_methods,
      'billing' => $billing,
      'shipping' => $shipping,
      'delivery_note' => get_user_meta($user_id,'aw_delivery_note',true)
    ];

    return $data;
  }

  /*
  *Change Password
  */

  public function  change_password($data){


    $username = $data['username'];
    $password = $data["password"];
    $new_password = $data["new_password"];

    $user = wp_authenticate($username, $password);
    $response = ['status' => 'unknown','data' => [],'message' => ''];
    if(!is_wp_error($user)) {

      if(empty($new_password)){
        $response = ['status' => 'error','message' => 'empty_new_password'];
      }else{

        wp_set_password($new_password,$user->ID);
        $response = ['status' => 'success'];


      }
    } else {
      $response = ['status' => 'error','data' => [],'message' => $user->get_error_code()];
    }

    return $response;
  }

  public function signup($data){

    $user_email = $data['email'];
    $user_name = $data['user_name'];
    $password = $data['password'];

    if(empty($data['email']) || empty($data['user_name']) || empty($data['password'])){
      return ['status' => 'error','sms' => 'Fields are empty'];


    }

    $response = ['status' => 'success','sms' => ''];
    $user_id = username_exists( $user_name );

    if (!$user_id && false == email_exists( $user_email ) ) {
      $user_id = wp_create_user( $user_name, $password, $user_email );
      if($user_id){
        $response = ['status' => 'success','id' => $user_id];

      }else{

        $response = ['status' => 'error','sms' => $user_id];

      }
    } else {
      $response = ['status' => 'error','sms' => 'Email or username already exists!'];

    }

    return $response;
  }

  public function forget_password($data){
    if(empty($data['email'])){
      return ['status' => 'error','sms' => 'Fields are empty'];

    }
    $user_id = email_exists( $data['email'] );
    if(!$user_id){
      return ['status' => 'error','sms' => 'Email not Exist!'];

    }

    $html = '';
    ob_start();

    $six_digit_random_number = random_int(100000, 999999);
    do_action( 'woocommerce_email_header', 'Forget Password', $data['email'] ); ?>

    <p>Your Otp is <b><?= $six_digit_random_number;?></b></p>
    <?php


    do_action( 'woocommerce_email_footer', $data['email'] );


    $html = ob_get_contents();
    ob_clean();

    $to = $data['email'];
    $subject = 'Forget Password';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    if(wp_mail( $to, $subject, $html, $headers )){
      update_user_meta($user_id,'aw_verify_code',$six_digit_random_number);
      return ['status' => 'success','sms' => 'Email Sent'];

    }else{
      return ['status' => 'error','sms' => 'Unable to send email!'];

    }

  }

  public function verify_code($data){

    if(empty($data['email']) || empty($data['code'])){
      return ['status' => 'error','sms' => 'Fields are empty'];

    }
    $user_id = email_exists( $data['email'] );
    if(!$user_id){
      return ['status' => 'error','sms' => 'Email not Exist!'];

    }

    $code = get_user_meta($user_id,'aw_verify_code',true);

    if($code == $data['code']){
      return ['status' => 'success','sms' => 'Verified'];
    }

    return ['status' => 'error','sms' => 'Code not verified!'];

  }

  public function aw_change_password($data){

    if(empty($data['email']) || empty($data['code'])){
      return ['status' => 'error','sms' => 'Fields are empty'];

    }
    $user_id = email_exists( $data['email'] );
    if(!$user_id){
      return ['status' => 'error','sms' => 'Email not Exist!'];

    }

    $code = get_user_meta($user_id,'aw_verify_code',true);

    if($code == $data['code']){

      $new_password = $data["new_password"];

      $response = ['status' => 'unknown','data' => [],'message' => ''];

      if(empty($new_password)){
        $response = ['status' => 'error','message' => 'empty_new_password'];
      }else{

        wp_set_password($new_password,$user_id);
        $response = ['status' => 'success','sms' => "Password Changed"];


      }

      return $response;
    }

    return ['status' => 'error','sms' => 'Code not verified!'];

  }

  public function reactivate_subscription($data){
    $id = $data['id'];
    $payment = $this->get_payment_status($id);
    $subscription  = wcs_get_subscription($id);
    $user_id = $data['user_id'];


    $current_time = current_time('timestamp');

    $note = "Subscription reactivated.";
    update_post_meta($id,'aw_pause_date','false');
    update_post_meta($id,'aw_next_delivery','false');

    $next_payment = new DateTime(date("Y-m-d H:i:s",$current_time));

    $next_payment->modify("+2 hours");
    $dates_to_update = array('next_payment' => $next_payment->format("Y-m-d H:i:s"));

    $subscription->update_status('active');
    $subscription->update_dates($dates_to_update);


    return $note;

  }

  public function un_pause($data){


    $id = $data['id'];
    $payment = $this->get_payment_status($id);
    $subscription  = wcs_get_subscription($id);
    $user_id = $data['user_id'];
    $current_time = current_time('timestamp');

    $note = "Subscription reactivated.";
    update_post_meta($id,'aw_pause_date','false');
    update_post_meta($id,'aw_next_delivery','false');

    $next_payment = new DateTime(date("Y-m-d H:i:s",$current_time));
    if($payment['show_options'] == false){
      $next_payment->modify("Next Tuesday");

    }else{
      $next_payment->modify("+2 hours");
    }

    $dates_to_update = array('next_payment' => $next_payment->format("Y-m-d H:i:s"));
    try{
      $subscription->update_dates($dates_to_update);
    }catch(Exception $error){
      var_dump($error);
    }


    return $note;

  }

  public function aw_app_settings($data){
    $maintanence = get_field("aw_maintenance_mode",'option');
    $update = get_field("aw_update_app",'option');
    return ['maintanence' => $maintanence,'update' => $update];
  }

  public function aw_verify_token($data){

    $user_id = $data['user_id'];
    $token = $data['aw_secure_hash'];

    $web_token = get_user_meta($user_id,'secure_token',true);

    if($web_token && $token == $web_token){
      return $this->user_profile(['user_id' => $user_id]);
    }else{
      return null;
    }
  }

  public function upload_profile_image($data){
    $path = ABSPATH . "wp-content/uploads/userimages";
    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }

    $image = $_POST['image'];
    $user_id = $data['user_id'];

    $file_name = $path . "/" . $user_id . '.jpg';

    $realImage = base64_decode($image);

    if(file_put_contents($file_name, $realImage)){
      update_user_meta($user_id,'aw_user_profile_image',true);
      return site_url('wp-content/uploads/userimages/'.$user_id.'.jpg');
    }else{
      return "error";
    }

  }

}
