<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;

class SiteLocationsController extends AppController {
	public function chartselection() {
		//check if tutorial should be run
		if (!$this->Auth->user("hasTakenTutorial")) {
			$this->set("runTutorial", true);
		
			//user will now have taken the tutorial, so set that flag true
			$this->loadModel("Users");
			$user = $this->Users
				->find("all")
				->where(["username" => $this->Auth->user("username")])
				->first();
			
			$user->hasTakenTutorial = 1;
			$this->Users->save($user);
		
			//changing db value doesn't update Auth object that contains info about currently-logged in user, so we need to change that object to match this $user object
			$this->Auth->setUser($user);
		}
		else if (isset($_GET["runTutorial"])) {
			$this->set("runTutorial", true);
		}
		
		//get measurement settings
		$this->loadModel("MeasurementSettings");
		
		$bacteriaSettings = $this->MeasurementSettings->find("all")
			->where(["Category" => "Bacteria"]);
			
		$pesticideSettings = $this->MeasurementSettings->find("all")
			->where(["Category" => "Pesticide"]);
			
		$nutrientSettings = $this->MeasurementSettings->find("all")
			->where(["Category" => "Nutrient"]);
			
		$physicalSettings = $this->MeasurementSettings->find("all")
			->where(["Category" => "Physical"]);
		
		//get groups
		$this->loadModel("SiteGroups");
		
		//show only groups that are either public or owned by this user
		$groups = $this->SiteGroups->find("all", ["conditions" =>
				["owner IN " => ["all", $this->Auth->user("userid")]]]);
		
		//get the sites and the most recent sample data for each
		$sites = $this->SiteLocations->find("all")->order("Site_Number");
		$connection = ConnectionManager::get("default");
		
		$mapData = ["SiteData" => $sites];
		$tableNames = ["bacteria_samples", "nutrient_samples", "pesticide_samples", "physical_samples"];
		
		for ($i=0; $i<sizeof($tableNames); $i++) {
			$query = "select * from (select site_location_id, max(Date) as maxdate from " .
				$tableNames[$i] .
				" group by site_location_id) as x inner join " .
				$tableNames[$i] .
				" as f on f.site_location_id = x.site_location_id and f.Date = x.maxdate ORDER BY `f`.`site_location_id` ASC";
				
			$queryResult = $connection->execute($query)->fetchAll("assoc");
			$mapData = array_merge($mapData, [$tableNames[$i] => $queryResult]);
		}
		
		$measurementSettings = ["bacteria" => $bacteriaSettings, "nutrient" => $nutrientSettings, "pesticide" => $pesticideSettings, "physical" => $physicalSettings];
		
		$this->set(compact("measurementSettings"));
		$this->set(compact("groups"));
		$this->set(compact("mapData"));
	}

	public function daterange() {
		$this->render(false);

		$sites = $this->request->getData("sites");
		
		$model = ucfirst($this->request->getData("category")) . "Samples";
		$this->loadModel($model);
		
		//get min/max date of all the sites
		$measureQuery = $this->$model
			->find("all", [
				"conditions" => [
					"site_location_id IN " => $sites
				],
				"fields" => [
					"mindate" => "MIN(Date)",
					"maxdate" => "MAX(Date)"
				]
			])->first();
		
		//format date properly
		$mindate = date("m/d/Y", strtotime($measureQuery["mindate"]));
		$maxdate = date("m/d/Y", strtotime($measureQuery["maxdate"]));
		$dateRange = json_encode([$mindate, $maxdate]);
		
		$this->response = $this->response->withStringBody($dateRange);
		$this->response = $this->response->withType("json");
		
		return $this->response;
	}

