<?php
/* Copyright (C) 2012 Juanjo Menent    <jmenent@2byte.es>
 * Copyright (C) 2013 Ferran Marcet    <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 */

/**
 \file 		 rewards/admin/rewards.php
 \ingroup    rewards
 \brief      Page admin module rewards
 */

$res=@include("../../../main.inc.php");					// For "custom" directory
if (! $res) $res=@include("../../main.inc.php");		// For root directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
dol_include_once("/rewards/lib/rewards.lib.php");

global $user, $langs, $db, $conf;

$langs->load("admin");
$langs->load("rewards@rewards");

if (!$user->admin)
accessforbidden();

/*
 * Actions
 */
if ($_POST["save"])
{
	$db->begin();
	
	$i=0;
	
	$i+=dolibarr_set_const($db,'REWARDS_RATIO',trim($_POST['RewardsRatio']),'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'REWARDS_DISCOUNT',trim($_POST['RewardsDiscount']),'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'REWARDS_MINPAY',trim($_POST['RewardsMinPay']),'chaine',0,'',$conf->entity);
	$i+=dolibarr_set_const($db,'REWARDS_ADD_CUSTOMER',trim($_POST['RewardsAddCustomer']),'chaine',0,'',$conf->entity);
	
	if ($i >= 4)
	{
		$db->commit();
		setEventMessage($langs->trans('RewardsSetupSaved'));
	}
	else
	{
		setEventMessage($langs->trans('Error'),'errors');
		$db->rollback();
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}	
}


/*
 * View
 */
$helpurl='EN:Module_Rewards|FR:Module_Rewards_FR|ES:M&oacute;dulo_Rewards';
llxHeader('','',$helpurl);
$html=new Form($db);

// read params
$rewardsratio = dolibarr_get_const($db,'REWARDS_RATIO',$conf->entity);
$rewardsdiscount = dolibarr_get_const($db,'REWARDS_DISCOUNT',$conf->entity);
$rewardsminpay = dolibarr_get_const($db,'REWARDS_MINPAY',$conf->entity);
$addcustomer = dolibarr_get_const($db, 'REWARDS_ADD_CUSTOMER',$conf->entity);
$rewardspos = dolibarr_get_const($db,'REWARDS_POS',$conf->entity);

//page
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
print_fiche_titre($langs->trans('RewardsSetup'),$linkback,'setup');

$head = rewardsadmin_prepare_head();

dol_fiche_head($head, 'configuration', $langs->trans('Rewards'), 0, 'barcode');

$var=true;
print '<form name="rewardssetup" action="'.$_SERVER['PHP_SELF'].'" method="post">';

print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans('Parameter').'</td>';
print '<td>'.$langs->trans('Value').'</td>';
print '<td>'.$langs->trans('Examples').'</td>';
print '</tr>';

//Ratio
$var=!$var;
print '<tr '.$bc[$var?1:0].'>';
print '<td>'.$langs->trans('SetupRatio').'</td>';
print '<td><input type="text" class="flat" name="RewardsRatio" value="'. ($_POST["RewardsRatio"]?$_POST["RewardsRatio"]:$rewardsratio) . '" size="5"> '.$langs->trans("Currency".$conf->currency).'='.$langs->trans("SetupInfoRatio").'</td>';
print '<td>10</td>';
print '</tr>';

//Discount
$var=!$var;
print '<tr '.$bc[$var?1:0].'>';
print '<td>'.$langs->trans("SetupDiscount").'</td>';
print '<td><input type="text" class="flat" name="RewardsDiscount" value="'. ($_POST["RewardsDiscount"]?$_POST["RewardsDiscount"]:$rewardsdiscount) . '" size="5"> '.$langs->trans("Currency".$conf->currency).' '.$langs->trans("SetupInfoDiscount").'</td>';
print '<td>0.5</td>';
print '</tr>';

//Minimal payment
$var=!$var;
print '<tr '.$bc[$var?1:0].'>';
print '<td>'.$langs->trans("MinimalPayment").'</td>';
print '<td><input type="text" class="flat" name="RewardsMinPay" value="'. ($_POST["RewardsMinPay"]?$_POST["RewardsMinPay"]:$rewardsminpay) . '" size="5"> '.$langs->trans("Currency".$conf->currency).' '.$langs->trans("SetupInfoMinPay").'</td>';
print '<td>50</td>';
print '</tr>';

//Add customers automatically
$var=!$var;
print '<tr '.$bc[$var?1:0].'>';
print '<td>'.$langs->trans("AddCustomerAutomatically").'</td>';
print '<td>';
print $html->selectyesno("RewardsAddCustomer",$addcustomer,1);
print '</td>';
print '<td></td>';
print '</tr>';

//POS Integration
$var=!$var;
print '<tr '.$bc[$var?1:0].'>';
print '<td>'.$langs->trans("POSUsePoints").'</td>';

if (! empty($conf->pos->enabled))
{
	$urlPOS=dol_buildpath("/pos/admin/pos.php",1);
	print '<td>';
	print yn($rewardspos);
	print '</td>';
	print '<td>'.$langs->trans("ConfigPOS",$urlPOS).'</td>';
}
else 
{
	print '<td>';
	print $langs->trans("NoPosInstalled");
	print '</td>';
	print '<td>'.$langs->trans("GetPOS","http://www.dolistore.com").'</td>';

}
print '</tr>';

print '</table>';

print '<br><center>';
print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
print '</center>';
print "</form>\n";

print '<br>';

clearstatcache();	
dol_htmloutput_events();
print '<br>';

llxFooter();
$db->close();