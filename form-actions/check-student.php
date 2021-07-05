<?php
namespace MiraiLexup;


class Elementor_Lexup_Check_Student extends \ElementorPro\Modules\Forms\Classes\Action_Base {
  /**
  * Get Name
  *
  * Return the action name
  *
  * @access public
  * @return string
  */
  public function get_name() {
    return 'lexup_check_student';
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
    return 'Lexup Check Student';
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

    // Get submitted Form data
    $rawFields = (array) $record->get( 'fields' );
    $email = $rawFields[$emailField]["value"];

    $is_student = $lexup->check_student_mail($email);

    $activecampaign = new ActivecampaignApi();
    $ac_user = [ "email" => $email ];

    if($is_student === true){
      $activecampaign->super_sync_contact($ac_user, [36]);

      $redirect_to = $settings[$this->get_name() . "_url_success" ];
      $redirect_to = $record->replace_setting_shortcodes( $redirect_to, true );
      if ( ! empty( $redirect_to ) && filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
        $ajax_handler->add_response_data( 'redirect_url', $redirect_to );
      }
      return;

    } elseif($is_student === false){
      $activecampaign->super_sync_contact($ac_user);

      $redirect_to = $settings[$this->get_name() . "_url_ineligible" ];
      $redirect_to = $record->replace_setting_shortcodes( $redirect_to, true );
      if ( ! empty( $redirect_to ) && filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
        $ajax_handler->add_response_data( 'redirect_url', $redirect_to );
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
      $this->get_name() . "_url_success",
      [
        'label' => "Success URL",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'before',
        'description' => "The url where the customer will be redirect if he is recognized as a student",
      ]
    );

    $widget->add_control(
      $this->get_name() . "_url_ineligible",
      [
        'label' => "Ineligible URL",
        'type' => \Elementor\Controls_Manager::TEXT,
        'separator' => 'before',
        'description' => "The url where the customer will be redirect if his mail domain is not a student one in Lexup",
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
