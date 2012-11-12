<?
/**
 * Provides common items for customtable.php and customfield.php
 *
 * @package Zymurgy
 * @subpackage base
 */

class CustomTableTool
{
    private $_reserved = array('ADD','ANALYZE','ASC','BETWEEN','BLOB','CALL','CHANGE','CHECK','CONDITION','CONVERT','CURRENT_DATE','CURRENT_USER','DATABASES','DAY_MINUTE',
        'DECIMAL','DELAYED','DESCRIBE','DISTINCTROW','DROP','ELSE','ESCAPED','EXPLAIN','FLOAT','FOR','FROM','GROUP','HOUR_MICROSECOND','IF','INDEX','INOUT','INT',
        'INT3','INTEGER','IS','KEY','LEADING','LIKE','LOAD','LOCK','LONGTEXT','MATCH','MEDIUMTEXT','MINUTE_SECOND','NATURAL','NULL','OPTIMIZE','OR','OUTER','PRIMARY',
        'READ','REFERENCES','RENAME','REQUIRE','REVOKE','SCHEMA','SELECT','SET','SONAME','SQL','SQLWARNING','SQL_SMALL_RESULT','STRAIGHT_JOIN','THEN','TINYTEXT','TRIGGER',
        'UNION','UNSIGNED','USE','UTC_TIME','VARBINARY','VARYING','WHILE','XOR','ASENSITIVE','CONTINUE','DETERMINISTIC','EXIT','INSENSITIVE','LOOP','READS','RETURN',
        'SENSITIVE','SQLEXCEPTION','TRIGGER','ALL','AND','ASENSITIVE','BIGINT','BOTH','CASCADE','CHAR','COLLATE','CONSTRAINT','CREATE','CURRENT_TIME','CURSOR','DAY_HOUR',
        'DAY_SECOND','DECLARE','DELETE','DETERMINISTIC','DIV','DUAL','ELSEIF','EXISTS','FALSE','FLOAT4','FORCE','FULLTEXT','HAVING','HOUR_MINUTE','IGNORE','INFILE',
        'INSENSITIVE','INT1','INT4','INTERVAL','ITERATE','KEYS','LEAVE','LIMIT','LOCALTIME','LONG','LOOP','MEDIUMBLOB','MIDDLEINT','MOD','NOT','NUMERIC','OPTION','ORDER',
        'OUTFILE','PROCEDURE','READS','REGEXP','REPEAT','RESTRICT','RIGHT','SCHEMAS','SENSITIVE','SHOW','SPATIAL','SQLEXCEPTION','SQL_BIG_RESULT','SSL','TABLE','TINYBLOB',
        'TO','TRUE','UNIQUE','UPDATE','USING','UTC_TIMESTAMP','VARCHAR','WHEN','WITH','YEAR_MONTH','CALL','CURSOR','EACH','FETCH','ITERATE','MODIFIES','RELEASE','SCHEMA',
        'SPECIFIC','SQLSTATE','UNDO','ALTER','AS','BEFORE','BINARY','BY','CASE','CHARACTER','COLUMN','CONTINUE','CROSS','CURRENT_TIMESTAMP','DATABASE','DAY_MICROSECOND',
        'DEC','DEFAULT','DESC','DISTINCT','DOUBLE','EACH','ENCLOSED','EXIT','FETCH','FLOAT8','FOREIGN','GRANT','HIGH_PRIORITY','HOUR_SECOND','IN','INNER','INSERT','INT2',
        'INT8','INTO','JOIN','KILL','LEFT','LINES','LOCALTIMESTAMP','LONGBLOB','LOW_PRIORITY','MEDIUMINT','MINUTE_MICROSECOND','MODIFIES','NO_WRITE_TO_BINLOG','ON',
        'OPTIONALLY','OUT','PRECISION','PURGE','REAL','RELEASE','REPLACE','RETURN','RLIKE','SECOND_MICROSECOND','SEPARATOR','SMALLINT','SPECIFIC','SQLSTATE',
        'SQL_CALC_FOUND_ROWS','STARTING','TERMINATED','TINYINT','TRAILING','UNDO','UNLOCK','USAGE','UTC_DATE','VALUES','VARCHARACTER','WHERE','WRITE','ZEROFILL',
        'CONDITION','DECLARE','ELSEIF','INOUT','LEAVE','OUT','REPEAT','SCHEMAS','SQL','SQLWARNING','WHILE','ID');

