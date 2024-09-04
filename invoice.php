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
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
dol_include_once('/rewards/class/rewards.class.php');
dol_include_once('/rewards/lib/rewards.lib.php');

global $langs, $user, $conf, $db;

$langs->load("companies");
$langs->load("bills");
$langs->load("rewards@rewards");

$id=GETPOST('facid','int'); 
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');

// Security check
$socid=0;
if ($user->socid) $socid=$user->socid;
$result=restrictedArea($user,'facture',$id,'');

$object = new Facture($db);
$object->fetch($id);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action === 'confirm_convert' && $confirm === 'yes' && $user->rights->rewards->creer)
{
	$objRewards = new Rewards($db);
	$result = $objRewards->usePoints($object,GETPOST('points'));
	
	if ($result > 0)
	{
		//header("Location: ".$_SERVER["PHP_SELF"]."?facid=".$id);
		if(version_compare(DOL_VERSION, "6.0.0") >= 0) {
			header("Location: " . DOL_URL_ROOT . "/compta/facture/card.php?facid=" . $id);
		}
		else{
			header("Location: ".DOL_URL_ROOT."/compta/facture.php?facid=".$id);
		}
		exit;
	}
	else
	{
		setEventMessage($objRewards->error,"errors");
	}	
}


/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/
$helpurl='EN:Module_Rewards|FR:Module_Rewards_FR|ES:M&oacute;dulo_Rewards';
llxHeader('','',$helpurl);
$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$object = new Facture($db);
	$object->fetch($id,$ref);
	
	$soc = new Societe($db);
	$soc->fetch($object->socid);
	
	$objRewards = new Rewards($db);
	$custrewards = $objRewards->getCustomerReward($object->socid);

	$head = facture_prepare_head($object);
	dol_fiche_head($head, 'rewards', $langs->trans("InvoiceCustomer"), 0, 'bill');
	
	$formconfirm='';
	
	if ($action === 'adddiscount')
	{
		$error=0;
		$points = GETPOST('points');
		$maxpoints = GETPOST('maxpoints');
		
		if(! is_numeric($points))
		{
			setEventMessage($langs->trans("OnlyPoints", $points),"errors");
			$action='usepoints';
			$error++;
		}
		elseif ($points<0)
		{
			setEventMessage($langs->trans("NoNegativePoints"),"errors");
			$action='usepoints';
			$error++;
		}
		
		
		
		if (! $error)
		{
			$money= $points*$conf->global->REWARDS_DISCOUNT;
			
			
			// On verifie si la facture a des paiements
			$sql = 'SELECT pf.amount';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
			$sql.= ' WHERE pf.fk_facture = '.$object->id;
				
			$result = $db->query($sql);
			if ($result)
			{
				$i = 0;
				$num = $db->num_rows($result);
					
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$totalpaye += $objp->amount;
					$i++;
				}
			}
			
			$resteapayer = $object->total_ttc - $totalpaye;
			
			if ($money>$resteapayer)
			{
				$maxuse = $resteapayer/$conf->global->REWARDS_DISCOUNT;
				setEventMessage($langs->trans("MaxTTC",$maxuse),"errors");
				$action='usepoints';
			}
			elseif ($points<=$maxpoints)
			{
				$money= $points*$conf->global->REWARDS_DISCOUNT;				
				$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id.'&points='.$points, $langs->trans('Convert'), $langs->trans('ConfirmConvert',$points,$money).' '.$conf->currency, 'confirm_convert', '', 0, 1);
			}
			else
			{
				setEventMessage($langs->trans("MaxPoints"),"errors");
				$action='usepoints';
			}
		}		
		
	}

	// Print form confirm
	print $formconfirm;
	
	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="25%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	$morehtmlref='';
	$discount=new DiscountAbsolute($db);
	$result=$discount->fetch(0,$object->id);
	if ($result > 0)
	{
		$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
	}
	if ($result < 0)
	{
		dol_print_error('',$discount->error);
	}

	$refDoli9or10 = null;
	if(version_compare(DOL_VERSION, 10.0) >= 0){
		$refDoli9or10 = 'ref';
	} else {
		$refDoli9or10 = 'facnumber';
	}

	print $form->showrefnav($object, 'ref', $linkback, 1, $refDoli9or10, 'ref', $morehtmlref);
	print '</td></tr>';

	// Ref customer
	print '<tr><td width="20%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RefCustomer');
	print '</td>';
	print '</tr></table>';
	print '</td>';
	print '<td colspan="5">';
	print $object->ref_client;
	print '</td></tr>';

	// Company
	print '<tr><td>'.$langs->trans("Company").'</td>';
	print '<td colspan="3">'.$soc->getNomUrl(1,'compta').'</td>';
	print '</tr>';
	
	// Rewards
	print '<tr><td>';
	print $langs->trans('RewardsSubject');
	print '<td>';
	form_conditions_rewards($_SERVER['PHP_SELF'].'?socid='.$socid,$custrewards,'none');
	print "</td>";
	print '</tr>';
	
	if($custrewards)
	{
		$sql = "SELECT sum(points) as points";
		$sql.=" FROM ".MAIN_DB_PREFIX."rewards";
		$sql.=" WHERE fk_soc=".$object->socid;
		$sql.=" AND entity=".$conf->entity;
		
		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			if ($num)
			{
				$objp = $db->fetch_object($result);
				$total = price2num($objp->points,'MT');
			}
		}
		print '<tr><td>';
		print $langs->trans('CurrentBalance');
		print '<td>';
		print $total.' '.$langs->trans("Points");
		print "</td>";
		print '</tr>';
	}
	
	print '</table>';
	dol_fiche_end();
	
	/*
	 * Boutons actions
	*/
	if ($custrewards)
	{
		if($action!=='usepoints')
		{
			
			// On verifie si la facture a des paiements
			$sql = 'SELECT pf.amount';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
			$sql.= ' WHERE pf.fk_facture = '.$object->id;
			
			$result = $db->query($sql);
			if ($result)
			{
				$i = 0;
				$num = $db->num_rows($result);
			
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$totalpaye += $objp->amount;
					$i++;
				}
			}
						
			$resteapayer = $object->total_ttc - $totalpaye;
			
			print '<div class="tabsAction">';
			if ($object->type==0 && $user->rights->facture->paiement)
			{
				if ($object->statut==1)
				{
					if($total>0 && $object->paye==0 && $resteapayer>0)
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=usepoints">'.$langs->trans('UsePoints').'</a>';
					}
					else 
					{
						print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseNoPoints").'">'.$langs->trans('UsePoints').'</span>';
					}
				}
				else if ($object->statut==2)
				{
					print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseNoPoints").'">'.$langs->trans('UsePoints').'</span>';
				}
				else
				{
					print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseInvoiceNoDraft").'">'.$langs->trans('UsePoints').'</span>';
				}
			}
			else 
			{
				print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseInvoiceInvalid").'">'.$langs->trans('UsePoints').'</span>';
			}
			print'</div>';
		}
		else 
		{
			print '<br>';
			print load_fiche_titre($langs->trans(FormUsePoints));
			print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formpoints">';
			
			print '<input type="hidden" name="action" value="adddiscount">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="facid" value="'.$object->id.'">';
			print '<input type="hidden" name="maxpoints" value="'.$total.'">';
			
			print '<table class="border" width="100%">';
			
			//Points to use
            print '<tr><td width="10%">'.$langs->trans('Points').'</td><td><input type="text" size="5" maxlength="5" name="points" value="'.($points?$points:$total).'"> / '.$total.' '.$langs->trans("DispoPoints").'</td>';
            
            print '</tr>';
			
            print '</table>';
            print '<br><div class="center">';
            print '<input type="submit" class="button" value="'.$langs->trans('Convert').'">';
            print '</div>'."\n";
            
			print '</form>'."\n";
		}
	}
}

dol_htmloutput_events();

llxFooter();

$db->close();