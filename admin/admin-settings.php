<?php

namespace MiraiLexup;

class MiraiLexupOptions
{
  /**
  * Holds the values to be used in the fields callbacks
  */
  private $options;

  /**
  * Start up
  */
  public function __construct()
  {
    add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
    add_action( 'admin_init', array( $this, 'page_init' ) );
  }

  /**
  * Add options page
  */
  public function add_plugin_page()
  {
    // This page will be under "Settings"
    add_options_page(
      'Mirai-Lexup Settings',
      'Mirai-Lexup',
      'manage_options',
      'mirai-lexup-options',
      array( $this, 'create_admin_page' )
    );
  }

  /**
  * Options page callback
  */
  public function create_admin_page()
  {
    ?>
    <div class="wrap">
      <h1>Mirai-Lexup</h1>
      <form method="post" action="options.php">
        <?php
        // This prints out all hidden setting fields
        settings_fields( 'mirai_lexup_options' );

        // do_settings_fields( 'mirai-lexup-options', 'setting_section_login_required' );

        do_settings_sections( 'mirai-lexup-options' );

        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  /**
  * Register and add settings
  */
  public function page_init()
  {

    $this->options = get_option( 'mirai_lexup_options' );

    register_setting(
      'mirai_lexup_options', // Option group
      'mirai_lexup_options', // Option name
      array( $this, 'sanitize' ) // Sanitize
    );

    add_settings_section(
      'setting_section_ambient', // ID
      'Choose ambient', // Title
      array( $this, 'print_section_ambient' ), // Callback
      'mirai-lexup-options' // Page
    );

    add_settings_field(
      'api_ambient', // ID
      'API ambient', // Title
      array( $this, 'api_ambient_callback' ), // Callback
      'mirai-lexup-options', // Page
      'setting_section_ambient' // Section
    );


    if(empty($this->options['admin_token'])){

      add_settings_section(
        'setting_section_login_required', // ID
        'Login to API', // Title
        array( $this, 'print_section_login_required' ), // Callback
        'mirai-lexup-options' // Page
      );

      add_settings_field(
        'admin_mail', // ID
        'Admin Email', // Title
        array( $this, 'admin_mail_callback' ), // Callback
        'mirai-lexup-options', // Page
        'setting_section_login_required' // Section
      );

      add_settings_field(
        'admin_password', // ID
        'Admin Password', // Title
        array( $this, 'admin_password_callback' ), // Callback
        'mirai-lexup-options', // Page
        'setting_section_login_required' // Section
      );

    } else {

      add_settings_section(
        'setting_section_login_present', // ID
        'Login to API', // Title
        array( $this, 'print_section_login_present' ), // Callback
        'mirai-lexup-options' // Page
      );

      add_settings_field(
        'admin_logout', // ID
        'Logout Admin', // Title
        array( $this, 'admin_logout_callback' ), // Callback
        'mirai-lexup-options', // Page
        'setting_section_login_present' // Section
      );

    }

  }

  /**
  * Sanitize each setting field as needed
  *
  * @param array $input Contains all settings fields as array keys
  */
  public function sanitize( $input )
  {
    $new_input = array();
    if( !empty($input['admin_mail']) && !empty($input['admin_password']) ){

      // Include api
      require_once( __DIR__ . '/../api/lexup-api.php' );
      $lexup = new LexupApi();

      $user_data = $lexup->login_user($input['admin_mail'], $input['admin_password']);

      if($user_data['tipo'] === "super_admin"){
        $new_input['admin_token'] = $user_data['token'];
      }

    } else if( !empty($input['admin_logout']) ){
      $new_input['admin_token'] = NULL;
    } else if( !empty($this->options['admin_token']) ) {
      $new_input['admin_token'] = $this->options['admin_token'];
    }

    if($input['api_ambient'] == "production"){
      $new_input['api_ambient'] = "production";
    } else {
      $new_input['api_ambient'] = "test";
    }

    return $new_input;
  }

  /**
  * Print the Section text
  */
  public function print_section_ambient()
  {
  }

  /**
  * Print the Section text
  */
  public function print_section_login_required()
  {
    print 'A login with a super_admin account is required!';
  }

  /**
  * Print the Section text
  */
  public function print_section_login_present()
  {
    // Include api
    require_once( __DIR__ . '/../api/lexup-api.php' );
    $lexup = new LexupApi($this->options['api_ambient'], $this->options['admin_token']);

    $admin_user = $lexup->get_logged_user($this->options['admin_token']);

    if(!empty($admin_user)){
      print 'Successfully logged in as: <code>' . $admin_user['email']['field'] . '</code>';
    } else {
      print '<strong>Login expired!</strong>';
    }

  }

  /**
  * Get the settings option array and print one of its values
  */
  public function admin_mail_callback()
  {
    if(empty($this->options['admin_token'])){
      echo('<input type="text" id="admin_mail" name="mirai_lexup_options[admin_mail]" value="" />');
    }
  }

  /**
  * Get the settings option array and print one of its values
  */
  public function admin_password_callback()
  {
    if(empty($this->options['admin_token'])){
      echo('<input type="password" id="admin_password" name="mirai_lexup_options[admin_password]" value="" />');
    }
  }

  /**
  * Get the settings option array and print one of its values
  */
  public function admin_logout_callback()
  {
    echo('
    <label>
    <input type="checkbox" id="admin_logout" name="mirai_lexup_options[admin_logout]" value="yes" />
    <p>Check to logout / reset admin data</p>
    </label>
    ');
  }


  /**
  * Get the settings option array and print one of its values
  */
  public function api_ambient_callback()
  {
    $check1="CHECKED";
    $check2="";

    if($this->options["api_ambient"] == "production"){
      $check1="";
      $check2="CHECKED";
    }

    echo('
    <label>
    <input type="radio" name="mirai_lexup_options[api_ambient]" value="test" '.$check1.' />
    TEST
    </label>
    <br>
    <label>
    <input type="radio" name="mirai_lexup_options[api_ambient]" value="production" '.$check2.' />
    PRODUCTION
    </label>
    ');
  }

}



if( is_admin() ){
  $mirai_lexup_settings_page = new MiraiLexupOptions();
}
