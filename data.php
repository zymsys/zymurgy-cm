<?php
/**
 * todo:
 * Accept request data as json using content-type header
 * Allow get requests for ranges of this->results (paging for grids)
 * PUT method for updates, POST should only be for inserts
 * HTTP Method alias support so that a client which can't PUT or DELETE can mimic the behaviour with POST
 * Testing with ko.ajax
 */
ob_start();
require_once 'cmo.php';
require_once Zymurgy::getFilePath("~model.php");
ob_clean();

class ZymurgyJSONDataController
{
    protected $result;
    protected $model;
    protected $isIdentity = false;
    
    function __construct($tableName)
    {
        Zymurgy::headtags(false);
        $this->result = new stdClass();
        $this->result->status = 'ok';
        $this->tableName = Zymurgy::$db->escape_string($tableName);
        $this->model = ZymurgyModel::factory($this->tableName);
    }

    public function addIdentityFilter($identity)
    {
        $this->isIdentity = true;
        $this->model->addIdentityFilter($identity);
    }

    public function emitJSONResult()
    {
        self::emitJSON($this->result);
    }

    public static function emitJSON($object)
    {
        header('Content-type: application/json');
        echo json_encode($object);
    }

    public function processHTTPRequest($method, $requestVariables)
    {
        switch ($method)
        {
            case 'POST':
                $this->processPost($requestVariables);
                break;
            case 'DELETE':
                $this->processDelete($requestVariables);
                break;
            default:
                $this->processGet($requestVariables);
                break;
        }
        $this->result->affectedrows = Zymurgy::$db->affected_rows();
        if (($this->result->affectedrows == 0) && ($this->model->getMemberTableName()))
        {
            $this->result->warning = "Zero rows affected.  Possibly limited by member data ownership (".
                $this->model->getMemberTableName().").";
        }
    }

    protected function processGet($requestVariables)
    {
        $this->addRequestFilter($requestVariables);
        $this->applySort($requestVariables);
        $this->applyRange($requestVariables);
        $this->result->data = $this->model->read();
        if ($this->isIdentity && $this->result->data) {
            $this->result->data = $this->result->data[0];
        } else if ($this->model->getMemberTableName() !== false) {
            $members = array();
            foreach ($this->result->data as $row) {
                if (isset($row['member'])) {
                    $members[$row['member']] = false;
                }
            }
            $ri = Zymurgy::$db->runParam("SELECT * FROM zcm_member WHERE id IN ({0})",
                array(implode(',',array_keys($members))));
            Zymurgy::$db->each($ri, function ($row) use (&$members) {
                unset($row['password']);
                unset($row['mpkey']);
                unset($row['authkey']);
                unset($row['formdata']);
                $members[$row['id']] = $row;
            });
            $this->result->members = $members;
        }
        $this->result->count = $this->model->count();
        $this->result->success = is_array($this->result->data);
        $this->result->sql = $this->model->getLastSQL();
    }

    protected function processDelete($requestVariables)
    {
        $this->result->success = $this->model->delete();
    }

    protected function processPost($requestVariables)
    {
        $newId = $this->model->write($requestVariables);
        if ($newId) $this->result->newid = $newId;
    }

    private function addRequestFilter($requestVariables)
    {
        if (isset($requestVariables['c']))
        {
            $requestedColumns = explode(',', $requestVariables['c']);
            $this->model->requestColumns($requestedColumns);
        }
        foreach($requestVariables as $key=>$value)
        {
            if (($key[0] == 'f') && (strlen($key) > 3))
            {
                switch ($key[1])
                {
                    case 'e': $operator = '=';  break;
                    case 'g': $operator = '>';  break;
                    case 'G': $operator = '>='; break;
                    case 'l': $operator = '<';  break;
                    case 'L': $operator = '<='; break;
                    case 'n': $operator = '!='; break;
                    case 'k': $operator = 'like'; break;
                    case 'N': $operator = 'N'; break;
                    default: unset($operator);
                }
                if (!isset($operator)) continue;
                $this->model->addFilter(new ZymurgyModelFilter($operator, $key[2] == 'o', substr($key,3), $value));
            }
        }
    }

    private function applySort($requestVariables)
    {
        if (array_key_exists('s',$_GET))
        {
            $parts = explode(',',$_GET['s']);
            foreach ($parts as $part)
            {
                list($col,$order) = explode('-',$part,2);
                if (!$order) $order = 'asc';
                $this->model->addSort($col, $order);
            }
        }
    }

    private function applyRange($requestVariables)
    {
        if (array_key_exists('p',$requestVariables))
        {
            list($page, $pageSize) = explode('-', $requestVariables['p']);
            $page = intval($page);
            $pageSize = intval($pageSize);
            $end = ($page * $pageSize);
            $page -= 1;
            $start = ($page * $pageSize);
            $this->model->setRange($start, $pageSize);
        }
    }
}

try
{
    ob_start();
    $controller = new ZymurgyJSONDataController($_GET['table']);
    if (array_key_exists('id', $_REQUEST))
    {
        $controller->addIdentityFilter($_REQUEST['id']);
    }
    $controller->processHTTPRequest($_SERVER['REQUEST_METHOD'], $_REQUEST);
    if (array_key_exists('rurl', $_REQUEST))
    {
        $rurl = str_replace(array("\r","\n"), '', $_REQUEST['rurl']);
        header('Location: '.$rurl);
    }
    else
    {
        ob_clean();
        $controller->emitJSONResult();
    }
}
catch (Exception $e)
{
    $errorResult = new stdClass();
    $errorResult->code = $e->getCode();
    $errorResult->errormsg = $e->getMessage();
    $errorResult->success = false;
    ZymurgyJSONDataController::emitJSON($errorResult);
}