    /**
     * Returns an array of parent tables, given the ID of a detail table.
     *
     * @param int $tid The ID of the detail table, as defined in the zcm_customtable table.
     * @return array
     */
    private function tableparents($tid)
    {
        $r = array();
        do
        {
            $tbl = Zymurgy::$db->get("select * from zcm_customtable where id=$tid");
            $r[$tbl['id']] = empty($tbl['navname']) ? $tbl['tname'] : $tbl['navname'];
            $tid = $tbl['detailfor'];
        }
        while ($tid > 0);
        return $r;
    }

    /**
     * Adds an array of links to the global for the breadcrumb trail
     *
     * @param int $tid The ID of the detail table, as defined in the zcm_customtable table.
     */
    public function tablecrumbs($tid)
    {
        global $crumbs;

        $parents = $this->tableparents($tid);
        $tids = array_keys($parents);
        while (count($parents) > 0)
        {
            $tid = array_pop($tids);
            $crumb = array_pop($parents).' Detail Tables';
            $crumbs["customtable.php?d=$tid"] = htmlspecialchars($crumb);
        }
    }

    /**
     * Validates a provided table name, to ensure the name is not an SQL reserved
     * word, or already being used in the database.
     *
     * @param string $name
     * @return bool True if the name is allowed. Otherwise false.
     */
    public function okname($name)
    {
        $name = strtoupper($name);
        $r = array_search($name,$this->_reserved);
        if ($r===true)
            return "That name is a reserved word.  Please choose a different table name.";
        $m = preg_match("/^[A-Za-z][\w]*\$/",$name);
        //$m = preg_match("/./",$name);
        if ($m==0)
            return "Please use only alphanumeric characters and only alpha for the first character.";
        /*Gave up on making the datagrid component compatible with stupid table/column names.  Use above alphanumeric test instead.
        if (strpos($name,'`')!==false)
            return "That name contains a back-tick (`).  Please choose a different table name.";*/
        return true;
    }

    /**
     * Returns the information on a custom table from it's ID.
     *
     * @param int $t The ID of the detail table, as defined in the zcm_customtable table
     * @return array
     */
    public function getTable($t)
    {
        $t = intval($t);
        $sql = "SELECT * FROM `zcm_customtable` where `id`=$t";
        return $this->getTableBySQL($sql, $t);
    }

    /**
     * @param $sql string SQL Statement to fetch a zcm_customtable row
     * @param $t int|string Table identifier; either name or ID
     * @return array Row from zcm_customtable
     * @throws Exception when there is no such table
     */
    private function getTableBySQL($sql, $t)
    {
        $ri = Zymurgy::$db->query($sql) or die("Can't get table ($sql): " .
            Zymurgy::$db->error());
        $tbl = Zymurgy::$db->fetch_assoc($ri);
        if (!is_array($tbl))
            throw new Exception("No such table ($t)");
        return $tbl;
    }

    /**
     * Returns the information on a custom table from it's name.
     *
     * @param $tableName string Name of a table in zcm_customtable
     * @return array
     */
    public function getTableByName($tableName)
    {
        $escapedTableName = Zymurgy::$db->escape_string($tableName);
        $sql = "SELECT * FROM `zcm_customtable` WHERE `tname`='$escapedTableName'";
        return $this->getTableBySQL($sql, $tableName);
    }

    /**
     * @param $id int Field ID
     * @return array
     */
    public function getColumn($id)
    {
        $id = intval($id);
        $ri = Zymurgy::$db->run("SELECT * FROM `zcm_customfield` WHERE `id`=$id");
        $field = Zymurgy::$db->fetch_assoc($ri);
        if (!$field) throw new Exception("No such column ($id)");
        return $field;
    }

