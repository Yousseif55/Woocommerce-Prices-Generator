<?php

/*  

Plugin Name: Prices Generator

Description: Auto generate price from specific equation handled by labour fields, Excluded Products Managed.

Author: Yousseif Ahmed 

Version: 1.1.1

*/

// Function to add custom bulk action in product listing
function codecruz_prices_generator($actions)
{
    $actions['generate_price'] = 'Generate Price(s)';
    return $actions;
}

// Function to handle custom bulk action
function handle_bulk_action ($redirect_url, $action, $post_ids) {
    if ($action == 'generate_price') {

        $excluded_products = get_option('selected_products', array());
        $generated_count = 0;
        $excluded_info = array();

        $options = [
            'paper_price',
            'paper_printing_price',
            'cover_price',
            'cover_printing_price',
            'cover_lamination',
            'shipping_variance_price',
            'book_packaging_cost',
            'cover_design_cost',
            'book_gluing_price',
            'book_cutting_price',
            'profit_fixed',
            'profit_ratio'
        ];

        $values = array_map(function ($option) {
            return get_option($option, 0);
        }, $options);

        foreach ($post_ids as $post_id) {
            $product = wc_get_product($post_id);
            if (in_array($post_id, $excluded_products)) {
                $excluded_info[] = $product->get_name() . ' #' . $post_id;
                continue; // Skip this product if it's excluded
            }
            $length = $product->get_length() . get_option('woocommerce_dimension_unit');
            $width = $product->get_width() . get_option('woocommerce_dimension_unit');
            $pages = get_post_meta($post_id, 'codecruz_pages', true) ?: 0;
            $parts = get_post_meta($post_id, 'codecruz_parts', true) ?: 0;
            $cover_status = get_post_meta($post_id, 'codecruz_cover', true) ?: '';

            $new_cost = ($pages ? ($values[0] + $values[1]) * $pages / 2 : ($length ? ($values[0] + $values[1]) * $length / 2 : 0)) +
                ($parts ? ($values[2] + $values[3] + $values[4]) * $parts : ($width ? ($values[2] + $values[3] + $values[4]) * $width : 0)) +
                ($parts ? ($values[8] + $values[9]) * $parts : ($width ? ($values[8] + $values[9]) * $width : 0));

            $new_price = ($new_cost * $values[11]) + $values[5] + $values[6] + $values[10] + ($cover_status == 'Not Ready' ? $values[7] : 0);
            $rounded_price = ceil($new_price / 10) * 10 - 1;

            update_post_meta($post_id, '_cost', $new_cost);
            $product->set_regular_price($rounded_price);
            $product->save();
            $generated_count++;

        }
        set_transient('generated_count', $generated_count, 30); // Store in transient for 30 seconds

        if (!empty($excluded_info)) {
            $excluded_list = implode(', ', $excluded_info);

            $excluded_data = array(
                'list' => $excluded_list
            );

            set_transient('excluded_info', $excluded_data, 30); // Store in transient for 30 seconds
        }

        // Redirect back to the products page after the operation
        wp_safe_redirect(admin_url('edit.php?post_type=product'));
        exit();
    }
}

// Display a notice after generating products with counts
function admin_notice_price_generated()
{

    $generated_count = get_transient('generated_count');
    $excluded_info = get_transient('excluded_info');

    if (!empty($generated_count)) {
        printf(
            '<div id="message" class="updated notice is-dismissable"><p>' . __('Generated %d new product(s).', 'txtdomain') . '</p></div>',
            $generated_count
        );
        delete_transient('generated_count'); // Remove the transient after displaying
    }

    if (!empty($excluded_info)) {
        $excluded_list = implode(', ', $excluded_info);

        printf(
            '<div id="message" class="notice notice-warning is-dismissable"><p>' . __('Excluded product(s): %s', 'txtdomain') . '</p></div>',
            $excluded_list
        );
        delete_transient('excluded_info'); // Remove the transient after displaying
    }
    $admin_notice = get_transient('codecruz_admin_notice');

    if ($admin_notice) {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html($admin_notice)
        );

        delete_transient('codecruz_admin_notice'); // Remove the transient after displaying
    }
}

// Function to add "Generate Price" link in product row actions
function codecruz_generate_price_action($actions, $post)
{

    if ($post->post_type == 'product') {


        $actions['generate_price'] = '<a href="' . wp_nonce_url(admin_url('admin-ajax.php?action=codecruz_product_price&id=' . $post->ID), 'generate-price-' . $post->ID) . '">Generate Price</a>';
    }
    return $actions;

}

