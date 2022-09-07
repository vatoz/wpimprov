<?php
class wpimprov_field{
    public $key;
    public $description;
    public $field_type;
    
    function __construct($key,$description,$field_type){
      $this->key=$key;
      $this->description=$description;
      $this->field_type=$field_type;
    }

    function render_editor($post_id) {    
    ?>
    <p>
        <label for="<?php echo $this->key; ?>"><?php echo $this->description; ?></label>
        <br />
        <input class="widefat" type="<?php
        echo $this->field_type; 
        ?>" name="<?php 
        echo $this->key; 
        ?>" id="<?php 
        echo $this->key; 
        ?>" <?php
        if($this->field_type=="checkbox"){            
            echo ' value="1" ';
            if(get_post_meta( $post_id,  $this->key , true ) == 1)  echo " checked  ";
        }else{
        ?>
        value="<?php 
        echo esc_attr( get_post_meta( $post_id,  $this->key , true ) ); 
        ?>" size="30" <?php                    
        }
        ?>
        />
    </p>
    <?php
    }

    function save_from_post($post_id){
      $new_meta_value = ( isset( $_POST[$this->key] ) ?( $_POST[$this->key] ) : '' );
       return wpimprov_meta_save($post_id,$this->key,$new_meta_value);  
    }

}
