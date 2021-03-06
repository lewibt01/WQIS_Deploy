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
		$groups = $this->SiteGroups->find("all", ["conditions" => ["owner IN " => ["all", $this->Auth->user("userid")]]]);
		
		//get the sites and the most recent sample data for each
		$sites = $this->SiteLocations->find("all")->order("Site_Number");
		$connection = ConnectionManager::get("default");
		
		$mapData = [];
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
		
		$this->set(compact("sites"));
		$this->set(compact("measurementSettings"));
		$this->set(compact("groups"));
		$this->set(compact("mapData"));
	}

	public function daterange() {
		$this->render(false);
		
		//we use this method both in the API and the site itself, so we have to be able to accept data either as $_POST or session-saved POST
		$session = $this->getRequest()->getSession();
		$postData = $session->check("postData") ? $session->read("postData") : $_POST;

		$sites = $postData["sites"];
		
		$model = ucfirst($postData["category"]) . "Samples";
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
		
		return $this->response->withType("json")->withStringBody(json_encode([$mindate, $maxdate]));
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

	public function addsite() {
		$this->render(false);
		
		if (!($this->request->is("post") && isset($_POST["Site_Number"]))) {
			return;
		}
		
		$SiteLocation = $this->SiteLocations->newEntity();
		$SiteLocation = $this->SiteLocations->patchEntity($SiteLocation, $this->request->getData());

		$Site_Number = $this->request->getData("Site_Number");

		if ($this->SiteLocations->save($SiteLocation)) {
			$site = $this->SiteLocations
				->find("all")
				->where(["Site_Number" => $Site_Number])
				->first();
			
			return $this->response->withType("json")->withStringBody(json_encode(["siteid" => $site->ID]));
		}
	}

	public function deletesite() {
		$this->render(false);
		
		//check if siteid is set
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
	
	//API methods
	public function latestmeasures() {
		//return the most recent sample data for all sites
		$this->render(false);
		
		$session = $this->getRequest()->getSession();
		$postData = $session->read("postData");
		
		$connection = ConnectionManager::get("default");
		
		$latestMeasures = [];
		$tableNames = ["bacteria_samples", "nutrient_samples", "pesticide_samples", "physical_samples"];
		
		if (isset($postData["sites"])) {
			$sites = $postData["sites"];
			
			for ($i=0; $i<sizeof($tableNames); $i++) {
				$query = "select * from (select site_location_id, max(Date) as maxdate from " .
					$tableNames[$i] .
					" where site_location_id in (" .
					implode(",", $sites) .
					") group by site_location_id) as x inner join " .
					$tableNames[$i] .
					" as f on f.site_location_id = x.site_location_id and f.Date = x.maxdate ORDER BY `f`.`site_location_id` ASC";
					
				$queryResult = $connection->execute($query)->fetchAll("assoc");
				$latestMeasures = array_merge($latestMeasures, [$tableNames[$i] => $queryResult]);
			}
		}
		else {
			for ($i=0; $i<sizeof($tableNames); $i++) {
				$query = "select * from (select site_location_id, max(Date) as maxdate from " .
					$tableNames[$i] .
					" group by site_location_id) as x inner join " .
					$tableNames[$i] .
					" as f on f.site_location_id = x.site_location_id and f.Date = x.maxdate ORDER BY `f`.`site_location_id` ASC";
					
				$queryResult = $connection->execute($query)->fetchAll("assoc");
				$latestMeasures = array_merge($latestMeasures, [$tableNames[$i] => $queryResult]);
			}
		}
		
		return $this->response->withType("json")->withStringBody(json_encode($latestMeasures));
	}
	
	public function attributes() {
		$this->render(false);
		
		$session = $this->getRequest()->getSession();
		//validate
		if (!($session->check("postData") && ($postData = $session->read("postData")) && isset($postData["site"]))) {
			return;
		}
		
		if (isset($postData["attributes"])) {
			$query = $this->SiteLocations
				->find("all")
				->select($postData["attributes"])
				->where(["Site_Number" => $postData["site"]])
				->first();
		}
		else {
			$query = $this->SiteLocations
				->find("all")
				->where(["Site_Number" => $postData["site"]])
				->first();
		}
		
		return $this->response->withType("json")->withStringBody(json_encode($query));
	}
	
	public function latitude() {
		$this->render(false);
		
		$session = $this->getRequest()->getSession();
		//validate
		if (!($session->check("postData") && ($postData = $session->read("postData")) && isset($postData["site"]))) {
			return;
		}
		
		$query = $this->SiteLocations
			->find("all")
			->select("latitude")
			->where(["Site_Number" => $postData["site"]])
			->first();
		
		return $this->response->withType("json")->withStringBody(json_encode($query));
	}
	
	public function longitude() {
		$this->render(false);
		
		$session = $this->getRequest()->getSession();
		//validate
		if (!($session->check("postData") && ($postData = $session->read("postData")) && isset($postData["site"]))) {
			return;
		}
		
		$query = $this->SiteLocations
			->find("all")
			->select("longitude")
			->where(["Site_Number" => $postData["site"]])
			->first();
		
		return $this->response->withType("json")->withStringBody(json_encode($query));
	}
	
	public function sitelocation() {
		$this->render(false);
		
		$session = $this->getRequest()->getSession();
		//validate
		if (!($session->check("postData") && ($postData = $session->read("postData")) && isset($postData["site"]))) {
			return;
		}
		
		$query = $this->SiteLocations
			->find("all")
			->select("Site_Location")
			->where(["Site_Number" => $postData["site"]])
			->first();
		
		return $this->response->withType("json")->withStringBody(json_encode($query));
	}
	
	public function sitename() {
		$this->render(false);
		
		$session = $this->getRequest()->getSession();
		//validate
		if (!($session->check("postData") && ($postData = $session->read("postData")) && isset($postData["site"]))) {
			return;
		}
		
		$query = $this->SiteLocations
			->find("all")
			->select("Site_Name")
			->where(["Site_Number" => $postData["site"]])
			->first();
		
		return $this->response->withType("json")->withStringBody(json_encode($query));
	}
	
	public function groups() {
		$this->render(false);
		
		$session = $this->getRequest()->getSession();
		//validate
		if (!($session->check("postData") && ($postData = $session->read("postData")) && isset($postData["site"]))) {
			return;
		}
		
		$query = $this->SiteLocations
			->find("all")
			->select("groups")
			->where(["Site_Number" => $postData["site"]])
			->first();
		
		return $this->response->withType("json")->withStringBody(json_encode($query));
	}
}