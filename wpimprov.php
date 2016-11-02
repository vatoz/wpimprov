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

add_action( 'init', 'wpimprov_create_post_type' );

/*
Vytvoří taxonomii pro týmy a samotný typ zápisku tým
Dále vytvoří event
*/
function wpimprov_create_post_type() {

        register_taxonomy(
	'wpimprov_event_team',
	'wpimprov_event',
	array(
                'label' => __( 'Teams','wpimprov' ),
                //'rewrite' => array( 'slug' => 'city' ),
                'show_ui'           => true,
		'show_admin_column' => true,
                'hierarchical'=>true,
                'capabilities' => array(
                    'assign_terms' => 'edit_posts',
                    'edit_terms' => 'administrator'
                ),
	)
	);
        
        register_taxonomy(
	'wpimprov_event_type',
	'wpimprov_event',
	array(
                'label' => __( 'Type of event','wpimprov' ),
                //'rewrite' => array( 'slug' => 'city' ),
                'show_ui'           => true,
		'show_admin_column' => true,
		'hierarchical'=>true,
                'capabilities' => array(
                    'assign_terms' => 'edit_posts',
                    'edit_terms' => 'administrator'
                ),
                
	)
	);
	
	register_post_type( 'wpimprov_team',
		array(
                    'labels' => array(
                    'name' => __( 'Improliga teams','wpimprov' ),
                    'singular_name' => __( 'Improliga team','wpimprov' )
		),
		'taxonomies'=>array(
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
                'taxonomies'=>array(
                        "wpimprov_event_team",
			'wpimprov_event_type'
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
                 $Result[]=new wpimprov_field('wpimprov-team-city',__( "City", 'wpimprov' ),'text');
                 $Result[]=new wpimprov_field('wpimprov-team-refreshed',__( "Refreshed", 'wpimprov' ),'date');
 
    break;
    case "wpimprov_event":
                 $Result[]=new wpimprov_field('wpimprov-event-fb',__( "Facebook event id", 'wpimprov' ),'text');
                 $Result[]=new wpimprov_field('wpimprov-event-start-time',__( "Start time", 'wpimprov' ),'datetime-local');
                 $Result[]=new wpimprov_field('wpimprov-event-end-time',__( "End time", 'wpimprov' ),'datetime-local');
                 $Result[]=new wpimprov_field('wpimprov-event-venue',__( "Venue", 'wpimprov' ),'text');
                 $Result[]=new wpimprov_field('wpimprov-event-ticket-uri',__( "Tickets", 'wpimprov' ),'text');
                 $Result[]=new wpimprov_field('wpimprov-event-geo-latitude',__( "Latitude", 'wpimprov' ),'number');
                 $Result[]=new wpimprov_field('wpimprov-event-geo-longitude',__( "Longitude", 'wpimprov' ),'number');
                 $Result[]=new wpimprov_field('wpimprov-event-source',__( "Source", 'wpimprov' ),'text');
                              
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


add_action( 'wpimprov_cron_hook', 'wpimprov_cron' );


function wpimprov_hook_schedule(){
  //Use wp_next_scheduled to check if the event is already scheduled
  $timestamp = wp_next_scheduled( 'wpimprov_cron_hook' );

  //If $timestamp == false schedule daily backups since it hasn't been done previously
  if( $timestamp == false ){
    //Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
    wp_schedule_event( time(), 'hourly', 'wpimprov_cron_hook' );
  }
}



function wpimprov_cron() {
    wpimprov_load_facebook(5, false);
}

function wpimprov_get_loaded(){
    global $wpdb;    
    //existing  autosaved posts    
    return $wpdb->get_results("SELECT meta_value as id FROM ".$wpdb->prefix ."postmeta where meta_key = 'wpimprov-event-fb'",ARRAY_A);

}



function wpimprov_load_facebook_source($fa,$Source,$Refreshed=null,$Verbose=false,$Taxonomy=0,$Limit=3){
     $options = get_option( 'wpimprov_settings' );
	                
    $mame=  wpimprov_get_loaded(); 
    $saved=0;    
    if($Verbose)echo $Source."<br>"; flush();

        $tmp= $fa->getEvents($Source, $Refreshed);


        if($Verbose) var_export($tmp);

        $tmp2= $fa->getPostsEvents($Source,$Refreshed);
        if($Verbose) var_export($tmp2);
        $events=array_merge($tmp,$tmp2);

        foreach($events as $Id){
            $found=false;
            foreach($mame as $r ){
                    if($r['id']==$Id){ 
                        $found =true; 
                        //echo "uz je v db , ignoruji".RA;
                    }
            }

            if(!$found){
                $fa->wpSaveEvent($Id, $Source,$options['wpimprov_textarea_tagging'],
                        $Taxonomy
                        );
                $mame[]=array("id"=>$Id);
               $saved++;
               if($saved>$Limit) return false;
            }

        }
    return true;
}

function wpimprov_load_facebook($Limit=5,$Verbose=false)
{
        define("VERBOSE",$Verbose);
        $saved=0;
        global $wpdb;   
        
                        
        $options = get_option( 'wpimprov_settings' );
			require_once 'fbActions.php';
			$fa=new fbActions($options['wpimprov_textarea_fb_app_id'],$options['wpimprov_textarea_fb_app_secret'],$options['wpimprov_textarea_fb_token']);
			
                        
                        $zdroje = $wpdb->get_results("select fb.post_id,fb.meta_value as source, dt.meta_value as refreshed from(select * from ".$wpdb->prefix ."postmeta where meta_key='wpimprov-team-fb' ) fb left join (select * from ".$wpdb->prefix ."postmeta where meta_key='wpimprov-team-refreshed') dt  on fb.post_id=dt.post_id    order by refreshed asc limit " . $Limit,ARRAY_A);
			foreach($zdroje as $Zdroj) {
                            if( wpimprov_load_facebook_source($fa,$Zdroj["source"],$Zdroj["refreshed"],$Verbose,wpimprov_taxonomy_from_post($Zdroj['post_id'],$Limit))){
                    
				$wpdb->update($wpdb->prefix ."postmeta",array("meta_value"=>date("Y-m-d")),array('post_id'=>$Zdroj["post_id"],"meta_key"=>"wpimprov-team-refreshed"));
                            }else{
                                return;
                            }        
                                        
				if($Verbose)	echo "<hr>";
			}
                        
                        
                        $zdroje = $wpdb->get_results("SELECT source,refreshed, description, DATEDIFF(now(),refreshed) as old  FROM ".$wpdb->prefix ."wpimpro_sources  order by refreshed asc limit " . $Limit,ARRAY_A);
			
			foreach($zdroje as $Zdroj) {
                            if( wpimprov_load_facebook_source($fa,$Zdroj["source"],$Zdroj["refreshed"],$Verbose,0,$Limit)){
                    
				$wpdb->update($wpdb->prefix ."wpimpro_sources",array("refreshed"=>date("Y-m-d")),array('source'=>$Zdroj["source"]));
                            }else     {
                                return;
                            }   
                                        
				if($Verbose)	echo "<hr>";
			}

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
                'wpimprov_pluginPage_section_main',
                __( 'Main settings', 'wpimprov' ),
                'wpimprov_settings_section_callback',
                'pluginPage'
        );
        
        
        add_settings_section(
                'wpimprov_pluginPage_section_fb',
                __( 'Facebook settings', 'wpimprov' ),
                'wpimprov_settings_section_callback',
                'pluginPage'
        );
        /*
           add_settings_field(
                'wpimprov_textarea_sources',
                __( 'List of sources with legend in form source|description on new lines', 'wpimprov' ),
                'wpimprov_textarea_field_sources_render',
                'pluginPage',
                'wpimprov_pluginPage_section_main'
        );
        */
        add_settings_field(
                'wpimprov_textarea_tagging',
                __( 'List of tag selectors in form string|tag on new lines', 'wpimprov' ),
                'wpimprov_textarea_field_tagging_render',
                'pluginPage',
                'wpimprov_pluginPage_section_main'
        );
        
          add_settings_field(
                'wpimprov_textarea_fb_app_id',
                __( 'Facebook App Id', 'wpimprov' ),
                'wpimprov_textarea_field_fb_app_id_render',
                'pluginPage',
                'wpimprov_pluginPage_section_fb'
        );
        add_settings_field(
                'wpimprov_textarea_fb_app_secret',
                __( 'Facebook App Secret', 'wpimprov' ),
                'wpimprov_textarea_field_fb_app_secret_render',
                'pluginPage',
                'wpimprov_pluginPage_section_fb'
        );
        
        add_settings_field(
                'wpimprov_textarea_fb_token',
                __( 'Facebook token', 'wpimprov' ),
                'wpimprov_textarea_field_fb_token_render',
                'pluginPage',
                'wpimprov_pluginPage_section_fb'
        );
        
        
}


function wpimprov_textarea_field_tagging_render(  ) {

        $options = get_option( 'wpimprov_settings' );
        ?>
        <textarea cols='40' rows='5' name='wpimprov_settings[wpimprov_textarea_tagging]'><?php echo $options['wpimprov_textarea_tagging']; ?></textarea>
        <?php

}

 function wpimprov_textarea_field_sources_render(  ) {

        $options = get_option( 'wpimprov_settings' );
        ?>
        <textarea cols='40' rows='5' name='wpimprov_settings[wpimprov_textarea_sources]'><?php echo $options['wpimprov_textarea_sources']; ?></textarea>
        <?php

}
 

function wpimprov_textarea_field_fb_app_id_render(  ) {

        $options = get_option( 'wpimprov_settings' );
        ?>
        <textarea cols='40' rows='1' name='wpimprov_settings[wpimprov_textarea_fb_app_id]'><?php echo $options['wpimprov_textarea_fb_app_id']; ?></textarea>
        <?php

}


function wpimprov_textarea_field_fb_app_secret_render(  ) {

        $options = get_option( 'wpimprov_settings' );
        ?>
        <textarea cols='40' rows='1' name='wpimprov_settings[wpimprov_textarea_fb_app_secret]'><?php echo $options['wpimprov_textarea_fb_app_secret']; ?></textarea>
        <?php

}


function wpimprov_textarea_field_fb_token_render(  ) {

        $options = get_option( 'wpimprov_settings' );
        ?>
        <textarea cols='40' rows='1' name='wpimprov_settings[wpimprov_textarea_fb_token]'><?php echo $options['wpimprov_textarea_fb_token']; ?></textarea>
        <?php

}


function wpimprov_settings_section_callback(  ) {

        //echo __( 'Automatic assigning of tags', 'wpimprov' );
    
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
                wpimprov_load_facebook(2, true);
       // calendar_from_fb_cron();
        //echo __( 'In near future without tag', 'calendar_from_fb' );
        //echo calendar_from_fb_display_func(array('list'=>'null'));

}





function wpimprov_install() {
	global $wpdb;
	$wp_improv_version=3;
        $installed= get_option("wpimprov_db_version");
        if($installed!==$wp_improv_version){
	$table_name = $wpdb->prefix . 'wpimpro_sources';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "
    CREATE TABLE `$table_name` (
    `id` bigint(64) NOT NULL,
    `source` varchar(80) NOT NULL,
    `description` varchar(80) ,
    `refreshed` date  NOT NULL DEFAULT '2005-01-01',
  	UNIQUE KEY id (id)
)       $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
  
  
	update_option( 'wpimprov_db_version', 
$wp_improv_version );
        }
}


register_activation_hook( __FILE__, 'wpimprov_install' );

add_action('plugins_loaded','wpimprov_install' );

function wpimprov_taxonomy_from_post($post_id){
    
  $existing_terms = get_terms('wpimprov_event_team', array(
    'hide_empty' => false
    )
  );

  foreach($existing_terms as $term) {
    if ($term->description == $post_id) {
        return $term->term_id;
      
    }
  }
    
}

function wpimprov_update_custom_terms($post_id) {
    
  // only update terms if it's a post-type-B post
  if ( 'wpimprov_team' != get_post_type($post_id)) {
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
  $term_slug = get_post( $post_id )->post_name;

  /*
  * Check if a corresponding term already exists by comparing 
  * the post ID to all existing term descriptions. 
  */
  $existing_terms = get_terms('wpimprov_event_team', array(
    'hide_empty' => false
    )
  );

  foreach($existing_terms as $term) {
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


function wpimprov_hierarchy_id_from_fb_id($fb_id){
    global $wpdb;
    $teams = $wpdb->get_results("SELECT post_id  FROM ".$wpdb->prefix ."postmeta where meta_key = 'wpimprov-team-fb' and meta_value = '".$fb_id."' ",ARRAY_A);
    if(isset($teams[0])){
        $post_id= $teams[0]["post_id"];  
         $existing_terms = get_terms('wpimprov_event_team', array(
            'hide_empty' => false
    ));
     
    foreach($existing_terms as $term) {
        if ($term->description == $post_id) {
            return $term->term_id;
        }
    }   
     
   }
   return 0; 
}
