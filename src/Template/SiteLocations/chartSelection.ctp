<?= $this->Html->css('chartSelection.css') ?>
<?= $this->Html->script('konami.js') ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.3.0/Chart.bundle.js"></script>
<?= $this->Html->script('chartjs-plugin-annotation.js') ?>
<script defer src="../js/charting.js"></script>
<script defer src="../js/lib/a"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css" rel="stylesheet" />

<link rel="stylesheet" href="https://js.arcgis.com/4.14/esri/themes/light/main.css" />

<script>
var admin = <?php echo $admin?>;
</script>

<div class="sidebarContainer">
	<div id="sidebarInner">
		<div id="sidebarSpacing" style="height: 6vh"></div> <!--filler space so the sidebar doesn't get covered by the navbar-->
		<div id="searchBox">
		<fieldset>
			<h6>Sites:</h6>
			<select class="js-example-placeholder-multiple form-control" id="sites" name="site[]" multiple="multiple" style="width: 100%">
			<?php
			//populate the site drop down box
			foreach ($siteLocations as $siteLocation) {
				$siteNumber = $this->Number->format($siteLocation->Site_Number);
				$siteName = h($siteLocation->Site_Name);
				$siteLocation = h($siteLocation->Site_Location);
				echo "<option value=$siteNumber title=\"$siteLocation\">$siteNumber $siteName</option>";
			}
			?>
			</select>
		
			<hr/>
		
			<h6>Categories:</h6>
			<?=
			$this->Form->select('categorySelect', [
				'bacteria' => 'Bacteria',
				'nutrient' => 'Nutrient',
				'pesticide' => 'Pesticide',
				'physical' => 'Physical Properties'
				], [
				'label' => 'Category',
				'id' => 'categorySelect',
				'class' => 'form-control select'
				]
			)
			?>
			
			<hr/>
			
			<div>
				<h6>From:</h6>
				<?=
				$this->Form->control('startDate', [
					'label' => false,
					'type' => 'text',
					'class' => 'form-control date-picker col-lg-12',
					'id' => 'startDate',
					'placeholder' => 'mm/dd/yyyy'
				])
				?>
			</div>
				
			<div>
				<h6>To:</h6>
				<?=
				$this->Form->control('endDate', [
					'label' => false,
					'type' => 'text',
					'class' => 'form-control date-picker col-lg-12',
					'id' => 'endDate',
					'placeholder' => 'mm/dd/yyyy'
				])
				?>
			</div>
				
			<hr/>
				
			<div>
				<h6>Where</h6>
				<?=
				$this->Form->select('measurementSelect', [
					'select' => 'Select a measure',
					'Ecoli' => 'E. Coli (CFU/100 mil)',
					'TotalColiform' => 'Coliform (CFU/100 mil)'
					], [
					'label' => 'Measurement',
					'id' => 'measurementSelect',
					'class' => 'form-control select'
					]
				)
				?>
			</div>
			<div>
			<?=
			$this->Form->select('overUnderSelect', [
				'>=' => 'Over',
				'<=' => 'Under',
				'==' => 'Equal To'
				], [
				'label' => 'Search',
				'id' => 'overUnderSelect',
				'class' => 'form-control select'
				]
			)
			?>
			</div>
			<div>
			<?=
			$this->Form->control('amountEnter', [
				'label' => false,
				'type' => 'text',
				'class' => 'form-control input col-lg-12',
				'id' => 'amountEnter',
				'placeholder' => 'Get Benchmark'
			])
			?>
			</div>
				
			<hr/>
			
			<div>
				<h6>Display fields:</h6>
				<ul id="checkboxList" style="list-style-type:none;">
					<li>
						<input type="checkbox" value="all" id="allCheckbox" checked><label for="allCheckbox">All</label>
					</li>
					<li>
						<input class="measurementCheckbox" type="checkbox" id="EcoliCheckbox" value="Ecoli" checked><label for="Ecoli">E. Coli (CFU/100 mil)</label>
					</li>
					<li>
						<input class="measurementCheckbox" type="checkbox" id="TotalColiformCheckbox" value="TotalColiform" checked><label for="TotalColiformCheckbox">Coliform (CFU/100 mil)</label>
					</li>
				</ul>
			</div>
				
			<button type="button" id="updateButton">Update</button>
			<button type="button" id="resetButton">Reset</button>
		</fieldset>
		<?= $this->Form->end() ?>
		</div>
	</div>
	<div class="sidebarToggle" id="sidebarToggle">
		<b id="sidebarToggleLabel">CLOSE</b>
	</div>
