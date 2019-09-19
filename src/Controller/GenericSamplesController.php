<?php

    namespace App\Controller;

    use App\Controller\AppController;

    class GenericSamplesController extends AppController {

	public function uploadlog() {
		//get the data from the file
		$file = $this->request->getData('file');
		
		if ($this->request->is('post') && $file) {
			//Check if file is valid
			$valid = $this->_fileIsValid($file);
			if (!$valid['isValid']) {
				$this->set(compact('valid'));
				return;
			}
			
			$csv = array_map('str_getcsv', file($file['tmp_name']));
			
			$fileType = GenericSamplesController::getFileType($csv);
			
			if ($fileType == 1) {
				//bacteria
				$this->loadModel('BacteriaSamples');
				$columnIDs = array('site_location_id', 'Date', 'Sample_Number', 'EcoliRawCount', 'Ecoli', 'EcoliException', 'TotalColiformRawCount', 'TotalColiform', 'ColiformException', 'Comments');
				$columnText = array("Site Number", "Date", "Sample Number", "Ecoli Raw Count", "Ecoli", "Total Coliform Raw Count", "Total Coliform", "Comments");
				GenericSamplesController::uploadGeneric($columnIDs, $columnText, $csv, $this->BacteriaSamples);
			}
			else if ($fileType == 2) {
				//nutrient
				$this->loadModel('NutrientSamples');
				$columnIDs = array('site_location_id', 'Date', 'Sample_Number', 'Phosphorus', 'PhosphorusException', 'NitrateNitrite', 'NitrateNitriteException', 'DRP', 'Comments');
				$columnText = array("Site Number", "Date", "Sample number", "Phosphorus (mg/L)", "Nitrate/Nitrite (mg/L)", "Dissolved Reactive Phosphorus", "Comments");
				GenericSamplesController::uploadGeneric($columnIDs, $columnText, $csv, $this->NutrientSamples);
			}
			else if ($fileType == 3) {
				//pesticide
				$this->loadModel('PesticideSamples');
				$columnIDs = array('site_location_id', 'Date', 'Sample_Number', 'Altrazine', 'AltrazineException', 'Alachlor', 'AlachlorException', 'Metolachlor', 'MetolachlorException', 'Comments');
				$columnText = array("Site Number", "Date", "Sample number", "Atrazine", "Alachlor", "Metolachlor", "Comments");
				GenericSamplesController::uploadGeneric($columnIDs, $columnText, $csv, $this->PesticideSamples);
			}
			else if ($fileType == 4) {
				//wqm
				$this->loadModel('WaterQualitySamples');
				$columnIDs = array('site_location_id', 'Date', 'Sample_Number', 'Time', 'Water_Temp',
					'Water_Temp_Exception', 'pH', 'pH_Exception', 'Conductivity', 'Conductivity_Exception', 'TDS',
					'TDS_Exception', 'DO', 'DO_Exception', 'Turbidity', 'Turbidity_Exception', 'Turbidity_Scale_Value',
					'Comments', 'Import_Date', 'Import_Time', 'Requires_Checking');
				$columnText = array("Site Number", "Date", "Sample number", "Time", "Water Temp", "PH", "Conductivity", "TDS", "DO", "Turbidity", "Turbidity (scale value)", "Comments", "Import Date", "Import Time", "Requires Checking");
				GenericSamplesController::uploadGeneric($columnIDs, $columnText, $csv, $this->WaterQualitySamples);
			}
			else if ($fileType == 5) {
				//site information
				$this->loadModel('SiteLocations');
				$columnIDs = array('Site_Number', 'Monitored', 'Longitude', 'Latitude', 'Site_Location', 'Site_Name');
				$columnText = array("Site Number", "Longitude", "Latitude", "Site Location", "Site Name");
				GenericSamplesController::uploadGeneric($columnIDs, $columnText, $csv, $this->SiteLocations);
			}
		}
	}

	public function getFileType($csv) {
		$bacteriaHeader = array("Site number", "Date", "Sample_number", "EcoliRawCount", "ECOLI", "EcoliException", "TotalColiformRawCount", "TotalColiform", "ColiformException", "Comments");
		$nutrientHeader = array("Site number", "Date", "Sample_number", "Phosphorus", "PhosphorusException", "NH3-N", "NH3-NException", "DRP", "Comments");
		$pesticideHeader = array("Site number", "Date", "Sample_number", "Atrazine", "AtrazineException", "Alachlor", "AlachlorException", "Metolachlor", "MetolachlorException", "Comments");
		$WQMHeader = array("Site number", "Date", "Sample_number", "Time", "Water_Temp", "WaterTempException", "PH", "pHException", "Conductivity", "ConductivityException", "TDS", "TDSException", "DO", "DOException", "Turbidity (meter reading)", "TurbidityException", "Turbidity (scale value)", "Comments", "ImportDate", "ImportTime", "Requires_checking");
		$siteInfoHeader = array("Site_Number", "Longitude", "Latitude", "Site_Location", "Site_Name");
		
		$headerRow = $csv[0];
		
		if ($headerRow == $bacteriaHeader) {
			return 1;
		}
		else if ($headerRow == $nutrientHeader) {
			return 2;
		}
		else if ($headerRow == $pesticideHeader) {
			return 3;
		}
		else if ($headerRow == $WQMHeader) {
			return 4;
		}
		else if ($headerRow == $siteInfoHeader) {
			return 5;
		}
	}
	
	public function uploadGeneric($columnIDs, $columnsText, $csv, $modelBare) {
		$log = array();
		
		//go through each non-header row
		for ($row=1; $row<sizeof($csv); $row++) {
			$currentRow = array();
			$uploadData = [];
			
			//Get every column's data in the row
		    for ($column = 0; $column < sizeof($columnIDs); $column++) {
				$currentElement = $csv[$row][$column];
				$currentColumn = $columnIDs[$column];
				//Check if the current column name does not contain exception
				if (strpos($currentColumn, "Exception") === false) {
					$currentRow[] = $currentElement;
				}

				$uploadData[$currentColumn] = $currentElement;
		    }
			
			//create the entity to save
			$entity = $modelBare->patchEntity($modelBare->newEntity(), $uploadData);
			
			if ($modelBare->save($entity)) {
				$currentRow[] = "File uploaded successfully";
			}
			else {
				$currentRow[] = $entity->getErrors();
			}
			$log[] = $currentRow;
		}
		
		$this->set(compact('log'));
		$this->set(compact('columnsText'));
	}
}