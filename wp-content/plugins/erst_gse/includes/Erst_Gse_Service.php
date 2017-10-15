<?php

require_once 'Erst_Gse_Client.php';
require_once 'Erst_Gse_Resource.php';

class Erst_Gse_Service
{
	private $sheets;
	private $service;
	private $spreadsheet_id;
	
	public function __construct()
	{
		$this->service = new Google_Service_Sheets(
			(new Erst_Gse_Client())->get_client()
		);
		$this->spreadsheet_id = $this->get_spreadsheet_id_from_url(
			esc_attr(get_option('erst_gse_spreadsheet_url'))
		);
	}
	
	public function export()
	{
		$this->sheets = (new Erst_Gse_Resource())->get_sheet_data();
		$this->create_sheets();

		foreach ($this->sheets as $sheet) {
			$this->service->spreadsheets_values->update(
				$this->spreadsheet_id, $sheet['title'].'!A1',
				new Google_Service_Sheets_ValueRange(array(
				  'values' => $sheet['data']
				)),
				array('valueInputOption' => USER_ENTERED)
			);
		}
	}

	public function find($table, $column, $searchPhrase)
	{
		/** @var stdClass $sheets */
		$sheets = (new Erst_Gse_Resource())->get_config();
		if (!$sheets->{$table}) {
			throw new Exception('Table not found');
		}
		$letters = range('A', 'Z');
		$columnLetter = $letters[$column - 1];

		$offset = $start = 2;
		$step = 200;
		$ids = [];

		// read data in batches
		while (1) {
			$range = $this->service->spreadsheets_values->get(
				$this->spreadsheet_id,
				sprintf(
					"%s!%s%d:%s%d",	// format: Бронь!A2:A1002
					$sheets->{$table}->title,
					$columnLetter,
					$offset,
					$columnLetter,
					$offset + $step - 1
				)
			);
			$values = $range->getValues();
			if (is_null($values)) { // there are no more data
				break;
			}

			foreach ($values as $v) {
				$ids[] = $v[0];
			}

			if (count($values) < $step) {
				break;
			}

			$offset+= $step;
		}

		$indexes = array();
		foreach ($ids as $k => $id) {
			if ($id == $searchPhrase) {
				$indexes[] = $k + $start;
			}
		}
		return $indexes;
	}

	public function updateRow($sheet_id, $row, $data)
	{
		$sheets = (new Erst_Gse_Resource())->get_config();
		if (!$sheets->{$sheet_id}) {
			throw new Exception('Table not found');
		}
		$this->service->spreadsheets_values->update(
			$this->spreadsheet_id,
			sprintf(
				"%s!A%d",	// format: Бронь!A2
				$sheets->{$sheet_id}->title,
				$row
			),
			new Google_Service_Sheets_ValueRange(array(
				'values' => array($data)
			)),
			array('valueInputOption' => 'USER_ENTERED')
		);
	}
	
	public function update()
	{
		$all_sheets_empty = true;
		$this->sheets = (new Erst_Gse_Resource())->get_sheet_data(true);

		foreach ($this->sheets as $sheet) {
			if (count($sheet['data']) > 0) {
				$all_sheets_empty = false;
				break;
			}
		}

		if (!$all_sheets_empty) {
			foreach ($this->sheets as $sheet) {
				$this->service->spreadsheets_values->append(
					$this->spreadsheet_id, $sheet['title'],
					new Google_Service_Sheets_ValueRange(array(
						'values' => $sheet['data']
					)),
					array('valueInputOption' => 'USER_ENTERED')
				);
			}
		}
	}
	
	private function get_spreadsheet_id_from_url($url)
	{
		return explode('/', $url)[5];
	}

	private function create_sheets()
	{
		try {	
			foreach ($this->sheets as $sheet) {
				$this->service->spreadsheets->batchUpdate($this->spreadsheet_id, 
					new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
						'requests' => array(
							'addSheet' => array(
								'properties' => array(
									'title' => $sheet['title']
								)
							)
						)
					))
				);
			}
		} catch(Exception $ignore) {
			
		}
	}

}
