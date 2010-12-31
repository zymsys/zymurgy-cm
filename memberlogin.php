<?php
require_once 'cmo.php';

if ((!array_key_exists('MembershipLoginForm',Zymurgy::$config))
	|| empty(Zymurgy::$config['MembershipLoginForm']))
{
	$myurl = strtolower(array_shift(explode('/',$_SERVER['SERVER_PROTOCOL'],2)));
	$myurl .= '://'.$_SERVER['SERVER_NAME'].'/'.$_SERVER['REQUEST_URI'];
	Zymurgy::$config['MembershipLoginForm'] = <<<HEREDOC
<form class="MemberLogin" method="post" action="$myurl">
	<table>
        <tr><td align="right">User ID:</td><td><input type="text" name="email" id="email"></td></tr>
        <tr><td align="right">Password:</td><td><input type="password" name="pass" id="pass"></td></tr>
        <!--tr><td align="right"><input type="checkbox" name="remember" id="remember"></td><td><label for="remember">Remember me on this computer</label></td></tr-->
        <tr><td align="center" colspan="2">&nbsp;<br><input type="Submit" value="Login"><br>&nbsp;</td></tr>
        <tr><td align="center" colspan="2"><a href="memberlogin.php?reg=forgotpassword">Forgot your password?</a></td></tr>
    </table>
</form>
HEREDOC;
}
?>
<!doctype html>
<html>
<head>
<title>Login - <?php echo htmlspecialchars(Zymurgy::$config['defaulttitle']) ?></title>
<?php 
echo Zymurgy::headtags();
echo Zymurgy::jQuery();
echo Zymurgy::jQueryUI();
?>
<script type="text/javascript">
$(document).ready(function () {
	$('#login').dialog({
		'title': 'Login - <?php echo addslashes(Zymurgy::$config['defaulttitle']) ?>',
		'width': 500,
		'height': 300,
		'closeOnEscape': false,
		'open': function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
	});
});
</script>
<style type="text/css">
.MemberBadLogin {
	color: red;
}
</style>
</head>
<body>
<div id="login" align="center">
<?php 
echo Zymurgy::memberlogin();
?>
</div>
</body>
</html>