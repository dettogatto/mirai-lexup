<?php
namespace MiraiLexup;

/**
* Plugin Name: Mirai - Lexup
* Description: Connects Elementor and Woocommerce to Lexup
* Plugin URI:  https://cosmo.cat
* Version:     0.1.1
* Author:      Nicola Cavallazzi
* Author URI:  https://cosmo.cat/
* Text Domain: mirai-lexup
*/

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Update checker
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
  'https://github.com/dettogatto/mirai-lexup/',
  __FILE__,
  'mirai-lexup'
);

//Optional: If you're using a private repository, specify the access token like this:
//$myUpdateChecker->setAuthentication('your-token-here');

//Optional: Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');


add_action( 'elementor_pro/init', function(){
  // Here its safe to include our action class file
  include_once( __DIR__ . '/form-actions/check-student.php' );

  // // Instantiate the action class
  $lexup_check_student = new Elementor_Lexup_Check_Student();

  // Register the action with form widget
  \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $lexup_check_student->get_name(), $lexup_check_student );

} );


include_once(__DIR__ . "/admin/admin-settings.php");
