<?php

define('ZYMURGY_DB_DEFAULT_ERROR', 'Unable to run query');
define('ZYMURGY_FETCH_ASSOC', 1);
define('ZYMURGY_FETCH_BOTH', 3);
define('ZYMURGY_FETCH_NUM', 2);

abstract class Zymurgy_Base_Db
{
    public $trace = false;

    /**
     * @var callback
     * If you set a changeTracker it will get called when the
     * Zymurgy::$db->insert, update or delete methods are invoked to track
     * field level changes made by those functions.  This feature has a massive
     * performance hit and should only be used on low traffic sites where
     * this kind of tracking is really needed.  If you save changes to a
     * change tracking table be sure not to use the Zymurgy::$db->insert
     * method to avoid infinite recursion.
     *
     * The callback should have the following signature:
     *
     * function changeTracker($operation, $table, $key, $columnName, $value)
     *
     * The $operation is one of 'insert', 'update' or 'delete'.  The primary
     * key is sent in $key, and compound keys are joined with commas.
     */
    public $changeTracker;

    abstract public function query($sql);
    abstract public function num_fields($ri);
    abstract public function fetch_array($result, $result_type = ZYMURGY_FETCH_BOTH);
    abstract public function fetch_assoc($result);
    abstract public function fetch_row($result);
    abstract public function fetch_object($result);
    abstract public function num_rows($result);
    abstract public function free_result($result);
    abstract public function insert_id();
    abstract public function escape_string($to_be_escaped);
    abstract public function error();
    abstract public function errno();
    abstract public function affected_rows();
    abstract public function enumeratetables();
    abstract public function getTableKeys($table);

    //Deprecated; this is evil.
//    abstract public function result($result, $row, $field = null);

    /**
     * Run query and give an error message if there's a problem.
     *
     * Returns a result identifier resource. Script ends on error.
     *
     * @todo should this use an exception instead of killing the script?
     *
     * @see query()
     *
     * @param string $sql The query to run
     * @param string $errormsg The error message to show in case of error, or false to throw an exception.
     * @return resource db result set
     */
    public function run($sql, $errormsg = ZYMURGY_DB_DEFAULT_ERROR)
    {
        // should this throw an exception instead?
        $ri = $this->query($sql);
        if (!$ri)
        {
            $backtrace = debug_backtrace();
            do
            {
                $bt = array_shift($backtrace);
            } while ($bt && (substr($bt['file'], -21) === Zymurgy::getUrlPath("~db/" . Zymurgy::$config['database'] . ".php")));
            if ($errormsg === false)
            {
                throw new Exception(
                    $this->error()." in ".$bt['file']." on line ".$bt['line'],
                    $this->errno()
                );
            }
            else
            {
                echo "$errormsg ($sql): ".$this->error()." in ".$bt['file']." on line ".$bt['line']."<!--\n";
                debug_print_backtrace();
                echo "\n-->";
                die;
            }
        }
        return $ri;
    }

    /**
     * Return value of request named $name, escaped for the character set of the current connection.
     * If $name isn't in the request then either throw an exception or return the escaped value of
     * $default if it was provided.
     *
     * @param string $name
     * @param string $default
     * @return string
     * @throws Exception
     */
    public function request($name, $default = false)
    {
        if (array_key_exists($name, $_REQUEST))
        {
            return $this->escape_string($_REQUEST[$name]);
        }
        else
        {
            if ($default === false)
            {
                throw new Exception("Can't find $name in request.", 0);
            }
            else
            {
                return $this->escape_string($default);
            }
        }
    }

    /**
     * Take an SQL template and replace values from $_REQUEST; values are escaped according to the current
     * connection's character set.  All values are quoted.
     * Example: example.php?id=5&name=Foo
     * sql_template: UPDATE `bar` SET `name`=[name] WHERE `id`=[id]
     * returns: UPDATE `bar` SET `name`='Foo' WHERE `id`='5'
     *
     * @param string $sql_template
     * @return string
     */
    public function requestsql($sql_template)
    {
        $replmap = array();
        foreach(array_keys($_REQUEST) as $key)
        {
            $replmap["[$key]"] = "'".$this->escape_string($_REQUEST[$key])."'";
        }
        return str_replace(array_keys($replmap), array_values($replmap), $sql_template);
    }

    /**
     * Get a single row or value from a query.
     *
     * Run a query and throw an error if there's a problem.  Return the first row as an array or
     * the value if only one column is returned.  Returns false if no data is returned.
     *
     * @see query()
     *
     * @param string $sql
     * @param string $errormsg
     * @return mixed
     */
    public function get($sql, $errormsg = ZYMURGY_DB_DEFAULT_ERROR)
    {
        $ri = $this->run($sql,$errormsg);
        $row = $this->fetch_array($ri);
        if ($this->num_fields($ri)==1)
        {
            return $row[0];
        }
        $this->free_result($ri);
        return $row;
    }


