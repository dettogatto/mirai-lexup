<?php

namespace MiraiLexup;

class ActivecampaignApi {
  private $api_url = "https://lexup.api-us1.com";
  private $api_key = "4873065a33d1bff16894bf0c17f788a9edd6205a5b88af598e3c7b8e42d9042220241e94";
  private $all_tags;
  private $all_lists;
  private $raw_fields;

  public function get_account_name(){
    $url = $this->api_url;
    $url = preg_replace('/https{0,1}:\/\//', "", $url);
    return ucfirst(explode('.', $url)[0]);
  }

  public function check_connection(){
    $request = $this->remote_post(
      $this->api_url."/api/3/contacts/-1",
      array('headers' => $this->get_headers(), "method" => "GET")
    );
    if($request){ return true; }
    return false;
  }

  public function add_tag_to_contact($contact_id, $tag_id){
    if(intval($tag_id) < 0){
      return false;
    }

    $body = array(
      "contactTag" => array(
        "contact" => $contact_id,
        "tag" => $tag_id
      )
    );

    $response = $this->remote_post(
      $this->api_url."/api/3/contactTags/",
      array(
        'headers' => $this->get_headers(true),
        'method' => 'POST',
        'body' => json_encode($body)
      )
    );
  }

  public function delete_ecom_order($id){
    $response = $this->remote_post(
      $this->api_url."/api/3/ecomOrders/".$id,
      array(
        'headers' => $this->get_headers(true),
        'method' => 'DELETE',
        'data_format' => 'body'
      )
    );
  }

  public function get_ecom_order_by_ext($external_id){
    $request = $this->remote_post(
      $this->api_url."/api/3/ecomOrders/?filters[externalid]=".$external_id,
      array('headers' => $this->get_headers(), "method" => "GET")
    );

    if( !$request ) {
      return false; // Bail early
    }

    $body  = json_decode($request, true);
    return $body["ecomOrders"][0];

  }

  public function get_contact_by_email($email){
    $request = $this->remote_post(
      $this->api_url."/api/3/contacts/?email=".$email,
      array('headers' => $this->get_headers(), "method" => "GET")
    );
    if( !$request ) {
      return false; // Bail early
    }
    $body = json_decode($request, true);
    if(count($body["contacts"]) > 0){
      return $body["contacts"][0];
    }
    return false;
  }

  public function sync_contact($data){
    $body = array( "contact" => $data );
    $request = $this->remote_post(
      $this->api_url."/api/3/contact/sync",
      array(
        'headers' => $this->get_headers(true),
        'method' => 'POST',
        'body' => json_encode($body)
      )
    );
    if( !$request ) {
      return false; // Bail early
    }
    $body  = json_decode($request, true);
    return $body["contact"];
  }

  public function super_sync_contact($contact, $tags = NULL, $fields = NULL, $lists = NULL){

    if(empty($contact["email"])){
      return false;
    }

    $contact = $this->sync_contact($contact);
    $contact_id = $contact['id'];

    if($contact_id){

      if(is_array($tags)){
        foreach($tags as $tag_id){
          $this->add_tag_to_contact($contact_id, $tag_id);
        }
      }

      if(is_array($fields)){
        foreach($fields as $field_id => $field_value){
          $this->update_contact_field($contact_id, $field_id, $field_value);
        }
      }

      if(is_array($lists)){
        foreach($lists as $list_id => $list_value){
          $this->subscribe_contact_to_list($contact_id, $list_value);
        }
      }

      return $contact;

    }

    return false;

  }

  public function get_contact_tags($contact_id){
    $request = $this->remote_post(
      $this->api_url."/api/3/contacts/".$contact_id."/contactTags",
      array('headers' => $this->get_headers(), 'method' => "GET")
    );
    if( !$request ) {
      return false; // Bail early
    }
    $body = json_decode($request, true);
    return $body["contactTags"];
  }

  public function get_contact_fields($contact_id){
    $request = $this->remote_post(
      $this->api_url."/api/3/contacts/".$contact_id."/fieldValues",
      array('headers' => $this->get_headers(), "method" => "GET")
    );
    if( !$request ) {
      return false; // Bail early
    }
    $body = json_decode($request, true);

    return $body["fieldValues"];
  }

