<?php
namespace MiraiLexup;


class Elementor_Lexup_Sign_Up extends \ElementorPro\Modules\Forms\Classes\Action_Base {
  /**
  * Get Name
  *
  * Return the action name
  *
  * @access public
  * @return string
  */
  public function get_name() {
    return 'lexup_sign_up';
  }

  /**
  * Get Label
  *
  * Returns the action label
  *
  * @access public
  * @return string
  */
  public function get_label() {
    return 'Lexup Sign Up';
  }

  /**
  * Run
  *
  * Runs the action after submit
  *
  * @access public
  * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
  * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
  */
  public function run( $record, $ajax_handler ) {

    // Include api
    require_once( __DIR__ . '/../api/lexup-api.php' );
    $options = get_option( 'mirai_lexup_options' );
    $lexup = new LexupApi($options["api_ambient"], $options["admin_token"]);

    $settings = $record->get( 'form_settings' );
    $nameField = $settings[$this->get_name() . "_name"];
    $surnameField = $settings[$this->get_name() . "_surname"];
    $emailField = $settings[$this->get_name() . "_email"];
    $passField = $settings[$this->get_name() . "_password"];
    $repassField = $settings[$this->get_name() . "_repassword"];

    // Get submitted Form data
    $rawFields = (array) $record->get( 'fields' );
    $name = $rawFields[$nameField]["value"];
    $surname = $rawFields[$surnameField]["value"];
    $email = $rawFields[$emailField]["value"];
    $pass = $rawFields[$passField]["value"];
    $repass = $rawFields[$repassField]["value"];

    $free_trial = ($settings[$this->get_name() . "_free_trial"] === "yes");

    $response = $lexup->register_user($name, $surname, $email, $pass, $repass, $free_trial);

    if($response && $response["success"]){

      $redirect_to = $settings[$this->get_name() . "_url_success" ];
      $redirect_to = $record->replace_setting_shortcodes( $redirect_to, true );
      if ( ! empty( $redirect_to ) && filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
        $ajax_handler->add_response_data( 'redirect_url', $redirect_to );
      }
      return;

    } elseif(!empty($response["errors"])){

      foreach ($response["errors"] as $field => $value) {
        if($field == "nome"){
          $ajax_handler->add_error($nameField, $value);
        } else if($field == "cognome"){
          $ajax_handler->add_error($surnameField, $value);
        } else if($field == "email"){
          $ajax_handler->add_error($emailField, $value);
        } else if($field == "password"){
          $ajax_handler->add_error($passField, $value);
        } else if($field == "repassword"){
          $ajax_handler->add_error($repassField, $value);
        }
      }

      return;
    }

    $ajax_handler->add_error(null, "Non riesco a comunicare con Lexup!");
    return;

  }

  /**
  * Register Settings Section
  *
  * Registers the Action controls
  *
  * @access public
  * @param \Elementor\Widget_Base $widget
  */
  public function register_settings_section( $widget ) {
    $widget->start_controls_section(
      'section_' . $this->get_name(),
      [
        'label' => $this->get_label(),
        'condition' => [
          'submit_actions' => $this->get_name(),
        ],
      ]
    );

    $widget->add_control(
      $this->get_name() . "_free_trial",
      [
        'label' => "Activate free trial?",
        'type' => \Elementor\Controls_Manager::CHOOSE,
        'separator' => 'before',
        'description' => "Whether to activate the free trial or not",
        'options' => [
          'yes' => [
            'title' => 'Yes',
            'icon' => 'fas fa-check'
          ],
          'no' => [
            'title' => 'No',
            'icon' => 'fas fa-times'
          ]
        ],
        'default' => 'no'
      ]
    );


    $widget->add_control(
      $this->get_name() . "_url_success",
      [
        'label' => "Success URL",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'before',
        'description' => "The url where the customer will be redirect if he is logged in with success",
      ]
    );

    $widget->add_control(
      $this->get_name() . "_name",
      [
        'label' => "FIELD: Name",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'before',
        'description' => "The form field ID containing the name of customer",
      ]
    );

    $widget->add_control(
      $this->get_name() . "_surname",
      [
        'label' => "FIELD: Surname",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'none',
        'description' => "The form field ID containing the family name of customer",
      ]
    );

    $widget->add_control(
      $this->get_name() . "_email",
      [
        'label' => "FIELD: Email",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'none',
        'description' => "The form field ID containing the email of customer",
      ]
    );

    $widget->add_control(
      $this->get_name() . "_password",
      [
        'label' => "FIELD: Password",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'none',
        'description' => "The form field ID containing the password of customer",
      ]
    );

    $widget->add_control(
      $this->get_name() . "_repassword",
      [
        'label' => "FIELD: Re-password",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'none',
        'description' => "The form field ID containing the repeated password",
      ]
    );


    $widget->end_controls_section();

  }

  /**
  * On Export
  *
  * Clears form settings on export
  * @access Public
  * @param array $element
  */
  public function on_export( $element ) {
  }


}
