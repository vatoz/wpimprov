<?php
/**
 * Plugin Name: Improv events & teams
 * Plugin URI: https://github.com/vatoz/wpimprov
 * Description: Will display events and teams
 * Version: 0.0.3
 * Author: Vaclav Cerny
 * Author URI: https://www.facebook.com/vaclav.cerny.12
 * License: MIT
 * Text Domain: wpimprov
 */
require 'wpimprov_field.php';
require "wpimprov_display.php";
require "wpimprov_sources.php";

if (($loader = require_once __DIR__ . '/vendor/autoload.php') == null)  {
  die('Vendor directory not found, Please run composer install.');
}

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;


add_action('init', 'wpimprov_create_post_type');

/*
  Create post types and associated taxonomies
 */

function wpimprov_create_post_type() {

    register_taxonomy(
            'wpimprov_event_team', 'wpimprov_event', array(
        'label' => __('Teams', 'wpimprov'),
        //'rewrite' => array( 'slug' => 'city' ),
        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'capabilities' => array(
            'assign_terms' => 'edit_posts',
            'edit_terms' => 'administrator'
        ),
            )
    );

    register_taxonomy(
            'wpimprov_event_type', 'wpimprov_event', array(
        'label' => __('Type of event', 'wpimprov'),
        //'rewrite' => array( 'slug' => 'city' ),
        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'capabilities' => array(
            'assign_terms' => 'edit_posts',
            'edit_terms' => 'administrator'
        ),
            )
    );

    register_post_type('wpimprov_team', array(
        'labels' => array(
            'name' => __('Improliga teams', 'wpimprov'),
            'singular_name' => __('Improliga team', 'wpimprov')
        ),
        'taxonomies' => array(
        ),
        "supports" => array(
            "title",
            "editor",
            "thumbnail",
            "revisions",
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-universal-access-alt',
        'rewrite' => array('slug' => 'team'),
            )
    );

    register_post_type('wpimprov_event', array(
        'labels' => array(
            'name' => __('Improliga events', 'wpimprov'),
            'singular_name' => __('Improliga event', 'wpimprov')
        ),
        'taxonomies' => array(
            "wpimprov_event_team",
            'wpimprov_event_type'
        ),
        "supports" => array(
            "title",
            "editor",
            "thumbnail",
            "revisions",
        ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'rewrite' => array('slug' => 'event'),
            )
    );
}

add_action('load-post.php', 'wpimprov_meta_boxes_setup');
add_action('load-post-new.php', 'wpimprov_meta_boxes_setup');

function wpimprov_meta_boxes_setup() {

    /* Add meta boxes on the 'add_meta_boxes' hook. */
    add_action('add_meta_boxes', 'wpimprov_add_post_meta_boxes');
    /* Save post meta on the 'save_post' hook. */
    add_action('save_post', 'wpimprov_save_post_class_meta', 10, 2);
}

/* Create few meta boxes to be displayed on the post editor screen. */

function wpimprov_add_post_meta_boxes() {

    add_meta_box(
            'wpimprov-event-class', // Unique ID
            esc_html__('Event details', 'wpimprov'), // Title
            'wpimprov_event_class_meta_box', // Callback function
            'wpimprov_event', // Admin page (or post type)
            'side', // Context
            'default'         // Priority
    );

    add_meta_box(
            'wpimprov-team-class', // Unique ID
            esc_html__('Team details', 'wpimprov'), // Title
            'wpimprov_team_class_meta_box', // Callback function
            'wpimprov_team', // Admin page (or post type)
            'side', // Context
            'default'         // Priority
    );
}

/* Will return array() of wpimprov_field
 * @param string content_type Usually wpimprov_team or wpimprov_event
 */

function wpimprov_field_def($content_type) {
    $Result = array();
    switch ($content_type) {
        case "wpimprov_team":
            $Result[] = new wpimprov_field('wpimprov-team-fb', __("Facebook id", 'wpimprov'), 'text');
            $Result[] = new wpimprov_field('wpimprov-team-web', __("Webpages", 'wpimprov'), 'text');
            $Result[] = new wpimprov_field('wpimprov-team-city', __("City", 'wpimprov'), 'text');
            $Result[] = new wpimprov_field('wpimprov-team-alias', __("Alias", 'wpimprov'), 'text');

            $Result[] = new wpimprov_field('wpimprov-team-refreshed', __("Refreshed", 'wpimprov'), 'date');
            $Result[] = new wpimprov_field('wpimprov-team-geo-latitude', __("Latitude", 'wpimprov'), 'float');
            $Result[] = new wpimprov_field('wpimprov-team-geo-longitude', __("Longitude", 'wpimprov'), 'float');
	    $Result[] = new wpimprov_field('wpimprov-team-inactive', __("Inactive", 'wpimprov'), 'checkbox');

            break;
        case "wpimprov_event":
            $Result[] = new wpimprov_field('wpimprov-event-fb', __("Facebook event id", 'wpimprov'), 'text');
            $Result[] = new wpimprov_field('wpimprov-event-start-time', __("Start time", 'wpimprov'), 'datetime-local');
            $Result[] = new wpimprov_field('wpimprov-event-end-time', __("End time", 'wpimprov'), 'datetime-local');
            $Result[] = new wpimprov_field('wpimprov-event-venue', __("Venue", 'wpimprov'), 'text');
            $Result[] = new wpimprov_field('wpimprov-event-venue-city', __("Venue city", 'wpimprov'), 'text');
            $Result[] = new wpimprov_field('wpimprov-event-venue-street', __("Venue street", 'wpimprov'), 'text');

            $Result[] = new wpimprov_field('wpimprov-event-ticket-uri', __("Tickets", 'wpimprov'), 'text');
            $Result[] = new wpimprov_field('wpimprov-event-geo-latitude', __("Latitude", 'wpimprov'), 'float');
            $Result[] = new wpimprov_field('wpimprov-event-geo-longitude', __("Longitude", 'wpimprov'), 'float');
            $Result[] = new wpimprov_field('wpimprov-event-source', __("Source", 'wpimprov'), 'text');
            $Result[] = new wpimprov_field('wpimprov-event-organizer', __("Organizer", 'wpimprov'), 'text');
            break;
    }
    return $Result;
}

function wpimprov_meta_box($object, $box, $content_type) {
    wp_nonce_field(basename(__FILE__), 'wpimpro_nonce');
    foreach (wpimprov_field_def($content_type) as $field) {
        $field->render_editor($object->ID);
    }
}

function wpimprov_event_class_meta_box($object, $box) {
    wpimprov_meta_box($object, $box, "wpimprov_event");
}

function wpimprov_team_class_meta_box($object, $box) {
    wpimprov_meta_box($object, $box, "wpimprov_team");
}

/* Save the meta box's post metadata. */

function wpimprov_save_post_class_meta($post_id, $post) {
    /* Verify the nonce before proceeding. */
    if (!isset($_POST['wpimpro_nonce']) || !wp_verify_nonce($_POST['wpimpro_nonce'], basename(__FILE__)))
        return $post_id;

    /* Get the post type object. */
    $post_type = get_post_type_object($post->post_type);

    /* Check if the current user has permission to edit the post. */
    if (!current_user_can($post_type->cap->edit_post, $post_id))
        return $post_id;


    foreach (wpimprov_field_def($post->post_type) as $field) {
        $field->save_from_post($post_id);
    }
}

register_activation_hook(__FILE__, 'wpimprov_hook_schedule');

add_action('wpimprov_cron_hook', 'wpimprov_cron');

/* Will set schedule for cron hook.
 */

function wpimprov_hook_schedule() {
    //Use wp_next_scheduled to check if the event is already scheduled
    $timestamp = wp_next_scheduled('wpimprov_cron_hook');
    //If $timestamp == false schedule daily backups since it hasn't been done previously
    if ($timestamp == false) {
        //Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
        wp_schedule_event(time(), 'hourly', 'wpimprov_cron_hook');
    }
}

/* Function is called regullary,
 * it will load events from facebook
 */

function wpimprov_cron() {
    //todo  wpimprov_load_facebook(3, false);

    global $wpdb;
     $wpdb->query("update  " . $wpdb->prefix . "postmeta set meta_value='Praha' where meta_key = 'wpimprov-event-venue-city' and meta_value='Prague' ");

}

/* Get wpimprov-event-fb meta values,
 * we dont want events again
 */

function wpimprov_get_loaded() {
    global $wpdb;
    return $wpdb->get_results("SELECT meta_value as id FROM " . $wpdb->prefix . "postmeta where meta_key = 'wpimprov-event-fb'", ARRAY_A);
}

function wpimprov_load_facebook_source($fa, $Source, $Refreshed = null, $Verbose = false, $Taxonomy = 0, $Limit = 3) {
    $options = get_option('wpimprov_settings');

    $mame = wpimprov_get_loaded();
    $saved = 0;
    if ($Verbose)
        echo $Source . "<br>";
    flush();


    $tmp = $fa->getEvents($Source, wpimprov_date_mod($Refreshed));


    if ($Verbose)
        var_export($tmp);

    $tmp2 = $fa->getPostsEvents($Source, wpimprov_date_mod($Refreshed));
    if ($Verbose)
        var_export($tmp2);
    $events = array_merge($tmp, $tmp2);

    foreach ($events as $Id) {
        $found = false;
        foreach ($mame as $r) {
            if ($r['id'] == $Id) {
                $found = true;
                //echo "uz je v db , ignoruji".RA;
            }
        }

        if (!$found) {
        $wpdb-query('insert into '. $wpdb->prefix . 'wpimpro_candidates ' . ' (id,team) values('.$candidate.','.$Taxonomy.')');

            $mame[] = array("id" => $Id);
            $saved++;
            if ($saved > $Limit)
                return false;
        }
    }
    return true;
}

function wpimprov_load_facebook($Limit = 5, $Verbose = false) {
    define("VERBOSE", $Verbose);
    $saved = 0;
    global $wpdb;


    $options = get_option('wpimprov_settings');
    require_once 'fbActions.php';
    $fa = new fbActions($options['wpimprov_textarea_fb_app_id'], $options['wpimprov_textarea_fb_app_secret'], $options['wpimprov_textarea_fb_token']);

    $loaded=0;
    $mame= wpimprov_get_loaded();
    $toload= explode("\n",$options['wpimprov_textarea_fbimport']);
    foreach($toload as $key=>$candidate){
        $candidate=trim($candidate);
        $found = false;
        if($Verbose) echo $candidate."<br>";
        foreach ($mame as $r) {
            if ($r['id'] == $candidate) {
                $found = true;
                unset($toload[$key]);
             if($Verbose) echo $candidate." vyrazuji<br>";

                //echo "uz je v db , ignoruji".RA;
            }
        }
        if(!$found){
            if (strlen($candidate)>7){
            $wpdb-query('insert into '. $wpdb->prefix . 'wpimpro_candidates ' . ' (id) values('.$candidate.')');
            }
        }
    }
    $options[  'wpimprov_textarea_fbimport']= implode("\n",$toload);
        update_option("wpimprov_settings",$options);



    $zdroje = $wpdb->get_results("select fb.post_id,fb.meta_value as source, dt.meta_value as refreshed from(select * from "
            . "" . $wpdb->prefix . "postmeta where meta_key='wpimprov-team-fb' )"
            . " fb left join (select * from " . $wpdb->prefix . "postmeta where meta_key='wpimprov-team-refreshed') dt  on fb.post_id=dt.post_id  "
            . "where dt.meta_value in(select min(meta_value) from " . $wpdb->prefix . "postmeta where meta_key='wpimprov-team-refreshed')"
            . "or dt.meta_value is null  order by rand() limit " . $Limit, ARRAY_A);
    foreach ($zdroje as $Zdroj) {
        if (is_null($Zdroj["refreshed"]) || strlen($Zdroj["refreshed"]) < 10) {
            $Zdroj["refreshed"] = "2010-01-01";
        }
        if (wpimprov_load_facebook_source($fa, $Zdroj["source"], $Zdroj["refreshed"], $Verbose, wpimprov_taxonomy_from_post($Zdroj['post_id'], $Limit))) {

            wpimprov_meta_save($Zdroj["post_id"], "wpimprov-team-refreshed", date("Y-m-d"));
        } else {
            return;
        }

        if ($Verbose)
            echo "<hr>";
    }


    $zdroje = $wpdb->get_results("SELECT source,refreshed, description, DATEDIFF(now(),refreshed) as old "
            . " FROM " . $wpdb->prefix . "wpimpro_sources  where refreshed<date(now()) order by rand() limit " . $Limit, ARRAY_A);

    foreach ($zdroje as $Zdroj) {
        if (wpimprov_load_facebook_source($fa, $Zdroj["source"], $Zdroj["refreshed"], $Verbose, 0, $Limit)) {

            $wpdb->update($wpdb->prefix . "wpimpro_sources", array("refreshed" => date("Y-m-d")), array('source' => $Zdroj["source"]));
        } else {
            return;
        }

        if ($Verbose)
            echo "<hr>";
    }
}

 add_action('admin_menu', 'wpimprov_add_admin_menu');
if (is_admin()) {

    add_action('admin_init', 'wpimprov_settings_init');
}

function wpimprov_add_admin_menu() {
  add_menu_page("Wpimprov: ".__('Weekly lists', 'wpimprov'), __('Wpimprov', 'wpimprov'), 'manage_options', 'wpimprov_list', 'wpimprov_list_page');
  add_submenu_page("wpimprov_list","Wpimprov: ".__('Other sources', 'wpimprov'), __('Other sources', 'wpimprov'), 'manage_options', 'wpimprov_sources', "wpimprov_sources_page_handler");
  add_submenu_page("wpimprov_list","Wpimprov: ".__('Add other source', 'wpimprov'), __('Add other source', 'wpimprov'), 'manage_options', 'wpimprov_sources_form', "wpimprov_sources_form_page_handler");
  add_submenu_page("wpimprov_list","Wpimprov: ".__('Settings', 'wpimprov'), __('Settings', 'wpimprov'), 'manage_options', 'wpimprov_settings', 'wpimprov_options_page');
  //  add_submenu_page("wpimprov_list","Wpimprov: ".__('Load data', 'wpimprov'), __('Load data', 'wpimprov'), 'manage_options', 'wpimprov_fbload', 'wpimprov_load_page');
  add_submenu_page("wpimprov_list","Wpimprov: ".__('Login', 'wpimprov'), __('Login to facebook', 'wpimprov'), 'manage_options', 'wpimprov_fb_login', 'wpimprov_login_page');
  add_submenu_page("wpimprov_list","Wpimprov: ".__('Login', 'wpimprov'), __('Login callback - todo remove ', 'wpimprov'), 'manage_options', 'wpimprov_fb_login_callback', 'wpimprov_login_callback_page');
  add_submenu_page("wpimprov_list","Wpimprov: ".__('Load user events to system', 'wpimprov'), __('After confirmation it will load user events to db', 'wpimprov'), 'manage_options', 'wpimprov_fb_user_events', 'wpimprov_userevents_page');
  add_submenu_page("wpimprov_list","Wpimprov: ".__('Load ical events to system', 'wpimprov'), __('After confirmation it will ical events to db', 'wpimprov'), 'manage_options', 'wpimprov_ical_events', 'wpimprov_icalevents_page');
  add_submenu_page("wpimprov_list","Wpimprov: ".__('FB Logout', 'wpimprov'), __('fb logout', 'wpimprov'), 'manage_options', 'wpimprov_fb_logout', 'wpimprov_fblogout_page');


}



function wpimprov_login_page(){
  if(!session_id()) {
      session_start();
  }

    $options = get_option('wpimprov_settings');
    $fb = new Facebook\Facebook([
  'app_id' => $options['wpimprov_textarea_fb_app_id'], // Replace {app-id} with your app id
  'app_secret' =>  $options['wpimprov_textarea_fb_app_secret'],
  'default_graph_version' => 'v9.0'
  ]);


  $helper = $fb->getRedirectLoginHelper();

  $permissions = ['user_events']; // Optional permissions

  $loginUrl = $helper->getLoginUrl(menu_page_url('wpimprov_fb_login_callback',false), $permissions);
  //$a=$helper->getSessionFromRedirect();
  //echo ":aaaa:".var_export($a,true);

  //session_write_close ();
  $k=explode("&",$loginUrl );
  foreach ($k as $r ) {
    if(strpos($r,'state')===0){
      echo "<b>".$r."</b><br>";
      $r=substr($r,6);
    //  $_SESSION['FBRLH_state']=$r;
      //$_SESSION['state']=$r;
      //$_COOKIE['state']=$r;
      //$wp_session['state']=$r;
      $options["state"]=$r;
      update_option("wpimprov_settings",$options);
    }else{
      echo $r."<br>";
    }

  }


  echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
  echo "<br>";
  echo (file_get_contents('privacy.txt'));
  echo "<br>";
  echo "callback: <textarea>".menu_page_url('wpimprov_fb_login_callback',false)."</textarea><br>";
  echo session_status();
  echo "<hr>session<br>";
  var_export($_SESSION);
  echo "<hr>cookie<br>";
  var_export($_COOKIE);

}

function wpimprov_fblogout_page(){
  wpimprov_setusertoken ("");
  echo "forgetted;;;";

}

function wpimprov_login_callback_page(){
if(!session_id()) {
    session_start();
}

  $options = get_option('wpimprov_settings');
  $_SESSION["state"]=$options["state"];
//$_SESSION['state']=$_GET['state'];
$_SESSION['FBRLH_state']=$options['state'];
//$_SESSION['state']=$_GET['state'];

//$_COOKIE['state']=$_GET['state'];
echo "<hr>session<br>";
var_export($_SESSION);
echo "<hr>cookie<br>";
var_export($_COOKIE);


foreach ( array('state','code','page') as $key){
  if(isset($_REQUEST[$key])){
    echo"<hr>". $key."<br>".$_REQUEST[$key]."<br>";
  }
}


 $options = get_option('wpimprov_settings');
    $fb = new Facebook\Facebook([
  'app_id' => $options['wpimprov_textarea_fb_app_id'], // Replace {app-id} with your app id
  'app_secret' =>  $options['wpimprov_textarea_fb_app_secret'],
  'default_graph_version' => 'v9.0', "state"=>$_SESSION["state"]
  ]);

$helper = $fb->getRedirectLoginHelper();
if(isset($_GET['state'])) {
	$helper->getPersistentDataHandler()->set('state', $_GET['state']);
}

try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error (1): ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error (2): ' . $e->getMessage();
  exit;
}

if (! isset($accessToken)) {
  if ($helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Bad request';
  }
  exit;
}

// Logged in
echo '<h3>Access Token</h3>';
var_dump($accessToken->getValue());

// The OAuth 2.0 client handler helps us manage access tokens
$oAuth2Client = $fb->getOAuth2Client();

// Get the access token metadata from /debug_token
$tokenMetadata = $oAuth2Client->debugToken($accessToken);
echo '<h3>Metadata</h3>';
var_dump($tokenMetadata);

// Validation (these will throw FacebookSDKException's when they fail)
$tokenMetadata->validateAppId($options['wpimprov_textarea_fb_app_id']); // Replace {app-id} with your app id
// If you know the user ID this access token belongs to, you can validate it here
//$tokenMetadata->validateUserId('123');
$tokenMetadata->validateExpiration();

if (! $accessToken->isLongLived()) {
  // Exchanges a short-lived access token for a long-lived one
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
    exit;
  }

  echo '<h3>Long-lived</h3>';
  var_dump($accessToken->getValue());
}
  wpimprov_setusertoken ($accessToken);
    echo "<br>token set.<br>";

    echo $accessToken;






}

