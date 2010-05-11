<?
/**
 * Purchase screen. Note that Zymurgy:CM is no longer available for online 
 * purchase.
 *
 * @package Zymurgy
 * @subpackage orphanned
 * @deprecated
 */
if (empty($member))
{
	echo "This installation of Zymurgy:CM is not linked to a zymurgy.ca account.  Use your zymurgy.ca user ID and password to link it to your account, or <a href=\"http://www.zymurgy.ca/members/login.php?reg=username\">create an account</a> now.";
}
else 
{
	echo "This installation of Zymurgy:CM is linked to $member.  You may link it to a different account.";
}
?>
<form method="POST" action="http://www.zymurgy.ca/members/link.php">
<input type="hidden" name="siteid" value="<?=$siteid?>">
<table>
<tr>
	<td align="right">email:</td>
	<td><input type="text" maxlength="80" name="email"></td>
</tr>
<tr>
	<td align="right">password:</td>
	<td><input type="password" name="password"></td>
</tr>
<tr>
	<td align="center" colspan="2"><input type="submit" value="Link this installation to your zymurgy.ca account."></td>
</tr>
</table>
</form>