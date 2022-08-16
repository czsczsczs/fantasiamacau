<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$current_action = $current_id = $page_id = '';
// handle action from url
if (isset($_GET['action']) ) {
	$current_action = sanitize_text_field($_GET['action']);
}

if (isset($_GET['bookId']) ) {
	$current_id = sanitize_text_field($_GET['bookId']);
}

if (isset($_GET['pageId']) ) {
	$page_id = sanitize_text_field($_GET['pageId']);
}

$url=admin_url( "admin.php?page=real3d_flipbook_admin" );

$reak3dflipbooks_converted = get_option("reak3dflipbooks_converted");

if(!$reak3dflipbooks_converted){

	$flipbooks = get_option("flipbooks");
	if(!$flipbooks){
		$flipbooks = array();
	}

	add_option('reak3dflipbooks_converted', true);
	$real3dflipbooks_ids = array();

	foreach ($flipbooks as $b) {
		$id = $b['id'];
		//trace($id);
		delete_option('real3dflipbook_'.(string)$id);
		add_option('real3dflipbook_'.(string)$id, $b);
		array_push($real3dflipbooks_ids,(string)$id);
	}

}else{

	$real3dflipbooks_ids = get_option('real3dflipbooks_ids');
	if(!$real3dflipbooks_ids){
		$real3dflipbooks_ids = array();
	}
	$flipbooks = array();
	foreach ($real3dflipbooks_ids as $id) {
		// trace($id);
		$book = get_option('real3dflipbook_'.$id);
		if($book){
			$flipbooks[$id] = $book;
			// array_push($flipbooks,$book);
		}else{
			//remove id from array
			$real3dflipbooks_ids = array_diff($real3dflipbooks_ids, array($id));
		}
	}
}

update_option('real3dflipbooks_ids', $real3dflipbooks_ids);

switch( $current_action ) {

	case 'edit':

		include("edit-flipbook.php");
		break;
		
	case 'add_new':

		$new_id = 0;
		$highest_id = 0;

		foreach ($real3dflipbooks_ids as $id) {
			if((int)$id > $highest_id) {
				$highest_id = (int)$id;
			}
		}

		$current_id = $highest_id + 1;
		//create new book 
		$book = array(	
			"id" => $current_id, 
			"name" => "flipbook " . $current_id,
			"pages" => array(),
			"date" => current_time( 'mysql' ),
			"status" => "draft"
		);
		$flipbooks[$current_id] = $book;

		include("edit-flipbook.php");
		break;
		
	case 'generate_json':
		include("flipbooks.php");
		break;
	
	case 'import_from_json':
		include("flipbooks.php");
		break;
	
	case 'import_from_json_confirm':

	// trace($_POST);

		//backup
		delete_option('real3dflipbooks_ids_back');
		add_option('real3dflipbooks_ids_back',$real3dflipbooks_ids);

		//delete all flipbooks
		foreach ($real3dflipbooks_ids as $id) {
			delete_option('real3dflipbook_'.(string)$id);
		}

		$json = stripslashes($_POST['flipbooks']);

		// trace($json);

		$newFlipbooks = r3dfb_objectToArray(json_decode($json));

		// trace(($newFlipbooks));

		if((string)$json != "" && is_array($newFlipbooks)){
			$real3dflipbooks_ids = array();

			foreach ($newFlipbooks as $b) {
				$id = $b['id'];

				add_option('real3dflipbook_'.(string)$id, $b);
				array_push($real3dflipbooks_ids,(string)$id);
			}
			
			update_option('real3dflipbooks_ids', $real3dflipbooks_ids);
			$flipbooks = $newFlipbooks;
		}
		
		include("flipbooks.php");
		break;
	
	default:

		include("flipbooks.php");
		break;
		
}


if(!function_exists("r3dfb_objectToArray")){

	function r3dfb_objectToArray($d) {
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}

		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map(__FUNCTION__, $d);
		}
		else {
			// Return array
			return $d;
		}
	}

}


