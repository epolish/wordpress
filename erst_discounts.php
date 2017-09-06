<?php
/*
Plugin Name: Discounts
Plugin URI: https://studioschastie.com.ua/
Description: Adds admin section for managing discounts
Version: 1.0
Author: Eleanorsoft Inc
Author URI: http://eleanorsoft.com/
*/

define("POST_TYPE_ERST_DISCOUNT", 'erst_discount');

/**
 * Register new title
 */
function erst_discount_title( $title ) {
    if ( POST_TYPE_ERST_DISCOUNT === get_post_type() ) {
        $title = __( 'Введите номер дисконтной карты' );
    }

    return $title;
}

add_filter( 'enter_title_here', 'erst_discount_title' );

/**
 * Register new admin notices
 */
function admin_error_notice() {settings_errors();
    $errors = get_transient( 'error' );

    if ( !$errors ) {
        return;
    }

    delete_transient( 'error' );
    ob_start();
    ?>
    <div class="error notice is-dismissible">
        <p><?= __( 'Номер дисконтной карты должен быть уникальным.' ) ?></p>
    </div>
    <?php
    echo ob_get_clean();
}

add_action( 'admin_notices', 'admin_error_notice' );

/**
 * Discount unique 'number' validation
 */
function filter_post_data( $data , $post_arr ) {
    $founded_post = get_page_by_title( $data['post_title'], OBJECT, POST_TYPE_ERST_DISCOUNT );

    if ( $data['post_title'] != 'Auto Draft' && $founded_post && $founded_post->ID != $post_arr['post_ID'] ) {
        set_transient( 'error', 'duplicate_title_error' );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    return $data;
}

add_filter( 'wp_insert_post_data' , 'filter_post_data' , '99', 2 );

/**
 * Register discount entity
 */
function register_erst_discount() {
    global $wp_post_types;

    register_post_type( POST_TYPE_ERST_DISCOUNT,
        array(
            'labels' => array(
                'name' => __( 'Дисконтные карты' ),
                'singular_name' => __( 'Дисконтная карта' ),
                'add_new' => __( 'Добавить дисконтную карту' ),
                'add_new_item' => __( 'Добавить дисконтную карту' ),
                'edit_item' => __( 'Редактировать дисконтную карту' ),
                'new_item' => __( 'Дисконтная карта' ),
                'view_item' => __( 'Просмотреть дисконтную карту' ),
                'search_items' => __( 'Искать дисконтные карты' ),
                'not_found' => __( 'Дисконтные карты не найдены' ),
                'not_found_in_trash' => __( 'В корзине дисконтные карты не найдены' ),
                'all_items' => __( 'Все дисконтные карты' ),
                'menu_name' => __( 'Дисконтные карты' ),
                'name_admin_bar' => __( 'Дисконтные карты' ),
            ),
            'public' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'supports' => array( 'title',  ),
            'register_meta_box_cb' => 'meta_erst_discount',
        )
    );
}

add_action( 'init', 'register_erst_discount' );

/**
 * Add non-standard fields for discounts
 */
function meta_erst_discount()
{
    add_meta_box(
        'erst_discount_options',
        __( 'Настройки дисконтной карты' ),
        function ( $post ) {
            $size = get_post_meta( $post->ID, 'erst_discount_size', true );
            $full_name = get_post_meta( $post->ID, 'erst_discount_full_name', true );
            wp_nonce_field( basename( __FILE__ ), 'erst_discount_options' );
            ?>
            <p>
                <label for="erst_discount_size"><?= __( 'Размер скидки (%)' ) ?></label>
                <input class="widefat" type="number" step="0.01" name="erst_discount_size" id="erst_discount_size"
                       value="<?php print esc_attr( $size ); ?>" />
            </p>
            <p>
                <label for="erst_discount_full_name"><?= __( 'Фамилия, имя, отчество' ) ?></label>
                <input class="widefat" type="text" name="erst_discount_full_name" id="erst_discount_full_name"
                       value="<?php print esc_attr( $full_name ); ?>" />
            </p>
            <?php
        },
        array( POST_TYPE_ERST_DISCOUNT ),
        $context = 'advanced',
        $priority = 'default',
        $callback_args = null
    );
}

/**
 * Save custom meta for discount
 *
 * @param integer $post_id
 * @param WP_Post $post
 * @return mixed
 */
function erst_discount_meta_save( $post_id, $post ) {
    /* Verify the nonce before proceeding. */
    if ( !isset( $_POST['erst_discount_options'] ) || !wp_verify_nonce( $_POST['erst_discount_options'], basename( __FILE__ ) ) )
        return $post_id;

    /* Get the post type object. */
    $post_type = get_post_type_object( $post->post_type );

    /* Check if the current user has permission to edit the post. */
    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
        return $post_id;

    $fields = array(
        'erst_discount_size',
        'erst_discount_full_name',
    );
    foreach ($fields as $field) {
        /* Get the posted data and sanitize it for use as an HTML class. */
        $new_meta_value = ( isset( $_POST[$field] ) ? $_POST[$field] : '' );

        /* Get the meta key. */
        $meta_key = $field;

        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );

        /* If a new meta value was added and there was no previous value, add it. */
        if ( $new_meta_value && '' == $meta_value )
            add_post_meta( $post_id, $meta_key, $new_meta_value, true );

        /* If the new meta value does not match the old value, update it. */
        elseif ( $new_meta_value && $new_meta_value != $meta_value )
            update_post_meta( $post_id, $meta_key, $new_meta_value );

        /* If there is no new meta value but an old value exists, delete it. */
        elseif ( '' == $new_meta_value && $meta_value )
            delete_post_meta( $post_id, $meta_key, $meta_value );
    }

    return $post_id;
}

add_action( 'save_post', 'erst_discount_meta_save', 10, 2 );

/**
 * Get discount size
 * @param $post
 * @return string
 */
function get_erst_discount_size( $post )
{
    if (is_object($post)) {
        $post = $post->ID;
    }
    return get_post_meta( $post, 'erst_discount_size', true );
}

/**
 * Get discount holder's full name
 * @param $post
 * @return string
 */
function get_erst_discount_full_name( $post )
{
    if (is_object($post)) {
        $post = $post->ID;
    }

    return get_post_meta( $post, 'erst_discount_full_name', true );
}

/**
 * Returns discounts in json format for ajax
 * @return json
 */
function api_discount() {
    global $wpdb;

    $response = array(
        'status'    => 404,
        'message'   => __( 'Not fount' )
    );
    $post_id = intval( $_POST['id'] );

    if ( $post_id ) {
        $post = get_post( $post_id );

        if ( $post && $post->post_type == POST_TYPE_ERST_DISCOUNT ) {
            $discount_size = get_post_meta( $post_id )['erst_discount_size'][0];

            if ($discount_size) {
                $response['status'] = 200;
                $response['message'] = __( 'Success' );
                $response['discount'] = $discount_size;
            }
        }
    }

    wp_send_json( $response );
    wp_die();
}

add_action( 'wp_ajax_discount', 'api_discount' );

/**
 * Return discount by number
 * @return object
 */
function get_discount_by_number($number) {
    return get_post_meta(
        get_page_by_title( $number, OBJECT, POST_TYPE_ERST_DISCOUNT )->ID
    );
}