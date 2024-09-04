<?php
/* Copyright (C) 2013      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Ferran Marcet        <fmarcet@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/societe/agenda.php
 *  \ingroup    societe
 *  \brief      Page of third party events
 */

$res=@include("../main.inc.php");                                // For root directory
if (! $res) $res=@include("../../main.inc.php");                // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
dol_include_once('/rewards/class/rewards.class.php');
dol_include_once('/rewards/lib/rewards.lib.php');

global $langs, $conf, $db, $user;

$langs->load('rewards@rewards');
$langs->load('bills');

// Security check
$socid = GETPOST('socid','int');
$action=GETPOST('action','string');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
if (! $sortfield) $sortfield='r.date';
if (! $sortorder) $sortorder='ASC';
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$limit = $conf->liste_limit;

$date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);	// Date for local PHP server
$date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
if(empty($date_start))$date_start = GETPOST("date_start","int");
if(empty($date_end))$date_end = GETPOST("date_end","int");
$sref = GETPOST("sref","alpha");

if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid, '&societe');
$object = new Societe($db);
$result = $object->fetch($socid);
if(($object->fournisseur==1 || $object->fournisseur==0) && $object->client!=1 && $object->client!=3){
	accessforbidden();
}

$rewards = new Rewards($db);

/*
 *	Actions
 */
// conditions rewards
if ($action === 'setconditions' && $user->rights->rewards->creer)
{

	$result=$rewards->setCustomerReward(GETPOST('rewards','string'),$socid);
	if ($result < 0) dol_print_error($db,$rewards->error);
}

if ($action == 'add_points')
{
	$points=GETPOST('points','int');
	$facture = new Facture($db);
	$facture->socid = $socid;
	$facture->id = '';
	$rewards->create($facture, $points);

}

if ($action == 'remove_points')
{
	$points=GETPOST('pointsremove','int');
	$facture = new Facture($db);
	$facture->socid = $socid;
	$facture->id = '';
	$rewards->create($facture, $points,'decrease');

}


/*
 *	View
 */

$form = new Form($db);

/*
 * Rewards card
 */
