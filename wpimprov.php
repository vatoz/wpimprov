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
			"custom-fields",
		),
		'public' => true,
		'has_archive' => true,
		'menu_icon' => 'dashicons-calendar-alt',
		'rewrite'  => array( 'slug' => 'event' ),
		)
	);	
	
	
}
