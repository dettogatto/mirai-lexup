<?php
namespace MiraiLexup;


class Elementor_Lexup_Login extends \ElementorPro\Modules\Forms\Classes\Action_Base {
  /**
  * Get Name
  *
  * Return the action name
  *
  * @access public
  * @return string
  */
  public function get_name() {
    return 'lexup_login';
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
    return 'Lexup Login';
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
    $options = get_option( 'mirai_lexup_options' );
    $lexup = new LexupApi($options["api_ambient"], $options["admin_token"]);

    $settings = $record->get( 'form_settings' );
    $emailField = $settings[$this->get_name() . "_email"];
    $passField = $settings[$this->get_name() . "_password"];

    // Get submitted Form data
    $rawFields = (array) $record->get( 'fields' );
    $email = $rawFields[$emailField]["value"];
    $pass = $rawFields[$passField]["value"];

    $response = $lexup->login_user($email, $pass);

    if($response && $response["success"]){

      $redirect_to = $settings[$this->get_name() . "_url_success" ];
      $redirect_to = $record->replace_setting_shortcodes( $redirect_to, true );
      if ( ! empty( $redirect_to ) && filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
        $ajax_handler->add_response_data( 'redirect_url', $redirect_to );
      }
      return;

    } elseif(!empty($response["errors"])){
      $ajax_handler->add_error(null, "Unauthorized");
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
      $this->get_name() . "_url_success",
      [
        'label' => "Success URL",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'before',
        'description' => "The url where the customer will be redirect if he is logged in with success",
      ]
    );

    $widget->add_control(
      $this->get_name() . "_email",
      [
        'label' => "FIELD: Email",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'before',
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
