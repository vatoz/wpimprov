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
        
	'posts_per_page'         => 100,    
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
                echo wpimprov_date_hours($meta['wpimprov-event-start-time'][0]).'<br>';
                
                
                echo  $meta['wpimprov-event-venue-city'][0].', '.$meta['wpimprov-event-venue'][0].'<br>';
                echo  '</div>';
                $posts_ar[substr( $meta['wpimprov-event-start-time'][0],0,10) ][]=  ob_get_clean();
                //$result.= var_export(get_post_meta($query2->post->ID, '', true),true). '</li>';
	}
        
        if(!isset($atts["hideempty"])){
             $result.='<div class="wpimprov_large_calendar wpimprov_calendar">';
        }else{
             $result.='<div class="wpimprov_hideempty_calendar wpimprov_calendar">';
        }
       
	$date = new DateTime("now");
	if ($date->format('N')>1){
            $date->sub(new DateInterval("P".($date->format('N')-1)."D"));
        }
	for($i=0;$i<(isset($atts["weeks"])?intval($atts["weeks"]):5);$i++){
	$result.="<div class='wpimprov_week '>";
	for($j=0;$j<7;$j++){
             if((!isset($atts["hideempty"]))||isset($posts_ar[$date->format('Y-m-d')])){
         
            $result.=  "<div class='wpimprov_day'>";		
            $result.= "<h3>";
            $result.="<span class=dow>".date_i18n ("l", $date->getTimestamp())." </span>" ;
            $result.=trim($date->format('d.'),"0"). trim($date->format('m.'),'0') ;
            $result.= "</h3>\n";
            //$result.=calendar_from_fb_date($date->format('Y-m-d'),$atts["list"]);
            if(isset($posts_ar[$date->format('Y-m-d')]) ){
                $result.=implode(" ",$posts_ar[$date->format('Y-m-d')]);
               
            }
            
            $result.= "</div>";//day
            }
            $date->add(new DateInterval("P1D"));	
	}
	$result.="</div>";//week
	
	}
	 	$result.="</div>";//calendar
        
        $result.="<div class=wpimprov_after_calendar style='clear:both;'></div>";        
        
         return $result;
}
return "no results";
}

function wpimprov_date_nice($date){
    return wpimprov_date_dmy( $date)." ".wpimprov_date_hours( $date) ;
}
function wpimprov_date_hours($date){
    $date=  substr($date,11,5);
    if($date[0]==='0'){//trim trailing zero
        $date=substr($date,1);
    }
    return $date;
}
function wpimprov_date_dmy($date){
    $date=
            trim(substr($date,8,2).'.','0').
            trim(substr($date,5,2).'.','0').
            substr($date,0,4);
            
            
    
    return $date;
}



function wpimprov_teams_display($atts ){
	
        $result="";
 		
          
        $args = array(
        'post_type' => 'wpimprov_team',
        
	'posts_per_page'         => 50,  
        'orderby'  => array( 'meta_value' => 'ASC', 'title' => 'ASC' ),
	'meta_key' => 'wpimprov-team-city'    
            
        );
        $query2 = new WP_Query( $args ); 

        $posts_ar=array();   
        if ( $query2->have_posts() ) {
	// The 2nd Loop
	while ( $query2->have_posts() ) {
		$query2->the_post();
                ob_start();
		echo '<div class="wpimprov_team">';
                echo wpimprov_responsive_image();
                echo  '<h3>'.'<a href="'.get_post_permalink($query2->post->ID).'">'.get_the_title( $query2->post->ID ).'</a></h3>' ;
                //$result.=var_export($query2->post,true);
               
                
                $meta=get_post_meta($query2->post->ID, '', true);
                
                echo  '</div>';
                $posts_ar[$meta['wpimprov-team-city'][0]][]=  ob_get_clean();
                //$result.= var_export(get_post_meta($query2->post->ID, '', true),true). '</li>';
	}
        
        
        $result.="<div class=wpimprov_teams>";
	
        foreach ($posts_ar as $City=>$Teams){
            
        $result.="<div class=wpimprov_city>";
	
        $result.="<h2>".$City."</h2>";
        foreach($Teams as $Team){
            $result.=$Team;    
        }
	$result.="</div>";
            
            
        }
	
	 	$result.="</div>";
       
                
         return $result;
}
return "no results";
}