function wpimprov_setusertoken($Value){

    global $current_user;
    if (is_user_logged_in()){ //check if user is logged in.
            // get current user info
           $u= get_currentuserinfo();
            //$old_notes = get_user_meta($current_user->ID, 'fb_token', true);

                //first note we are saving fr this user
update_user_meta( $u->ID, 'fb_token', $Value);



}

}
function wpimprov_usertoken(){
    global $current_user;
    if( is_user_logged_in()){ //check if user is logged in.
        // get current user info
       $u=  get_currentuserinfo();
        $old_notes = get_user_meta($u->ID, 'fb_token', true);
        if (!isset($old_notes)){
            return false;
        }
        if (isset($old_notes)){//we have notes. Removed the extra ! here.
           return  $old_notes ;
            }
        }

    }



function wpimprov_settings_init() {

    register_setting('pluginPage', 'wpimprov_settings');

    add_settings_section(
            'wpimprov_pluginPage_section_main', __('Main settings', 'wpimprov'), 'wpimprov_settings_section_callback', 'pluginPage'
    );


    add_settings_section(
            'wpimprov_pluginPage_section_fb', __('Facebook settings', 'wpimprov'), 'wpimprov_settings_section_callback', 'pluginPage'
    );

    add_settings_field(
            'wpimprov_textarea_tagging', __('List of tag selectors in form string|tag on new lines', 'wpimprov'), 'wpimprov_textarea_field_tagging_render', 'pluginPage', 'wpimprov_pluginPage_section_main'
    );
    add_settings_field(
            'wpimprov_textarea_fbimport', __('List of event ids to be imported', 'wpimprov'), 'wpimprov_textarea_field_fbimport_render', 'pluginPage', 'wpimprov_pluginPage_section_fb'
    );

    add_settings_field(
            'wpimprov_textarea_fb_app_id', __('Facebook App Id', 'wpimprov'), 'wpimprov_textarea_field_fb_app_id_render', 'pluginPage', 'wpimprov_pluginPage_section_fb'
    );
    add_settings_field(
            'wpimprov_textarea_fb_app_secret', __('Facebook App Secret', 'wpimprov'), 'wpimprov_textarea_field_fb_app_secret_render', 'pluginPage', 'wpimprov_pluginPage_section_fb'
    );

    add_settings_field(
            'wpimprov_textarea_fb_token', __('Facebook token', 'wpimprov'), 'wpimprov_textarea_field_fb_token_render', 'pluginPage', 'wpimprov_pluginPage_section_fb'
    );

    add_settings_field(
            'wpimprov_textarea_disclaimer', __('Event disclaimer', 'wpimprov'), 'wpimprov_textarea_field_disclaimer_render', 'pluginPage', 'wpimprov_pluginPage_section_main'
    );

    add_settings_field(
            'wpimprov_textarea_ical', __('iCal source of events', 'wpimprov'), 'wpimprov_textarea_field_ical_render', 'pluginPage', 'wpimprov_pluginPage_section_main'
    );


}