	public function sitemanagement() {
		$SiteLocations = $this->SiteLocations->find("all")->order(["Site_Number" => "ASC"]);
		$numSites = $SiteLocations->count();
		
		//get min/max date of all the sites
		$categories = ["BacteriaSamples", "NutrientSamples", "PesticideSamples", "PhysicalSamples"];
		for ($i=0; $i<sizeof($categories); $i++) {
			$this->loadModel($categories[$i]);
		}
		
		foreach ($SiteLocations as $site) {
			$minDate = 3000; //please don't be using this still in 980 years...
			$maxDate = 0;
			
			foreach ($categories as $category) {
				$measureQuery = $this->$category
					->find("all", [
						"conditions" => [
							"site_location_id" => $site->Site_Number
						],
						"fields" => [
							"minDate" => "MIN(YEAR(Date))",
							"maxDate" => "MAX(YEAR(Date))"
						]
					])->first();
			
				if ($measureQuery["minDate"] != null && intval($measureQuery["mindate"]) < $minDate) {
					$minDate = intval($measureQuery["minDate"]);
				}
				if (intval($measureQuery["maxDate"]) > $maxDate) {
					$maxDate = intval($measureQuery["maxDate"]);
				}
			}
			
			if ($minDate === 3000) {
				$site->dateRange = "No data";
			}
			else {
				$site->dateRange = $minDate . " to " . $maxDate;
			}
		}
		
		//get all groups visible to this user
		$this->loadModel("SiteGroups");
		$SiteGroups = $this->SiteGroups
			->find("all", [
				"conditions" => [
					"owner IN " => ["all", $this->Auth->user("userid")]
				],
				"fields" => [
					"groupKey",
					"groupName"
				]
			]);
		
		//get group-site relationships
		$Groupings = $this->SiteLocations->find()->select(["Site_Number", "groups"]);
		
		$this->set(compact("SiteGroups"));
		$this->set(compact("Groupings"));
		$this->set(compact("SiteLocations"));
		$this->set(compact("numSites"));
	}
	
	public function updatefield() {
		$this->render(false);
	
		//ensure sample number data was included
		if (!$this->request->getData("siteNumber")) {
			return;
		}
		$siteNumber = $this->request->getData("siteNumber");
	
		$parameter = $this->request->getData("parameter");
		$value = $this->request->getData("value");
		
		//get the site we are editing
		$site = $this->SiteLocations
			->find("all")
			->where(["Site_Number" => $siteNumber])
			->first();
		
		if ($parameter != "groups") {
			//Set the edited field
			$site->$parameter = $value;
		}
		else {
			//need to handle groups separately, because we get the value as an array but need to convert to comma-separated values for DB storage
			if ($value == []) {
				$site->groups = "";
			}
			else {
				$groupsString = $value[0];
				for ($i=1; $i<sizeof($value); $i++) {
					$groupsString = $groupsString . "," . $value[$i];
				}
				$site->groups = $groupsString;
			}
		}
		
		//save changes
		$this->SiteLocations->save($site);
	}

	public function fetchsitedata() {
		$this->render(false);
		//Check if siteid is set
		if (!$this->request->getData("siteid")) {
			return;
		}
		$siteid = $this->request->getData("siteid");

		$site = $this->SiteLocations
			->find("all")
			->where(["ID" => $siteid])
			->first();

		$json = json_encode(["sitenumber" => $site->Site_Number,
			"longitude" => $site->Longitude,
			"latitude" => $site->Latitude,
			"sitelocation" => $site->Site_Location,
			"sitename" => $site->Site_Name]);
		
		$this->response = $this->response->withStringBody($json);
		$this->response = $this->response->withType("json");
			
		return $this->response;
	}

	public function addsite() {
		$this->render(false);
		$SiteLocation = $this->SiteLocations->newEntity();
		if ($this->request->is("post")) {
			$SiteLocation = $this->SiteLocations->patchEntity($SiteLocation, $this->request->getData());

			$Site_Number = $this->request->getData("Site_Number");

			if ($this->SiteLocations->save($SiteLocation)) {
				$site = $this->SiteLocations
					->find("all")
					->where(["Site_Number" => $Site_Number])
					->first();
				$this->response->type("json");

				$json = json_encode(["siteid" => $site->ID]);
				$this->response->body($json);
				return;
			}
		}
	}

	public function deletesite() {
		$this->render(false);
		//Check if siteid is set
		if (!$this->request->getData("siteid")) {
			return;
		}
		$siteid = $this->request->getData("siteid");
		$site = $this->SiteLocations
			->find("all")
			->where(["ID" => $siteid])
			->first();
		//delete the site
		$this->SiteLocations->delete($site);
	}
}