if ($socid)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$custrewards = $rewards->getCustomerReward($socid);
	$helpurl='EN:Module_Rewards|FR:Module_Rewards_FR|ES:M&oacute;dulo_Rewards';
	llxHeader("",$langs->trans("Rewards"),$helpurl);

	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'rewards', $langs->trans("ThirdParty"),0,'company');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("ThirdPartyName").'</td><td colspan="3">';
	print $form->showrefnav($object,'socid','',($user->socid?0:1),'rowid','nom');
	print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
    }

	if ($object->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
		print '</td></tr>';
	}

	if ($object->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
		print '</td></tr>';
	}

	if (! empty($conf->barcode->enabled))
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$object->barcode.'</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">";
	dol_print_address($object->address, 'gmap', 'thirdparty', $object->id);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$object->cp."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$object->ville."</td></tr>";

	// Country
	if ($object->country) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($object->country_code);
		print ($img?$img.' ':'');
		print $object->country;
		print '</td></tr>';
	}

	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($object->phone,$object->country_code,0,$object->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($object->fax,$object->country_code,0,$object->id,'AC_FAX').'</td></tr>';

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td>';
	print dol_print_email($object->email,0,$object->id,'AC_EMAIL');
	print '</td>';

	// Web
	print '<td>'.$langs->trans('Web').'</td><td>';
	print dol_print_url($object->url);
	print '</td></tr>';

	// Rewards
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('RewardsSubject');
	print '<td>';
	if (($action != 'editconditions') && $user->rights->rewards->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;socid='.$socid.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editconditions' && $user->rights->rewards->creer)
	{
		form_conditions_rewards($_SERVER['PHP_SELF'].'?socid='.$socid,$custrewards,'rewards');
	}
	else
	{
		form_conditions_rewards($_SERVER['PHP_SELF'].'?socid='.$socid,$custrewards,'none');
		if ($custrewards)
		{
			print " (".$langs->trans("DispoPoints").": ".$rewards->getCustomerPoints($socid).")";
		}

	}
	print "</td>";
	print '</tr>';

	print '</table>';

	print '</div>';

	$formquestionaddpoints=array(
			'text' => $langs->trans("ConfirmPoints"),
			array('type' => 'text', 'name' => 'points','label' => $langs->trans("HowManyPointsAdd"), 'value' => '', 'size'=>5)
	);
	$formquestionremovepoints=array(
			'text' => $langs->trans("ConfirmPoints"),
			array('type' => 'text', 'name' => 'pointsremove','label' => $langs->trans("HowManyPointsRemove"), 'value' => '', 'size'=>5)
	);

	if ($custrewards>0)
	{
	print '<div class="tabsAction">';

	if ($user->rights->rewards->creer){
		print '<span id="action-addpoints" class="butAction">'.$langs->trans('AddPoints').'</span>'."\n";
		print $form->formconfirm($_SERVER["PHP_SELF"].'?socid='.$socid,$langs->trans('AddPoints'),$langs->trans('AddPointsThird'),'add_points',$formquestionaddpoints,'yes','action-addpoints',170,400);

		print '<span id="action-removepoints" class="butAction">'.$langs->trans('RemovePoints').'</span>'."\n";
		print $form->formconfirm($_SERVER["PHP_SELF"].'?socid='.$socid,$langs->trans('RemovePoints'),$langs->trans('RemovePointsThird'),'remove_points',$formquestionremovepoints,'yes','action-removepoints',170,400);
	}

	print '</div>';

		$refDoli9or10 = null;
		if(version_compare(DOL_VERSION, 10.0) >= 0){
			$refDoli9or10 = 'ref';
		} else {
			$refDoli9or10 = 'facnumber';
		}

		$sql = "SELECT f.datef, f.".$refDoli9or10.", r.fk_invoice, f.type, r.points, s.nom, r.date, r.fk_user_author";
		$sql.=" FROM ".MAIN_DB_PREFIX."rewards r";
		$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."facture f ON f.rowid=r.fk_invoice";
		$sql.=" INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid=r.fk_soc";
		$sql.=" WHERE r.fk_soc=".$socid;
		$sql.=" AND r.entity=".$conf->entity;


		if ($sref)
		{
			$sql.= " AND f.".$refDoli9or10." LIKE '%".$db->escape($sref)."%'";
		}

		//Date filter
		if ($date_start && $date_end) $sql.= " AND r.date >= '".$db->idate($date_start)."' AND r.date <= '".$db->idate($date_end)."'";

		$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;

		$nbtotalofrecords = 0;
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $db->query($sql);
			$nbtotalofrecords = $db->num_rows($result);
		}

		//$sql.= $db->plimit($limit +1,$offset);
		$result = $db->query($sql);

		$param='&socid='.$socid;
		if ($date_start)      $param.='&date_start='.$date_start;
		if ($date_end)        $param.='&date_end='.$date_end;
		if ($sref)            $param.='&sref='.$sref;
		$num = $db->num_rows($result);
		print '<br>';
		//print_barre_liste($title, $page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

		// Lines
		print '<br>';
		print '<table class="notopnoleftnoright" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Date'),'fiche.php','f.datef','',$param,'width="240px"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Bill'),'fiche.php','f.'.$refDoli9or10,'',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Points'),'fiche.php','r.points','',$param,'width="150px"',$sortfield,$sortorder);


		//print '<td align="center">'.$langs->trans("Points").'</td>';
		print '<td align="center" width="150px">'.$langs->trans("Balance").'</td>';
		print '</tr>';

		$result = $db->query($sql);
		if ($result)
		{
			$facturestatic = new Facture($db);
			$var=true;
			$period=$form->select_date($date_start,'date_start',0,0,1,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,1,'',1,0,1);
			$num = $db->num_rows($result);
			$i = 0; $total = 0;


			// Lignes des champs de filtre
			print '<form method="get" action="fiche.php">';
			print '<input type="hidden" name="socid" value="'.$socid.'">';
			print '<tr class="liste_titre">';

			//DATE ORDER
			print '<td class="liste_titre">';
			print $period;
			print '</td>';

			//REF
			print '<td>';
			print '<input class="flat" size="10" style="width: 180px !important" type="text" name="sref" value="'.$sref.'">';
			print '</td>';

			print '<td>';
			print '</td>';


			//SEARCH BUTTON
			print '</td><td align="right" class="liste_titre">';
			print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';

			print '</td></tr>';
			print '</form>';

			while ($i < $num)
			{
				$var=!$var;
				$objp = $db->fetch_object($result);

				$objDoli9or10 = null;
				if(version_compare(DOL_VERSION, 10.0) >= 0){
					$objDoli9or10 = $objp->ref;
				} else {
					$objDoli9or10 = $objp->facnumber;
				}

				$total = price2num($total + $objp->points,'MT');
				$i++;
				if($objp->fk_invoice) {
					if($objDoli9or10) {
						print '<tr ' . $bc[$var ? 1 : 0] . '>';
						//Date
						print '<td nowrap="nowrap">' . dol_print_date($db->jdate($objp->date), "day") . "</td>\n";

						print '<td>';
						$facturestatic->id = $objp->fk_invoice;
						$facturestatic->ref = $objDoli9or10;
						$facturestatic->type = $objp->type;
						if (!empty($facturestatic->id))
							print $facturestatic->getNomUrl(1);
						else {
							$user->fetch($objp->fk_user_author);
							print $langs->trans("ManualMovement", $user->lastname);
						}
						print '</td>';

						//Points
						print '<td align="right" nowrap="nowrap">' . price($objp->points) . '</td>';

						//Balance
						print '<td align="right" nowrap="nowrap">' . price($total) . '</td>';

						print '</tr>';
					}
				}
				else{

					print '<tr ' . $bc[$var ? 1 : 0] . '>';
					//Date
					print '<td nowrap="nowrap">' . dol_print_date($db->jdate($objp->date), "day") . "</td>\n";

					print '<td>';
					$facturestatic->id = $objp->fk_invoice;
					$facturestatic->ref = $objDoli9or10;
					$facturestatic->type = $objp->type;
					if (!empty($facturestatic->id))
						print $facturestatic->getNomUrl(1);
					else {
						$user->fetch($objp->fk_user_author);
						print $langs->trans("ManualMovement", $user->lastname);
					}
					print '</td>';

					//Points
					print '<td align="right" nowrap="nowrap">' . price($objp->points) . '</td>';

					//Balance
					print '<td align="right" nowrap="nowrap">' . price($total) . '</td>';

					print '</tr>';
				}
			}
		}

		print '<tr class="liste_total"><td align="left" colspan="3">';
		print $langs->trans("CurrentBalance");
		print '</td>';
		print '<td align="right" nowrap>'.price($total).'</td>';
		print '</tr>';
		print "</table>";

	    print '<br>';
	}

}


llxFooter();

$db->close();