    /**
     * Get an associative array of all of a table's columns with column names for keys.  Takes a table's ID
     * or name.  If you pass an ID it must be of type int.
     *
     * @param $table int|string
     * @return array
     */
    public function getColumnsForTable($table)
    {
        if (!is_int($table))
        {
            $table = Zymurgy::$db->getParam("SELECT `id` FROM `zcm_customtable` WHERE `tname`={0}", array($table));
        }
        $ri = Zymurgy::$db->runParam("SELECT * FROM `zcm_customfield` WHERE `tableid`={0}", array($table));
        $columns = array();
        while (($row = Zymurgy::$db->fetch_assoc($ri))!==false)
        {
            $columns[$row['cname']] = $row;
        }
        Zymurgy::$db->free_result($ri);
        return $columns;
    }

    /**
     * @param $id int Field ID
     */
    public function dropColumn($id)
    {
        $id = intval($id);
        $field = $this->getColumn($id);
        $table = $this->getTable($field['tableid']);
        $tableName = Zymurgy::$db->escape_string($table['tname']);
        $columnName = Zymurgy::$db->escape_string($field['cname']);
        Zymurgy::$db->run("ALTER TABLE `{$tableName}` DROP `{$columnName}`");
        Zymurgy::$db->run("DELETE FROM `zcm_customfield` WHERE `id`=$id");
    }

    /**
     * @param $columnId int Column ID to update
     * @param $newColumnData array Key values pairs for column names and new values
     */
    public function updateField($columnId, $newColumnData)
    {
        $data = array();
        foreach ($newColumnData as $key=>$value)
        {
            $data[Zymurgy::$db->escape_string($key)] = Zymurgy::$db->escape_string($value);
        }
        $originalFieldData = Zymurgy::customTableTool()->getColumn($columnId);
        $tableData = Zymurgy::customTableTool()->getTable($originalFieldData['tableid']);
        $tableName = Zymurgy::$db->escape_string($tableData['tname']);
        $originalColumnName = Zymurgy::$db->escape_string($originalFieldData['cname']);
        $columnName = isset($data['cname']) ? $data['cname'] : $originalColumnName;
        $inputSpec = isset($data['inputspec']) ? $data['inputspec'] : $originalFieldData['inputspec'];
        $indexed = isset($data['indexed']) ? $data['indexed'] : $originalFieldData['indexed'];
        if (($originalFieldData['cname'] != $columnName) || ($originalFieldData['inputspec'] != $inputSpec)) {
            $originalInputWidget = InputWidget::GetFromInputSpec($originalFieldData['inputspec']);
            $newInputWidget = InputWidget::GetFromInputSpec($inputSpec);
            if ($originalInputWidget->SupportsFlavours() && !$newInputWidget->SupportsFlavours()) {
                //Moving from flavoured to vanilla
                Zymurgy::ConvertFlavouredToVanilla($tableData['tname'], $originalFieldData['cname'], $inputSpec);
            } elseif (!$originalInputWidget->SupportsFlavours() && $newInputWidget->SupportsFlavours()) {
                //Moving from vanilla to flavoured
                Zymurgy::ConvertVanillaToFlavoured($tableData['tname'], $originalFieldData['cname']);
            }
            //Do this even if we converted to/from flavoured to support column rename
            //Column name or type has changed, update the db.
            $sqlType = Zymurgy::inputspec2sqltype($inputSpec);
            $sql = "alter table `{$tableName}` change `{$originalColumnName}` `{$columnName}` $sqlType";
            mysql_query($sql) or die("Unable to change field ($sql): " . mysql_error());
        }
        if ($originalFieldData['indexed'] != $indexed) {
            //Add or remove an index
            if ($indexed == 'Y') {
                $sql = "alter table `{$tableName}` add index(`{$columnName}`)";
            } else {
                $sql = "alter table `{$tableName}` drop index `{$columnName}`";
            }
            mysql_query($sql) or die("Can't change index ($sql): " . mysql_error());
        }
        Zymurgy::$db->update('zcm_customfield', "`id`=$columnId", $data, false);
    }

