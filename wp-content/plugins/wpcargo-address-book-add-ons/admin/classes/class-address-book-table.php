<?php
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Address_Book_List_Table extends WP_List_Table {
	
	var $text_domain = 'wpcargo-address-book';
	
	var $data;
    function __construct( $data = array() ){
        global $status, $page;
		
		$this->data = $data;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'book',     //singular name of the listed records
            'plural'    => 'books',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    function column_default($item, $column_name){
        switch($column_name){
            case 'title':
			case 'viewbook':
            case 'role':
			case 'shipper':
			case 'receiver':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    function get_columns(){
        $columns = array(
            //'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'		=> __( 'User Name', 'wpcargo-address-book' ),
			'viewbook'	=> __( 'View Book Address', 'wpcargo-address-book'),
            'role'		=> __( 'Role', 'wpcargo-address-book'),
            'shipper'  	=> __( 'No. Shipper Address', 'wpcargo-address-book'),
			'receiver'	=> __( 'No. Receiver Address', 'wpcargo-address-book'),
        );
        return $columns;
    }
    function get_sortable_columns() {
        $sortable_columns = array(
            'title'	=> array('title',false),     //true means it's already sorted
            'role'	=> array('role',false),
        );
        return $sortable_columns;
    }
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries
        $per_page = 12;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
        $data = $this->data;
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');
		
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
}
?>