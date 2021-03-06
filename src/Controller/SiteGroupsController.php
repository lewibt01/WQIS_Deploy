<?php
namespace App\Controller;

use App\Controller\AppController;

class SiteGroupsController extends AppController {
	public function sitegroups() {
		//for administrators, get all groups. Otherwise, only show groups that are either public or visible to this user
		if ($this->Auth->user("admin")) {
			$SiteGroups = $this->SiteGroups->find("all");
		}
		else {
			$SiteGroups = $this->SiteGroups->find("all", ["conditions" =>
				["owner IN " => ["all", $this->Auth->user("userid")]]]);
		}
		$this->set(compact("SiteGroups"));
		
		$this->SiteLocations = $this->loadModel("SiteLocations");
		$Groupings = $this->SiteLocations->find()->select(["Site_Number", "groups"]);
		$this->set(compact("Groupings"));
		
		$SiteLocations = $this->SiteLocations->find("all");
		$this->set(compact("SiteLocations"));
	}
	
	public function fetchgroupdata() {
		$this->render(false);
		//Check if groupkey is set
		if (!$this->request->getData("groupkey")) {
			return;
		}
		$groupkey = $this->request->getData("groupkey");

		$group = $this->SiteGroups
			->find("all")
			->where(["groupKey" => $groupkey])
			->first();

		$this->loadModel("SiteLocations");
		$sitesInGroup = $this->SiteLocations
			->find("all")
			->where(["groups LIKE" => "%" . $groupkey . "%"])
			->select("Site_Number");

		$sites = array();
		foreach ($sitesInGroup as $grouping) {
			$sites[] = $grouping->Site_Number;
		}

		$json = json_encode(["groupname" => $group->groupName,
			"groupdescription" => $group->groupDescription,
			"sites" => $sites]);
		
		return $this->response->withType("json")->withStringBody($json);
	}

	public function updategroupdata() {
		$this->render(false);

		if (!$this->request->getData("groupkey")) {
			return;
		}

		$groupkey = $this->request->getData("groupkey");

		$group = $this->SiteGroups
			->find("all")
			->where(["groupKey" => $groupkey])
			->first();

		$this->loadModel("SiteLocations");
		$sitesDB = $this->SiteLocations->find("all");
		
		$sitesInGroup = $_POST["sites"];
		
		foreach ($sitesDB as $site) {
			$siteNum = $site->Site_Number;
			//does it already have this group assigned in the DB?
			$siteGroupsDB = explode(",", $site->groups);
			if (in_array($groupkey, $siteGroupsDB)) {
				//yes
				//should it be?
				if (!in_array($siteNum, $sitesInGroup)) {
					//no, rebuild this list without this groupkey
					$groupsForSiteNew = [];
					for ($i=0; $i<sizeof($siteGroupsDB); $i++) {
						if ($siteGroupsDB[$i] != $groupkey) {
							$groupsForSiteNew[] = $siteGroupsDB[$i];
						}
					}
				
					if (sizeof($groupsForSiteNew) == 0) {
						$groupsStringNew = "";
					}
					else {
						$groupsStringNew = $groupsForSiteNew[0];
						for ($i=1; $i<sizeof($groupsForSiteNew); $i++) {
							$groupsStringNew = $groupsStringNew . "," . $groupsForSiteNew[$i];
						}
					}
					
					//save this
					$site->groups = $groupsStringNew;
					$this->SiteLocations->save($site);
				}
			}
			else {
				//no
				//should it?
				if (in_array($siteNum, $sitesInGroup)) {
					//yes, rebuild this list with this groupkey
					$siteGroupsDB[] = $groupkey;
				
					if (sizeof($siteGroupsDB) == 0) {
						$groupsStringNew = "";
					}
					else {
						$groupsStringNew = $siteGroupsDB[0];
						for ($i=1; $i<sizeof($siteGroupsDB); $i++) {
							$groupsStringNew = $groupsStringNew . "," . $siteGroupsDB[$i];
						}
					}
					
					//save this
					$site->groups = $groupsStringNew;
					$this->SiteLocations->save($site);
				}
			}
		}
			
		$group->groupName = $this->request->getData("groupname");
		$group->groupDescription = $this->request->getData("groupdescription");

		if ($this->SiteGroups->save($group)) {
			return;
		}
	}

	public function addgroup() {
		$this->render(false);

		if ($this->request->is("post")) {
			$groupName = $this->request->getData("groupname");
			$SiteGroup = $this->SiteGroups->newEntity(["groupName" => $groupName, "groupDescription" => $this->request->getData("groupdescription")]);
			
			if ($this->Auth->user("admin") && $this->request->getData("makePrivate") == "false") {
				$SiteGroup->owner = "all";
			}
			else {
				$SiteGroup->owner = $this->Auth->user("userid");
			}
			
			if ($this->SiteGroups->save($SiteGroup)) {
				$group = $this->SiteGroups
					->find("all")
					->where(["groupName" => $groupName])
					->first();

				$this->loadModel("SiteLocations");
				foreach ($this->request->getData("sites") as $site) {
					//add this group to the sites group list
					//first get the site
					$siteObj = $this->SiteLocations
						->find("all")
						->where(["Site_Number" => $site])
						->first();
					
					//get its existing list of groups
					$existingGroups = $siteObj->groups;
					
					if ($existingGroups == null) { //no groups present
						$siteObj->groups = $group->groupKey;
					}
					else {
						$siteObj->groups = $siteObj->groups . "," . $group->groupKey;
					}
					
					$this->SiteLocations->save($siteObj);
				}

				return $this->response->withType("json")->withStringBody(json_encode(["groupKey" => $group->groupKey]));
			}
		}
	}

	public function deletegroup() {
		$this->render(false);
		//Check if groupKey is set
		if (!$this->request->getData("groupkey")) {
			return;
		}
		$groupkey = $this->request->getData("groupkey");

		$group = $this->SiteGroups
			->find("all")
			->where(["groupKey" => $groupkey])
			->first();
			
		//get all existing sites with this group assigned
		$this->loadModel("SiteLocations");
		$sites = $this->SiteLocations->find("all");
		foreach ($sites as $site) {
			$groupsForSite = explode(",", $site->groups);
			
			if (in_array($groupkey, $groupsForSite)) {
				//rebuild this list without this groupkey
				$groupsForSiteNew = [];
				for ($i=0; $i<sizeof($groupsForSite); $i++) {
					if ($groupsForSite[$i] != $groupkey) {
						$groupsForSiteNew[] = $groupsForSite[$i];
					}
				}
				
				if (sizeof($groupsForSiteNew) == 0) {
					$groupsStringNew = "";
				}
				else {
					$groupsStringNew = $groupsForSiteNew[0];
					for ($i=1; $i<sizeof($groupsForSiteNew); $i++) {
						$groupsStringNew = $groupsStringNew . "," . $groupsForSiteNew[$i];
					}
				}
				
				//save this
				$site->groups = $groupsStringNew;
				$this->SiteLocations->save($site);
			}
		}

		//then delete the site_group
		$this->SiteGroups->delete($group);
	}
}
?>