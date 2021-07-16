<?php

namespace MiraiLexup;

class LexupApi {

  private $base_url = 'https://api.staging.lexup.net/';
  private $admin_token;
  private $user_token;
  private $user_id;

  function __construct($ambient = "test", $admin_token = NULL){
    if(isset($_COOKIE['lexuptk1']) && strlen($_COOKIE['lexuptk1']) > 10){
      $this->user_token = strrev($_COOKIE['lexuptk1']);
    }
    if(isset($_COOKIE['lexupid'])){
      $this->user_id = intval($_COOKIE['lexupid']);
    }
    if(!empty($admin_token)){
      $this->admin_token = $admin_token;
    }
    if($ambient == "production"){
      $this->base_url = 'https://api.production.lexup.net/';
    }
  }

  function login_user($email, $password){
    $url = 'v1/login';
    $data = ["login" => $email, "password" => $password];
    $response = $this->curl($url, $data);
    if(!empty($response["data"])){
      $this->user_token = $response["data"]["token"];
      if(!$this->user_token){ return false; }
      setcookie('lexuptk1', strrev($this->user_token), time()+3600*24);
      $this->user_id = $response["data"]["id"];
      if(!$this->user_id){ return false; }
      setcookie('lexupid', intval($this->user_id), time()+3600*24);
      return [
        "success" => true,
        "tipo" => $response["data"]["tipo"],
        "id" => $response["data"]["id"],
        "nome" => $response["data"]["nome"],
        "cognome" => $response["data"]["cognome"],
        "avatar" => $response["data"]["avatar"],
        "token" => $this->user_token
      ];
    }
    if(!empty($response['errors'])){
      return [
        "success" => false,
        "errors" => $response["errors"]
      ];
    }
    return false;
  }

  function register_user($nome, $cognome, $email, $password, $repassword, $free_trial = false){
    $url = 'v1/login/registration';
    $data = [
      "nome" => $nome,
      "cognome" => $cognome,
      "email" => $email,
      "password" => $password,
      "repassword" => $repassword,
      "free_trial" => $free_trial
    ];
    $response = $this->curl($url, $data);
    if(isset($response['data'])){
      return [
        'success' => true
      ];
    }
    if(!empty($response['errors'])){
      return [
        "success" => false,
        "errors" => $response["errors"]
      ];
    }
    return false;
  }

  function cms_create_user($nome, $cognome, $email, $telefono = NULL, $titolo = "Professionista"){
    $url = 'cms/v1/utenti';

    // Check if student
    if($this->check_student_mail($email)){
      $titolo = "Studente";
    }

    $telefono_data = [];
    if(false && !empty($telefono)){ // Currently bugged in API
      $telefono_data = [
        "field" => (string)$telefono,
        "show" => false
      ];
    }

    $data = [
      "account" => "subscriber",
      "nome" => $nome,
      "cognome" => $cognome,
      "email" =>[
        "field" => $email,
        "show" =>false
      ],
      "telefono" => $telefono_data,
      "indirizzo" =>[],
      "titolo" =>[
        "field" => $titolo,
        "show" => false
      ],
      "presso" => [],
      "showCommenti" => true,
      "showAppunti" => false
    ];

    echo(json_encode($data, JSON_PRETTY_PRINT));
    $response = $this->admin_curl($url, $data);
    var_dump($response);
    if(!empty($response['data'])){
      $this->user_id = $response['data']['id'];
      setcookie('lexupid', $this->user_id, time()+3600*24);
      return [
        "success" => true,
        "id" => $this->user_id,
        "data" => $response['data']
      ];
    } else {
      return [
        "success" => false,
        "errors" => $response["errors"]
      ];
    }
  }

