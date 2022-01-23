<?php
/**
 * Stahuje Akce z iCalu facebooku
 * @author Vatoz
 *
 */
 if (($loader = require_once __DIR__ . '/vendor/autoload.php') == null)  {
   die('Vendor directory not found, Please run composer install.');
 }

 use ICal\ICal;

class icalActions {
  private $icaldata;
    function __construct( $icalurl){
    $this->icaldata=$icalurl;
   }



   public function user_load($formuri,$mame,$TagHelper){
   echo '<strong>Choose ical events to upload</strong><form action="'.$formuri.'" method=post>' ;

         //$ja=$this->gt('/me',false);

          echo "loading nonduplicate events from ical ".$this->icaldata."<br>";

          $tmpfname = tempnam("/tmp", "ical");
          echo ($tmpfname);
          $k=file_get_contents($this->icaldata);
          file_put_contents($tmpfname,$k);


               try {
                 $ical=new ICal();
                    $ical->initFile($tmpfname);
               } catch (\Exception $e) {
                   die($e);
               }


          foreach ($ical->events() as $event){
            $tid=$event->description;
            $tid=substr($tid, strrpos($tid,"\n")+1);


            if (substr($tid,0,13 )=="https://www.f"){
              preg_match('/(\d+)/', $tid, $matches);
              $tid=$matches[1];

              $ti2=strrpos($event->description,"\n");
              $event->description=substr($event->description,0,$ti2);

            }else{
              echo "Error"."last row does not contain fb";
            }


           if($this->mametest($mame,$tid)){
               echo '<!--uz mame'   .$tid.' -->';
           }else{


          if(isset($_REQUEST['load'][$event->uid])){
           echo '<strong>Saving '.$event->summary.' to db</strong> <br>';



            $this->wpSaveEventFromData($event,$TagHelper,$tid);
          }else{
           echo '<input type=checkbox name="load['.$event->uid.']">'.$event->summary.'    <br>' ;

          //var_export($event,false);
          }
          }

        }
        echo "<input type=submit></form>" ;
        unlink ($tmpfname);
   }




private function mametest($mame,$Id) {
    foreach ($mame as $r) {
            if ($r['id'] == $Id) {
                return true;
                //echo "uz je v db , ignoruji".RA;
            }
        }
        return false;
}

private function ical_date_2_local_date($date){
    if(strlen($date)>10){
        $myDateTime = DateTime::createFromFormat('Ymd\THis', $date);
        //$myDateTime->setTimezone(new DateTimeZone(get_option('timezone_string')));
        return  $myDateTime->format('Y-m-d\TH:i:s');
    }else{
        return "";
    }
}

function wpSaveEventFromData($Akce,$TagHelper,$fbid){

          $data=array();

        $data["keyword"]=$this->key_from_tags($Akce->summary, $TagHelper);
        if ($data["keyword"]=="")   $data["keyword"]=$this->key_from_tags($Akce->description, $TagHelper);

        //$image_id =$this->_wpImage_upload($data["cover"],$data["id"]." - ".$data["name"]);
        //var_export($Akce);


        $post=wp_insert_post(
                array(
                    'post_content'=>$Akce->description,
                    'post_title'=>$Akce->summary,
                    'post_type'=>"wpimprov_event",
                    'post_status'=>'publish',

                    'meta_input'=>array(
                         'wpimprov-event-start-time'=>$this->ical_date_2_local_date( $Akce->dtstart_tz),
                         'wpimprov-event-end-time'=>$this->ical_date_2_local_date($Akce->dtend_tz),
                         'wpimprov-event-fb'=>$fbid,
                         'wpimprov-event-venue'=>$Akce->location,
                         //'wpimprov-event-venue-street'=>$data['street'],
                         //'wpimprov-event-venue-city'=>$data['city'],
                         //'wpimprov-event-ticket-uri'=>$data['ticket_uri'],
                         //'wpimprov-event-geo-latitude'=>$data['latitude'],
                         //'wpimprov-event-geo-longitude'=>$data['longitude']
                         //,                       'wpimprov-event-source'=>$Source,
                         'wpimprov-event-organizer'=> $Akce->organizer_array[0]["CN"]

                    )

                ),false
                );

       $t=term_exists($data["keyword"],"wpimprov_event_type");
       if(is_array($t)){
         wp_set_post_terms( $post, array( $t['term_taxonomy_id']), "wpimprov_event_type", true );
       }

       $t=term_exists($Akce->organizer_array[0]["CN"],"wpimprov_event_team");
       if(is_array($t)){
         wp_set_post_terms( $post, array( $t['term_taxonomy_id']), "wpimprov_event_team", true );
       }



       //set_post_thumbnail($post,$image_id);
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




}
