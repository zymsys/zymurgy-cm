<?php 
	require_once('InputWidget.php');
	require_once('cmo.php');

	class ZymurgyMemberDataTable
	{
		/**
		 * Child tables.  This is an array of ZymurgyMemberDataTable instances.
		 *
		 * @var array
		 */
		public $children = array();
		
		/**
		 * Parent table
		 *
		 * @var ZymurgyMemberDataTable
		 */
		public $parent = null;
		
		/**
		 * Fields for this table.  This is an array of ZymurgyMemberDataField instances.
		 *
		 * @var array
		 */
		private $fields = array();
		
		/**
		 * ID number from zcm_customtable identifying this table's meta data
		 *
		 * @var int
		 */
		private $tableid;
		
		/**
		 * Name of the database table
		 *
		 * @var string
		 */
		private $tablename;
		
		/**
		 * Navigation name for this table, used in grid UI's.
		 *
		 * @var string
		 */
		public $navname;
		
		/**
		 * Creates a ZymurgyMemberDataTable from a table ID.  To create one from a navigation name use the FactoryByNavName static method.
		 *
		 * @param int $tableid
		 */
		public function __construct($tableid)
		{
			// Authenticate and make sure we have the member's info
			if (!Zymurgy::memberauthenticate())
			{
				$rurl = urlencode($_SERVER['REQUEST_URI']);
				Zymurgy::JSRedirect(Zymurgy::$config['MemberLoginPage']."?rurl=$rurl");
			}
			
			// Design my Contract terms
			if (!is_numeric($tableid)) die("Invalid table ID: [$tableid]");
			$table = Zymurgy::$db->get("select * from zcm_customtable where id=$tableid");
			if ($table === FALSE) die("No such custom table ID: $tableid");
			
			// Define basic characteristics
			$this->tableid = $table['id'];
			$this->navname = $table['navname'];
			$this->tablename = $table['tname'];
			
			if ($table['detailfor']>0)
			{
				//Ugly...  Used so that is_null checks to see if we're the root 
				// node work correctly, but this means that we can't count on 
				// parent being an object reference.  Should be re-visited.
				
				$this->parent = true; 
			}
			
			$this->PopulateFieldList();
			$this->PopulateChildTables();		
		}
			
		/**
		 * Gets a child table instance by its id.
		 *
		 * @param int $childid
		 * @return ZymurgyMemberDataTable
		 */
		public function getChild($childid)
		{
			return $this->children[$childid];
		}
	
		/**
		 * Populates the list of fields for the base table. Called by the 
		 * constructor.
		 */
		private function PopulateFieldList()
		{
			$sql = "select * from zcm_customfield where tableid = {$this->tableid} order by disporder";
			
			$ri = Zymurgy::$db->run($sql) or die("Cannot retrieve list of fields for {$this->tablename}");
			
			while (($row = Zymurgy::$db->fetch_array($ri))!==FALSE)
			{
				$this->fields[$row['id']] = new ZymurgyMemberDataField($row); 
			}
			
			Zymurgy::$db->free_result($ri);		
		}
		
		/**
		 * Populates the list of child tables. Called by the constructor.
		 */
		private function PopulateChildTables()
		{
			$sql = "select id from zcm_customtable where detailfor = {$this->tableid} order by disporder";
			
			$ri = Zymurgy::$db->run($sql) or die("Cannot retrieve list of child tables for {$this->tablename}");
			
			while (($row = Zymurgy::$db->fetch_array($ri))!==FALSE)
			{
				$childid = $row['id'];
				
				$newtable = new ZymurgyMemberDataTable($childid);
				$newtable->parent = $this;
				$this->children[$childid] = $newtable;
			}
			
			Zymurgy::$db->free_result($ri);
		}
		
		/**
		 * Create a ZymurgyMemberDataTable from a custom table navigation name.
		 *
		 * @param string $navname
		 * @return ZymurgyMemberDataTable
		 */
		public static function FactoryByNavName($navname)
		{
			//Look up table info by $navname
			$tableid = Zymurgy::$db->get("select id from zcm_customtable where navname='".
				Zymurgy::$db->escape_string($navname)."'");
			if ($tableid === FALSE) die("No such custom table: $navname");
			return new ZymurgyMemberDataTable($tableid);
		}
		
		/**
		 * Create a form UI, or handle the submitted data.
		 *
		 * @param int $rowid
		 */
		public function renderForm($rowid = 0)
		{
			if ($_SERVER['REQUEST_METHOD']=='POST')
			{
				$this->renderFormSubmit();
			}
			else 
			{
				$this->renderFormUI($rowid);
			}
		}
		
		/**
		 * Handle data submitted from a form.
		 * TODO:  Ensure that duplicate entries can't be inserted when a user 
		 * loads two initial instances of the form, and then submits one after 
		 * the other.
		 */
		private function renderFormSubmit()
		{
			$updateid = 0 + $_POST['rowid'];
			
			
			if(isset($_POST["delete"]))
			{
				$this->DeleteRecord(
					$this->tablename,
					$updateid);
			}
			else
			{
				$values = $this->RetrieveFieldValues();
				
				if ($updateid <= 0)
				{
					$this->InsertRecord(
						$this->tablename,
						$values);
				}
				else 
				{
					$this->UpdateRecord(
						$this->tablename,
						$updateid,
						$values);
				}
				
				echo("<script type=\"text/javascript\">setTimeout('window.location.href = ".
					"window.location.href', 1000);</script>");
			}
		}
		
		/**
		 * Delete the record in the specified table at the specified ID.
		 *
		 * @param unknown_type $tableName The name of the table containing the record to delete.
		 * @param unknown_type $rowID The ID of the record to delete.
		 */
		private function DeleteRecord(
			$tableName,
			$rowID)
		{
			$sql = "delete from $tableName where id = $rowID";
				
			Zymurgy::$db->run($sql);
		}
		
		/**
		 * Insert a record into the database.
		 *
		 * @param unknown_type $tableName The name of the table to insert the record into.
		 * @param unknown_type $values The array of fieldname/value pairs to insert.
		 */
		private function InsertRecord(
			$tableName,
			$values)
		{
			if (is_null($this->parent))
			{
				$values['member'] = Zymurgy::$member['id'];
			}
			
			$sql = "insert into $tableName (".
				implode(',',array_keys($values)).") values ('".
				implode("','",$values)."')";
				
			echo $sql;
			
			Zymurgy::$db->run($sql);
		}
		
		/**
		 * Update a record in the database.
		 *
		 * @param unknown_type $tableName The name of the table to update.
		 * @param unknown_type $rowID The ID of the record to update.
		 * @param unknown_type $values The array of fieldname/value pairs to update.
		 */
		private function UpdateRecord(
			$tableName,
			$rowID,
			$values)
		{
			$ucol = array();
			
			foreach($values as $colname=>$value)
			{
				$ucol[] = "$colname = '$value'";
			}
			
			$sql = "update $tableName set ".implode(', ', $ucol)." where (id = $rowID)";
			
			if (is_null($this->parent))
			{
				$sql .= " and (member=".Zymurgy::$member['id'].")";
			}
				
			echo $sql;
			
			Zymurgy::$db->run($sql);
		}
		
		/**
		 * Retrieve the list of fieldname/value pairs set in the user form.
		 *
		 * @return The array of fieldname/value pairs set in the form.
		 */
		private function RetrieveFieldValues()
		{
			$values = array();
			
			// Retrieve basic field values
			foreach($this->fields as $fieldid=>$field)
			{
				$values[$field->columnname] = Zymurgy::$db->escape_string(
					$_POST["field{$field->fieldid}"]);
			}
			
			// For child tables, retrieve the field name and ID for the 
			// parent table
			if(isset($_POST["parentid"]))
			{
				$sql = "SELECT tname FROM zcm_customtable WHERE id = ".
					Zymurgy::$db->escape_string($_POST["parentid"]);
					
				$parentName = Zymurgy::$db->get($sql);
				
				$values[$parentName] =
					Zymurgy::$db->escape_string($_POST[$parentName]);
			}			
			
			return $values;
		}
		
		/**
		 * Create a form's UI
		 *
		 * @param unknown_type $rowid
		 */
		private function renderFormUI($rowid = 0)
		{
			$memberdata = $this->RetrieveMemberData($rowid);			
			$this->RenderTabs();		
			
			echo "<form id=\"zymurgyForm{$this->tablename}\" action=\"{$_SERVER['SCRIPT_URI']}\" method=\"post\">\r\n";
			
			$rowid = is_array($memberdata)
				? $memberdata["id"]
				: 0;
			
			echo "<input type=\"hidden\" name=\"tableid\" value=\"$this->tableid\" />\r\n";
			echo "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\" />\r\n";
			
			echo "<table>\r\n";
			
			foreach($this->fields as $fieldid=>$field)
			{
				if (is_array($memberdata))
					$value = $memberdata[$field->columnname];
				else 
					$value = '';
					
				$field->renderTableRow($value);
			}
			
			echo "<tr><td colspan=\"2\"><input type=\"submit\" value=\"Save\" /></td></tr>\r\n";
			
			echo "</table></form>\r\n";
			
			$this->RenderChildTabViews($rowid);
		}
		
		private function RetrieveMemberData(
			$rowid)	
		{
			if ($rowid == 0)
			{
				$memberdata = Zymurgy::$db->get("select * from {$this->tablename} where member=".
					Zymurgy::$member['id']);
			}
			else 
			{
				if (!is_numeric($rowid)) die("Invalid row id for {$this->tablename}: $rowid");
				$memberdata = Zymurgy::$db->get("select * from {$this->tablename} where id=$rowid");
			}
			
			return $memberdata;
		}
		
		private function RenderTabs()
		{
			if (count($this->children) > 0)
			{
				echo "<div id=\"ZymurgyTabset{$this->tablename}\" class=\"yui-navset\">\r\n";
				echo "<ul class=\"yui-nav\">\r\n";
				
				// Generate tab for main data form
				echo "<li class=\"selected\"><a href=\"#tab{$this->tableid}\"><em>".
					"{$this->navname}</em></a></li>\r\n";
				
				// Generate a tab for each child form
				foreach($this->children as $child)
				{
					echo "<li><a href=\"#tab{$child->tableid}\"><em>{$child->navname}</em></a></li>\r\n";
				}
				
				echo "</ul>\r\n";
				echo "<div class=\"yui-content\">\r\n";
				echo "<div>\r\n";
			}			
		}
		
		private function RenderChildTabViews($rowid)
		{
			if (count($this->children) > 0)
			{
				// Close the div for the main data tab view
				echo "</div>";
				
				foreach($this->children as $child)
				{
					echo "<div>";
					$child->renderGrid($rowid);
					echo "</div>";
				}
				
				echo "</div>"; //yui-content
				echo "</div>\r\n"; //yui-navset
				
				echo "<script type=\"text/javascript\">\r\n";
	    		echo "var tabView{$this->tablename} = new YAHOO.widget.TabView(".
	    			"'ZymurgyTabset{$this->tablename}');\r\n";
				echo "</script>";
			}
		}
		
		public function renderDialogs()
		{
			if (!is_null($this->parent))
			{
				$this->RenderFormDialogHTML();
				$this->RenderDeleteDialogHTML();
				
				echo("<script type=\"text/javascript\">\r\n");
				
				$this->RenderFormDialogButtons();
				$this->RenderDeleteDialogButtons();
				$this->RenderFormDialogScript();
				$this->RenderDeleteDialogScript();
				
				echo("</script>\r\n");
			}

			foreach ($this->children as $child)
			{
				$child->renderDialogs();
			}					
		}
		
		private function RenderFormDialogHTML()
		{
			?>
				<div id="ZymurgyDialog<?= $this->tablename ?>">
					<div class="hd"><?= $this->navname ?></div>
					<div class="bd">
						<?
						if(count($this->children) > 0)
						{
							echo "<div id=\"ZymurgyTabset{$this->tablename}\" class=\"yui-navset\">\r\n";
							echo "<ul class=\"yui-nav\">\r\n";
				
							// Generate tab for main data form
							echo "<li class=\"selected\"><a href=\"#tab{$this->tableid}_{$this->tableid}\">".
								"<em>{$this->navname}</em></a></li>\r\n";
				
							// Generate a tab for each child form
							foreach($this->children as $child)
							{
								echo "<li><a href=\"#tab{$this->tableid}_{$child->tableid}\">".
									"<em>{$child->navname}</em></a></li>\r\n";
							}
							
							echo "</ul>\r\n";
							echo "<div class=\"yui-content\">\r\n";
							echo "<div>\r\n";
						}
						?>
					
						<form 
						 name="ZymurgyDialogForm<?= $this->tablename ?>"
						 method="post"
						 action="/zymurgy/memberdata.php">
							<input type="hidden" name="tableid" value="<?= $this->tableid ?>" />
							<input type="hidden" name="rowid" id="rowid<?= $this->tableid ?>" value="0" />
							<input type="hidden" name="parentid" value="<?= $this->parent->tableid ?>"/>
							<input 
						 	 type="hidden" 
						 	 id="<?= $this->parent->tablename ?>_<?= $this->tableid ?>" 
						 	 name="<?= $this->parent->tablename ?>" 
						 	 value="" />
							
							<table>
							<?
							foreach($this->fields as $fieldid=>$field)
							{
								$field->renderTableRow('');
							}
							?>
							</table>
							
							<?
							if(count($this->children) > 0)
							{
								echo "</div>";

								foreach($this->children as $child)
								{
									echo("<div>");
									$child->renderGrid();
									echo("</div>");
								}
				
								echo "</div>"; //yui-content
								echo "</div>\r\n"; //yui-navset
								
								echo "<script type=\"text/javascript\">\r\n";
					    		echo "var tabView{$this->tablename} = new YAHOO.widget.TabView(".
					    			"'ZymurgyTabset{$this->tablename}');\r\n";
								echo "</script>";
							}
							?>
						</form>
					</div>
				</div>
			<?
		}
	
		private function RenderDeleteDialogHTML()
		{
			?>
				<div id="ZymurgyDeleteDialog<?= $this->tablename ?>">
					<div class="hd">Delete Record</div>
					<div class="bd">
						<form
						 name="ZymurgyDeleteForm<?= $this->tablename ?>" 
						 method="post" 
						 action="/zymurgy/memberdata.php">
							<input type="hidden" name="tableid" value="<?= $this->tableid ?>" />
							<input type="hidden" name="rowid" id="rowid<?= $this->tableid ?>_delete" value="0" />
							<input type="hidden" name="delete" value="1"/>			
						</form>
						Are you sure you want to delete this record?
					</div>
				</div>	
			<?		
		}
		
		/**
		 * Render the form dialog Save and Cancel buttons.
		 * 
		 * ZK: Get the Save button to reload DataTable data. 
		 * Unfortunately, DataTable does not have a ReQuery() method, so we get to 
		 * use a hack instead.
		 * 
		 * Refer: http://yuilibrary.com/projects/yui2/ticket/1804089
		 */
		private function RenderFormDialogButtons()
		{
			?>
				var ZymurgyDialog<?= $this->tablename ?>ID = 0;
			
				var ZymurgyDialog<?= $this->tablename ?>Buttons = [ 
					{ 
						text:"Save", 
						isDefault: true,
						handler:function() {
							this.submit();
							// alert("<?= $this->tablename ?>: " + ZymurgyDialog<?= $this->tablename ?>ID);
							ZymurgyDataTable<?= $this->tablename ?>.getDataSource().sendRequest(
								ZymurgyDataTable<?= $this->tablename ?>.get("initialRequest").replace("r=0", "r=" + ZymurgyDialog<?= $this->tablename ?>ID), 
								ZymurgyDataTable<?= $this->tablename ?>.onDataReturnInitializeTable,
								ZymurgyDataTable<?= $this->tablename ?>);
						}
					},
				    { 
				    	text:"Cancel",
				    	handler:function() {
							this.cancel();
						}
				    } 
				];
			<?
		}
		
		/**
		 * Render the delete dialog Yes and No buttons.
		 * 
		 * ZK: Get the Yes button to reload DataTable data. 
		 * Unfortunately, DataTable does not have a ReQuery() method, so we get to 
		 * use a hack instead.
		 * 
		 * Refer: http://yuilibrary.com/projects/yui2/ticket/1804089
		 */
		private function RenderDeleteDialogButtons()
		{
			?>
				var ZymurgyDeleteDialog<?= $this->tablename ?>Buttons = [ 
					{
						text:"Yes", 
						handler:function() {
							this.submit();
							ZymurgyDataTable<?= $this->tablename ?>.getDataSource().sendRequest(
								ZymurgyDataTable<?= $this->tablename ?>.get("initialRequest").replace("r=0", "r=" + ZymurgyDialog<?= $this->tablename ?>ID), 
								ZymurgyDataTable<?= $this->tablename ?>.onDataReturnInitializeTable,
								ZymurgyDataTable<?= $this->tablename ?>);
						}
					},
					{ 
						text:"No", 
						isDefalt: true, 
						handler:function() {
							this.cancel();
						}
					}
				];
			<?
		}
		
		private function RenderFormDialogScript()
		{
			?>
				var ZymurgyDialog<?= $this->tablename ?>;
	
				YAHOO.util.Event.addListener(window, "load", function() {
					ZymurgyDialog<?= $this->tablename ?> = new YAHOO.widget.Dialog(
						"ZymurgyDialog<?= $this->tablename ?>",{
						visible:false,
						buttons:  ZymurgyDialog<?= $this->tablename ?>Buttons 
					});
				
					ZymurgyDialog<?= $this->tablename ?>.setField = function(id,value) {
						var el = document.getElementById('field'+id);
						el.value = value;
					};
				
					ZymurgyDialog<?= $this->tablename ?>.render();
				});
	
				function New<?= $this->tablename ?>()
				{
					<? 
						foreach($this->fields as $fieldid=>$field)
						{ 
					?>
					document.getElementById('field<?= $fieldid ?>').value = "";
					<? 
						} 
					?>
					
					document.getElementById('rowid<?= $this->tableid ?>').value = -1;
					
					<? if(count($this->children) > 0) {
						foreach($this->children as $child)
						{ ?>
							document.getElementById('ZymurgyDataTable<?= $child->tablename ?>').style.display = "none";
							document.getElementById('btnAdd<?= $child->tablename ?>').style.display = "none";
						<? }
					} ?>
		
					ZymurgyDialog<?= $this->tablename ?>.show();
				}
			<?
		}
		
		private function RenderDeleteDialogScript()
		{
			?>
				var ZymurgyDeleteDialog<?= $this->tablename ?>;
	
				YAHOO.util.Event.addListener(window, "load", function() {
					ZymurgyDeleteDialog<?= $this->tablename ?> = new YAHOO.widget.Dialog(
						"ZymurgyDeleteDialog<?= $this->tablename ?>", {
							visible: false,
							buttons: ZymurgyDeleteDialog<?= $this->tablename ?>Buttons		
						}
					);
					
					ZymurgyDeleteDialog<?= $this->tablename ?>.setField = function(id, value) {
						var el = document.getElementById('field'+id);
						el.value = value;
					};
					
					ZymurgyDeleteDialog<?= $this->tablename ?>.render();
				});	
			<?
		}
			
		public function renderGrid($rowid=0)
		{
			$dtkeys = array();
			$dskeys = array();
			$editors = array();
			$dlgassigns = array();
			$dtkeys['id'] = "{key:\"id\", hidden:true}";
			$dtkeys['id'] = "{key:\"id\"}";
			
			foreach($this->fields as $fieldid=>$field)
			{
				$columndef = "{key:\"{$field->columnname}\", label:\"{$field->gridheader}\"}";
				$dskeys[$field->columnname] = $columndef;
				
				if (!empty($field->gridheader))
					$dtkeys[$field->columnname] = $columndef;
					
				$editors[$fieldid] = "<label for=\"field$fieldid\">{$field->caption}</label>";
				$dlgassigns[$fieldid] = "ZymurgyDialog{$this->tablename}.setField($fieldid,rowdata['{$field->columnname}']);";
			}
			
			$dtkeys['edit'] = "{key: \"edit\", label: \"Edit\", formatter: this.formatEdit}";
			$dtkeys['delete'] = "{key: \"delete\", label: \"Delete\", formatter: this.formatDelete}";
			
			//Render the grid
			echo "<div id=\"ZymurgyDataTable{$this->tablename}\"></div>\r\n";
			echo "<p><input type=\"button\" id=\"btnAdd{$this->tablename}\" name=\"btnAdd{$this->tablename}\" value=\"Add Item\" onclick=\"New{$this->tablename}();\"></p>\r\n";
			?>
			<script type="text/javascript">
				var ZymurgyDataTable<?= $this->tablename ?>;
		
				YAHOO.util.Event.addListener(window, "load", function() {
				    InitializeGrid<?= $this->tablename ?> = new function() {
				        this.formatEdit = function(elCell, oRecord, oColumn, sData) {
				            elCell.innerHTML = "Edit";
				        };
				        
				        this.formatDelete = function(elCell, oRecord, oColumn, sData) {
				            elCell.innerHTML = "Delete";
				        };
				        
						this.onCellClickEvent = function(oArgs) {
							var target = oArgs.target;
							var column = InitializeGrid<?= $this->tablename ?>.myDataTable.getColumn(target);
							if (column.key == 'edit') 
							{
								var dtrec = InitializeGrid<?= $this->tablename ?>.myDataTable.getRecord(target);
								var rowdata = dtrec.getData();
								
								var el = document.getElementById('rowid<?= $this->tableid ?>');
								el.value = rowdata['id'];
								
								<?
								echo implode("\r\n",$dlgassigns);
								
								foreach($this->children as $child)
								{
									?>									
									ZymurgyDialog<?= $child->tablename ?>ID = rowdata['id'];
									// alert("<?= $child->tablename ?>: " + ZymurgyDialog<?= $child->tablename ?>ID);
									
									ZymurgyDataTable<?= $child->tablename ?>.getDataSource().sendRequest(
										ZymurgyDataTable<?= $child->tablename ?>.get("initialRequest").replace("r=0", "r=" + rowdata['id']), 
										ZymurgyDataTable<?= $child->tablename ?>.onDataReturnInitializeTable,
										ZymurgyDataTable<?= $child->tablename ?>);
									document.getElementById("<?= $this->tablename ?>_<?= $child->tableid ?>").value = rowdata['id'];								
									document.getElementById('ZymurgyDataTable<?= $child->tablename ?>').style.display = "block";
									document.getElementById('btnAdd<?= $child->tablename ?>').style.display = "block";
									<?
								}
								?>
								ZymurgyDialog<?= $this->tablename ?>.show();
							} 
							else if(column.key == 'delete') 
							{
								var dtrec = InitializeGrid<?= $this->tablename ?>.myDataTable.getRecord(target);
								var rowdata = dtrec.getData();
				
								var el = document.getElementById('rowid<?= $this->tableid ?>_delete');
								el.value = rowdata['id'];
								
								<?
								foreach($this->children as $child)
								{
								?>
									ZymurgyDialog<?= $child->tablename ?>ID = rowdata['id'];
									// alert("<?= $child->tablename ?>: " + ZymurgyDialog<?= $child->tablename ?>ID);								
								<?
								}
								?>
								
								ZymurgyDeleteDialog<?= $this->tablename ?>.show();
							}
						};
				
				        this.myDataSource = new YAHOO.util.DataSource("/zymurgy/memberdata.php?");
				        this.myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
				        this.myDataSource.connXhrMode = "queueRequests";
				        this.myDataSource.responseSchema = {
				            resultsList: "ResultSet.Result",
				            fields: ["id","<?= implode('","',array_keys($dskeys)) ?>"]
				        };
				
				        this.myDataTable = new YAHOO.widget.DataTable(
				        	"ZymurgyDataTable<?= $this->tablename ?>",
				        	[<?= implode(",\r\n\t\t\t",$dtkeys) ?>],
				            this.myDataSource, 
				            {
				            		initialRequest: "t=<?= $this->tableid ?>&r=<?= $rowid ?>&m=<?= $this->parent->tableid ?>"
				           	}
				        );
						this.myDataTable.subscribe('cellClickEvent',this.onCellClickEvent);
						
						document.getElementById("<?= $this->parent->tablename ?>_<?= $this->tableid ?>").value = "<?= $rowid ?>";								
						
						ZymurgyDataTable<?= $this->tablename ?> = this.myDataTable;
				    };
				});
			</script>
			<?
		}
		
		public function renderJSON($rowid)
		{
			$ri = Zymurgy::$db->run("select * from {$this->tablename} where {$this->parent->tablename}=$rowid");
			$r = array();
			while (($row = Zymurgy::$db->fetch_array($ri))!==false)
			{
				$rp = array("\"id\":\"{$row['id']}\"");
				foreach($this->fields as $field)
				{
					$rp[] = "\"{$field->columnname}\":\"{$row[$field->columnname]}\"";
				}
				$r[] = implode(',',$rp);
			}
			$datablock = implode("},\r\n{\r\n",$r);
			?>
			{
				"ResultSet":
				{
					"totalResultsAvailable":<?= count($r) ?>,
					"totalResultsReturned":<?= count($r) ?>,
					"firstResultPosition":1,
					"Result":[
						{
							<?= $datablock ?>
			            }
	        		]
				}
			}
			<?
		}
	}
	
	class ZymurgyMemberDataField
	{
		public $columnname;
		public $inputspec;
		public $caption;
		public $gridheader;
		public $fieldid;
		
		public function __construct($fieldrow)
		{
			$this->fieldid = $fieldrow['id'];
			$this->columnname = $fieldrow['cname'];
			$this->inputspec = $fieldrow['inputspec'];
			$this->caption = $fieldrow['caption'];
			$this->gridheader = $fieldrow['gridheader'];
		}
		
		public function renderTableRow($value)
		{
			$iw = new InputWidget();
			echo "<tr><td>{$this->caption}</td><td>";
			$iw->Render($this->inputspec,"field{$this->fieldid}",$value);
			echo "</td></tr>\r\n";
		}
		
		/*public function renderDialogRow($value)
		{
			$iw = new InputWidget();
			echo "<label for=\"field$this->fieldid\">{$this->caption}</label>";
			$iw->Render($this->inputspec,"field".$this->fieldid,$value);
			echo "\r\n";
		}*/
	}
	
	// ==========
	// MAIN PROCESSING SECTION
	// ==========
		
	if (array_key_exists('t',$_GET))
	{
		//Look up data for ajax request.
		$m = new ZymurgyMemberDataTable($_GET['m']);
		$c = $m->getChild($_GET['t']);
		$c->renderJSON($_GET['r']);
	} 
	else if ($_SERVER['REQUEST_METHOD']=='POST')
	{
		echo "Saving posted data for table #{$_POST['tableid']} and row #{$_POST['rowid']}\r\n";
		
		//echo("<br>Creating instance.");
		$t = new ZymurgyMemberDataTable(0 + $_POST['tableid']);
		
		//echo("<br>Rendering form.");
		$t->renderForm(0 + $_POST['rowid']);
	}
?>