    /**
     * @param $tableId int Table ID to add the field
     * @param $data array Key value pairs with column / values for zcm_customfield
     */
    public function addField($tableId, $data)
    {
        $tableData = Zymurgy::customTableTool()->getTable($tableId);
        $sqltype = Zymurgy::inputspec2sqltype($data['inputspec']);
        Zymurgy::$db->run("alter table `{$tableData['tname']}` add `{$data['cname']}` $sqltype");
        if ($data['indexed']=='Y')
        {
            Zymurgy::$db->run("alter table `{$tableData['tname']}` add index(`{$data['cname']}`)");
        }
        $data['tableid'] = intval($tableId);
        Zymurgy::$db->insert('zcm_customfield', $data, false);
        Zymurgy::$db->setDispOrder('zcm_customfield');
    }

    /**
     * Drop the custom table, and remove it from the list. Also drop any Detail
     * Tables associated with this table.
     *
     * @param $tid int The ID of the table to DROP, as defined in the
     * zcm_customtable table.
     */
    public function dropTable($tid)
    {
        $tid = intval($tid);
        $tableData = $this->getTable($tid);
        $table = $tableData['tname'];
        Zymurgy::$db->run("DROP TABLE `$table`");
        Zymurgy::$db->run("DELETE FROM `zcm_customtable` WHERE `id`=$tid");
        Zymurgy::$db->run("DELETE FROM `zcm_customfield` WHERE `tableid`=$tid");
        $parents = array();
        $ri = Zymurgy::$db->run("SELECT `id`, `tname` FROM `zcm_customtable` WHERE `detailfor`=$tid");
        while (($row=Zymurgy::$db->fetch_array($ri))!==false)
        {
            $parents[$row['id']] = $row['tname'];
        }
        foreach($parents as $ptid=>$pname)
        {
            $this->dropTable($ptid);
        }
    }

    /**
     * Update a custom table
     *
     * @param $data array Key value pairs representing column names and values for zcm_customtable
     */
    public function updateTable($data)
    {
        $tableData = Zymurgy::customTableTool()->getTable($data['id']);
        if ($tableData['tname'] != $data['tname']) {
            //Table name has changed
            $oldname = $tableData['tname'];
            $newname = $data['tname'];
            $needchange = array();
            $changed = array();
            $errmsg = '';
            //Find relationships and rename those
            $sql = "select * from zcm_customtable where detailfor={$tableData['id']}";
            $ri = mysql_query($sql) or die("Unable to get relationships ($sql): " . mysql_error());
            while (($drow = mysql_fetch_array($ri)) !== false) {
                $needchange[] = $drow['tname'];
            }
            mysql_free_result($ri);
            foreach ($needchange as $tname) {
                $sql = "alter table $tname change $oldname $newname bigint";
                $ri = mysql_query($sql);
                if ($ri !== false) {
                    $changed[] = $tname;
                } else {
                    $errmsg = "Couldn't rename the $oldname column to $newname in the table $tname ($sql): " . mysql_error();
                    break; //Don't bother changing any more, we're going to try to undo the damage and get out.
                }
            }
            if (empty($errmsg)) {
                //All required relationships have been successfully updated.
                $sql = "rename table `{$tableData['tname']}` to `{$data['tname']}`";
                $ri = mysql_query($sql);
                if (!$ri) {
                    $e = mysql_errno();
                    switch ($e) {
                        default:
                            $errmsg = "SQL error $e trying to rename table {$tableData['tname']} to {$data['tname']} ($sql): " . mysql_error();
                    }
                }
            }
            if (!empty($errmsg)) {
                //Something went wrong.  Back out.
                foreach ($changed as $tname) {
                    $sql = "update $tname change $newname $oldname bigint";
                    $ri = mysql_query($sql);
                    if ($ri === false) {
                        //Uh oh, can't even back out!  Best we can do is alert the developer of the snafu.
                        $errmsg .= "<br />Additionally we couldn't back out one of the table changes already made.  We tried ($sql) but received the error: " . mysql_error();
                    }
                }
                return $errmsg;
            }
        }
        if ($tableData['hasdisporder'] != $data['hasdisporder']) {
            //display order flag has changed
            if ($data['hasdisporder'] == 0) {
                //Remove display order column
                $sql = "alter table `{$data['tname']}` drop disporder";
                $ri = mysql_query($sql) or die ("Unable to remove display order ($sql): " . mysql_error());
            } else {
                //Add display order column
                $sql = "alter table `{$data['tname']}` add disporder bigint";
                mysql_query($sql) or die("Unable to add display order ($sql): " . mysql_error());
                $sql = "alter table `{$data['tname']}` add index(disporder)";
                mysql_query($sql) or die("Unable to add display order index ($sql): " . mysql_error());
                $sql = "update `{$data['tname']}` set disporder=id";
                mysql_query($sql) or die("Unable to set default display order ($sql): " . mysql_error());
            }
        }
        if ((0 + $tableData['ismember']) != (0 + $data['ismember'])) {
            //ismember flag has changed
            if ($data['ismember'] == 0) {
                //Remove member column
                $sql = "alter table `{$data['tname']}` drop member";
                $ri = mysql_query($sql) or die ("Unable to remove member ($sql): " . mysql_error());
            } else {
                //Add member column
                $sql = "alter table `{$data['tname']}` add member bigint";
                mysql_query($sql) or die("Unable to add member ($sql): " . mysql_error());
                $sql = "alter table `{$data['tname']}` add index(member)";
                mysql_query($sql) or die("Unable to add member index ($sql): " . mysql_error());
            }
        }
        if ($tableData['selfref'] != $data['selfref']) {
            //Self reference has changed
            if (empty($data['selfref'])) {
                //Remove self ref column
                Zymurgy::$db->run("alter table `{$data['tname']}` drop selfref");
            } else {
                //Add self ref column
                Zymurgy::$db->run("alter table `{$data['tname']}` add selfref bigint default 0");
                Zymurgy::$db->run("alter table `{$data['tname']}` add index(selfref)");
            }
        }
        Zymurgy::$db->update('zcm_customtable', "`id`={$data['id']}", $data);
        return true;
    }

