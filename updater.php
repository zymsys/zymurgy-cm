<?
/**
 * The concept:  In the admin the CMF will check in with http://www.zymurgy2.com/cmf/updater.php
 * passing it's own host and build number.  The Z2 updater script will keep the db of cmf installs
 * up to date and return the current build number and welcome HTML for the main page.
 * 
 * Welcome text will come in three flavours.  Super user text aimed at Zymurgy clients and admin
 * and normal user text supplied by Zymurgy clients for viewing in turn by their clients.  The CMS
 * will display all three with edit links to super users, or just the appropriate text for other logins.
 * 
 * Now that the CMF knows the latest build number it compares against its own to see if there is
 * an update.  If there is it adds an update link to the super user's nav strip.
 * 
 * The updater first updates the database by querying for a list of SQL queries to upgrade the
 * database to the current build.  Then it queries a list of files to update and downloads them one 
 * at a time by HTTP requests and installs them to the local host using FTP.
 * 
 * Ensuring updates go correctly:  As SQL and files are requested the server logs that they have been
 * supplied.  As they succeed they notify Z2 and the database logs the success.  When future updates 
 * are requested, anything which has already succeeded is not re-sent.  Errors are also logged to Z2
 * and alerts sent so that problems can be corrected ASAP.
 * 
 * Working well in disconnected environments:  If the server is behind a restrictive firewall or non
 * connected intranet then this communication to Z2 will fail.  Initial communication should be 
 * done through AJAX so that communication errors can be hidden from the user and so that they
 * won't delay delivery of the front-end.
 * 
 * Scripts should be made available to clients which take them from any given build to the current
 * build.  These scripts can be generated on the fly.
 * 
 * In short:
 * 
 *  - Build updater script for Z2 which logs activity and provides current build information.
 *  - Build AJAX client into CMF which loads welcome messages and updater links.
 *  - Build manual process to create scripts which perform manual updates for non-connected hosts.
 */
ftp_connect()
?>