<?php
/**
 * Plugin Name: Improv events & teams 
 * Plugin URI: https://github.com/vatoz/wpimprov
 * Description: Will display events and teams
 * Version: 0.0.1
 * Author: Vaclav Cerny
 * Author URI: https://www.facebook.com/vaclav.cerny.12
 * License: MIT
 * Text Domain: wpimprov
 */
require 'wpimprov_field.php';

add_action( 'init', 'wpimprov_create_post_type' );

/*
Vytvoří taxonomii pro týmy a samotný typ zápisku tým
Dále vytvoří event
*/
function wpimprov_create_post_type() {
	register_taxonomy(
	'wpimprov_city',
	'wpimprov_team',
	array(
			'label' => __( 'City','wpimprov' ),
			//'rewrite' => array( 'slug' => 'city' ),
			'show_ui'           => true,
		'show_admin_column' => true,
		'hierarchical'=>true,
	)
	);
	
	register_post_type( 'wpimprov_team',
		array(
		'labels' => array(
			'name' => __( 'Improliga teams','wpimprov' ),
			'singular_name' => __( 'Improliga team','wpimprov' )
		),
		'taxonomies'=>array(
			'wpimprov_city'
			),
		"supports"=>array(
			"title",
			"editor",
			"thumbnail",
			"revisions",
		),
		'public' => true,
		'has_archive' => true,
		'menu_icon' => 'dashicons-universal-access-alt',
		'rewrite'  => array( 'slug' => 'team' ),
		)
	);
	
	register_post_type( 'wpimprov_event',
		array(
		'labels' => array(
			'name' => __( 'Improliga events','wpimprov' ),
			'singular_name' => __( 'Improliga event','wpimprov' )
		),
		"supports"=>array(
			"title",
			"editor",
			"thumbnail",
			"revisions",
		),
		'public' => true,
		'has_archive' => true,
		'menu_icon' => 'dashicons-calendar-alt',
		'rewrite'  => array( 'slug' => 'event' ),
		)
	);	
	
	
}

add_action( 'load-post.php', 'wpimprov_meta_boxes_setup' );
add_action( 'load-post-new.php', 'wpimprov_meta_boxes_setup' );
function wpimprov_meta_boxes_setup() {

  /* Add meta boxes on the 'add_meta_boxes' hook. */
  add_action( 'add_meta_boxes', 'wpimprov_add_post_meta_boxes' );
  /* Save post meta on the 'save_post' hook. */
  add_action( 'save_post', 'wpimprov_save_post_class_meta', 10, 2 );
}

/* Create one or more meta boxes to be displayed on the post editor screen. */
function wpimprov_add_post_meta_boxes() {

  add_meta_box(
    'wpimprov-event-class',      // Unique ID
    esc_html__( 'Event details', 'wpimprov' ),    // Title
    'wpimprov_event_class_meta_box',   // Callback function
    'wpimprov_event',         // Admin page (or post type)
    'side',         // Context
    'default'         // Priority
  );
  
  add_meta_box(
    'wpimprov-team-class',      // Unique ID
    esc_html__( 'Team details', 'wpimprov' ),    // Title
    'wpimprov_team_class_meta_box',   // Callback function
    'wpimprov_team',         // Admin page (or post type)
    'side',         // Context
    'default'         // Priority
  );
  
}
function wpimprov_field_def($content_type){
 $Result=array();
  switch($content_type){
    case "wpimprov_team":
                 $Result[]=new wpimprov_field('wpimprov-team-fb',__( "Facebook id", 'wpimprov' ),'text');
                 $Result[]=new wpimprov_field('wpimprov-team-web',__( "Webpages", 'wpimprov' ),'text');
    
    break;
    case "wpimprov_event":
                 $Result[]=new wpimprov_field('wpimprov-event-fb',__( "Facebook event id", 'wpimprov' ),'text');
                 $Result[]=new wpimprov_field('wpimprov-event-start-time',__( "Start time", 'wpimprov' ),'datetime-local');
                 $Result[]=new wpimprov_field('wpimprov-event-end-time',__( "End time", 'wpimprov' ),'datetime-local');
                 $Result[]=new wpimprov_field('wpimprov-event-venue',__( "Venue", 'wpimprov' ),'text');
                 $Result[]=new wpimprov_field('wpimprov-event-ticket-uri',__( "Tickets", 'wpimprov' ),'text');
                 $Result[]=new wpimprov_field('wpimprov-event-geo-latitude',__( "Latitude", 'wpimprov' ),'number');
                 $Result[]=new wpimprov_field('wpimprov-event-geo-longitude',__( "Longitude", 'wpimprov' ),'number');
                              
    break;
             
  }
  return $Result;

}