function wpimprov_textarea_field_tagging_render() {

    $options = get_option('wpimprov_settings');
    ?>
    <textarea cols='40' rows='5' name='wpimprov_settings[wpimprov_textarea_tagging]'><?php echo $options['wpimprov_textarea_tagging']; ?></textarea>
    <?php
}

function wpimprov_textarea_field_sources_render() {

    $options = get_option('wpimprov_settings');
    ?>
    <textarea cols='40' rows='5' name='wpimprov_settings[wpimprov_textarea_sources]'><?php echo $options['wpimprov_textarea_sources']; ?></textarea>
    <?php
}

function wpimprov_textarea_field_fb_app_id_render() {

    $options = get_option('wpimprov_settings');
    ?>
    <textarea cols='40' rows='1' name='wpimprov_settings[wpimprov_textarea_fb_app_id]'><?php echo $options['wpimprov_textarea_fb_app_id']; ?></textarea>
    <?php
}


function wpimprov_textarea_field_fbimport_render() {
    $options = get_option('wpimprov_settings');
    ?>
    <textarea cols='40' rows='5' name='wpimprov_settings[wpimprov_textarea_fbimport]'><?php echo $options['wpimprov_textarea_fbimport']; ?></textarea>
    <?php
}

function wpimprov_textarea_field_ical_render(){
    $options = get_option('wpimprov_settings');
    ?>
    <textarea cols='40' rows='1' name='wpimprov_settings[wpimprov_textarea_ical]'><?php echo $options['wpimprov_textarea_ical']; ?></textarea>
    <?php

}
function wpimprov_textarea_field_disclaimer_render(){
    $options = get_option('wpimprov_settings');
    ?>
    <textarea cols='40' rows='1' name='wpimprov_settings[wpimprov_textarea_disclaimer]'><?php echo $options['wpimprov_textarea_disclaimer']; ?></textarea>
    <?php

}
function wpimprov_textarea_field_fb_app_secret_render() {

    $options = get_option('wpimprov_settings');
    ?>
    <textarea cols='40' rows='1' name='wpimprov_settings[wpimprov_textarea_fb_app_secret]'><?php echo $options['wpimprov_textarea_fb_app_secret']; ?></textarea>
    <?php
}