    /**
     * Add a custom table
     *
     * @param $data array Key value pairs representing column names and values for zcm_customtable
     */
    function addTable($data)
    {
        $detailFor = $data['detailfor'];
        $idFieldName = $data["idfieldname"];
        if (strlen($idFieldName) <= 0) {
            $idFieldName = "id";
        }
        $sql = "create table `{$data['tname']}` ($idFieldName bigint not null auto_increment primary key";
        if ($detailFor > 0) {
            $tbl = Zymurgy::customTableTool()->getTable($detailFor);
            $detailForField = $data["detailforfield"];
            if (strlen($detailForField) <= 0) {
                $detailForField = $tbl["tname"];
            }

            $sql .= ", `$detailForField` BIGINT, KEY `{$tbl['tname']}` (`$detailForField`)";
        }
        if ($data['hasdisporder'] == 1) {
            $sql .= ", disporder bigint, key disporder (disporder)";
        }
        if ($data['ismember'] == 1) {
            $sql .= ", member bigint, key member (member)";
        }
        $sql .= ")";
        $ri = mysql_query($sql) or die("Unable to create table ($sql): " . mysql_error());
        if (!$ri) {
            $e = mysql_errno();
            switch ($e) {
                case 1050:
                    return "The table {$data['tname']} already exists.  Please select a different name.";
                default:
                    return "<p>SQL error $e trying to create table: " . mysql_error() . "</p>";
            }
            return false;
        }
        if (!empty($data['selfref'])) {
            Zymurgy::$db->run("alter table `{$data['tname']}` add selfref bigint default 0");
            Zymurgy::$db->run("alter table `{$data['tname']}` add index(selfref)");
        }
        Zymurgy::$db->insert('zcm_customtable', $data);
        Zymurgy::$db->setDispOrder('zcm_customtable');
        return true;
    }

    public function getColumnByName($tableId, $columnName)
    {
        $tableId = intval($tableId);
        $columnName = Zymurgy::$db->escape_string($columnName);
        $ri = Zymurgy::$db->run("SELECT * FROM `zcm_customfield` WHERE `tableid`=$tableId AND `cname`='$columnName'");
        return Zymurgy::$db->fetch_assoc($ri);
    }
}