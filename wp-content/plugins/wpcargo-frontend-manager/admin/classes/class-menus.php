<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class WPCFE_Dashboard_Sidebar_Menu extends Walker_Nav_Menu {
	function start_el(&$output, $item, $depth=0, $args=array(), $id = 0) {
        if( 'nav_menu_item' != $item->post_type ){
            return false;
        }
		$object 		= $item->object;
		$type 			= $item->type;
		$title 			= $item->title;
		$description 	= $item->description;
        $permalink 		= $item->url;
        $parent_menu    = $item->menu_item_parent;
        $menu_icon = get_post_meta( $item->object_id, 'wpcfe_menu_icon', true );
        $active_class = '';
        if( in_array( 'current-menu-item', $item->classes ) ){
            $active_class = " active";
        };
		//Add SPAN if no Permalink
		if( $permalink ) {
			$output .= '<a class="list-group-item list-group-item-action waves-effect '.implode(" ", $item->classes).$active_class.'" href="' . $permalink . '" >';
		} 
        if( $menu_icon && !$parent_menu ){
            $output .= '<i class="'.$menu_icon.'"></i>';
        }
        $output .= $title;		
		if( $permalink ) {
			$output .= '</a>';
		}
	}
}
class WPCFE_Dashboard_Top_Menu extends Walker_Nav_Menu {
    function start_lvl( &$output, $depth = 0, $args = array() ) {
        if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        $indent = str_repeat($t, $depth);
        $output .= "{$n}$indent<div class='dropdown-menu'><ul>{$n}";
    }
    function end_lvl( &$output, $depth = 0, $args = array() ) {
        if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        $indent = str_repeat($t, $depth);
        $output .= "$indent</ul></div>{$n}";
    }
    function start_el(&$output, $item, $depth=0, $args=array(), $id = 0) {
        if( 'nav_menu_item' != $item->post_type ){
            return false;
        }
        $object         = $item->object;
        $type           = $item->type;
        $title          = $item->title;
        $description    = $item->description;
        $permalink      = $item->url;
        $parent_menu    = $item->menu_item_parent;
        $classes        = $item->classes;
        $menu_icon = get_post_meta( $item->object_id, 'wpcfe_menu_icon', true );
        $active_class = '';
        if( in_array( 'current-menu-item', $classes ) ){
            $active_class = " active";
        };
        if( !$parent_menu ){
            // $output .= "<li class='nav-item " .  implode(" ", $classes ) .$active_class."'> ";
            $output .= "<li class='nav-item ".$active_class."'> ";
            if( $permalink ) {
                if( in_array( 'menu-item-has-children', $classes  ) ){
                    $output .= '<a href="' . $permalink . '" class="dropdown-toggle nav-link waves-effect" data-toggle="dropdown" aria-expanded="false">';
                }else{
                    $output .= '<a href="' . $permalink . '" class="nav-link waves-effect">';
                }    
            } 
        }else{
            $output .= '<a href="' . $permalink . '" class="dropdown-item waves-effect">';
        }
        
        if( $menu_icon && !$parent_menu ){
            $output .= '<i class="fa fa-'.$menu_icon.'"></i>';
        }
        if( !$parent_menu ){
            $output .= $title ;
            if( in_array( 'menu-item-has-children', $classes  ) ){
                $output .= '<b class="caret"></b>';
            }
        }else{
            $output .= $title;
        }       
        if( $permalink ) {
            $output .= '</a>';
        }       
    }
}