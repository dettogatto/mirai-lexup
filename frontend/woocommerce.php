<?php

// TODO on order creation add meta to order with user ID or all needed data


add_action('woocommerce_payment_complete', function($order_id) {
  $order = wc_get_order( $order_id );
  $parent_id = $order->get_parent_id();
  $items = $order->get_items();

  $options = get_option( 'mirai_lexup_options' );
  $lexup = new LexupApi($options["api_ambient"], $options["admin_token"]);

  $months = 1;
  $titolo = "professionista";

  foreach($items as $item){
    $name = strtolower($item->get_name());
    if(strpos($name, 'annuale') !== false){
      $months = 12;
    }
    if(strpos($name, 'student') !== false){
      $titolo = 'studente';
    }
  }

  $user_id = $lexup->get_last_created_user_id();
  if(!$user_id){
    // TODO log error somewhere
    $order->add_order_note("Could not find Lexup User ID. Subscription not active on Lexup.");
    return false;
  }

  $response = $lexup->cms_renew_subscription($user_id, $order_id, $parent_id, $months);

  if($response["success"]){
    update_post_meta($order_id, 'lexup_user_id', $user_id);
    // TODO Activecampaing
  } else {
    // TODO log error somewhere
    $order->add_order_note("Could activate subscription on Lexup.");
    return false;
  }

});


// woocommerce_subscription_payment_complete
// Triggered when a payment is made on a subscription (new or renewal).

// add_action('woocommerce_subscription_payment_complete', function($subscription) {
//   //check if meta exists/is not true
//   if (!get_post_meta($subscription->id, 'mirai_first_payment_done', true)) {
//     //update meta to bool(true)
//     update_post_meta($subscription->id, 'mirai_first_payment_done', true);
//     //run your function
//     // Active Campaign stuff
//   }
// });


// woocommerce_subscription_renewal_payment_complete
// Triggered when a renewal payment is made on a subscription.


// woocommerce_subscription_renewal_payment_failed
// Triggered when a renewal payment fails for a subscription.

// Check if there is valid lexup account before order confirmation
// If not create one
add_action('woocommerce_after_checkout_validation', function($posted){
  // Check values in $_POST
  $email = $_POST['billing_email'];
  $nome = $_POST['billing_first_name'];
  $cognome = $_POST['billing_last_name'];
  $options = get_option( 'mirai_lexup_options' );
  $lexup = new LexupApi($options["api_ambient"], $options["admin_token"]);

  $last_user_id = $lexup->get_last_created_user_id();
  $last_user = $lexup->get_user_info_by_id($last_user_id);

  if($last_user && strtolower($last_user["email"]["field"]) == strtolower($email)){
    // The user is already present and logged in
  } else if($lexup->check_mail_available($email)){
    // The user must be created
    $response = $lexup->cms_create_user($nome, $cognome, $email);
    if(!$response['success']){
      wc_add_notice( "Could not create user on Lexup", 'error');
    }
  } else {
    // User exists but is not logged in
    wc_add_notice( "User not logged in", 'error');
  }

});
