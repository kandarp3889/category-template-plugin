<?php
/*
Plugin Name: Category Details
Plugin URI: http://example.com/
Description: Displays the title, description, and custom image of a product category.
Version: 1.0
Author: Your Name
Author URI: http://example.com/
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define the shortcode callback function
function display_products_shortcode() {
    // Get the current term and taxonomy from the URL
    $term_slug = get_query_var('term');
    $taxonomy = get_query_var('taxonomy');

    // Get the term object
    $term = get_term_by('slug', $term_slug, $taxonomy);

    // Check if the term exists
    if (!is_wp_error($term) && !empty($term)) {
        // Get all products within the main product category
        $main_category_products = get_posts(array(
            'post_type' => 'products',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $term_slug,
                ),
            ),
			    'order' => 'ASC', // Add this line to set the order as ascending
        ));

        // Initialize an array to store filtered products by product brand
        $filtered_products_by_brand = array();

        // Loop through main category products
        foreach ($main_category_products as $product) {
            // Get product brands associated with the product
            $product_brands = wp_get_post_terms($product->ID, 'product-brand');

            // Loop through product brands
            foreach ($product_brands as $product_brand) {
                // Initialize an array for each product brand
                if (!isset($filtered_products_by_brand[$product_brand->term_id])) {
                    $filtered_products_by_brand[$product_brand->term_id] = array(
                        'product_brand' => $product_brand,
                        'category_image' => get_field('category_image', 'product-brand_' . $product_brand->term_id), 
                        'products' => array(),
                    );
                }

                // Add product to the array under its corresponding product brand
                $filtered_products_by_brand[$product_brand->term_id]['products'][] = $product;
            }
        }

        // Output product grid for each product brand
        foreach ($filtered_products_by_brand as $brand_data) {
            $category_image = $brand_data['category_image'];
            $products = $brand_data['products'];

            // Output banner with category image
            if (!empty($category_image)) {
                echo '<div class="product-brand"><img src="' . esc_url($category_image) . '"><hr/></div>';
            }

            // Output product grid
            echo '<div class="custom-product-grid">';
            foreach ($products as $product) {
                echo '<div class="custom-product">';
                echo get_the_post_thumbnail($product->ID);
                echo '<div class="category-detial"><h2>' . esc_html(get_the_title($product->ID)) . '</h2>';
		// Display product description
                $description = apply_filters('the_content', $product->post_content);
                if (!empty($description)) {
					echo '<div class="product-desc">';
                    echo wp_kses_post($description);
				  echo '</div>';
                }

                
                // Check if the product has a repeater field
                if (have_rows('product_repeater', $product->ID)) {
                    // Display "View All" button
                    echo '<a class="elementor-button elementor-button-link elementor-size-xs cstm-view-all-btn" href="' . esc_url(get_permalink($product->ID)) . '">
                            <span class="elementor-button-content-wrapper">
                                <span class="elementor-button-text">View All -></span>
                            </span>
							
                          </a>';
                } else {
                    // Display "View PDF" button
                    $pdf_url = get_field('product_pdf', $product->ID);
                    if ($pdf_url) {
                        echo '<a class="elementor-button elementor-button-link elementor-size-xs cstm-pdf-btn cstn-pdf-btn2" href="' . esc_url($pdf_url) . '" target="_blank">
                                <span class="elementor-button-content-wrapper">
                                    <span class="elementor-button-icon elementor-align-icon-left">
                                        <svg aria-hidden="true" class="e-font-icon-svg e-far-file-pdf" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M369.9 97.9L286 14C277 5 264.8-.1 252.1-.1H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V131.9c0-12.7-5.1-25-14.1-34zM332.1 128H256V51.9l76.1 76.1zM48 464V48h160v104c0 13.3 10.7 24 24 24h104v288H48zm250.2-143.7c-12.2-12-47-8.7-64.4-6.5-17.2-10.5-28.7-25-36.8-46.3 3.9-16.1 10.1-40.6 5.4-56-4.2-26.2-37.8-23.6-42.6-5.9-4.4 16.1-.4 38.5 7 67.1-10 23.9-24.9 56-35.4 74.4-20 10.3-47 26.2-51 46.2-3.3 15.8 26 55.2 76.1-31.2 22.4-7.4 46.8-16.5 68.4-20.1 18.9 10.2 41 17 55.8 17 25.5 0 28-28.2 17.5-38.7zm-198.1 77.8c5.1-13.7 24.5-29.5 30.4-35-19 30.3-30.4 35.7-30.4 35zm81.6-190.6c7.4 0 6.7 32.1 1.8 40.8-4.4-13.9-4.3-40.8-1.8-40.8zm-24.4 136.6c9.7-16.9 18-37 24.7-54.7 8.3 15.1 18.9 27.2 30.1 35.5-20.8 4.3-38.9 13.1-54.8 19.2zm131.6-5s-5 6-37.3-7.8c35.1-2.6 40.9 5.4 37.3 7.8z"></path>
                                        </svg>
                                    </span>
                                    <span class="elementor-button-text">View PDF</span>
                                </span>
                              </a>';
                    }
                }
                echo '</div></div>';
            }
            echo '</div>';
        }
    } else {
        // If term not found or error occurred, output a message
        return 'Term not found or error occurred.';
    }
}

// Register the shortcode
add_shortcode('display_products', 'display_products_shortcode');



function display_product_repeater() {
    // Get the current post ID
    $post_id = get_the_ID();

 // Get the product brands associated with the current post
 $product_brands = wp_get_post_terms($post_id, 'product-brand');
    
 // Check if the product has associated brands
 if (!is_wp_error($product_brands) && !empty($product_brands)) {
     // We assume each product is associated with only one brand
     $product_brand = $product_brands[0];
     
     // Get the ACF field 'category_image' for this product brand
     $category_image = get_field('category_image', 'product-brand_' . $product_brand->term_id);
 }
    
    // Check if the repeater field has rows of data
    if (have_rows('product_repeater', $post_id)) {
        // Start the grid container
        $output = '<div class="custom-product-grid cstm-single-product-grid">';
        
        // Output banner with category image
        if (!empty($category_image)) {
            echo '<div class="product-brand"><img src="' . esc_url($category_image) . '"><hr/></div>';
        }
        
        // Loop through the rows of data
        while (have_rows('product_repeater', $post_id)) {
            the_row();
            $product_image = get_sub_field('product_image'); // URL
            $product_title = get_sub_field('product_title');
            $product_pdf = get_sub_field('product_pdf'); // URL
			$product_desc = get_sub_field('product_description');
            
            // Output each item
            $output .= '<div class="custom-product">';
            if ($product_image) {
                $output .= '<img src="' . esc_url($product_image) . '" alt="' . esc_attr($product_title) . '" />';
            }
            $output .= '<div class="category-detial"><h2>' . esc_html($product_title) . '</h2>';
			if ($product_desc) {
    $output .= '<p>' . esc_html($product_desc) . '</p>';
                }
            if ($product_pdf) {
                $output .= '<a class="elementor-button elementor-button-link elementor-size-xs cstm-pdf-btn" href="' . esc_url($product_pdf) . '" target="_blank">
                    <span class="elementor-button-content-wrapper">
                        <span class="elementor-button-icon elementor-align-icon-left">
                            <svg aria-hidden="true" class="e-font-icon-svg e-far-file-pdf" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                                <path d="M369.9 97.9L286 14C277 5 264.8-.1 252.1-.1H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V131.9c0-12.7-5.1-25-14.1-34zM332.1 128H256V51.9l76.1 76.1zM48 464V48h160v104c0 13.3 10.7 24 24 24h104v288H48zm250.2-143.7c-12.2-12-47-8.7-64.4-6.5-17.2-10.5-28.7-25-36.8-46.3 3.9-16.1 10.1-40.6 5.4-56-4.2-26.2-37.8-23.6-42.6-5.9-4.4 16.1-.4 38.5 7 67.1-10 23.9-24.9 56-35.4 74.4-20 10.3-47 26.2-51 46.2-3.3 15.8 26 55.2 76.1-31.2 22.4-7.4 46.8-16.5 68.4-20.1 18.9 10.2 41 17 55.8 17 25.5 0 28-28.2 17.5-38.7zm-198.1 77.8c5.1-13.7 24.5-29.5 30.4-35-19 30.3-30.4 35.7-30.4 35zm81.6-190.6c7.4 0 6.7 32.1 1.8 40.8-4.4-13.9-4.3-40.8-1.8-40.8zm-24.4 136.6c9.7-16.9 18-37 24.7-54.7 8.3 15.1 18.9 27.2 30.1 35.5-20.8 4.3-38.9 13.1-54.8 19.2zm131.6-5s-5 6-37.3-7.8c35.1-2.6 40.9 5.4 37.3 7.8z"></path>
                            </svg>
                        </span>
                        <span class="elementor-button-text">View PDF</span>
                    </span>
                </a>';
            }
            $output .= '</div></div>';
        }
        
        // End the grid container
        $output .= '</div>';
        
        return $output;
    } else {
        return '<p>' . esc_html__('No products found.', 'text-domain') . '</p>';
    }
}

// Register the shortcode
add_shortcode('product_repeater_grid', 'display_product_repeater');