function wpimprov_textarea_field_fb_token_render() {

    $options = get_option('wpimprov_settings');
    ?>
    <textarea cols='40' rows='1' name='wpimprov_settings[wpimprov_textarea_fb_token]'><?php echo $options['wpimprov_textarea_fb_token']; ?></textarea>
    <?php
}

function wpimprov_settings_section_callback() {

    //echo __( 'Automatic assigning of tags', 'wpimprov' );
}

function wpimprov_date_mod($D){
        $date = new DateTime($D);
        $inte=new DateInterval("P10D");
        $inte->invert =1;
        $date->add($inte);
        return   $date->format('Y-m-d');
}


function wpimprov_list_page() {
    echo wpimprov_list_display(array('list'=>'show'));


}

function wpimprov_options_page() {
    ?>
    <form action='options.php' method='post'>

        <h2>wpimprov</h2>

    <?php
    settings_fields('pluginPage');
    do_settings_sections('pluginPage');
    submit_button();
    ?>

    </form>     <br>

    <?php


}

function wpimprov_userevents_page(){
 $options = get_option('wpimprov_settings');
    require_once 'fbActions.php';

   $fa = new fbActions($options['wpimprov_textarea_fb_app_id'], $options['wpimprov_textarea_fb_app_secret'],wpimprov_usertoken());
   $fa->user_load(menu_page_url('wpimprov_fb_user_events',false),wpimprov_get_loaded(), $options['wpimprov_textarea_tagging']);

}

