<?php

class Erst_Gse_Resource
{

	private $service;
	private $table_config;
	const MAX_ROW_COUNT = 9709551615;
	
	public function __construct()
	{
		$this->table_config = json_decode(get_option('erst_gse_table_settings'));
	}

	public function get_config()
	{
		return $this->table_config;
	}

	public function get_sheet_data($update = false)
	{

		global $wpdb;
		
		$sheets = [];
		$table_meta_old = $update ? json_decode(get_option('erst_gse_table_meta')) : null;
		
		foreach ($this->table_config as $key => $value) {
			$field_titles = [];
			
			if (!$table_meta_old) {
				foreach ($value->fields as $sub_key => $sub_value) {
					$field_titles[] = $sub_value;
				}
			}

			$rows = [];
			if ($key{0} == '_') { // it's pseudo table. Get its data from hook
				$transport = new stdClass();
				$transport->start_from = ($field_titles ? null : $table_meta_old->$key);
				do_action('get_data_for_table_' . $key, $transport);
				$rows = $transport->data;
				$table_meta[$key] = $transport->count;
			} else {
				$rows = $wpdb->get_results(
					$this->get_select_sql($key, $value->fields, $field_titles ? null : $table_meta_old->$key), ARRAY_N
				);
				$table_meta[$key] = $wpdb->get_results($this->get_rows_count_sql($key), ARRAY_N)[0][0];
			}

			$data = ($field_titles ? array_merge([$field_titles], $rows) : $rows);

			$sheets[] =[
				'title' => $value->title,
				'data' 	=> $this->replace_null_values($data)
			];
			

		}
		
		update_option('erst_gse_table_meta', json_encode($table_meta));
		
		return $sheets;
	}
	
	public function get_select_sql($table_name, $fields, $start_from = null)
	{
		global $wpdb;
		
		$columns = '';
		$select = 'SELECT';
		$from = ' FROM '.$wpdb->prefix.$table_name;
		
		foreach ($fields as $key => $value) {
			$columns .= ', '.$key;
		}
		
		$columns[0] = ' ';
		$sql = $select.$columns.$from;
		
		if ($start_from) {
			$sql .= ' LIMIT '.$start_from.', '.self::MAX_ROW_COUNT;
		}
		
		return $sql;
	}
	
	public function get_rows_count_sql($table_name)
	{
		global $wpdb;

		return 'SELECT COUNT(*) FROM '.$wpdb->prefix.$table_name;
	}
	
	private function replacement(&$item, $key)
	{
		$item = ($item === NULL) ? "" : $item;
		if ($item{0} == '+') { // fix misinterpretation as formula
			$item = "'" . $item;
		}
		if ($item{0} == '=') { // fix misinterpretation as formula
			$item = "'" . $item;
		}
	}

	private function replace_null_values(&$array)
	{
		array_walk_recursive($array, array($this, 'replacement'));
		
		return $array;
	}
}