function wpimprov_meta_box( $object, $box ,$content_type) { 
   wp_nonce_field( basename( __FILE__ ), 'wpimpro_nonce' ); 
  
  foreach(wpimprov_field_def($content_type) as $field){
    $field->render_editor( $object->ID);
  }
  
}

function wpimprov_event_class_meta_box( $object, $box ) { 
        wpimprov_meta_box( $object, $box,"wpimprov_event");
        }

function wpimprov_team_class_meta_box( $object, $box ) { 
        wpimprov_meta_box( $object, $box,"wpimprov_team");
        }                                    



                                  /* Save the meta box's post metadata. */
function wpimprov_save_post_class_meta( $post_id, $post ) {
  /* Verify the nonce before proceeding. */
  if ( !isset( $_POST['wpimpro_nonce'] ) || !wp_verify_nonce( $_POST['wpimpro_nonce'], basename( __FILE__ ) ) )
    return $post_id;                    

  /* Get the post type object. */
  $post_type = get_post_type_object( $post->post_type );

  /* Check if the current user has permission to edit the post. */
  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
    return $post_id;
  
  
  foreach(wpimprov_field_def($post->post_type) as $field){
    $field->save_from_post( $post_id);
  }
    
}


register_activation_hook( __FILE__, 'wpimprov_hook_schedule' );


add_action( 'wpimprov_hook', 'wpimprov_cron' );


function wpimprov_hook_schedule(){
  //Use wp_next_scheduled to check if the event is already scheduled
  $timestamp = wp_next_scheduled( 'wpimprov_cron' );

  //If $timestamp == false schedule daily backups since it hasn't been done previously
  if( $timestamp == false ){
    //Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
    wp_schedule_event( time(), 'daily', 'wpimprov_cron' );
  }
}

function wpimprov_cron() {
         //wpimprov_repair(true);
         //wpimprov_repair(false);
}

if(is_admin()){
        add_action( 'admin_menu', 'wpimprov_add_admin_menu' );
 
        add_action( 'admin_init', 'wpimprov_settings_init' );

}
function wpimprov_add_admin_menu(  ) {

        add_menu_page( 'wpimprov', 'wpimprov', 'manage_options', 'wpimprov', 'wpimprov_options_page' );

}


function wpimprov_settings_init(  ) {

        register_setting( 'pluginPage', 'wpimprov_settings' );

        add_settings_section(
                'wpimprov_pluginPage_section',
                __( 'My settings', 'wpimprov' ),
                'wpimprov_settings_section_callback',
                'pluginPage'
        );

        add_settings_field(
                'wpimprov_textarea_field_0',
                __( 'List of tag selectors in form string|tag on new lines', 'wpimprov' ),
                'wpimprov_textarea_field_0_render',
                'pluginPage',
                'wpimprov_pluginPage_section'
        );


}


function wpimprov_textarea_field_0_render(  ) {

        $options = get_option( 'wpimprov_settings' );
        ?>
        <textarea cols='40' rows='5' name='wpimprov_settings[wpimprov_textarea_field_0]'><?php echo $options['wpimprov_textarea_field_0']; ?></textarea>
        <?php

}


function wpimprov_settings_section_callback(  ) {

        echo __( 'Automatic assigning of tags', 'wpimprov' );

}


function wpimprov_options_page(  ) {

        ?>
        <form action='options.php' method='post'>

                <h2>wpimprov</h2>

                <?php
                settings_fields( 'pluginPage' );
                do_settings_sections( 'pluginPage' );
                submit_button();
                ?>

        </form>     <br>



        <?php
       // calendar_from_fb_cron();
        //echo __( 'In near future without tag', 'calendar_from_fb' );
        //echo calendar_from_fb_display_func(array('list'=>'null'));

}





