<?php 
/* Template Name: api */

global $post;


// output forum stats in json format
if ( isset( $_REQUEST['forum_stats'] ) && function_exists('bbp_get_statistics') ) {
	$stats = bbp_get_statistics();
	echo json_encode($stats);
	die;
}



/**
 * Returns version number for changelog by id
 * @param  string $post_id post id for changelog
 * @return string          
 */
function axiom_get_changelog_version($post_id){
	$post    = get_post( $post_id );
	$post_id = $post->ID;

	$version = get_post_meta( $post_id, 'release_version', true );
	return axiom_plugin_get_verified_version( $version );
}


/**
 * convert version number to standard version format (x.x.x)
 * @param  string $version version number
 * @return string          standard version
 */
function axiom_plugin_get_verified_version($version){

	if( strlen($version) < 5 ) {
		// extract version numbers in array
		$v_parts = explode( ".", $version );
		// remove empty indexes in array
		$v_parts = array_filter($v_parts);
		// version number contains 3 integer. count the missed nums
		$less 	 = 3 - count($v_parts);
		// add "0" if version number count is less that 3
		for ($i=0; $i < $less; $i++) { 
			$v_parts[] = "0";
		}
		// join version numbers by "."
		$version = implode( ".", $v_parts );
	}
	// return valid and standard version number
	return $version;
}



if ( isset( $_REQUEST['log'] ) ) {

	// get changelog category slug
	$log_cat = esc_sql( $_REQUEST['log'] );

    $tax_args = array('taxonomy' => 'changelog-cat', 'terms' => $log_cat, 'field' => 'slug' );
    
    // create wp_query to get all logs
    $args = array(
      'post_type'			=> 'changelog',
      'orderby'				=> "menu_order date",
      'post_status'			=> 'publish',
      'posts_per_page'		=> -1,
      'ignore_sticky_posts'	=> 1,
      'paged'				=> 1,
      'tax_query'			=> array( $tax_args )
    );


    // The Query
    $log_query = null;
	$log_query = new WP_Query( $args );

	// stores latest version data
	$latest_version 	 = '';
	$latest_release_date = '';
	$latest_changelog	 = '';

	// stores all log contents
	$output = array();
	$error  = array();

	// loop through all changelogs
	if ( $log_query->have_posts() ) {

		while ( $log_query->have_posts() ) {

			$log_query->the_post();
			
			// get current changelog version 
	    	$version 		= axiom_get_changelog_version( $log_query->post->ID );
	    	// get current changelog release date
	    	$release_date 	= get_post_meta( $log_query->post->ID, 'release_date', true );
	    	// get short changelog
	    	$excerpt 		= get_the_excerpt( $log_query->post->ID );
	    	// get changelog single page url if requested
	    	$permalink		= isset( $_REQUEST['pl'] )?'<a href="' . get_permalink( $log_query->post->ID ) . '" target="_blank" title="View full changelog" >#</a>' :'';


	    	// store date for latest version 
	    	if ( empty( $latest_version ) ) 	 $latest_version 	  = $version;
	    	if ( empty( $latest_release_date ) ) $latest_release_date = $release_date;
	    	if ( empty( $latest_changelog ) ) 	 $latest_changelog    = $excerpt;


	    	$log_content 	= "";

	    	if( isset( $_REQUEST['latest'] )  || isset( $_REQUEST['l'] ) ) {
	    		$log_content	= array( 'release_date' => $release_date, 'version' => $version, 'changelog' => $excerpt );

	    		$output		= $log_content;
	    		break;

	    	} elseif ( isset( $_REQUEST['v'] ) ) {

	    		// if version number is not passed, show an error
	    		if ( empty( $_REQUEST['v'] ) ) {
	    			$error[] = __( 'Please specify a version number', 'update-api' );
	    			break;
	    		}
	    		if( axiom_plugin_get_verified_version( $_REQUEST['v'] ) == $version ) {
		    		$log_content	= array( 'release_date' => $release_date, 'version' => $version, 'changelog' => $excerpt );

		    		$output	= $log_content;
		    		break;
		    	}
	    	
	    	} elseif ( ! isset( $_REQUEST['v'] ) ) {
	    		$log_content .= sprintf( "Version $version / ($release_date) %s \n", $permalink );
	    		$log_content .= sprintf( "============================%s \n", $permalink?"==":"" );
	    		$log_content .= $excerpt . "\n\n\n";

	    		$output[]	= $log_content;

	    	} 

		}
	}

	
	
	if ( count( $error ) ) {
		echo json_encode( array( 'error' => implode( ". ", $error ) ) );

	} elseif( isset( $_REQUEST['action'] ) ) {

		switch ($_REQUEST['action']) {
			case 'version':
				echo $latest_version;
				break;
			case 'info':
				$obj = new stdClass();
				$obj->slug = '';
				$obj->plugin_name = 'plugin.php';
				$obj->new_version = $latest_version;
				$obj->requires = '3.0';
				$obj->tested = '';
				$obj->downloaded = '';
				$obj->last_updated = $latest_release_date;
				$obj->sections = array(
			    	'description' => $latest_changelog,
			    	'changelog' => '<pre style="white-space: pre-line;">' . implode( "", $output ) . '</pre>'
				);
				$obj->download_link = '';
				echo serialize( $obj );
				break;
			case 'license':
				echo 'false';
				break;
		}

	} elseif( isset( $_REQUEST['format'] ) && $_REQUEST['format'] == 'json' ) {
		echo json_encode( $output );

	} elseif ( isset( $_REQUEST['v'] ) ) {
		if( isset( $output['changelog'] ) ) {
			echo $output['changelog'];
		}

	} elseif ( isset( $_REQUEST['view'] ) && 'pre' == $_REQUEST['view'] ) {
		echo '<pre style="white-space: pre-line;">' . implode( "", $output ) . '</pre>';

	} else {
		echo implode( "", $output ) ;
	
	}
	
	// Restore original Post Data
	wp_reset_postdata();
}


?>
