<?php 
/* Template Name: api */

global $post;


// output forum stats in json format
if ( isset( $_REQUEST['forum_stats'] ) && function_exists('bbp_get_statistics') ) {
	$stats = bbp_get_statistics();
	echo json_encode($stats);
	die;
}


if ( ! isset( $_REQUEST['log'] ) ){ 
	return;

} elseif ( isset( $_REQUEST['view'] ) && 'image' == $_REQUEST['view'] ) {

	$upload_dir     = wp_upload_dir();
	$log_image_path = $upload_dir['baseurl'] . '/changelog/' . $_REQUEST['log'] . '.png';
	return file_exists( $log_image_path ) ? $log_image_path : '';
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

	if( strlen( $version ) < 5 ) {
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

	// stores all log contents
	$output = get_transient( 'avt_changelog_output_' . $log_cat );
	$error  = array();

	if( false === $output || isset( $_REQUEST['flush_log'] ) || isset( $_REQUEST['live'] ) ){

		$output = array();

		// get limit for outputing the number of changelog versions
		$log_limit = isset( $_REQUEST['limit'] ) ? (int) $_REQUEST['limit'] : -1;

	    $tax_args = array('taxonomy' => 'changelog-cat', 'terms' => $log_cat, 'field' => 'slug' );
	    
	    // create wp_query to get all logs
	    $args = array(
	      'post_type'			=> 'changelog',
	      'orderby'				=> "menu_order date",
	      'post_status'			=> 'publish',
	      'posts_per_page'		=> $log_limit,
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
		$latest_tested	     = '';

		

		// loop through all changelogs
		if ( $log_query->have_posts() ) {

			while ( $log_query->have_posts() ) {

				$log_query->the_post();
				
				// get current changelog version 
		    	$version 		= axiom_get_changelog_version( $log_query->post->ID );
		    	// get current changelog release date
		    	$release_date 	= get_post_meta( $log_query->post->ID, 'release_date', true );
		    	// get short changelog
		    	$excerpt 		= $log_query->post->post_excerpt;
		    	// get changelog single page url if requested
		    	$permalink		= isset( $_REQUEST['pl'] ) ? '<a href="' . get_permalink( $log_query->post->ID ) . '" target="_blank" title="View full changelog" >#</a>' :'';
		    	// get current changelog compatibility version
		    	$compatibility_version 	= get_post_meta( $log_query->post->ID, 'compatibility_version', true );

		    	// store date for latest version 
		    	if ( empty( $latest_version ) ) 	 $latest_version 	  = $version;
		    	if ( empty( $latest_release_date ) ) $latest_release_date = $release_date;
		    	if ( empty( $latest_changelog ) ) 	 $latest_changelog    = $excerpt;
		    	if ( empty( $latest_tested ) ) 	     $latest_tested       = $compatibility_version;


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
		if( ! isset( $_REQUEST['live'] ) ){
			set_transient( 'avt_changelog_output_' . $log_cat , $output, 0.5 * HOUR_IN_SECONDS );
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
				
				$info                           = new stdClass;
	            $info->name                     = '';
	            $info->slug                     = '';
	            $info->version                  = '';
	            $info->new_version 			   = $latest_version;
	            $info->author                   = '';
	            $info->requires                 = '';
	            $info->tested                   = $latest_tested;
	            $info->last_updated             = $latest_release_date;
	            $info->homepage                 = '';
	            $info->sections['description']  = $latest_changelog;
	            
	            $info->sections['changelog']    = '<pre>' . implode( '', $output ) . '</pre>';
	            $info->sections['FAQ']          = '';
	            $info->download_link            = '';

	            echo serialize( $info );
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
		echo '<pre style="white-space: pre-line; font-size: 12px;">' . implode( "", $output ) . '</pre>';

	} else {
		echo implode( "", $output ) ;
	
	}
	
	// Restore original Post Data
	wp_reset_postdata();
}
