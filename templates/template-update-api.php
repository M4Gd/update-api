<?php 
/* Template Name: api */

global $post;


// output forum stats in json format
if ( isset( $_GET['forum_stats'] ) && function_exists('bbp_get_statistics') ) {
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
		$v_parts = array_filter($v_parts, 'strlen');
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



if ( isset( $_GET['log'] ) ) {

	// get changelog category slug
	$log_cat = esc_sql( $_GET['log'] );

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
	    	$permalink		= isset( $_GET['pl'] )?'<a href="' . get_permalink( $log_query->post->ID ) . '" >#</a>' :'';

	    	$log_content 	= "";

	    	if( isset( $_GET['latest'] )  || isset( $_GET['l'] ) ) {
	    		$log_content	= array( 'release_date' => $release_date, 'version' => $version, 'changelog' => $excerpt );

	    		$output		= $log_content;
	    		break;

	    	} elseif ( isset( $_GET['v'] ) ) {

	    		// if version number is not passed, show an error
	    		if ( empty( $_GET['v'] ) ) {
	    			$error[] = __( 'Please specify a version number', 'update-api' );
	    			break;
	    		}
	    		if( axiom_plugin_get_verified_version( $_GET['v'] ) == $version ) {
		    		$log_content	= array( 'release_date' => $release_date, 'version' => $version, 'changelog' => $excerpt );

		    		$output	= $log_content;
		    		break;
		    	}
	    	
	    	} elseif ( ! isset( $_GET['v'] ) ) {
	    		$log_content .= sprintf( "Version $version / ($release_date) %s \n", $permalink );
	    		$log_content .= sprintf( "============================%s \n", $permalink?"==":"" );
	    		$log_content .= $excerpt . "\n\n\n";

	    		$output[]	= $log_content;

	    	} 

		}
	}

	if ( count($error) ) {
		echo json_encode( array( 'error' => implode( ". ", $error ) ) );

	} elseif( isset( $_GET['format'] ) && $_GET['format'] == 'json' ) {
		echo json_encode( $output );

	} elseif ( isset( $_GET['v'] ) ) {
		if( isset( $output['changelog'] ) ) {
			echo $output['changelog'];
		}

	} else {
		echo implode( "", $output ) ;
	
	}
	
	// Restore original Post Data
	wp_reset_postdata();
}


?>