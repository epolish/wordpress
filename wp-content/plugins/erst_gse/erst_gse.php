<?php
/*
Plugin Name: Google Spreadsheet Export for Studioschastie.com
Plugin URI: https://studioschastie.com.ua/
Description: Adds admin section for managing google spreadsheet export
Version: 1.0
Author: NUCLEO Inc
Author URI: http://design.nucleo.com.ua/
*/


/*
 * Current plugin config
 *
 * {
  "certificate_request": {
	"title": "Сертификаты",
	"fields": {
	  "created_at": "Дата создания",
	  "type": "Тип",
	  "name": "ФИО",
	  "phone": "Телефон",
	  "email": "Email",
	  "message": "Сообщение"
	}
  },
  "dress_rent_request": {
	"title": "Платья",
	"fields": {
	  "created_at": "Дата создания",
	  "rent_period": "Период брони",
	  "name": "ФИО",
	  "phone": "Телефон",
	  "email": "Email",
	  "full_date": "Желаемая дата брони",
	  "code": "Код платья",
	  "size": "Размер платья",
	  "message": "Сообщение"
	}
  },
  "packages_request": {
	"title": "Заявка на фотосъёмку",
	"fields": {
	  "created_at": "Дата создания",
	  "type": "Тип",
	  "package": "Пакет",
	  "name": "ФИО",
	  "phone": "Телефон",
	  "email": "Email",
	  "message": "Сообщение"
	}
  },
  "_orders": {
	"title": "Бронь",
	"fields": {
		"id": "№ заказа",
	    "created_at": "Дата создания",
		"date": "Дата",
		"start_time": "Начало",
		"end_time": "Конец",
		"status": "Статус",
		"name": "Имя",
		"surname": "Фамилия",
		"email": "Email",
		"comments": "Комментарии",
		"discount_number": "Дисконтная карта",
		"area": "Зал",
		"additional_monoblock": "Дополнительный моноблок",
		"more_participants": "Больше участников",
		"animals": "Животные",
		"dress_rent": "Аренда платья",
		"makeup_room": "Гримерка",
		"makeup_service": "Макияж"
	}
  }
}
 */

define( 'PLUGIN_NAME', 'erst_gse' );
define( 'ADMIN_TITLE', __( 'Google Spreadsheet Export' ) );
define( 'PLUGIN_DIR', dirname( __FILE__ ).DIRECTORY_SEPARATOR  ); 

function erst_gse_template() {
	require_once PLUGIN_DIR.'html/form.php';

	wp_enqueue_style( 'jsoneditor', plugins_url( '/css/vendor/jsoneditor.min.css', __FILE__ ) );
	wp_enqueue_script( 'jsoneditor', plugins_url( '/js/vendor/jsoneditor.min.js', __FILE__ ) );	
};

function plugin_setup_menu() {
	add_menu_page(
		ADMIN_TITLE,
		ADMIN_TITLE,
		'manage_options',
		PLUGIN_NAME.'_plugin',
		PLUGIN_NAME.'_template'
	);
};

add_action( 'admin_menu', 'plugin_setup_menu' );

function erst_gse_init() {
	require_once PLUGIN_DIR.'includes/Erst_Gse_Service.php';
	
	delete_transient( PLUGIN_NAME.'_error' );
	delete_transient( PLUGIN_NAME.'_success' );
	$client = new Erst_Gse_Client();

	try {
		if ( isset( $_GET['code'] ) ) {
			$client->set_token_from_code( 
				esc_attr( $_GET['code'] ) 
			);
			( new Erst_Gse_Service() )->export();
			set_transient( PLUGIN_NAME.'_success', __( 'Операция произошла успешно' ) );
		}

		if ( $_SERVER['REQUEST_METHOD'] == 'POST') {
			$client_id = $_POST[ 'client_id' ];
			$client_secret = $_POST[ 'client_secret' ];
			$developer_key = $_POST[ 'developer_key' ];
			$spreadsheet_url = $_POST[ 'spreadsheet_url' ];
			$table_settings = $_POST[ 'table_settings' ];
			
			if ( 
				isset( $client_id ) && isset( $client_secret ) && isset( $developer_key )
				&& isset( $spreadsheet_url ) && isset( $table_settings )
			) {
				delete_option( 'erst_gse_oauth_token' );
				update_option( 'erst_gse_client_id', esc_attr( $client_id ) );
				update_option( 'erst_gse_client_secret', esc_attr( $client_secret ) );
				update_option( 'erst_gse_developer_key', esc_attr( $developer_key ) );
				update_option( 'erst_gse_spreadsheet_url', esc_attr( $spreadsheet_url ) );
				update_option( 'erst_gse_table_settings', stripslashes( $table_settings ) );
				
				$client->check_connection();

				set_transient( PLUGIN_NAME.'_success', __( 'Операция произошла успешно' ) );
			} else {
				set_transient( PLUGIN_NAME.'_error', __( 'Не все поля заполнены' ) );
			}
		}		
	} catch ( Google_Service_Exception $ex ) {
		set_transient( PLUGIN_NAME.'_error', $ex->getMessage() );
	} catch ( Exception $ex ) {
		set_transient( PLUGIN_NAME.'_error', $ex->getMessage() );
	}

	add_rewrite_rule(
		'^'.PLUGIN_NAME.'_cron/(\w)?',
		'index.php?'.PLUGIN_NAME.'_cron_action=$matches[1]',
		'top'
	  );
};

add_action( 'init', PLUGIN_NAME.'_init' );

function erst_gse_query_vars( $vars ) {
	$vars[] = PLUGIN_NAME.'_cron_action';

	return $vars;
}

add_filter( 'query_vars', PLUGIN_NAME.'_query_vars' );

function erst_gse_requests ( $wp ) { 

	$valid_actions = array('r');

	if ( !empty( $wp->query_vars[PLUGIN_NAME.'_cron_action'] ) &&
		in_array( $wp->query_vars[PLUGIN_NAME.'_cron_action'], $valid_actions )
	) {
		require_once PLUGIN_DIR.'includes/Erst_Gse_Service.php';
		
		try {
			( new Erst_Gse_Service() )->update();
		} catch ( Google_Service_Exception $ex ) {
			print $ex->getMessage();
		} catch ( Exception $ex ) {
			print $ex->getMessage();
		}
		exit;
	} elseif ($wp->query_vars[PLUGIN_NAME.'_cron_action'] == 'o') {

		// THIS SHOULD BE REWRITTEN USING actions!!
		require_once PLUGIN_DIR.'includes/Erst_Gse_Service.php';
		$service = new Erst_Gse_Service();

		global $wpdb;
		$table = ssch_booking_order_table_name();
		$sql = "SELECT id FROM $table WHERE updated=1";
		$ids = $wpdb->get_col($sql);

		print "Update orders: " . implode(', ', $ids) . "\n";
		foreach ($ids as $id) {
			$indexes = $service->find('_orders', 1, $id);

			$data = get_order_representation_for_google_sheet($id);
			foreach ($indexes as $k => $index) {
				if (isset($data[$k])) {
					$service->updateRow('_orders', $index, $data[$k]);
				}
			}
			$wpdb->update($table, array('updated' => 0), array('id' => $id));
		}
		exit;

	}
}

add_action( 'parse_request', PLUGIN_NAME.'_requests' );