function wpimprov_teams_map_display($atts ){
    ob_start();
        $result="";
 		
          
        $args = array(
        'post_type' => 'wpimprov_team',
        
	'posts_per_page'         => 90,  
        'meta_key' => 'wpimprov-team-city'    
            
        );
        $query2 = new WP_Query( $args ); 

        $posts_ar=array();   
        if ( $query2->have_posts() ) {
echo '
	<link rel="stylesheet" href="'.plugin_dir_url(__FILE__).'map/leaflet.css" />
	<script src="'.plugin_dir_url(__FILE__).'map/leaflet.js"></script>
	
	<link rel="stylesheet" href="'.plugin_dir_url(__FILE__).'map/MarkerCluster.css" />
	<link rel="stylesheet" href="'.plugin_dir_url(__FILE__).'map/MarkerCluster.Default.css" />
	<script src="'.plugin_dir_url(__FILE__).'map/leaflet.markercluster-src.js"></script>
<div id="map"></div>
<script type="text/javascript">
';


            
$results=array();
// The 2nd Loop
	while ( $query2->have_posts() ) {
		$query2->the_post();
                $row=array();
                
                
                $meta=get_post_meta($query2->post->ID, '', true);
                
                if(isset($meta['wpimprov-team-geo-latitude'][0])){
                    $row[0]=$meta['wpimprov-team-geo-latitude'][0];
                }else{
                    $row[0]=50;
                }
                
                if(isset($meta['wpimprov-team-geo-longitude'][0])){
                    $row[1]=$meta['wpimprov-team-geo-longitude'][0];
                }else{
                    $row[1]=15;
                }
                
                $row[2]=get_the_title( $query2->post->ID );
                ob_start();
                the_post_thumbnail_url("w_100");
                $row[3]=  ob_get_clean();
                $row[4]=get_post_permalink($query2->post->ID);
		
                
                $results[]=$row;
                
            }
        
        
        echo "var teams=".  json_encode($results).";";
	
        
        echo '
	</script>
        <script src="'.plugin_dir_url(__FILE__).'map/display.js"></script>
	';
	
	 return ob_get_clean();
       
            
}
return "no results";
}

function wpimprov_team_calendar($post_id  ){
    
        $future=wpimprov_team_calendar_internal($post_id,true);
        $past=wpimprov_team_calendar_internal($post_id,false);
        
        $result= '<div class="team_calendar">'.
                ($future?"<h1 id=team_future >".__('Future events', 'wpimprov')."</h1>".$future:'<span id=no_future>'.__('No future events', 'wpimprov').'</span>')
                .
                ($past?"<h1 id=team_past>".__('Past events', 'wpimprov')."</h1>".$past:'<span=no_past>'.__('No past events', 'wpimprov').'</span>')        
                .
                "</div>"  
                ;
         
          return $result;
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
        $result="<div class=wpimprov_".($future?'future':'past').">";
           
        if ( $query2->have_posts() ) {
	// The 2nd Loop
	while ( $query2->have_posts() ) {
		$query2->the_post();
                
		$result.='<div class=wpimprov_event>';
                if($future){        
                    $result.=wpimprov_responsive_image();
                }
                
                $result.= '<h2>'.'<a href="'.get_post_permalink($query2->post->ID).'">'.get_the_title( $query2->post->ID ).'</a></h2>' ;
                //$result.=var_export($query2->post,true);
               
                
                $meta=get_post_meta($query2->post->ID, '', true);
                if($future){        
                    $result.= wpimprov_date_nice($meta['wpimprov-event-start-time'][0]).'<br>';
                
                }else{
                    
                    $result.= wpimprov_date_dmy($meta['wpimprov-event-start-time'][0]).'<br>';
                
                }
                
                $result.= trim($meta['wpimprov-event-venue'][0].','.$meta['wpimprov-event-venue-city'][0],",").'<br>';
                $result.= '</div>';
                //$result.= var_export(get_post_meta($query2->post->ID, '', true),true). '</li>';
	}
        $result.= '</div>';
         return $result;
}

}

function wpimprov_list_display( $atts ){
	
        $result="<textarea rows=15 cols=150>";
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
        
	'posts_per_page'         => 100,    
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
                $meta=get_post_meta($query2->post->ID, '', true);
                
                echo  '* @'. $meta['wpimprov-event-source'][0].' ('.get_the_title( $query2->post->ID ).') - ';
                
                echo  $meta['wpimprov-event-venue-city'][0].', '.$meta['wpimprov-event-venue'][0];
                
       
                echo' - ' ;
                //$result.=var_export($query2->post,true);
               
                
                echo wpimprov_date_hours($meta['wpimprov-event-start-time'][0]);
                
                
                echo  "\n";
                $posts_ar[substr( $meta['wpimprov-event-start-time'][0],0,10) ][]=  ob_get_clean();
                //$result.= var_export(get_post_meta($query2->post->ID, '', true),true). '</li>';
	}
        
       
	$date = new DateTime("now");
	if ($date->format('N')>1){
            $date->sub(new DateInterval("P".($date->format('N')-1)."D"));
        }
	for($i=0;$i<(isset($atts["weeks"])?intval($atts["weeks"]):5);$i++){
	$result.="\n";
	for($j=0;$j<7;$j++){
             if((!isset($atts["hideempty"]))||isset($posts_ar[$date->format('Y-m-d')])){
         
            $result.=date_i18n ("l", $date->getTimestamp())." " ;
            $result.=trim($date->format('d.'),"0"). trim($date->format('m.'),'0')."\n" ;
            if(isset($posts_ar[$date->format('Y-m-d')]) ){
                $result.=implode(" ",$posts_ar[$date->format('Y-m-d')]);
               
            }
            }
            $date->add(new DateInterval("P1D"));	
	$result.="\n";//week
	
            
            }
	$result.="\n";//week
	
	}
	 	
        $result.="</textarea>";        
        
         return $result;
}
return "no results";
}



add_shortcode( 'wpimprov_calendar', 'wpimprov_calender_display' );

add_shortcode( 'wpimprov_teams' , 'wpimprov_teams_display' );

add_shortcode( 'wpimprov_teams_map' , 'wpimprov_teams_map_display' );
