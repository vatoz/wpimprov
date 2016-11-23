<?php

require_once 'wpimprov.php';

/*
Will return calendar with upcoming events, filtered by event type
 * call in post by [wpimprov_calendar list="show"]
 *  */
function wpimprov_calender_display( $atts ){
	
        $result="";
	if(!isset($atts["list"])){
		$atts["list"]="";
	}
 		
          
	$date = new DateTime("now");
	if ($date->format('N')>1){
            $date->sub(new DateInterval("P".($date->format('N')-1)."D"));
        }	
       
        $t=term_exists($atts["list"],"wpimprov_event_type");
        
        $args = array(
        'post_type' => 'wpimprov_event',
        'tax_query' => array(
            array(
            'taxonomy' => 'wpimprov_event_type',
            'field' => 'id',
            'terms' =>  $t['term_taxonomy_id']
             )
        ),
        
	'posts_per_page'         => 50,    
        'meta_query'=>array(
            'key' => 'wpimprov-event-start-time',    
            'value'=>$date->format('Y-m-d'),
            'compare'=>'>=',
            
        ),    
        'orderby'  => array( 'meta_value' => 'ASC', 'title' => 'ASC' ),
	'meta_key' => 'wpimprov-event-start-time'    
            
        );
        $query2 = new WP_Query( $args ); 

        $posts_ar=array();   
        if ( $query2->have_posts() ) {
	// The 2nd Loop
	while ( $query2->have_posts() ) {
		$query2->the_post();
                ob_start();
		echo '<div class="wpimprov_event">';
                echo wpimprov_responsive_image();
                echo  '<h3>'.'<a href="'.get_post_permalink($query2->post->ID).'">'.get_the_title( $query2->post->ID ).'</a></h3>' ;
                //$result.=var_export($query2->post,true);
               
                
                $meta=get_post_meta($query2->post->ID, '', true);
                echo  substr($meta['wpimprov-event-start-time'][0],11,5).'<br>';
                
                echo  $meta['wpimprov-event-venue-city'][0].', '.$meta['wpimprov-event-venue'][0].'<br>';
                echo  '</div>';
                $posts_ar[substr( $meta['wpimprov-event-start-time'][0],0,10) ][]=  ob_get_clean();
                //$result.= var_export(get_post_meta($query2->post->ID, '', true),true). '</li>';
	}
        
        
        $result.="<div class=wpimprov_large_calendar>";
	
	$date = new DateTime("now");
	if ($date->format('N')>1){
            $date->sub(new DateInterval("P".($date->format('N')-1)."D"));
        }
	for($i=0;$i<5;$i++){
	$result.="<div class='wpimprov_week '>";
	for($j=0;$j<7;$j++){
            $result.=  "<div class='wpimprov_day'>";		
            $result.= "<h2 >";
            $result.=$date->format('d.m.') ;
            $result.= "</h2>\n";
            //$result.=calendar_from_fb_date($date->format('Y-m-d'),$atts["list"]);
            if(isset($posts_ar[$date->format('Y-m-d')]) ){
                $result.=implode(" ",$posts_ar[$date->format('Y-m-d')]);
               
            }
            
            $result.= "</div>";//day
            $date->add(new DateInterval("P1D"));	
	}
	$result.="</div>";//week
	
	}
	 	$result.="</div>";//calendar
        $result.=<<<style
                <style type="text/css">
@media (min-width: 800px) {
  .wpimprov_day {
        width: 14%; display:block;float:left;
    }
  .wpimprov_week {clear:both} 
      
       .wpimprov_large_calendar {
    font-size:12px;   
   }
  .wpimprov_large_calendar h2{
    font-size:19px;   
   } 
                
  .wpimprov_large_calendar h3{
    font-size:16px;   
   }
                
}</style>
                
style;
                
         return $result;
}
return "no results";
}


function wpimprov_team_calendar($post_id  ){
    
        $future=wpimprov_team_calendar_internal($post_id,true);
        $past=wpimprov_team_calendar_internal($post_id,false);
        
        return '<div>'.
                ($future?"<h1>".__('Future events', 'wpimprov')."</h1>".$future:__('No future events', 'wpimprov'))
                .
                ($past?"<h1>".__('Past events', 'wpimprov')."</h1>".$past:__('No past events', 'wpimprov'))        
                .
                "</div>"  
                ;
}

function wpimprov_team_calendar_internal( $post_id,$future=true ){
		
	$date = new DateTime("now");
	
        
        $args = array(
        'post_type' => 'wpimprov_event',
        'tax_query' => array(
            array(
            'taxonomy' => 'wpimprov_event_team',
            'field' => 'id',
            'terms' => wpimprov_taxonomy_from_post($post_id)
             )
        ),
        
	'posts_per_page'         => 150,    
        'meta_query'=>array(
            'key' => 'wpimprov-event-start-time',    
            'value'=>$date->format('Y-m-d'),
            'compare'=>$future?'>=':'<',
        ),    
        'orderby'  => array( 'meta_value' => $future?'ASC':'DESC', 'title' => 'ASC' ),
	'meta_key' => 'wpimprov-event-start-time'    
        );
        
        $query2 = new WP_Query( $args ); 
        $result="";
           
        if ( $query2->have_posts() ) {
	// The 2nd Loop
	while ( $query2->have_posts() ) {
		$query2->the_post();
                
		$result.='<div class=wpimprov_event>';
                
                $result.=wpimprov_responsive_image();
                
                
                $result.= '<h2>'.'<a href="'.get_post_permalink($query2->post->ID).'">'.get_the_title( $query2->post->ID ).'</a></h2>' ;
                //$result.=var_export($query2->post,true);
               
                
                $meta=get_post_meta($query2->post->ID, '', true);
                $result.= $meta['wpimprov-event-start-time'][0].'<br>';
                
                $result.= $meta['wpimprov-event-venue'][0].','.$meta['wpimprov-event-venue-city'][0].'<br>';
                $result.= '</div>';
                //$result.= var_export(get_post_meta($query2->post->ID, '', true),true). '</li>';
	}
        
         return $result;
}

}



add_shortcode( 'wpimprovcalendar', 'wpimprov_calender_display' );
