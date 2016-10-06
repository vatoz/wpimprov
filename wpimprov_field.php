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
    <input class="widefat" type="<?php echo $this->field_type; ?>" name="<?php echo $this->key; ?>" id="<?php echo $this->key; ?>" value="<?php echo esc_attr( get_post_meta( $post_id,  $this->key , true ) ); ?>" size="30" />
  </p>

<?php
}

function save_from_post($post_id){
  $new_meta_value = ( isset( $_POST[$this->key] ) ?( $_POST[$this->key] ) : '' );
  /* Get the meta value of the custom field key. */
  $meta_value = get_post_meta( $post_id, $this->key, true );
  
  if ( $new_meta_value && '' == $meta_value )
    add_post_meta( $post_id, $this->key, $new_meta_value, true );

  /* If the new meta value does not match the old value, update it. */
  elseif ( $new_meta_value && $new_meta_value != $meta_value )
    update_post_meta( $post_id, $this->key, $new_meta_value );

  /* If there is no new meta value but an old value exists, delete it. */
  elseif ( '' == $new_meta_value && $meta_value )
    delete_post_meta( $post_id, $this->key, $meta_value );
  }

}