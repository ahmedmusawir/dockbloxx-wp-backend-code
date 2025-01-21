<?php
/** 
 * For more info: https://developer.wordpress.org/themes/basics/theme-functions/
 *
 */		

 /**
 * Adds YouTube video data to the product's images array in the WooCommerce REST API response.
 *
 * This function retrieves the YouTube video ID from an ACF field associated with the product.
 * If a video ID is found, it constructs a video object and appends it to the 'images' array
 * in the WooCommerce REST API response. This ensures the video is treated as part of the product's
 * gallery on the frontend.
 *
 * Hook: woocommerce_rest_prepare_product_object
 * Priority: 10
 *
 * @param WP_REST_Response $response The REST API response object for the product.
 * @param WC_Product       $product  The WooCommerce product object.
 * @param WP_REST_Request  $request  The REST API request object.
 * @return WP_REST_Response The modified REST API response with YouTube video data.
 */

 add_filter('woocommerce_rest_prepare_product_object', 'add_video_data_to_rest_api', 10, 3);

function add_video_data_to_rest_api($response, $product, $request) {
    // Initialize the YouTube video ID
    $video_id = get_field('gallery_youtube_video_id', $product->get_id());

    // Initialize the video data
    $video_data = null;

    if ($video_id) {
        // Construct the video data object
        $video_data = array(
            'id'   => 'youtube_video',
            'src'  => 'https://www.youtube.com/embed/' . $video_id,
            'type' => 'video',
        );

        // Add video_data to the response
        $response->data['video_data'] = $video_data;
    }

    // Add a test string to the images array
    // if (!empty($response->data['images'])) {
    //     $response->data['images'][] = array(
    //         'id'   => 'test_string',
    //         'src'  => 'This is a test string',
    //         'type' => 'text',
    //     );
    // }

    if (!empty($response->data['images'])) {
        $response->data['images'][] = $video_data;
    }

    return $response;
}


 /**
 * Add custom price_display field to WooCommerce REST API.
 *
 * This function calculates the price range for variable products
 * and adds it as a custom field called `price_display` in the product response.
 * For simple products, it simply returns the regular price.
 *
 * @param WP_REST_Response $response The REST API response object.
 * @param WC_Product $product The WooCommerce product object.
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response Modified REST API response object.
 */
function add_price_display_to_rest_api( $response, $product, $request ) {
    // Initialize the price_display field
    $price_display = '';

    // Check if the product is variable
    if ( $product->is_type( 'variable' ) ) {
        $prices = $product->get_variation_prices( true ); // Get variation prices (including sales)
        $min_price = min( $prices['price'] );
        $max_price = max( $prices['price'] );

        // Format as price range
        $price_display = wc_price( $min_price ) . ' - ' . wc_price( $max_price );
    } else {
        // For simple products, just return the price
        $price_display = wc_price( $product->get_price() );
    }

    echo '<pre>'; // Add this to format the output for better readability
    var_dump($price_display); // Dump the product object
    echo '</pre>';

    // Add the custom field to the API response
    $response->data['price_display'] = $price_display;

    return $response;
}
// THIS IS NO LONGER NECESSARY CUZ BY DEFAULT WE HAVE price_html 
// add_filter( 'woocommerce_rest_prepare_product_object', 'add_price_display_to_rest_api', 10, 3 );


 /**
 * Registers a custom GraphQL field `totalProducts` in the RootQuery.
 * 
 * This field allows querying the total number of published products in the WordPress site.
 * It is particularly useful for pagination or displaying product statistics in a headless frontend.
 *
 * GraphQL Field Details:
 * - Field Name: `totalProducts`
 * - Field Type: `Int`
 * 
 * Logic:
 * - Queries the WordPress database to fetch all posts of type `product` with a status of `publish`.
 * - Uses `get_posts` to retrieve product IDs (fields set to `ids` for optimized performance).
 * - Returns the total count of published products.
 *
 * @return int Total number of published products.
 */
 function register_total_products_field() {
    register_graphql_field( 'RootQuery', 'totalProducts', [
        'type'    => 'Int',
        'resolve' => function() {
            // Query the database to get the total count of published products
            $args = [
                'post_type'   => 'product',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields'      => 'ids',
            ];
            $products = get_posts( $args );
            return count( $products );
        },
    ] );
}

add_action( 'graphql_register_types', 'register_total_products_field' );


// Increase the default max limit for GraphQL queries
// add_filter( 'graphql_connection_max_query_amount', function( $amount ) {
//     return 20; // Set your desired max value here
// } );


// ENABLE GRAPH QL INTROSPECTION
add_filter( 'graphql_enable_introspection', function( $is_enabled ) {
    return current_user_can( 'manage_options' ); // Only enable for admins
} );

// FLUSH WP CACHE
add_action('init', function() {
    if (current_user_can('manage_options')) {
        wp_cache_flush();
    }
});


// Woocom Fix Thumbnail for Prouct Slider - by Moose
add_filter('woocommerce_single_product_image_thumbnail_html', 'custom_filter_product_image_html', 10, 2);

