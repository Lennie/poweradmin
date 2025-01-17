<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2010  Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2022  Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Script that handles requests to add new records to zone templates
 *
 * @package     Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2022  Poweradmin Development Team
 * @license     https://opensource.org/licenses/GPL-3.0 GPL
 */

use Poweradmin\AppFactory;
use Poweradmin\RecordType;
use Poweradmin\Validation;
use Poweradmin\ZoneTemplate;

require_once 'inc/toolkit.inc.php';
require_once 'inc/message.inc.php';

include_once 'inc/header.inc.php';

$app = AppFactory::create();
$dns_ttl = $app->config('dns_ttl');

if (!isset($_GET['id']) || !Validation::is_number($_GET['id'])) {
    error(ERR_INV_INPUT);
    include_once('inc/footer.inc.php');
    exit;
}
$zone_templ_id = htmlspecialchars($_GET['id']);

$ttl = isset($_POST['ttl']) && Validation::is_number($_POST['ttl']) ? $_POST['ttl'] : $dns_ttl;
$prio = isset($_POST['prio']) && Validation::is_number($_POST['prio']) ? $_POST['prio'] : 0;
$name = $_POST['name'] ?? "[ZONE]";
$type = $_POST['type'] ?? "";
$content = $_POST['content'] ?? "";

$templ_details = ZoneTemplate::get_zone_templ_details($zone_templ_id);
$owner = ZoneTemplate::get_zone_templ_is_owner($zone_templ_id, $_SESSION['userid']);

if (isset($_POST["commit"])) {
    if (!(do_hook('verify_permission' , 'zone_master_add' )) || !$owner) {
        error(ERR_PERM_ADD_RECORD);
    } else {
        if (ZoneTemplate::add_zone_templ_record($zone_templ_id, $name, $type, $content, $ttl, $prio)) {
            success(_('The record was successfully added.'));
            $name = $type = $content = $ttl = $prio = "";
        }
    }
}

if (!(do_hook('verify_permission' , 'zone_master_add' )) || !$owner) {
    error(ERR_PERM_ADD_RECORD);
    error(ERR_INV_INPUT);
    include_once("inc/footer.inc.php");
    exit;
}

echo "    <h5 class=\"mb-3\">" . _('Add record to zone template') . " \"" . $templ_details['name'] . "\"</h5>\n";

echo "     <form method=\"post\">\n";
echo "      <input type=\"hidden\" name=\"domain\" value=\"" . $zone_templ_id . "\">\n";
echo "      <table class=\"table table-striped table-hover table-sm\">\n";
echo "       <tr>\n";
echo "        <td>" . _('Name') . "</td>\n";
echo "        <td>&nbsp;</td>\n";
echo "        <td>" . _('Type') . "</td>\n";
echo "        <td>" . _('Content') . "</td>\n";
echo "        <td>" . _('Priority') . "</td>\n";
echo "        <td>" . _('TTL') . "</td>\n";
echo "       </tr>\n";
echo "       <tr>\n";
echo "        <td><input class=\"form-control form-control-sm\" type=\"text\" name=\"name\" value=\"" . $name . "\"></td>\n";
echo "        <td>IN</td>\n";
echo "        <td>\n";
echo "         <select class=\"form-select form-select-sm\" name=\"type\">\n";
$found_selected_type = !(isset($type) && $type);
foreach (RecordType::getTypes() as $record_type) {
    if (isset($type) && $type) {
        if ($type == $record_type) {
            $add = " SELECTED";
            $found_selected_type = true;
        } else {
            $add = "";
        }
    } else {
        // TODO: from where comes $zone_name value and why this check exists here?
        if (isset($zone_name) && preg_match('/i(p6|n-addr).arpa/i', $zone_name) && strtoupper($record_type) == 'PTR') {
            $add = " SELECTED";
        } elseif (strtoupper($record_type) == 'A') {
            $add = " SELECTED";
        } else {
            $add = "";
        }
    }
    echo "          <option" . $add . " value=\"" . $record_type . "\">" . $record_type . "</option>\n";
}
if (!$found_selected_type)
    echo "          <option SELECTED value=\"" . htmlspecialchars($type) . "\"><i>" . htmlspecialchars($type) . "</i></option>\n";
echo "         </select>\n";
echo "        </td>\n";
echo "        <td><input class=\"form-control form-control-sm\" type=\"text\" name=\"content\" value=\"" . $content . "\"></td>\n";
echo "        <td><input class=\"form-control form-control-sm\" type=\"text\" name=\"prio\" value=\"" . $prio . "\"></td>\n";
echo "        <td><input class=\"form-control form-control-sm\" type=\"text\" name=\"ttl\" value=\"" . $ttl . "\"</td>\n";
echo "       </tr>\n";
echo "<tr><td colspan=\"6\">";
echo "      <div class=\"pt-3\"><input class=\"btn btn-primary btn-sm\" type=\"submit\" name=\"commit\" value=\"" . _('Add record') . "\"></div>\n";
echo "</td></tr>";
echo "</table>";
echo "<table>";
echo "     <tr>\n";
echo "      <td colspan=\"6\"><br><b>" . _('Hint:') . "</b></td>\n";
echo "     </tr>\n";
echo "     <tr>\n";
echo "      <td colspan=\"6\">" . _('The following placeholders can be used in template records') . "</td>\n";
echo "     </tr>\n";
    echo "     <tr>\n";
    echo "      <td colspan=\"6\"><br>&nbsp;&nbsp;&nbsp;&nbsp; * [ZONE] - " . _('substituted with current zone name') . "<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp; * [SERIAL] - " . _('substituted with current date and 2 numbers') . " (YYYYMMDD + 00)<br>\n";
    echo "&nbsp;&nbsp;&nbsp;&nbsp; * [NS1] - " . _('substituted with 1st name server') . "<br>\n";
    echo "&nbsp;&nbsp;&nbsp;&nbsp; * [NS2] - " . _('substituted with 2nd name server') . "<br>\n";
    echo "&nbsp;&nbsp;&nbsp;&nbsp; * [NS3] - " . _('substituted with 3rd name server') . "<br>\n";
    echo "&nbsp;&nbsp;&nbsp;&nbsp; * [NS4] - " . _('substituted with 4th name server') . "<br>\n";
    echo "&nbsp;&nbsp;&nbsp;&nbsp; * [HOSTMASTER] - " . _('substituted with hostmaster') . "</td>\n";
    echo "     </tr>\n";
echo "     <tr>\n";
echo "      <td colspan=\"6\"><br><b>" . _('Examples:') . "</b></td>\n";
echo "     </tr>\n";
echo "     <tr>\n";
echo "      <td colspan=\"6\">" . _('To add a subdomain foo in a zonetemplate you would put foo.[ZONE] into the name field.') . "<br>";
echo "      " . _('To add a wildcard record put *.[ZONE] in the name field.') . "<br>";
echo "      " . _('Use just [ZONE] to have the domain itself return a value.') . "<br>";
echo "      " . _('For the SOA record, place [NS1] [HOSTMASTER] [SERIAL] 28800 7200 604800 86400 in the content field.') . "</td>";
echo "     </tr>\n";
echo "     <tr>\n";
echo "      </table>\n";
echo "     </form>\n";

include_once('inc/footer.inc.php');