    /**
     * Assemble an update statement from components, and run it.
     *
     * @param $table string Table name
     * @param $where string Where clause
     * @param $data array Key value pairs with column names and contents
     * @param bool $escape Escape names and values? (defaults to true)
     * @param string $errorMessage
     */
    public function update($table, $where, $data, $escape = true, $errorMessage=ZYMURGY_DB_DEFAULT_ERROR)
    {
        if ($escape)
        {
            $table = $this->escape_string($table);
        }
        $data = $this->escape($data, $escape);
        if ($this->changeTracker)
        {
            $keys = $this->getTableKeys($table);
            $oldRowData = array();
            $ri = $this->run("SELECT * FROM `$table` WHERE $where");
            while (($row = $this->fetch_assoc($ri)) !== false)
            {
                $keyValue = array();
                foreach ($keys as $key)
                {
                    $keyValue[] = $row[$key];
                }
                $flatKeyValue = implode(',', $keyValue);
                $oldRowData[$flatKeyValue] = $row;
            }
        }
        $sql = "UPDATE `$table` SET ";
        $updates = array();
        foreach ($data as $columnName => $value)
        {
            $updates[] = "`$columnName`=$value";
        }
        $sql .= implode(", ", $updates) . " WHERE " . $where;
        $this->run($sql, $errorMessage);
        if ($this->changeTracker)
        {
            $changeTracker = $this->changeTracker;
            $ri = $this->run("SELECT * FROM `$table` WHERE $where");
            while (($row = $this->fetch_assoc($ri)) !== false)
            {
                $keyValue = array();
                foreach ($keys as $key)
                {
                    $keyValue[] = $row[$key];
                }
                $flatKeyValue = implode(',', $keyValue);
                if (isset($oldRowData[$flatKeyValue]))
                {
                    $oldRow = $oldRowData[$flatKeyValue];
                    foreach ($row as $key => $value) {
                        if (isset($oldRow[$key]) && ($oldRow[$key] !== $row[$key]))
                        {
                            $changeTracker('update', $table, $flatKeyValue, $key, $row[$key]);
                        }
                    }

                }

            }
        }
    }

    public function insert($table, $data, $escape = true, $errorMessage=ZYMURGY_DB_DEFAULT_ERROR)
    {
        if ($escape)
        {
            $table = $this->escape_string($table);
        }
        $sql = "INSERT INTO `$table` (`";
        $data = $this->escape($data, $escape);
        $sql .= implode("`, `", array_keys($data));
        $sql .= "`) VALUES (";
        $sql .= implode(", ", array_values($data));
        $sql .= ")";
        $this->run($sql, $errorMessage);
        $insertId = $this->insert_id();
        if ($this->changeTracker)
        {
            $changeTracker = $this->changeTracker;
            $id = array();
            $where = array();
            $keys = $this->getTableKeys($table);
            foreach ($keys as $key) {
                $value = isset($data[$key]) ? $data[$key] : $insertId;
                $id[] = $value;
                $where[] = "`$key`='" . $this->escape_string($value) . "'";
            }

            $row = $this->get("SELECT * FROM `$table` WHERE " . implode(' AND ', $where));
            foreach ($data as $key => $value) {
                $changeTracker('insert', $table, implode(',',$id), $key, $row[$key]);
            }

        }
        return $insertId;
    }

    /**
     * @param $data
     * @param $escape
     * @return array
     */
    private function escape($data, $escape = true)
    {
        $escaped = array();
        foreach ($data as $columnName => $value) {
            if (is_null($value)) {
                $escaped[$columnName] = 'NULL';
            } else if ($value instanceof DateTime) {
                $escaped[$columnName] = "'" . $value->format('Y-m-d H:i:s') . "'";
            } else {
                if ($escape) $value = $this->escape_string($value);
                $escaped[$columnName] = "'$value'";
            }
        }
        return $escaped;
    }

    public function delete($table, $where, $escape = true, $errorMessage=ZYMURGY_DB_DEFAULT_ERROR)
    {
        if ($escape)
        {
            $table = $this->escape_string($table);
        }
        $oldRows = array();
        if ($this->changeTracker)
        {
            $keys = $this->getTableKeys($table);
            $selectColumns = array();
            foreach ($keys as $key) {
                $selectColumns[] = "`$key`";
            }
            $ri = $this->run("SELECT " . implode(',',$selectColumns) . " FROM `$table` WHERE $where");
            while (($row = $this->fetch_array($ri, ZYMURGY_FETCH_NUM)) !== false)
            {
                $oldRows[] = implode(',', $row);
            }
        }
        $ri = $this->run("DELETE FROM `$table` WHERE $where", $errorMessage);
        if ($oldRows)
        {
            $changeTracker = $this->changeTracker;
            foreach ($oldRows as $key) {
                $changeTracker('delete', $table, $key, '', '');
            }
        }
        return $ri;
    }

    public function setDispOrder($table)
    {
        $this->run("UPDATE `$table` SET `disporder`=`id` WHERE `disporder` IS NULL");
    }

    public function runParam($sql, $params, $errorMessage=ZYMURGY_DB_DEFAULT_ERROR)
    {
        $sql = $this->param($params, $sql);
        return $this->run($sql, $errorMessage);
    }

    public function param($params, $sql)
    {
        $replace = array();
        foreach ($params as $key => $value) {
            $replace['{' . $key . '}'] = "'" . $this->escape_string($value) . "'";
        }
        $sql = str_replace(array_keys($replace), array_values($replace), $sql);
        return $sql;
    }

    public function getParam($sql, $params, $errorMessage=ZYMURGY_DB_DEFAULT_ERROR)
    {
        $sql = $this->param($params, $sql);
        return $this->get($sql, $errorMessage);
    }

    public function each($result, $callable)
    {
        while (($row = $this->fetch_assoc($result)) !== false) {
            $callable($row);
        }
    }

    public function getAll($sql, $params = array(), $errorMessage=ZYMURGY_DB_DEFAULT_ERROR)
    {
        $sql = $this->param($params, $sql);
        $ri = $this->runParam($sql, $params, $errorMessage);
        $result = array();
        while (($row = $this->fetch_assoc($ri)) !== false) {
            $result[] = $row;
        }
        $this->free_result($ri);
        return $result;
    }
}