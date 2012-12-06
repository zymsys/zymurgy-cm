<?php
function envOrDefault($name, $default)
{
    $result = getenv($name);
    return ($result === false) ? $default : $result;
}

return array(
    "mysqlhost"=>envOrDefault('DB_HOST', '127.0.0.1'),
    "mysqluser"=>envOrDefault('DB_USER', 'root'),
    "mysqlpass"=>envOrDefault('DB_PASSWORD', ''),
    "mysqldb"=>envOrDefault('DB_NAME', 'zymurgycm'),
    "Mailer Type"=>"smtp",
    "Mailer SMTP Hosts"=>"192.168.200.21",
    "sitehome"=>$_SERVER["HTTP_HOST"],
    "defaulttitle"=>envOrDefault('SITE_TITLE', 'Zymurgy:CM Powered Web Site'),
    "defaultdescription"=>"",
    "defaultkeywords"=>"",
    "userwikihome"=>"http://www.zymurgycm.com/userwiki/index.php/",
    "Default Timezone"=>"America/New_York",
    "tracking"=>"",
    "MemberProvider"=>getenv('MEMBER_PROVIDER') ? getenv('MEMBER_PROVIDER') . 'Member' : '',
    "Membership LDAP Server Address"=>envOrDefault('LDAP_HOST', '127.0.0.1'),
    "Membership LDAP Base DN"=>envOrDefault('LDAP_DN', 'dc=example,dc=com'),
    "Membership LDAP Default User Domain"=>envOrDefault('LDAP_DOMAIN', 'example'),
    "MemberLoginPage"=>"~memberlogin.php",
    "MemberDefaultPage"=>"/pages/members",
    "headerbackground"=>"#666698",
    "headercolor"=>"#FFFFFF",
    "gridcss"=>"~include/datagrid.css",
    "ConvertPath"=>"",
    "database"=>"mysql",
);