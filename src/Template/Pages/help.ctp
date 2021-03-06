<?php
echo $this->Html->css("help.css");
echo $this->Html->script("help.js");
?>

<h1>Water Quality Information System Help Page</h1>
<h2>Map</h2>
<div class="indent">
	<p>
		The <b>Map</b>, built on ESRI's <a href="https://www.esri.com/en-us/arcgis/about-arcgis/overview">ArcGIS</a> platform, provides a visual representation of the location of each sample site. It is a geographic information system, designed to aid in the analysis of spatial data. Click on a point to view the most recent sample data associated with it.
	</p>
	
	<h4>Layers</h4>
	<div class="indent">
		<p>
			One of the main advantages of using a GIS-based map is access to toggleable <b>map layers</b>. Layers are a way to display different geographical features or datasets on the map. WQIS currently incorporates the following layers:
			
			<ul>
				<li>Watersheds</li>
				<li>Drains</li>
				<li>Rivers and streams</li>
				<li>IDEM Impaired Waters</li>
				<li>Water bodies</li>
				<li>Floodplains</li>
				<li>Dams</li>
				<li>Wells</li>
				<li>Wetlands and Deepwater Habitats</li>
			</ul>
			
			Each layer can be independently turned on or off using the checkboxes below the map. When applicable, color or pattern keys for visible layers are shown in the top-left corner of the map.
		</p>
	</div>
	
	<h4>Basemaps</h4>
	<div class="indent">
		<p>
			<b>Basemaps</b> are different backgrounds for the map that highlight different features. For example, the default 'Satellite' basemap offers a colorful photographic view. Whereas the 'Streets' basemap displays all major and minor road ways in the area, but forgoes environmental details.
		</p>
	</div>
</div>

<h2>Search</h2>
<div class="indent">
	<p>
		Use the sidebar on the left side to select the information you're interested in viewing. First, select one or more sample sites. Any number of sites can be selected simultaneously.
	</p>
	<p>
		"Search by group" is an optional parameter which fills in the Sites selection box with all of the sites contained in the selected group. You can still manually add or remove sites from the search, as normal.
	</p>
	<p>
		The "Aggregate Mode" option underneath determines how the sites are displayed after a search. If enabled, Aggregate Mode takes the average value of each site for each date and measurement. This allows patterns shared by several sites (eg, nearby points all near a common pollutant source) to be easily viewed.
	</p>
	
	<p>
		Then, select the category of data to view. Bacteria, nutrient, pesticide, and physical properties data are available. For more information on these types of data, see our about page. The selected category will determine which specific measurements can be searched for.
	</p>
	<p>
		Select a start and end date over which to view records. These fields will automatically be filled in to match the full range of data available for the specified sites and category, but you can choose a different range if desired.
	</p>
	<p>
		The "Filter by" section is optional. If used, this allows a user to select records to view where some measurement is greater than, less than, or equal to a given value. Note that the Timeline does not support searches using the Where option.
	</p>
	<p>
		Finally, "Display fields" allows the user to choose which measurements they wish to see. By default, "All" is selected, but if you are only interested in a subset of measurements, selecting those measurements will result in a view which is less cluttered and loads faster. Some meta fields, such as site number and date, are present in all cases and cannot be disabled.
	</p>
	<p>
		Use the "Update" button at the bottom to perform the search, the results of which will display in the Timeline and Table.
	</p>
</div>

<h2>Timeline</h2>
<div class="indent">
	<p>
		The <b>Timeline</b> section is for graphically displaying measurement trends across time. The Timeline will show searched measures over a user defined period of time.
	</p>
	<p>
		A graph will appear for each selected field, and multiple lines will appear on the graph for each site selected. If multiple sites are selected, they will be highlighted in unique colors for easy differentiation. These results can be viewed in an in-line or grid format. The in-line format simply dedicated each graph to its own line, while the grid layout will display all graphs in rows of two.
	</p>
	<p>
		The graph can be zoomed in on, either using the +/- buttons underneath, or pinching in/out on the graph itself (if on a touch screen or trackpad) or using the scrollwheel (on a conventional mouse). Panning can be done by dragging left or right on the graph.
	</p>
	<p>
		Each graph will display benchmark lines, indicating the upper (red) and lower (blue) bounds for acceptable values for the measure. For information on how these benchmarks are defined, see our About page. These lines can be toggled on or off using the "Show benchmark lines" checkbox at the top of the Timeline panel
	</p>
	<p>
		By default the Timeline graphs display as scatterplots, but the user may choose to display them as a line graph instead. Note that, while line graph view can still be used even with a filter enabled, this may be misleading, as it implies continuity in the measurements despite measures not fitting the filter being hidden.
	</p>
	<p>
		Users may also compare two measurements within a single graph. Click the "Compare to other measure" button underneath a graph, and select one from the popup window. The second measure will be shown in a different color, with a scale given on the right side of the graph. Note that, because color is necessary to distinguish the two measures being compared, this feature is available only for either single-site searches, or searches using the Aggregate tool.
	</p>
