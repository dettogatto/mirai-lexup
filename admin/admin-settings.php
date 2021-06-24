<?php
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
    // Set class property
    $this->options = get_option( 'mirai_lexup_options' );
    ?>
    <div class="wrap">
      <h1>Mirai-Lexup</h1>
      <form method="post" action="options.php">
        <?php
        // This prints out all hidden setting fields
        settings_fields( 'mirai_lexup_options' );
        do_settings_sections( 'mirai-sportick-options' );
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
    register_setting(
      'mirai_lexup_options', // Option group
      'mirai_lexup_options', // Option name
      array( $this, 'sanitize' ) // Sanitize
    );

    add_settings_section(
      'setting_section_id', // ID
      'Settings', // Title
      array( $this, 'print_section_info' ), // Callback
      'mirai-sportick-options' // Page
    );

    add_settings_field(
      'api_key', // ID
      'API Key (not required atm)', // Title
      array( $this, 'api_key_callback' ), // Callback
      'mirai-sportick-options', // Page
      'setting_section_id' // Section
    );

  }

  /**
  * Sanitize each setting field as needed
  *
  * @param array $input Contains all settings fields as array keys
  */
  public function sanitize( $input )
  {
    $new_input = array();
    if( isset( $input['api_key'] ) )
    $new_input['api_key'] = $input['api_key'];


    return $new_input;
  }

  /**
  * Print the Section text
  */
  public function print_section_info()
  {
    print 'Enter your settings below:';
  }

  /**
  * Get the settings option array and print one of its values
  */
  public function api_key_callback()
  {
    printf(
      '<input type="text" id="api_key" name="mirai_lexup_options[api_key]" value="%s" />',
      isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key']) : ''
    );
  }

}



if( is_admin() ){
  $mirai_lexup_settings_page = new MiraiLexupOptions();
}
