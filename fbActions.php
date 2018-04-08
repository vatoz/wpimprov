<?php
/**
 * Stahuje Akce z facebooku
 * @author Vatoz
 * 
 */
if (($loader = require_once __DIR__ . '/vendor/autoload.php') == null)  {
  die('Vendor directory not found, Please run composer install.');
}

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

class fbActions {
    public $statRequests=0;
    private $fb;
   
    function __construct( $app_id,$app_secret,$token){
    $this->fb=new Facebook\Facebook([
    'app_id' => $app_id,
    'app_secret' => $app_secret,
    'default_graph_version' => 'v2.12',
    'default_access_token' => $token,
    ]);
    
   } 

    
/* Nahraje data z fb
 */
private function gt($Request, $print=true){
    if(VERBOSE) echo "GT: ".$Request."<br>";
    global $statRequests;
    $statRequests++;
  
    try {
   $me = $this->fb->get( $Request);
   $me=$me->getDecodedBody();
  //echo $me->getName();
 // var_export($me);
  $me=(array)$me;
  //var_export($me);
	if ($print){var_export($me);}
  return $me;
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

}


private function fb_date_2_local_date($date){
    if(strlen($date)>10){        
        $myDateTime = DateTime::createFromFormat('Y-m-d\TH:i:sO', $date);
        //$myDateTime->setTimezone(new DateTimeZone(get_option('timezone_string')));
        return  $myDateTime->format('Y-m-d\TH:i:s');
    }else{
        return "";
    }
} 
 /*
Načte z Facebooku jednu událost a uloží jí do databáze
*/

function wpSaveEvent($Id,$Source,$TagHelper, $TeamHierarchy=0){
	if(VERBOSE) echo "saveEvent: ".$Id."<br>";
          $Akce=$this->gt('/'.$Id,false);
        $tmp2=$this->gt('/'.$Id.'?fields=cover,ticket_uri,place',false);        
        if(isset($tmp2['ticket_uri'])){
            $Akce['ticket_uri']=$tmp2['ticket_uri'] ;
        }else{
            $Akce['ticket_uri']="";
        }
        
        $tmp2cover=(array)$tmp2['cover']; 
        $Akce['cover']=$tmp2cover['source'] ;
    
        $data=array();
        foreach(array('id','name','description','ticket_uri','start_time','end_time','cover') as $Key){
            if(isset($Akce[$Key]))  $data[$Key]=$Akce[$Key];            
        }
        
        
        $tmpplace=(array)$tmp2['place'];
        
        $tmp3=(array)$tmpplace['location'];
        $data['venue']= $tmpplace['name'];
        $data["street"]=$tmp3['street'];
        $data["city"]=$tmp3['city'];
        $data['latitude']=$tmp3['latitude'];
        $data['longitude']=$tmp3['longitude'];
 
        $data["source"]=$Source;
        
        $data["keyword"]=$this->key_from_tags($data["name"], $TagHelper);
        if ($data["keyword"]=="")   $data["keyword"]=$this->key_from_tags($data["description"], $TagHelper);
        
        $image_id =$this->_wpImage_upload($data["cover"],$data["id"]." - ".$data["name"]);

        if(!isset($Akce['event_times'])){
            $Akce['event_times'][]=$Akce;
            if(VERBOSE) echo 'single<br>';    
        }else{
            if(VERBOSE) echo 'multiple<br>';
        }
        foreach($Akce['event_times'] as $event_time){
        $post=wp_insert_post(
                array(
                    'post_content'=>$data['description'],
                    'post_title'=>$data['name'],
                    'post_type'=>"wpimprov_event",
                    'post_status'=>'publish',
                
                    'meta_input'=>array(
                         'wpimprov-event-start-time'=>$this->fb_date_2_local_date($event_time['start_time']),
                         'wpimprov-event-end-time'=>$this->fb_date_2_local_date($event_time['end_time']),      
                         'wpimprov-event-fb'=>$Akce['id'],
                         'wpimprov-event-venue'=>$data['venue'],
                        'wpimprov-event-venue-street'=>$data['street'],
                        'wpimprov-event-venue-city'=>$data['city'],
                         'wpimprov-event-ticket-uri'=>$data['ticket_uri'],
                         'wpimprov-event-geo-latitude'=>$data['latitude'],
                         'wpimprov-event-geo-longitude'=>$data['longitude'],
                         'wpimprov-event-source'=>$Source,
                    )
                    
                ),false
                );
       
       $t=term_exists($data["keyword"],"wpimprov_event_type");
       if(is_array($t)){
         wp_set_post_terms( $post, array( $t['term_taxonomy_id']), "wpimprov_event_type", true );  
       }
       
       
       if($TeamHierarchy>0){
            wp_set_post_terms( $post, array( $TeamHierarchy), "wpimprov_event_team", true );  
       }
       set_post_thumbnail($post,$image_id);    
        }
       }
function _wpImage_upload($url, $Description){        
        // Need to require these files
	if ( !function_exists('media_handle_upload') ) {
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
	}
        $tmp = download_url( $url );
	if( is_wp_error( $tmp ) ){
		return 0;// download failed, handle error
	}
	$desc = $Description;;
	$file_array = array();

	// Set variables for storage
	// fix file filename for query strings
	preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
	$file_array['name'] = basename($matches[0]);
	$file_array['tmp_name'] = $tmp;

	// If error storing temporarily, unlink
	if ( is_wp_error( $tmp ) ) {
		@unlink($file_array['tmp_name']);
		$file_array['tmp_name'] = '';
	}

	// do the validation and storage stuff
	$id = media_handle_sideload( $file_array, 0, $desc );

	// If error storing permanently, unlink
	if ( is_wp_error($id) ) {
		@unlink($file_array['tmp_name']);
		return $id;
	}
        return $id;
        
}

function key_from_tags($text,$tags){
	$deb=0;
	$definiceSeznam=explode("\n", $tags);
	global $wpdb	; 
        foreach($definiceSeznam as $definice){
			
			$definice=explode("|", $definice);
			if(count($definice)==2){
			if($deb) echo ".";
				if(stripos( $text, $definice[0])!==false){
					return $definice[1];
				}
			}
		}	
	return "";
}



 /*
Načte z Facebooku  události sdílené stránkou
  * @todo test
  *   */
function getEvents($Page,$Since="now"){
		
    $t=$this->gt("me");
    var_export($t);
    
    if(VERBOSE) echo "getEvents ".$Page."<br>";
    if($Since=="now"){
        $date = new DateTime("now");
         
         $inte=new DateInterval("P10D");
         $inte->invert =1;
         $date->add($inte);
         $Since=   $date->format('Y-m-d');
    }

    $tmp= $this->gt('/'.$Page.'/events?limit=100&since='.$Since,false);
    //echo '/'.$Page.'/events?limit=100&since='.$Since;
    $Result=array();
    //var_export($tmp);
    //echo "<br><br>";
    foreach($tmp['data'] as $Row){
        $Row=(array)$Row;
        $Result[]=$Row['id'];

    }

return $Result;
}

function getPostsEvents($Page,$Since="now"){
		if(VERBOSE) echo "getPostsEvents: ".$Page."<br>";
    if($Since=="now"){
        $date = new DateTime("now");
         
         $inte=new DateInterval("P10D");
         $inte->invert =1;
         $date->add($inte);
         $Since=   $date->format('Y-m-d');
    }

    $tmp= $this->gt('/'.$Page.'/posts?limit=100&since='.$Since."&fields=link",false);
    $Result=array();
    foreach($tmp['data'] as $Row){
        $Row=(array)$Row;
        if(isset($Row["link"])){
            if(substr($Row["link"],0, 32)=="https://www.facebook.com/events/"){
            		$t=substr($Row["link"], 32,-1);
            		if(strpos($t,"permalink")===false){
            			$Result[]=$t;
            			if (VERBOSE) echo __LINE__.":".$t."<br>";
								} else{
										if (VERBOSE) echo "Wrong: ".$Row["link"]."<br>";	
								}
               
              
              
     
            }
        
        }
        
    }

return $Result;
}


}
