<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class wpimprov_Sources_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'source',
            'plural' => 'sources',
        ));
    }

    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

   
    function column_source($item)
    {
        // links going to /admin.php?page=[your_plugin_page][&other_params]
        // notice how we used $_REQUEST['page'], so action will be done on curren page
        // also notice how we use $this->_args['singular'] so in this example it will
        // be something like &person=2
        //todo
        $actions = array(
            'edit' => sprintf('<a href="?page=wpimprov_sources_form&id=%s">%s</a>', $item['id'], __('Edit', 'wpimprov')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'wpimprov')),
        );

        return sprintf('%s %s',
            $item['source'],
            $this->row_actions($actions)
        );
    }

 function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpimpro_sources'; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }  

   
    function get_columns()
    {
        $columns = array(
            'source' => __('Source', 'wpimprov'), 
            'refreshed' => __('Refreshed', 'wpimprov'),
            'description' => __('Description', 'wpimprov')
        );
        return $columns;
    }

    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'source' => array('source', true),
            'refreshed' => array('refreshed', false),
            'description' => array('description', false),
        );
        return $sortable_columns;
    }

   


    /**
     * [REQUIRED] This is the most important method
     *
     * It will get rows from database and prepare them to be showed in table
     */
    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpimpro_sources'; // do not forget about tables prefix

        $per_page = 50; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);
        
         // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'source';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
}

/**
 * PART 3. Admin page
 * ============================================================================
 *
 * In this part you are going to add admin page for custom table
 *
 * http://codex.wordpress.org/Administration_Menus
 */


/**
 * List page handler
 *
 * This function renders our custom table
 * Notice how we display message about successfull deletion
 * Actualy this is very easy, and you can add as many features
 * as you want.
 *
 * Look into /wp-admin/includes/class-wp-*-list-table.php for examples
 */
function wpimprov_sources_page_handler()
{
    global $wpdb;

    $table = new wpimprov_Sources_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'wpimprov'), count($_REQUEST['id'])) . '</p></div>';
    }
    ?>
<div class="wrap">

    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Sources', 'wpimprov')?> <a class="add-new-h2"
                                 href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=wpimprov_sources_form');?>"><?php _e('Add new', 'wpimprov')?></a>
    </h2>
    <?php echo $message; ?>

    <form id="sources-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php $table->display() ?>
    </form>

</div>
<?php
}

/**
 * PART 4. Form for adding andor editing row
 * ============================================================================
 *
 * In this part you are going to add admin page for adding andor editing items
 * You cant put all form into this function, but in this example form will
 * be placed into meta box, and if you want you can split your form into
 * as many meta boxes as you want
 *
 * http://codex.wordpress.org/Data_Validation
 * http://codex.wordpress.org/Function_Reference/selected
 */
 


/**
 * Form page handler checks is there some data posted and tries to save it
 * Also it renders basic wrapper in which we are callin meta box render
 */
function wpimprov_sources_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpimpro_sources'; // do not forget about tables prefix

    $message = '';
    $notice = '';

    // this is default $item which will be used for new records
    $default = array(
        'id' => 0,
        'source' => '',
        'refreshed' => '2005-01-01',
        'description' => '',
    );

    if(!isset($_REQUEST['nonce'])){
        $_REQUEST['nonce']="";
    }
    
    // here we are verifying does this request is post back and have correct nonce
    if (wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        // combine our default item with request params
        $item = shortcode_atts($default, $_REQUEST);
        // validate data, and if all ok save item to database
        // if id is zero insert otherwise update
        $item_valid = wpimprov_validate_source($item);
        if ($item_valid === true) {
            if ($item['id'] == 0) {
                unset($item['id']);
                $result = $wpdb->insert($table_name, $item);
                $item['id'] = $wpdb->insert_id;
                if ($result) {
                    $message = __('Item was successfully saved', 'wpimprov');
                } else {
                    $notice = __('There was an error while saving item', 'wpimprov');
                }
            } else {
                $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
                if ($result) {
                    $message = __('Item was successfully updated', 'wpimprov');
                } else {
                    $notice = __('There was an error while updating item', 'wpimprov');
                }
            }
        } else {
            // if $item_valid not true it contains error message(s)
            $notice = $item_valid;
        }
    }
    else {
        // if this is not post back we load item to edit or give new one to create
        $item = $default;
        if (isset($_REQUEST['id'])) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
            if (!$item) {
                $item = $default;
                $notice = __('Item not found', 'wpimprov');
            }
        }
    }

    // here we adding our custom meta box
    add_meta_box('wpimprov_sources_form_meta_box', 'Source data', 'wpimprov_sources_form_meta_box_handler', 'wpimprov_source', 'normal', 'default');

    ?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Source', 'wpimprov')?> <a class="add-new-h2"
                                href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=wpimprov_sources');?>"><?php _e('back to list', 'wpimprov')?></a>
    </h2>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>
    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php /* And here we call our custom meta box */ ?>
                    <?php do_meta_boxes('wpimprov_source', 'normal', $item); ?>
                    <input type="submit" value="<?php _e('Save', 'wpimprov')?>" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>
<?php
}

/**
 * This function renders our custom meta box
 * $item is row
 *
 * @param $item
 */
function wpimprov_sources_form_meta_box_handler($item)
{
    ?>

<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
    <tbody>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="source"><?php _e('Source', 'wpimprov')?></label>
        </th>
        <td>
            <input id="source" name="source" type="text" style="width: 95%" value="<?php echo esc_attr($item['source'])?>"
                   size="50" class="code" placeholder="<?php _e('Faceboook source', 'wpimprov')?>" required>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="description"><?php _e('Description', 'wpimprov')?></label>
        </th>
        <td>
            <input id="description" name="description" type="text" style="width: 95%" value="<?php echo esc_attr($item['description'])?>"
                   size="50" class="code" placeholder="<?php _e('Description', 'wpimprov')?>" >
        </td>
    </tr>
 
       <tr class="form-field">
        <th valign="top" scope="row">
            <label for="refreshed"><?php _e('Refreshed', 'wpimprov')?></label>
        </th>
        <td>
            <input id="refreshed" name="refreshed" type="text" style="width: 95%" value="<?php echo esc_attr($item['refreshed'])?>"
                   size="50" class="code" placeholder="<?php _e('Refreshed', 'wpimprov')?>" required>
        </td>
    </tr>
 
    </tbody>
</table>
<?php
}

/**
 * Simple function that validates data and retrieve bool on success
 * and error message(s) on error
 *
 * @param $item
 * @return bool|string
 */
function wpimprov_validate_source($item)
{
    $messages = array();

    if (empty($item['source'])) $messages[] = __('Source is required', 'wpimprov');
    
    if (empty($item['refreshed'])) $messages[] = __('Refreshed is required', 'wpimprov');
    
    //if (!empty($item['email']) && !is_email($item['email'])) $messages[] = __('E-Mail is in wrong format', 'custom_table_example');
    //if (!ctype_digit($item['age'])) $messages[] = __('Age in wrong format', 'custom_table_example');
    //if(!empty($item['age']) && !absint(intval($item['age'])))  $messages[] = __('Age can not be less than zero');
    if(!empty($item['refreshed']) && !( date("Y-m-d", strtotime($item["refreshed"]))==$item["refreshed"])  ) $messages[] = __('Refreshed must be date');
    //...

    if (empty($messages)) return true;
    return implode('<br />', $messages);
}



