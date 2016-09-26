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
*/
function wpimprov_create_post_type() {
	register_taxonomy(
	'wpimprov_city',
	'wpimprov_team',
	array(
			'label' => __( 'City' ),
			//'rewrite' => array( 'slug' => 'city' ),
			'show_ui'           => true,
		'show_admin_column' => true,
		'hierarchical'=>true,
	)
	);
	
	register_post_type( 'wpimprov_team',
		array(
		'labels' => array(
			'name' => __( 'Teams' ),
			'singular_name' => __( 'Team' )
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
		)
	);
}
