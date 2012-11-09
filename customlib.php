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
     * Returns the information on a custom table.
     *
     * @param int $t The ID of the detail table, as defined in the zcm_customtable table
     * @return array
     */
    public function getTable($t)
    {
        $sql = "select * from zcm_customtable where id=$t";
        $ri = mysql_query($sql) or die("Can't get table ($sql): ".mysql_error());
        $tbl = mysql_fetch_array($ri);
        if (!is_array($tbl))
            throw new Exception("No such table ($t)");
        return $tbl;
    }

    /**
     * @param $id int Field ID
     * @return array
     */
    public function getColumn($id)
    {
        $id = intval($id);
        $field = Zymurgy::$db->get("SELECT * FROM `zcm_customfield` WHERE `id`=$id");
        if (!$field) throw new Exception("No such column ($id)");
        return $field;
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
}