// Function to handle AJAX request for generating product price
function codecruz_product_price_ajax()
{
    // Check nonce and get ID
    $post_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    $excluded_products = get_option('selected_products', array());

    if (in_array($post_id, $excluded_products)) {
        set_transient('codecruz_admin_notice', 'Product is excluded from Generation.', 30); // Store notice message	
        $redirect_url = admin_url('edit.php?post_type=product');
        wp_safe_redirect($redirect_url);
        exit;
    }
    // Define the options once
    $options = [
        'paper_price',
        'paper_printing_price',
        'cover_price',
        'cover_printing_price',
        'cover_lamination',
        'shipping_variance_price',
        'book_packaging_cost',
        'cover_design_cost',
        'book_gluing_price',
        'book_cutting_price',
        'profit_fixed',
        'profit_ratio',
    ];

    // Retrieve options in a single database query
    $values = array_map(function ($option) {
        return get_option($option, 0);
    }, $options);

    // Retrieve product dimensions and meta data
    $product = wc_get_product($post_id);
    $length = $product->get_length();
    $width = $product->get_width();
    $pages = get_post_meta($post_id, 'codecruz_pages', true) ?: 0;
    $parts = get_post_meta($post_id, 'codecruz_parts', true) ?: 0;
    $cover_status = get_post_meta($post_id, 'codecruz_cover', true) ?: '';

    // Calculate new cost
    $new_cost = ($pages ? ($values[0] + $values[1]) * $pages / 2 : ($length ? ($values[0] + $values[1]) * $length / 2 : 0)) +
        ($parts ? ($values[2] + $values[3] + $values[4]) * $parts : ($width ? ($values[2] + $values[3] + $values[4]) * $width : 0)) +
        ($parts ? ($values[8] + $values[9]) * $parts : ($width ? ($values[8] + $values[9]) * $width : 0));

    // Calculate new price
    $new_price = ($new_cost * $values[11]) + $values[5] + $values[6] + $values[10] + ($cover_status == 'Not Ready' ? $values[7] : 0);
    $rounded_price = ceil($new_price / 10) * 10 - 1;

    // Update cost and regular price
    update_post_meta($post_id, '_cost', $new_cost);
    $product->set_regular_price($rounded_price);
    $product->save();

    // Redirect back to Media page
    set_transient('codecruz_admin_notice', 'Price generated successfully.', 30); // Store notice message
    $redirect_url = admin_url('edit.php?post_type=product');
    wp_safe_redirect($redirect_url);
    exit;
}


// Function to add menu page for Prices Generator
function add_codecruz_prices_menu_page()
{
    add_menu_page(
        'Prices Generator',
        'Prices Generator',
        'manage_options',
        'prices-generator',
        'pricesgenerator_settings_page',
        'dashicons-archive'
    );
}

