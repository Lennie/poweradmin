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
 * Script that record deletion from zone template
 *
 * @package     Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2022  Poweradmin Development Team
 * @license     https://opensource.org/licenses/GPL-3.0 GPL
 */

use Poweradmin\AppFactory;
use Poweradmin\Validation;
use Poweradmin\ZoneTemplate;

require_once 'inc/toolkit.inc.php';
require_once 'inc/message.inc.php';

include_once 'inc/header.inc.php';

$app = AppFactory::create();

if (!isset($_GET['id']) || !Validation::is_number($_GET['id'])) {
    error(ERR_INV_INPUT);
    include_once('inc/footer.inc.php');
    exit;
}
$record_id = htmlspecialchars($_GET['id']);

if (!isset($_GET['zone_templ_id']) || !Validation::is_number($_GET['zone_templ_id'])) {
    error(ERR_INV_INPUT);
    include_once('inc/footer.inc.php');
    exit;
}
$zone_templ_id = htmlspecialchars($_GET['zone_templ_id']);

$confirm = "-1";
if (isset($_GET['confirm']) && Validation::is_number($_GET['confirm'])) {
    $confirm = $_GET['confirm'];
}

$owner = ZoneTemplate::get_zone_templ_is_owner($zone_templ_id, $_SESSION['userid']);
if ($confirm == '1' && $owner) {
    if (ZoneTemplate::delete_zone_templ_record($record_id)) {
        success(SUC_RECORD_DEL);
    }
    include_once('inc/footer.inc.php');
    exit;
}

if (!(do_hook('verify_permission' , 'zone_master_add' )) || !$owner) {
    error(ERR_PERM_DEL_RECORD);
    include_once('inc/footer.inc.php');
    exit;
}

$templ_details = ZoneTemplate::get_zone_templ_details($zone_templ_id);
$record_info = ZoneTemplate::get_zone_templ_record_from_id($record_id);

$app->render('delete_zone_templ_record.html', [
    'record_id' => $record_id,
    'zone_templ_id' => $zone_templ_id,
    'templ_details' => $templ_details,
    'record_info' => $record_info,
]);

include_once('inc/footer.inc.php');