</div>

<h2>Table</h2>
<div class="indent">
	<p>
		The <b>Table</b> displays, row-by-row, all queried data associated with each sample record. As with the Timeline, the Table displays all the measurements selected in the "View data" section of the search panel. Additionally, the site number, sample number, and date are alway shown if searching by one or more discrete sites. If aggregate mode is used, the site and sample numbers are not shown, only the date, because these fields cannot be averaged.
	</p>
	<?php if ($admin) { ?>
	<div class="adminInfo">
		<p>
			Comment fields associated with all measures are also present and are always displayed if searching by one or more discrete sites. These comments contain additional information, usually from the people who collected the samples in the field, that may be relevant to interpreting the data. If aggregate mode is enabled, the comment fields are not shown, for the same reason as above. Comment data is not currently available to non-administrator users, but may be made available upon request.
		</p>
	</div>
	<?php } ?>
	
	<p>
		By default, the Table displays 25 records at a time, with more results on additional pages. The user can choose to view results in chunks of 10, 25, 100, 500, or all results.
	</p>
	<p>
		The user can also choose to sort the Table by any of the fields shown, in either ascending or descending order. By default, the Table is sorted by Date in descending order. To change this, click on the header for the appropriate column. Sort direction is indicated by the arrows in the header.
	</p>
	<?php if ($admin) { ?>
	<div class="adminInfo">
		<p>
			For administrators, two additional functions are present in the Table:
		</p>
		<p>
			Any measurement field (not site number, date, or sample number, but including comments) can be edited by clicking its cell. Upon clicking out of the cell, the value will be updated in the database. If the update cannot be done, an error message will be displayed.
		</p>
		<p>
			Individual rows can be deleted as well, using the trashcan icon in the admin-only "Actions" column of the table. Be careful, this action cannot be undone without restoring the database from a backup.				
		</p>
	</div>
	<?php } ?>
</div>

<h2>Export</h2>
<div class="indent">
	<p>
		The <b>Export</b> button at the bottom-right of the page is used for exporting the data listed in the table. The data is exported as a CSV (Comma Separated Values) file. Due to its simplicity, this file format is readable by both humans and nearly every spreadsheet software in existence.
	</p>
	<p>
		The arrangement of the data in the exported CSV is very similar to that displayed on the Table above. All of the same search functionality works as it does for the table as well. However, there are two differences: Firstly, there is no pagination in the exported file. All records are exported, regardless of the number of sample records the Table is set to display or its current page number. Secondly, sorting by a particular column is not supported; all exported files are sorted in descending order by the Date field.
	</p>
</div>

<h2 id="sites">Sites</h2>
<div class="indent">
	<p>
		The <b><a href="/WQIS/site-locations/sitemanagement">Site Info</a></b> page lists meta-information about every site the WQIS program monitors, including its site number, latitude and longitude, name, a description of its location, and what groups it belongs to.
	</p>
	<p>
		The "view" button next to each row will take you to the main page, and automatically select this site in the map.
	</p>
	<?php if ($admin) { ?>
		<div class="adminInfo">
			<p>
				For administrators, this page also serves as the site management page. All fields, except for the site number, can be edited by clicking on each cell. Clicking the "Add Site" button at the top or bottom of the page opens a pop-up window which can be filled in to create a new site. Existing sites may also be deleted.
			</p>
		</div>
	<?php } ?>
</div>

<h2 id="measurements">Measurements</h2>
<div class="indent">
	<p>
		The <b><a href="/WQIS/measurement-settings/measurements">Measurement Info</a></b> page provides meta-information about each measure supported by WQIS. The name and unit of each are listed. Minimum and maximum benchmarks, which represent the <i>recommended</i> range of values, are listed where such guidelines exist. Minimum and maximum detectable levels, which are limited by the capabilities of the sensors or laboratory equipment that record these measurements, are listed when appropriate as well.
	</p>
	
	<?php if ($admin) { ?>
		<div class="adminInfo">
			<p>
				The <b>Measure Key</b> column on the far left, visible only to administrators, is used internally by the database.
			</p>
			
			<p>
				Administrators may edit any value except for the measure key. Adding or deleting measurements will require the assistance of a database administrator.
			</p>
		</div>
	<?php } ?>
</div>

