<?
/**
 * Containd Zymrgy_DB wrapper for DBO.
 *
 * @access private
 * @package
 */

require_once(Zymurgy::getFilePath("~db/base.php"));

/**
 * Wrapper for DBO DB interface.
 *
 * This class will auto-connect to the database specified in config.php using the username and password there.
 *
 * <b>DO NOT create an instance of this class yourself, always use {@link Zumurgy::$db} instead.</b>
 *
 * @package Zymurgy
 * @subpackage base
 */
class Zymurgy_DB extends Zymurgy_Base_Db
{
    private $_link;

    private $_fetchMap = array(
        ZYMURGY_FETCH_ASSOC => PDO::FETCH_ASSOC,
        ZYMURGY_FETCH_BOTH => PDO::FETCH_BOTH,
        ZYMURGY_FETCH_NUM => PDO::FETCH_NUM,
    );

    private $_lastStatement;

    private $_tableKeys = array();

    /**
     * Create a new connection.
     *
     * Do not call this, use {@link Zumurgy::$db} instead.
     *
     * @ignore
     */
    public function __construct()
    {
        $this->_link = new PDO(Zymurgy::$config['dbConnection'], Zymurgy::$config['dbUser'], Zymurgy::$config['dbPassword']);
    }

    public function query($sql)
    {
        $this->_lastStatement = $this->_link->query($sql);
        return $this->_lastStatement;
    }

    public function num_fields($ri)
    {
        return $ri->columnCount();
    }

    public function fetch_array($result, $result_type = ZYMURGY_FETCH_BOTH)
    {
        return $result->fetch($this->_fetchMap[$result_type]);
    }

    public function fetch_assoc($result)
    {
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    public function fetch_row($result)
    {
        return $result->fetch(PDO::FETCH_NUM);
    }

    public function fetch_object($result)
    {
        return $result->fetch(PDO::FETCH_OBJ);
    }

    public function num_rows($result)
    {
        return $result->rowCount();
    }

    public function free_result($result)
    {
        return $result->closeCursor();
    }

    public function insert_id()
    {
        return $this->_link->lastInsertId();
    }

    public function escape_string($to_be_escaped)
    {
        $quoted = $this->_link->quote($to_be_escaped);
        return substr($quoted, 1, -1); //Strip outside quotes added by PDO::quote()
    }

    public function error()
    {
        $info = $this->_link->errorInfo();
        return $info[2];
    }

    public function errno()
    {
        return $this->_link->errorCode();
    }

    public function affected_rows()
    {
        return $this->_lastStatement->rowCount();
    }

    public function enumeratetables()
    {
        $name = $this->_link->getAttribute(PDO::ATTR_DRIVER_NAME);
        $tables = array();
        switch ($name) {
            case 'mysql':
                $ri = $this->run("show tables");
                while (($row = $this->fetch_row($ri)))
                {
                    $tables[] = $row[0];
                }
                $this->free_result($ri);
                return $tables;
            default:
                throw new Exception("No support for enumerating tables on " . $name . " databases.");
        }
    }

    public function getTableKeys($table)
    {
        $name = $this->_link->getAttribute(PDO::ATTR_DRIVER_NAME);
        $keys = array();
        switch ($name) {
            case 'mysql':
                $ri = $this->run("show columns from `$table`");
                while (($row = $this->fetch_assoc($ri)))
                {
                    if ($row['Key'] === 'PRI')
                    {
                        $keys[] = $row['Field'];
                    }
                }
                $this->free_result($ri);
                return $keys;
            default:
                throw new Exception("No support for enumerating table keys on " . $name . " databases.");
        }
    }
}