  public function remove_tag_from_contact($contact_id, $tag_id){
    $tags = $this->get_contact_tags($contact_id);
    if(!$tags){
      return false;
    }
    $c_tag_id;
    foreach ($tags as $tkey => $tval) {
      if(intval($tval->tag) == intval($tag_id)){
        $c_tag_id = intval($tval->id);
        break;
      }
    }
    if(isset($c_tag_id)){
      $response = $this->remote_post(
        $this->api_url."/api/3/contactTags/".$c_tag_id,
        array(
          'headers' => $this->get_headers(true),
          'method' => 'DELETE'
        )
      );
    }
  }

  public function update_contact_field($contact_id, $field_id, $value){
    $body = array(
      "fieldValue" => array(
        "value" => $value,
        "contact" => $contact_id,
        "field" => $field_id
      )
    );


    $response = $this->remote_post(
      $this->api_url."/api/3/fieldValues/",
      array(
        'headers' => $this->get_headers(true),
        'method' => 'POST',
        'body' => json_encode($body)
      )
    );
  }

  public function subscribe_contact_to_list($contact_id, $list_id){
    if(intval($list_id) < 0){
      return false;
    }

    $body = array(
      "contactList" => array(
        "contact" => $contact_id,
        "list" => $list_id,
        "status" => 1
      )
    );

    $response = $this->remote_post(
      $this->api_url."/api/3/contactLists/",
      array(
        'headers' => $this->get_headers(true),
        'method' => 'POST',
        'body' => json_encode($body)
      )
    );

    return $response;

  }

  public function get_all_tags() {
    if(isset($this->all_tags)){
      return $this->all_tags;
    }
    $request = $this->remote_post(
      $this->api_url."/api/3/tags?limit=100",
      array('headers' => $this->get_headers(), "method" => "GET")
    );
    if( !$request ) {
      return false; // Bail early
    }
    $body  = json_decode($request, true);
    if(!is_array($body["tags"])){
      return false;
    }

    $result = [];
    foreach($body["tags"] as $tag){
      //if($tag->tagType == "contact"){
      $result[$tag["id"]] = $tag["tag"];
      //}
    }
    asort($result);
    $this->all_tags = $result;
    return $this->all_tags;

  }

  public function get_all_lists() {
    if(isset($this->all_lists)){
      return $this->all_lists;
    }
    $request = $this->remote_post(
      $this->api_url."/api/3/lists?limit=100",
      array('headers' => $this->get_headers(), "method" => "GET")
    );
    if( !$request ) {
      return false; // Bail early
    }
    $body  = json_decode($request, true);
    if(!is_array($body["lists"])){
      return false;
    }

    $result = [];
    foreach($body["lists"] as $list){
      //if($list->tagType == "contact"){
      $result[$list["id"]] = $list["name"];
      //}
    }
    asort($result);
    $this->all_lists = $result;
    return $this->all_lists;

  }

  public function get_all_fields($type = "") {
    if(!isset($this->raw_fields)){
      $request = $this->remote_post(
        $this->api_url."/api/3/fields?limit=100",
        array('headers' => $this->get_headers(), "method" => "GET")
      );
      if( !$request ) {
        return false; // Bail early
      }
      $body  = json_decode($request, true);
      if(!is_array($body["fields"])){
        return false;
      }
      $this->raw_fields = $body["fields"];
    }

    $result = [];
    foreach($this->raw_fields as $field){
      if($type == "" || $type == "all" || $type == $field["type"]){
        $result[$field["id"]] = $field["title"];
      }
    }
    asort($result);
    return $result;

  }

  private function get_headers($with_json = false){
    $res = array('Api-Token: ' . $this->api_key);
    if($with_json){
      $res[] = 'Content-Type: application/json; charset=utf-8';
    }
    return $res;
  }

  private function remote_post($url, $data){
    $body = isset($data["body"]) ? $data["body"] : [];
    $method = isset($data["method"]) ? $data["method"] : "POST";
    $headers = isset($data["headers"]) ? $data["headers"] : [];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $server_output = curl_exec($ch);
    curl_close ($ch);
    return $server_output;

  }
}