<h2 id="groups">Groups</h2>
<div class="indent">
	<p>
		<b>Groups</b> provide a means of organizing related sites. Groups may be created based on any arbitrary criteria, such as spatial proximity, being along the same tributary, etc. All groups have a name, a description, and a set of member sites.
	</p>
	<p>
		A list of all groups and their associated sites may be seen on the <b><a href="/WQIS/site-groups/sitegroups">Groups page</a></b>.
	</p>
	<p>
		Some groups of general interest are visible to all users; these are called <i>public groups</i>. Four such groups currently exist, representing the four high-level watersheds monitored by WQIS. Additionally, individual users may create their own <i>private</i> groups, as needed by their own research or policy interests, using the <b>Add Group</b> button at the bottom of the Groups page. These private groups will not be visible to any other users.
	</p>
	<p>
		From the <b><a href="/WQIS/site-locations/chartselection">View Data</a></b> page, groups may be accessed by clicking the <b>Select sites</b> dropdown in the sidebar. All groups visible to them, public or private, are listed at the top of the dropdown under the <b>Select a Group</b> section. Selecting a group will automatically populate the selection box with every individual site in that group. Individual sites may be added or removed from the search as normal.
	</p>
	<p>
		A user may edit or delete any group which they are the owner of, using the pencil or trashcan buttons in the <b>Actions</b> column of the Groups page.
	</p>
	
	<?php if ($admin) { ?>
	<div class="adminInfo">
		<p>
			Administrators may create and edit public groups, and may view and edit any private group owned by any user.
		</p>
		<p>
			A site may also be added to or removed from a group using the <b><a href="/site-locations/sitemanagement">Sites</a></b> page.
		</p>
		<p>
			Like normal users, administrators may also create private groups for their own personal use. This can be done by selecting the <b>Make private</b> checkbox in the <b>Add group</b> popup window. <i>By default, all administrator-created groups are public unless this checkbox is selected</i>.
		</p>
	</div>
	<?php } ?>
</div>

<?php if ($admin) { ?>
<div class="adminInfo">
	<h2>The following sections refer to administrator-only functionality</h2>
</div>

<h2 id="fileUpload">File upload</h2>
<div class="indent">
	<p>
		<b>File upload</b> is the core function available to administrators. It is the primary means by which sample data is added to the system.
	</p>
	<p>
		The St Joseph River Watershed Initiative internally stores their data in a large Excel file, spanning multiple pages. WQIS, however, accepts data only as CSV files. These can be produced from the master Excel file simply using the <b>Save as</b> function in Excel. Only one page may be exported at a time
	</p>
	<p>
		If constructing input files manually, or via an automated process to bring in bulk data from another organization, it must be ensured that the files produced conform to the appropriate sample category prototype. Sample files should only contain measures from a single category. For instance, a bacteria file should contain only E. Coli and Total Coliform data, not nitrate/nitrite data. Example sample files can be found at <a href="/WQIS/webroot/files/exampleFiles.zip">this link</a>.
	</p>
	<p>
		It is not necessary to specify what type of sample file is being uploaded, this will be determined automatically based on the column headers of the file (hence the importance of adhering to the prescribed format). Certain variations in this format are tolerated (column names are treated as case-insensitive, and some common punctuational or abbreviational options are handled correctly), but this should not be relied upon.
	</p>
	<p>
		Similarly, it is important to ensure that the data itself conforms to the expected format. Other than the comment field, each field accepts only numerical values. Some common, though incorrect, variations in this are supported, such as converting <code>"no data"</code> or similar strings to a <code>null</code> value. Again, however, this should not be relied upon. If there are non-conforming entries which need to be supported, contact the development team to ensure the appropriate logic can be implemented to handle those, otherwise they will be rejected.
	</p>
</div>

<h2 id="entryForm">Entry form</h2>
<div class="indent">
	<p>
		The <b>entry form</b> tool is the secondary means of uploading data to WQIS. In the administrator panel, select the category of measurements to add, then "Go to Entry Form". Select the date of the measurements at the top of the page. All records added at one time must be from the same date. Then fill in the site number (sample number will be filled in automatically as a function of site number and date), and all relevant measurements. Multiple records can be added at once (using the Add Site button to add a new line in the entry form), but each must be for a different site.
	</p>
</div>

<h2 id="userManagement">User management</h2>
<div class="indent">
	<p>
		The <b><a href="/WQIS/users/usermanagement">User Management</a></b> page allows administrators to view and edit all account information about the users of WQIS. Click the pencil icon in the Actions column to edit a user's data. Users can also be deleted by clicking the trashcan icon, or created using the "Add user" button.
	</p>
</div>

<h2>View feedback</h2>
<div class="indent">
	<p>
		The <b><a href="/WQIS/contact/viewfeedback">View Feedback</a></b> page displays all user-provided feedback on the site. The date, message, and username of the person who gave the feedback are shown. Users who are not logged in may also give feedback, they may leave a name and email address to contact them. Feedback which has been viewed already can be deleted using the trashcan icon.
	</p>
</div>

<h2>API</h2>
<div class="indent">
	<p>
		To allow a wider array of queries and alternate means of displaying and analyzing data beyond the scope of the WQIS project itself, WQIS exposes an Application Programming Interface available to external developers. See the <b><a href="/WQIS/pages/developers">WQIS for Developers</a></b> page for more details.
	</p>
</div>

<br>
<?php } ?>

<button onclick="location.href='/WQIS/site-locations/chartselection?runTutorial';" id="helpPageButton" type="button" class="btn btn-success btn-lg btn-block">Try going through the guided walkthrough again!</button>