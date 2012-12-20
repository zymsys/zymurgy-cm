<?
/**
 *
 * @package Zymurgy
 * @subpackage auth
 */
class ldapMember extends ZymurgyMember
{
    const MEMBERSHIP_LDAP_SERVER_ADDRESS = "Membership LDAP Server Address";
    const MEMBERSHIP_LDAP_BASE_DN = "Membership LDAP Base DN";
    const MEMBERSHIP_LDAP_DEFAULT_USER_DOMAIN = 'Membership LDAP Default User Domain';

    public function __construct()
    {
        $issue = '';
        $isValid = $this->ValidateConfigurationItem($issue, self::MEMBERSHIP_LDAP_SERVER_ADDRESS);
        $isValid = $isValid && $this->ValidateConfigurationItem($issue, self::MEMBERSHIP_LDAP_BASE_DN);

        if(!$isValid)
        {
            $issue = "Could not set up LDAP Membership Provider: <ul>\n".
                $issue.
                "</ul>\n";

            die($issue);
        }
    }

    protected function ValidateConfigurationItem(&$issue, $name)
    {
        $isValid = true;

        if(!isset(Zymurgy::$config[$name]))
        {
            $issue .= "<li>The <b>$name</b> configuration must be set.</li>\n";
            $isValid = false;
        }

        return $isValid;
    }

    private function findmemberfromsession()
    {
        $sid = session_id();
        if (empty($sid))
        {
            session_start();
        }
        if (array_key_exists('AuthName',$_SESSION))
        {
            $member = Zymurgy::$db->get("select * from zcm_member where mpkey='".
                Zymurgy::$db->escape_string($_SESSION['AuthName'])."'");
            if (is_array($member))
            {
                $this->populatememberfromrow($member);
                $this->createauthkey($member['id']);
                return true;
            }
        }
        return false;
    }

    /**
     * Authenticate that the user is logged in.
     *
     * @return boolean
     */
    public function memberauthenticate()
    {
        $sid = session_id();
        if (empty($sid))
        {
            session_start();
        }
        if (parent::memberauthenticate())
        {
            //Parent think's we're logged in, but are we still logged into the MP?
            if (array_key_exists('AuthName',$_SESSION))
            {
                if ($_SESSION['AuthName'] == Zymurgy::$member['username'])
                {
                    return true;
                }
            }
        }
        return $this->findmemberfromsession();
    }

    /**
     * Try to log in with the provided user ID and password using vtiger's portal authentication soap service.
     * If log in is successful then emulate vtiger's session variables for compatibility with the portal.
     *
     * @param string $userId
     * @param string $password
     * @return boolean
     */
    public function memberdologin($userId, $password, $writeCookie = true)
    {
        $ldapServer = Zymurgy::$config[self::MEMBERSHIP_LDAP_SERVER_ADDRESS];
        $link = ldap_connect($ldapServer);
        if (!$link) throw new Exception('Unable to connect to LDAP Server at ' . $ldapServer);
        ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($link, LDAP_OPT_REFERRALS, 0);
        if (strpos($userId, '@') === false)
        {
            if (isset(Zymurgy::$config[self::MEMBERSHIP_LDAP_DEFAULT_USER_DOMAIN]))
            {
                $userDomain = Zymurgy::$config[self::MEMBERSHIP_LDAP_DEFAULT_USER_DOMAIN];
                $userId .= '@' . $userDomain;
            }
        }
        $bind = @ldap_bind($link, $userId, $password);
        if (!$bind) return false;

        $baseDN = Zymurgy::$config[self::MEMBERSHIP_LDAP_BASE_DN];
        list($bareAccountName, $domain) = explode('@', $userId, 2);
        $search = @ldap_search($link, $baseDN,
            "(&(objectCategory=person)(objectClass=user)(samaccountname={$bareAccountName}))");
        if ($search === false)
        {
            return false;
        }
        $entries = ldap_get_entries($link, $search);
        $groups = array();
        if ($entries && ($entries['count'] > 0))
        {
            $user = $entries[0];
            for ($i = 0; $i < $user['memberof']['count']; $i += 1)
            {
                $parts = explode(',', $user['memberof'][$i]);
                foreach ($parts as $part) {
                    list($type, $value) = explode('=', $part);
                    if ($type == 'CN') $groups[] = $value;
                }
            }

            $sid = session_id();
            if (empty($sid))
            {
                session_start();
            }
            $_SESSION['AuthName'] = $userId;
            if (!$this->findmemberfromsession())
            {
                //Member isn't yet known to Z:CM, add it.
                $email = '';
                if (isset($user['mail']) && isset($user['mail'][0]))
                {
                    $email = $user['mail'][0];
                }
                $fullName = '';
                if (isset($user['cn']) && isset($user['cn'][0]))
                {
                    $fullName = $user['cn'][0];
                }
                Zymurgy::$db->run("insert into zcm_member (email,username,password,fullname,regtime,lastauth,mpkey) values ('".
                    Zymurgy::$db->escape_string($email)."','".
                    Zymurgy::$db->escape_string($userId)."','n/a','".
                    Zymurgy::$db->escape_string($fullName).
                    "', now(),now(),'".
                    Zymurgy::$db->escape_string($userId)."')");
                $this->findmemberfromsession();
            }
            $this->syncGroups($groups);
            return true;
        }
        return false;
    }

