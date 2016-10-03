<?php
/**
 * Plugin Name: Improv events & teams 
 * Plugin URI: https://github.com/vatoz/wpimprov
 * Description: Will display events and teams
 * Version: 0.0.1
 * Author: Vaclav Cerny
 * Author URI: https://www.facebook.com/vaclav.cerny.12
 * License: MIT
 */
 

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
			"custom-fields",
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
}


function wpimprov_event_class_meta_box( $object, $box ) { ?>

  <?php wp_nonce_field( basename( __FILE__ ), 'wpimpro_event_class_nonce' ); ?>

  <p>
    <label for="wpimprov-event-fb"><?php _e( "Facebook event id", 'wpimprov' ); ?></label>
    <br />
    <input class="widefat" type="number" name="wpimprov-event-fb" id="wpimprov-event-fb" value="<?php echo esc_attr( get_post_meta( $object->ID, 'wpimprov-event-fb', true ) ); ?>" size="30" />
  </p>
  
  
  <p>
    <label for="wpimprov-event-start-time"><?php _e( "Event start date time", 'wpimprov' ); ?></label>
    <br />
    <input class="widefat" type="datetime-local" name="wpimprov-event-start-time" id="wpimprov-event-start-time" value="<?php echo esc_attr( get_post_meta( $object->ID, 'wpimprov-event-start-time', true ) ); ?>" size="30" />
  </p>
  
  <p>
    <label for="wpimprov-event-end-time"><?php _e( "Event end date time", 'wpimprov' ); ?></label>
    <br />
    <input class="widefat" type="datetime-local" name="wpimprov-event-end-time" id="wpimprov-event-end-time" value="<?php echo esc_attr( get_post_meta( $object->ID, 'wpimprov-event-end-time', true ) ); ?>" size="30" />
  </p>
  
  
  
  
  
  
<?php }


function wpimprov_meta($post_id, $key){
  $new_meta_value = ( isset( $_POST[$key] ) ?( $_POST[$key] ) : '' );
  /* Get the meta value of the custom field key. */
  $meta_value = get_post_meta( $post_id, $key, true );
  
  if ( $new_meta_value && '' == $meta_value )
    add_post_meta( $post_id, $key, $new_meta_value, true );

  /* If the new meta value does not match the old value, update it. */
  elseif ( $new_meta_value && $new_meta_value != $meta_value )
    update_post_meta( $post_id, $key, $new_meta_value );

  /* If there is no new meta value but an old value exists, delete it. */
  elseif ( '' == $new_meta_value && $meta_value )
    delete_post_meta( $post_id, $key, $meta_value );


}

                                  /* Save the meta box's post metadata. */
function wpimprov_save_post_class_meta( $post_id, $post ) {
                                   error_log(__LINE__);
  /* Verify the nonce before proceeding. */
  if ( !isset( $_POST['wpimpro_event_class_nonce'] ) || !wp_verify_nonce( $_POST['wpimpro_event_class_nonce'], basename( __FILE__ ) ) )
    return $post_id;                    
error_log(__LINE__)                      ;
  /* Get the post type object. */
  $post_type = get_post_type_object( $post->post_type );
                   error_log(__LINE__);
  if($post->post_type!=="wpimprov_event"){
    error_log($post->post_type)       ;
    return $post_id;
  
  }
    error_log(__LINE__) ;
  /* Check if the current user has permission to edit the post. */
  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
    return $post_id;
    error_log(__LINE__);

  wpimprov_meta($post_id,"wpimprov-event-end-time");
  wpimprov_meta($post_id,"wpimprov-event-start-time");
  wpimprov_meta($post_id,"wpimprov-event-fb");
  error_log(__LINE__);
}