function wpimprov_icalevents_page(){
 $options = get_option('wpimprov_settings');
    require_once 'icalActions.php';

   $fa = new icalActions($options['wpimprov_textarea_ical']);
   $fa->user_load(menu_page_url('wpimprov_ical_events',false),wpimprov_get_loaded(), $options['wpimprov_textarea_tagging']);

}



function wpimprov_load_page() {
    wpimprov_load_facebook(2, true);
}

/* Create table for sources
 */

function wpimprov_install() {
    global $wpdb;
    $wp_improv_version = 7;
    $installed = get_option("wpimprov_db_version");
    if ($installed !== $wp_improv_version) {
        $table_name = $wpdb->prefix . 'wpimpro_sources';
        $table_name_2 = $wpdb->prefix . 'wpimpro_candidates';

        $charset_collate = $wpdb->get_charset_collate();
        $sql=array();
        $sql[] = "
    CREATE TABLE `$table_name` (
    `id` bigint(64) NOT NULL AUTO_INCREMENT,
    `source` varchar(80) NOT NULL,
    `description` varchar(80) ,
    `refreshed` date  NOT NULL DEFAULT '2005-01-01',
  	UNIQUE KEY id (id)
)       $charset_collate;";


    $sql[] = "
    CREATE TABLE `$table_name_2` (
    `id` bigint(64) NOT NULL
  	,`team` int,

)       $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);


        update_option('wpimprov_db_version', $wp_improv_version);
    }
}

