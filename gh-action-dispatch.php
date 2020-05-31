<?php
/**
 * Plugin Name: Github Action Dispatch
 * Plugin URI: https://github.com/atflick
 * Description: Adds a button and status badge in the admin bar to allow running Github actions from WP.
 * Version: 1.0
 * Author: Andy Flickinger
 */
class GH_Action_Dispatch {
  public function __construct() {
    // setup menu
    add_action( 'admin_menu', array( $this, 'create_settings_page' ) );
    // add page sections
    add_action( 'admin_init', array( $this, 'setup_sections' ) );
    add_action( 'admin_init', array( $this, 'setup_fields' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'plugin_enqueue' ) );
    add_action( 'admin_bar_menu', array( $this, 'add_toolbar_items' ), 100 );
  }

  public function create_settings_page () {
    // adding menu item and page
    $page_title = 'GH Action Dispatch';
    $menu_title = 'Action Dispatch';
    $capability = 'manage_options';
    $slug = 'gh-action-dispatch';
    $callback = array( $this, 'settings_page_content' );
    $icon = 'dashicons-admin-plugins';
    $position = 100;

    add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
  }

  public function settings_page_content () {
    ?>
      <div class="wrap">
        <h2>Github Action Dispatch</h2>
        <form method="post" action="options.php">
          <?php
            settings_fields( 'gh-action-dispatch' );
            do_settings_sections( 'gh-action-dispatch' );
            submit_button();
          ?>
        </form>
      </div>
    <?php
  }

  public function setup_sections() {
    add_settings_section( 'settings_section', 'Settings', false, 'gh-action-dispatch' );
  }

  public function setup_fields() {
    $fields = array(
      array(
          'uid' => 'gh_username',
          'label' => 'Github Username',
          'section' => 'settings_section',
          'type' => 'text',
          'options' => false,
          'placeholder' => 'Username',
          'helper' => false,
          'supplemental' => false,
          'default' => ''
      ),
      array(
          'uid' => 'gh_repo',
          'label' => 'Repo Name',
          'section' => 'settings_section',
          'type' => 'text',
          'options' => false,
          'placeholder' => 'Repo Name',
          'helper' => false,
          'supplemental' => false,
          'default' => ''
      ),
      array(
          'uid' => 'gh_event',
          'label' => 'Event Type',
          'section' => 'settings_section',
          'type' => 'text',
          'options' => false,
          'placeholder' => 'Event Type',
          'helper' => false,
          'supplemental' => 'Should correspond to the event in the workflow file.',
          'default' => ''
      ),
      array(
          'uid' => 'gh_auth_key',
          'label' => 'Authorization Key',
          'section' => 'settings_section',
          'type' => 'text',
          'options' => false,
          'placeholder' => 'Key',
          'helper' => false,
          'supplemental' => 'Setup your auth key',
          'default' => ''
      ),
      array(
          'uid' => 'toolbar_button',
          'label' => 'Label for action button',
          'section' => 'settings_section',
          'type' => 'text',
          'options' => false,
          'placeholder' => '',
          'helper' => false,
          'supplemental' => 'This is the label that will show on the button in the top toolbar. The button will dispatch the action.',
          'default' => 'Build Site'
      )
    );
    foreach( $fields as $field ){
      add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'gh-action-dispatch', $field['section'], $field );
      register_setting( 'gh-action-dispatch', $field['uid'] );
    }
  }

  public function field_callback($arguments) {
    $value = get_option( $arguments['uid'] ); // Get the current value, if there is one
    if ( ! $value ) { // If no value exists
      $value = $arguments['default']; // Set to our default
    }

    // Check which type of field we want
    switch ( $arguments['type'] ){
      case 'text': // If it is a text field
        printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
        break;
    }

    // If there is help text
    if ( $helper = $arguments['helper'] ){
      printf( '<span class="helper">%s</span>', $helper ); // Show it
    }

    // If there is supplemental text
    if ( $supplimental = $arguments['supplemental'] ){
      printf( '<p class="description">%s</p>', $supplimental ); // Show it
    }
  }

  public function plugin_enqueue($hook) {
    $gh_user = get_option('gh_username') ? get_option('gh_username') : '';
    $gh_repo = get_option('gh_repo') ? get_option('gh_repo') : '';
    $gh_event = get_option('gh_event') ? get_option('gh_event') : '';
    $gh_auth_key = get_option('gh_auth_key') ? get_option('gh_auth_key') : '';
    $options = array(
      'webhook_url' => 'https://api.github.com/repos/'.$gh_user.'/'.$gh_repo.'/dispatches',
      'auth_key' => $gh_auth_key,
      'event_type' => $gh_event,
      'status_check' => 'https://api.github.com/repos/'.$gh_user.'/'.$gh_repo.'/actions/runs',
      'refresh_icon' => plugin_dir_url(__FILE__).'/img/refresh.svg',
      'check_icon' => plugin_dir_url(__FILE__).'/img/check.svg',
      'dots_icon' => plugin_dir_url(__FILE__).'/img/dots.svg',
      'cross_icon' => plugin_dir_url(__FILE__).'/img/cross.svg'
    );

    wp_enqueue_script('gh-action-dispatch', plugin_dir_url(__FILE__).'/js/gh-action-dispatch.js', array(), '20200asdd', true);
    wp_enqueue_style('gh-action-dispatch-styles', plugin_dir_url(__FILE__).'/css/gh-action-dispatch.css');
    wp_localize_script('gh-action-dispatch', 'ghActionDispatchOptions', $options);
  }

  public function add_toolbar_items($admin_bar) {
    $label = get_option('toolbar_button') ? get_option('toolbar_button') : 'Dispatch Action';
    $admin_bar->add_menu(array(
      'id' => 'gh-action-dispatch-btn',
      'title' => $label,
      'href' => '#',
      'meta' => array(
        'title' => __($label)
      ),
    ));
  }
}

new GH_Action_Dispatch();