    /**
     * Clear all Zymurgy and Infusionsoft authentication and log out from both.
     * Redirect the user to $logoutpage.
     *
     * @param string $logoutpage
     */
    public function memberlogout($logoutpage)
    {
        $this->memberauthenticate();

        if (is_array(Zymurgy::$member))
        {
            $sql = "update zcm_member set authkey=null where id=".Zymurgy::$member['id'];
            Zymurgy::$db->query($sql) or die("Unable to logout ($sql): ".Zymurgy::$db->error());
            if(!headers_sent())
            {
                setcookie('ZymurgyAuth');
            }
        }
        unset($_SESSION['AuthName']);
        Zymurgy::JSRedirect($logoutpage);
    }

    public function membersignup(
        $formname,
        $useridfield,
        $passwordfield,
        $confirmfield,
        $redirect)
    {
        throw new Exception("Can't sign up through the LDAP membership provider.");
    }

    private function syncGroups($groups)
    {
        $groupIds = array();
        $belongsTo = array();
        $builtIn = array();
        foreach ($groups as $groupName) {
            $groupIds[$groupName] = '';
            $belongsTo[$groupName] = '';
        }

        $ri = Zymurgy::$db->run("SELECT `id`, `name`, `builtin` FROM `zcm_groups`");
        while (($row = Zymurgy::$db->fetch_array($ri))!==false)
        {
            if ($row['builtin'] == 1)
            {
                $builtIn[$row['id']] = $row['name'];
            }
            else
            {
                if (isset($groupIds[$row['name']]))
                {
                    $groupIds[$row['name']] = $row['id'];
                }
            }
        }
        Zymurgy::$db->free_result($ri);
        foreach ($groupIds as $groupName=>$groupId) {
            if (!empty($groupId)) continue;
            Zymurgy::$db->run("INSERT INTO `zcm_groups` (`name`) VALUES ('" .
                Zymurgy::$db->escape_string($groupName) . "')");
            $groupIds[$groupName] = Zymurgy::$db->insert_id();
        }
        $groupsById = array_flip($groupIds);
        $ri = Zymurgy::$db->run("SELECT `groupid` FROM `zcm_membergroup` WHERE `memberid`='" .
            Zymurgy::$db->escape_string(Zymurgy::$member['id']) . "'");
        $removeGroups = array();
        while (($row = Zymurgy::$db->fetch_array($ri))!==false)
        {
            if (isset($groupsById[$row['groupid']]))
            {
                unset($groupsById[$row['groupid']]);
            }
            else
            {
                if (!isset($builtIn[$row['groupid']]))
                {
                    $removeGroups[] = $row['groupid'];
                }
            }
        }
        Zymurgy::$db->free_result($ri);
        foreach ($groupsById as $groupId => $groupName) {
            Zymurgy::$db->run("INSERT INTO `zcm_membergroup` (`memberid`,`groupid`) VALUES ('" .
                Zymurgy::$db->escape_string(Zymurgy::$member['id']) . "', {$groupId})");
        }
        if ($removeGroups)
        {
            Zymurgy::$db->run("DELETE FROM `zcm_membergroup` WHERE `memberid`=" . Zymurgy::$member['id'] .
                " AND `groupid` IN (" . implode(',', $removeGroups) . ")");
        }
    }
}
?>