register_activation_hook(__FILE__, 'wpimprov_install');

add_action('plugins_loaded', 'wpimprov_install');

/* Will find taxonomy term id based on wpimprov_team post_id
 * @return int Taxonomy id
 * @param int post_id
 */

function wpimprov_taxonomy_from_post($post_id) {
    $existing_terms = get_terms('wpimprov_event_team', array(
        'hide_empty' => false
            )
    );

    foreach ($existing_terms as $term) {
        if ($term->description == $post_id) {
            return $term->term_id;
        }
    }
}

/* Is called on post's save
 * and it will update or create associated term in taxonomy
 */

function wpimprov_update_custom_terms($post_id) {
    // only update terms if it's a post-type-B post
    if ('wpimprov_team' != get_post_type($post_id)) {
        return;
    }
    // don't create or update terms for system generated posts
    if (get_post_status($post_id) == 'auto-draft') {
        return;
    }
    /*
     * Grab the post title and slug to use as the new
     * or updated term name and slug
     */
    $term_title = get_the_title($post_id);
    $term_slug = get_post($post_id)->post_name;

    /*
     * Check if a corresponding term already exists by comparing
     * the post ID to all existing term descriptions.
     */
    $existing_terms = get_terms('wpimprov_event_team', array(
        'hide_empty' => false
            )
    );

    foreach ($existing_terms as $term) {
        if ($term->description == $post_id) {
            //term already exists, so update it and we're done
            wp_update_term($term->term_id, 'wpimprov_event_team', array(
                'name' => $term_title,
                'slug' => $term_slug
                    )
            );
            return;
        }
    }
    /*
     * If we didn't find a match above, this is a new post,
     * so create a new term.
     */
    wp_insert_term($term_title, 'wpimprov_event_team', array(
        'slug' => $term_slug,
        'description' => $post_id
            )
    );
}

