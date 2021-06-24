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
    if(!empty($response['data'])){
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

  function crm_create_user($nome, $cognome, $email, $titolo = "Professionista"){
    $url = 'cms/v1/utenti';

    // Check if student
    if($this->check_student_mail($email)){
      $titolo = "Studente";
    }

    $data = [
      "account" => "subscriber",
      "nome" => $nome,
      "cognome" => $cognome,
      "email" =>[
        "field" => $email,
        "show" =>false
      ],
      "telefono" =>[],
      "indirizzo" =>[],
      "titolo" =>[
        "field" => $titolo,
        "show" => false
      ],
      "presso" => [],
      "showCommenti" => true,
      "showAppunti" => false
    ];
    $response = $this->admin_curl($url, $data);
    if(!empty($response['data'])){
      $this->user_id = $response['data']['id'];
      setcookie('lexupid', $this->user_id, time()+3600*24);
      return [
        "success" => true,
        "id" => $this->user_id
      ];
    } else {
      return [
        "success" => false,
        "errors" => $response["errors"]
      ];
    }
  }

  function crm_create_subscription(){
    // TODO
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
    var_dump($url);
    $response = $this->admin_curl($url, NULL, "GET");
    return $response;
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


  private function admin_curl($url, $body = NULL, $method = "POST", $token = false){
    if(empty($this->admin_token)){
      echo("No admin token given!");
      die();
    }
    return $this->curl($url, $body, $method, $this->admin_token);
  }

  private function curl($url, $body = NULL, $method = "POST", $token = false){
    $url = $this->base_url . $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    if($body){
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if($token){
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' . $token));
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $server_output = curl_exec($ch);
    $response = json_decode($server_output, true);
    return $response;
  }

}