</div>

<div class="col-lg-12" id="main">
	<div class="card">
		<div class="card-header">
			<h4><a class="collapsible-panel card-link" data-toggle="collapse" href="#collapseOne">Map</a></h4>
		</div>
		<div id="collapseOne" class="panel-collapse collapse show">
			<div class="panel-body" id="mapContainer">
				<div id="map"></div>
				<div class="row">
					<div class="col-sm">
						<input type="checkbox" id="watershedsLayer" checked /> Watersheds
						<input type="checkbox" id="drainsLayer" /> Drains
						<input type="checkbox" id="riverLayer" /> Rivers/Streams
						<input type="checkbox" id="impairedLayer" /> IDEM - Impaired Waters
						<input type="checkbox" id="bodiesLayer" /> Water Bodies
						<input type="checkbox" id="floodLayer" /> Floodplains
						<input type="checkbox" id="damLayer" /> Dams
						<input type="checkbox" id="wellLayer" /> Wells
						<input type="checkbox" id="wetlandLayer" /> Wetlands
					</div>
					<div class="col-sm-6">
						Use basemap
						<select id="selectBasemap">
							<option value="gray">Gray</option>
							<option value="satellite">Satellite</option>
							<option value="osm">Streets</option>
							<option value="hybrid">Hybrid</option>
							<option value="terrain">Terrain</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
		
	<div class="card">
		<div class="card-header">
			<h4><a class="collapsible-panel card-link" data-toggle="collapse" href="#collapseTwo">Timeline</a></h4>
		</div>
		<div id="collapseTwo" class="panel-collapse collapse show">
			<div class="panel-body">
				<div id="chartsLayoutSelect" style="display: none">
					<button type="button" id="chartsInlineButton">In-line</button>
					<button type="button" id="chartsGridButton">Grid</button>
					<input type="checkbox" id="showBenchmarks" value="showBenchmarks" checked>Show benchmark lines
				</div>
				<div id="chartDiv" style="text-align: center;"></div>
				<span id="chartsNoData">No data to display</span>
				<span id="chartsWhereError" style="display: none">Graph view does not support the "Where" search feature</span>
			</div>
		</div>
	</div>
		
	<div class="card">
		<div class="card-header">
			<h4><a class="collapsible-panel card-link" data-toggle="collapse" href="#collapseThree">Table</a></h4>
		</div>
		<div id="collapseThree" class="panel-collapse collapse show">
			<div class="panel-body">
				<div id="tableSettings" style="display: none">
					Show 
					<select id="numRowsDropdown">
						<option value=10>10</option>
						<option value=25 selected="selected">25</option>
						<option value=100>100</option>
						<option value=500>500</option>
						<option value=-1>All</option>
					</select>
					results
				</div>
				<div id="tableDiv"></div>
				<span id="tableNoData">No data to display</span>

				<div id="tablePageSelector" style="display: none">
					<button type="button" id="firstPageButton">First</button>
					<button type="button" id="previousPageButton">Previous</button>
					Page <input type="text" id="pageNumBox" name="pageNumBox" value="1" size=3></input> of <span id="totalPages">x</span>
					<button type="button" id="nextPageButton">Next</button>
					<button type="button" id="lastPageButton">Last</button>
				</div>
			</div>
		</div>
	</div>
		
	<?=
	$this->Form->button('Export', [
		'label' => false,
		'type' => 'submit',
		'class' => 'btn btn-basic btn-lg mb-3 mt-3 col-md-4 float-right',
		'id' => 'exportBtn'
	])
	?>
</div>