add_action('save_post', 'wpimprov_update_custom_terms');

/*
  Save meta value for post
 */

function wpimprov_meta_save($post_id, $key, $new_meta_value) {
    /* Get the meta value of the custom field key. */
    $meta_value = get_post_meta($post_id, $key, true);

    if ($new_meta_value && '' == $meta_value)
        add_post_meta($post_id, $key, $new_meta_value, true);

    /* If the new meta value does not match the old value, update it. */
    elseif ($new_meta_value && $new_meta_value != $meta_value)
        update_post_meta($post_id, $key, $new_meta_value);

    /* If there is no new meta value but an old value exists, delete it. */
    elseif ('' == $new_meta_value && $meta_value)
        delete_post_meta($post_id, $key, $meta_value);
}


add_action('plugins_loaded', 'wpimprov_load_textdomain');
function wpimprov_load_textdomain() {
	load_plugin_textdomain( 'wpimprov', false, dirname( plugin_basename(__FILE__) )  );
}

function wpimprov_responsive_image(){
    ob_start();
    $tn_id = get_post_thumbnail_id( );

    $img = wp_get_attachment_image_src( $tn_id, 'full' );
    $width = $img[1];


    echo '<img src="';
            if($width>300){
                the_post_thumbnail_url("w_300");
            }else{

                the_post_thumbnail_url("full");
            }
            echo '" srcset="';

            the_post_thumbnail_url("full");
            echo " ".$width."w ";


            foreach(array(100,300,800,1200) as $size){
                    if($width>$size){
                        echo ",";
                            the_post_thumbnail_url("w_".$size);
            echo " ".$size."w ";

                }
                }


                echo '" class=img-responsive>';
    return ob_get_clean();
}



add_image_size( "w_100",100);
add_image_size( "w_300",300);
add_image_size( "w_800",800);
add_image_size( "w_1200",1200);