// Function to generate content for Prices Generator menu page
function pricesgenerator_settings_page()
{
    $parameters = [
        [
            'name' => 'Paper',
            'fields' => [
                'paper_price',
                'paper_printing_price',
            ]
        ],
        [
            'name' => 'Cover',
            'fields' => [
                'cover_price',
                'cover_printing_price',
                'cover_lamination',
            ]
        ],
        [
            'name' => 'Finishing',
            'fields' => [
                'book_gluing_price',
                'book_cutting_price',
            ]
        ],
        [
            'name' => 'Shipping',
            'fields' => [
                'shipping_variance_price',
                'book_packaging_cost',
            ]
        ],
        [
            'name' => 'Profit',
            'fields' => [
                'profit_fixed',
                'profit_ratio',
            ]
        ],
        [
            'name' => 'Cover Design Cost',
            'fields' => [
                'cover_design_cost',
            ]
        ]
    ];

    ?>
    <div class="wrap">
        <div class="form-header">
            <h1>Prices Generating Settings</h1>
            <hr>
            <form method="post" action="">
                <?php foreach ($parameters as $parameter): ?>
                    <div class="parameter-group">
                        <?php foreach ($parameter['fields'] as $field): ?>
                            <div class="parameter">
                                <label for="<?php echo $field; ?>"><b>
                                        <?php echo ucwords(str_replace('_', ' ', $field)); ?>
                                    </b></label>
                                <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" step="0.01"
                                    value="<?php echo get_option($field, 0); ?>">
                            </div>
                        <?php endforeach;
                        if ($parameter['name'] !== 'Profit' && $parameter['name'] !== 'Cover Design Cost'): ?>
                            <div class="parameter">
                                <label class="sum" for="<?php echo $parameter['name']; ?>_sum"><b>Sum</b></label>
                                <label class="sum-label" id="<?php echo $parameter['name']; ?>_sum_label" readonly></label>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
                <div class="parameter-group">
                    <div class="parameter"> <label for="my_product_search"><b>Excluded Products</b></label>
                        <select id="my_product_search" name="my_product_search[]"
                            data-security="<?php echo wp_create_nonce('search-products'); ?>" style="width: 300px;"
                            class="bc-product-search" multiple>
                            <?php
                            $selected_products = get_option('selected_products', array());

                            if (!empty($selected_products)) {
                                foreach ($selected_products as $product_id) {
                                    $product = wc_get_product($product_id);
                                    echo '<option value="' . $product_id . '" selected>' . $product->get_formatted_name() . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <input type="submit" name="submit_prices_settings" class="pricebutton" value="Save Changes">
            </form>
        </div>
    </div>
    <script>
        (function ($) {
            $(document).ready(function () {
                $('.bc-product-search').select2({
                    ajax: {
                        url: ajaxurl,
                        data: function (params) {
                            return {
                                term: params.term,
                                action: 'woocommerce_json_search_products_and_variations',
                                security: $(this).attr('data-security'),
                            };
                        },
                        processResults: function (data) {
                            var terms = [];
                            if (data) {
                                $.each(data, function (id, text) {
                                    terms.push({ id: id, text: text });
                                });
                            }
                            return {
                                results: terms
                            };
                        },
                        cache: true
                    }
                });
            });
        })(jQuery);
        <?php foreach ($parameters as $parameter): ?>
            <?php if ($parameter['name'] !== 'Profit' && $parameter['name'] !== 'Cover Design Cost'): ?>
                function update<?php echo str_replace(' ', '', $parameter['name']); ?>Sum() {
                    let sum = 0;
                    <?php foreach ($parameter['fields'] as $field): ?>
                        let <?php echo $field; ?>Value = document.getElementById('<?php echo $field; ?>').value;
                        sum += <?php echo $field; ?>Value !== '' ? parseFloat(<?php echo $field; ?>Value) : 0;
                    <?php endforeach; ?>

                    document.getElementById('<?php echo $parameter['name']; ?>_sum_label').textContent = sum.toFixed(2);
                }

            <?php endif; ?>
        <?php endforeach; ?>

        <?php foreach ($parameters as $parameter): ?>
            <?php if ($parameter['name'] !== 'Profit' && $parameter['name'] !== 'Cover Design Cost'): ?>

                <?php foreach ($parameter['fields'] as $field): ?>
                    document.getElementById('<?php echo $field; ?>').addEventListener('input', update<?php echo str_replace(' ', '', $parameter['name']); ?>Sum);
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php foreach ($parameters as $parameter): ?>
            <?php if ($parameter['name'] !== 'Profit' && $parameter['name'] !== 'Cover Design Cost'): ?>

                                            update<?php echo str_replace(' ', '', $parameter['name']); ?>Sum();
            <?php endif; ?>
        <?php endforeach; ?>

    </script>

    <?php
    // Save the costs settings
    if (isset($_POST['submit_prices_settings'])) {

        echo '<div id="message" class="updated notice is-dismissible"><p>' . __('Prices saved.') . '</p></div>';
    }
}
if (isset($_POST['submit_prices_settings'])) {
    $fields = [
        'paper_price',
        'paper_printing_price',
        'cover_price',
        'cover_printing_price',
        'cover_lamination',
        'shipping_variance_price',
        'book_packaging_cost',
        'cover_design_cost',
        'book_gluing_price',
        'book_cutting_price',
        'profit_fixed',
        'profit_ratio'
    ];

    foreach ($fields as $field) {
        $value = isset($_POST[$field]) ? $_POST[$field] : 0;
        update_option($field, $value);
    }
    $selected_products = isset($_POST["my_product_search"]) ? $_POST["my_product_search"] : array();

    if (!empty($selected_products)) {
        update_option('selected_products', $selected_products);
    } else {
        if (get_option('selected_products')) {
            delete_option('selected_products');
        }
    }
}