function custom_filter_product_image_html($html, $post_thumbnail_id) {
    $full_size_image = wp_get_attachment_image_src($post_thumbnail_id, 'full');
    $image = wp_get_attachment_image($post_thumbnail_id, 'full', false, array(
        'title' => get_post_field('post_title', $post_thumbnail_id),
    ));
    return '<div data-thumb="' . esc_url($full_size_image[0]) . '" class="woocommerce-product-gallery__image">' . $image . '</div>';
}	

// Theme support options
require_once(get_template_directory().'/functions/theme-support.php'); 

// WP Head and other cleanup functions
require_once(get_template_directory().'/functions/cleanup.php'); 

// Register scripts and stylesheets
require_once(get_template_directory().'/functions/enqueue-scripts.php'); 

// Register custom menus and menu walkers
require_once(get_template_directory().'/functions/menu.php'); 

// Register sidebars/widget areas
//require_once(get_template_directory().'/functions/sidebar.php'); 

// Makes WordPress comments suck less
//require_once(get_template_directory().'/functions/comments.php'); 

// Removes WordPress comments from everywhere:
	// Removes from admin menu
	add_action( 'admin_menu', 'my_remove_admin_menus' );
	function my_remove_admin_menus() {
	    remove_menu_page( 'edit-comments.php' );
	}
	// Removes from post and pages
	add_action('init', 'remove_comment_support', 100);

	function remove_comment_support() {
	    remove_post_type_support( 'post', 'comments' );
	    remove_post_type_support( 'page', 'comments' );
	}
	// Removes from admin bar
	function mytheme_admin_bar_render() {
	    global $wp_admin_bar;
	    $wp_admin_bar->remove_menu('comments');
	}
	add_action( 'wp_before_admin_bar_render', 'mytheme_admin_bar_render' );

// Replace 'older/newer' post links with numbered navigation
require_once(get_template_directory().'/functions/page-navi.php'); 

// Adds support for multiple languages
require_once(get_template_directory().'/functions/translation/translation.php'); 

// Adds site styles to the WordPress editor
// require_once(get_template_directory().'/functions/editor-styles.php'); 

// Remove Emoji Support
// require_once(get_template_directory().'/functions/disable-emoji.php'); 

// Related post function - no need to rely on plugins
// require_once(get_template_directory().'/functions/related-posts.php'); 

// Use this as a template for custom post types
// require_once(get_template_directory().'/functions/custom-post-type.php');

// Customize the WordPress login menu
// require_once(get_template_directory().'/functions/login.php'); 

// Customize the WordPress admin
// require_once(get_template_directory().'/functions/admin.php');

// ACF Options Page
if( function_exists('acf_add_options_page') ) {
    acf_add_options_page();
    acf_add_options_sub_page('Global');
    acf_add_options_sub_page('Reviews & FAQs');
    acf_add_options_sub_page('Global Product');
} 

// Remove WooCommerce Marketing Hub & Analytics Menu from the sidebar - for WooCommerce v4.3+
/*add_filter( 'woocommerce_admin_features', function( $features ) {
	return array_values(
		array_filter( $features, function($feature) {
			return ! in_array( $feature, [ 'marketing', 'analytics', 'analytics-dashboard', 'analytics-dashboard/customizable' ] );
		} ) 
	);
} );*/
/**
 * Filter the list of features and get rid of the features not needed.
 * 
 * array_values() are being used to ensure that the filtered array returned by array_filter()
 * does not preserve the keys of initial $features array. As key preservation is a default feature 
 * of array_filter().
 */

// Disallow theme file editing (this can get overridden in wp-config, especially in WPE)
function disable_mytheme_action() {
	define('DISALLOW_FILE_EDIT', TRUE);
}
add_action('init','disable_mytheme_action');


// Remove WooCommerce Stylesheets
add_filter( 'woocommerce_enqueue_styles', 'jk_dequeue_styles' );
function jk_dequeue_styles( $enqueue_styles ) {
	unset( $enqueue_styles['woocommerce-general'] );	// Remove the gloss
	//unset( $enqueue_styles['woocommerce-layout'] );		// Remove the layout
	//unset( $enqueue_styles['woocommerce-smallscreen'] );	// Remove the smallscreen optimisation
	return $enqueue_styles;
}


//Rename additional information tab in WooCommerce
add_filter( 'woocommerce_product_tabs', 'woo_rename_tabs', 98 );
function woo_rename_tabs( $tabs ) {
	$tabs['additional_information']['title'] = __( 'Variations' );	
	// Rename the additional information tab
	return $tabs;
}


//Change woocommerce_shop_loop_item_title
remove_action( 'woocommerce_shop_loop_item_title','woocommerce_template_loop_product_title', 10 );
add_action('woocommerce_shop_loop_item_title', 'astra_woo_shop_products_title', 10 );
function astra_woo_shop_products_title() {
    echo '<h4 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">' . get_the_title() . '</h4>';
}