  function cms_renew_subscription($user_id, $payment_id, $original_payment_id = null, $months = 1){
    $url = "cms/v1/subscription/full";

    $data = [
      "app_user_id" => 1,
      "type" => "LEXUP_WOOCOMMERCE",
      "starting_date" => NULL,
      "ending_date" => NULL,
      "payment_provider_name"  =>"WOOCOMMERCE",
      "payment_provider_unique_id" => (string) $payment_id, // id univoco che identifica il pagamento nel database di woocommerce
      "original_payment_id" => null, // nel caso di pagamento ricorrenti l'id originale del primo abbonamento
      "payment_provider_transaction_id" => (string) $payment_id, // In alcuni casi i sistemi di pagamento oltre ad avere un id di pagamento hanno un id di transazione collegato al trasferimento monetario
      "receipt" => "Receipt",
      "payment_date" => null
    ];

    $user_data = $this->get_user_info_by_id($user_id);
    if(!empty($user_data)){
      // The user is valid

      $starting_time;
      if(!empty($user_data['abbonamentoDataFine']) && strtotime($user_data['abbonamentoDataFine']) >= time()){
        $starting_time = strtotime($user_data['abbonamentoDataFine']);
      } else {
        $starting_time = time();
      }
      $starting_date = date('Y-m-d', $starting_time);
      $end_time = strtotime('+'.$months.' months', $starting_time);
      $ending_date = date('Y-m-d', $end_time);

      $data["app_user_id"] = $user_data["appUserId"];
      $data["starting_date"] = $starting_date;
      $data["ending_date"] = $ending_date;

      $response = $this->admin_curl($url, $data);

      if(!empty($response["data"]) && $response["data"]["success"]){
        // Success!
        return $response["data"];
      } elseif (!empty($response["errors"])) {
        $response["success"] = false;
        return $response;
      } elseif (!empty($response["exception"])) {
        return [
          "success" => false,
          "message" => $response["message"],
          "errors" => [
            "payment_provider_unique_id" => "There is a good chance this is not unique"
          ]
        ];
      }
    }

    return [
      "success" => false,
      "errors" => [
        "user_id" => "Invalid user id provided"
      ]
    ];

  }

  function check_student_mail($email){
    $url = 'v1/privileged/email/domain/verify';
    $domain = substr($email, strpos($email, '@') + 1);
    $data = ["email_domain" => $domain];
    $response = $this->curl($url, $data);
    if(!empty($response["data"]) && $response["data"]["user_type"] == "STUDENT"){
      return true;
    }
    return false;
  }

  function get_user_info_by_id($id){
    $url = 'cms/v1/utenti/' . intval($id);
    $response = $this->admin_curl($url, NULL, "GET");
    if(!empty($response["data"])){
      return $response["data"];
    }
    return false;
  }

  function get_last_created_user_id(){
    if(!empty($this->user_id)){
      return $this->user_id;
    }
    return false;
  }

  function get_logged_user($tk = NULL){
    $url = 'v1/utente';
    if(!$tk){
      $tk = $this->user_token;
    }
    if(empty($tk)){
      return false;
    }
    $response = $this->curl($url, NULL, "GET", $tk);
    if(!empty($response["data"])){
      return $response["data"];
    }
    return false;
  }

  function check_mail_available($email){
    $url = 'v1/login/registration';
    $data = [
      "email" => $email
    ];
    $response = $this->curl($url, $data);
    if(!empty($response['errors'])){
      if(!empty($response['errors']['email']) && strpos(implode(" ", $response['errors']['email']), 'taken') !== false){
        return false;
      }
      return true;
    }
    return false;
  }

  private function admin_curl($url, $body = NULL, $method = "POST", $token = false){
    if(empty($this->admin_token)){
      echo("No admin token given!");
      die();
    }
    return $this->curl($url, $body, $method, $this->admin_token);
  }

  private function curl($url, $body = NULL, $method = "POST", $token = false){
    $url = $this->base_url . $url;
    $headers = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    if($body){
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
      $headers[] = 'Content-Type: application/json';
    }
    if($token){
      $headers[] = 'Authorization: ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $server_output = curl_exec($ch);
    $response = json_decode($server_output, true);
    return $response;
  }

}
