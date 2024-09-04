<?php
/* Copyright (C) 2013 Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       rewards/rewards.php
 *	\ingroup    rewards
 *	\brief      List of customers to make rewards
 */

$res=@include("../main.inc.php");                                // For root directory
if (! $res) $res=@include("../../main.inc.php");                // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once('/rewards/class/rewards.class.php');

global $langs, $user, $conf, $db;

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("commercial");

// Security check
$socid = GETPOST('socid','int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user,'societe',$socid,'');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page=GETPOST('page','int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$search_nom=GETPOST("search_nom");
$search_zipcode=GETPOST("search_zipcode");
$search_town=GETPOST("search_town");
$search_code=GETPOST("search_code");
$search_compta=GETPOST("search_compta");

// Load sale and categ filters
$search_sale  = GETPOST("search_sale");
$search_categ = GETPOST("search_categ",'int');
$catid        = GETPOST("catid",'int');

$selected=GETPOST('clients_to_rewards');
//$allThirds=GETPOST('allThirds','array');
$allThirds = unserialize(stripslashes($_GET['allThirds']));

$action=GETPOST('action','alpha');


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('customerlist'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks

if (($action === 'create' || $action === 'add') && empty($mesgs))
{
	$objRewards = new Rewards($db);

	if (is_array($allThirds)) {
		foreach ($allThirds as $sel) {
			$objRewards->setCustomerReward('no', $sel);

		}
	}
	if (is_array($selected) ) {
		foreach ($selected as $sel) {
			$objRewards->setCustomerReward('yes', $sel);

		}
	}
	
    $oldsoc=explode('^',GETPOST('oldsoc'));

    if (is_array($oldsoc))
    {
        foreach ($oldsoc as $old)
        {
            $exist=false;
            foreach ($selected as $sel)
            {
                if($sel==$old)
                {
                    $exist=true;
                    break;
                }
            }
            if(! $exist)
            {
                $objRewards->setCustomerReward('no', $old);
            }
        }
    }
}

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $search_categ='';
    $catid='';
    $search_sale='';
    $socname="";
    $search_nom="";
    $search_zipcode="";
    $search_town="";
    $search_code="";
    $search_compta="";
    $search_idprof1='';
    $search_idprof2='';
    $search_idprof3='';
    $search_idprof4='';
}



/*
 * view
 */

$formother=new FormOther($db);
$thirdpartystatic=new Societe($db);

$helpurl='EN:Module_Rewards|FR:Module_Rewards_FR|ES:M&oacute;dulo_Rewards';
llxHeader('',$langs->trans("Rewards"),$helpurl);

?>
<script type="text/javascript">
	jQuery(document).ready(function() {
	jQuery("#checkall").click(function() {
		$('.checkforreward').attr('checked', true);
	});
	jQuery("#checknone").click(function() {
		$('.checkforreward').attr('checked', false);
	});
});
</script>
<?php

$sql = "SELECT s.rowid, s.nom as name, s.client, s.zip as zip, s.town, st.libelle as stcomm, s.prefix_comm, s.code_client, s.code_compta, s.status as status,";
$sql.= " s.datec, s.canvas";
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= ", (select fk_soc from ".MAIN_DB_PREFIX."rewards_soc where fk_soc=s.rowid) as reward";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! empty($search_categ) || ! empty($catid)){

    // We need this table joined to the select in order to filter by categ
    if (version_compare(DOL_VERSION, 3.8) >= 0) {

        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc";
    } else {
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_societe";
    }

}
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || !$user->rights->societe->client->voir) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (1, 3)";
$sql.= ' AND s.entity IN ('.getEntity('societe', 1).')';
if (!$user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
if ($catid > 0)          $sql.= " AND cs.fk_categorie = ".$catid;
if ($catid == -2)        $sql.= " AND cs.fk_categorie IS NULL";
if ($search_categ > 0)   $sql.= " AND cs.fk_categorie = ".$search_categ;
if ($search_categ == -2) $sql.= " AND cs.fk_categorie IS NULL";
if ($search_nom)   $sql.= " AND s.nom LIKE '%".$db->escape($search_nom)."%'";
if ($search_zipcode) $sql.= " AND s.zip LIKE '".$db->escape($search_zipcode)."%'";
if ($search_town) $sql.= " AND s.town LIKE '%".$db->escape($search_town)."%'";
if ($search_code)  $sql.= " AND s.code_client LIKE '%".$db->escape($search_code)."%'";
if ($search_compta) $sql.= " AND s.code_compta LIKE '%".$db->escape($search_compta)."%'";
// Insert sale filter
if ($search_sale)
{
	$sql .= " AND sc.fk_user = ".$search_sale;
}

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$param = "&amp;search_nom=".$search_nom."&amp;search_code=".$search_code."&amp;search_zipcode=".$search_zipcode."&amp;search_town=".$search_town;
 	if ($search_categ != '') $param.='&amp;search_categ='.$search_categ;
 	if ($search_sale != '')	$param.='&amp;search_sale='.$search_sale;

	print_barre_liste($langs->trans("CustomerRewards"), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	$i = 0;

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	// Filter on categories
 	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(2,$search_categ,'search_categ',1);
	 	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
	 	$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
 	}
 	if ($moreforfilter)
	{
		print '<div class="liste_titre">';
	    print $moreforfilter;
	    print '</div>';
	}

	print '<table class="liste" width="100%">'."\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AccountancyCode"),$_SERVER["PHP_SELF"],"s.code_compta","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"datec","",$param,'align="right"',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("RewardsSubject"),$_SERVER["PHP_SELF"],"s.status","",$param,'align="right"',$sortfield,$sortorder);
    print '	<td align="center">'.$langs->trans("RewardsSubject").'</td>';
    
    $parameters=array();
    $formconfirm=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook

    print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_nom" value="'.$search_nom.'" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_zipcode" value="'.$search_zipcode.'" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_town" value="'.$search_town.'" size="10">';
    print '</td>';

    print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_code" value="'.$search_code.'" size="10">';
    print '</td>';

    print '<td align="left" class="liste_titre">';
    print '<input type="text" class="flat" name="search_compta" value="'.$search_compta.'" size="10">';
    print '</td>';

    print '</td><td>&nbsp;</td>';

    //ALL/NONE
	print '<td class="liste_titre" align="right">';
	if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a>';
	print '&nbsp; ';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	
	print '</td>';

    $parameters=array();
    $oldsoc=array();
	$allThirds=array();

    $formconfirm=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook

    print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		$var=!$var;

		print '<tr '.$bc[$var?1:0].'>';
		print '<td>';
		$thirdpartystatic->id=$obj->rowid;
        $thirdpartystatic->name=$obj->name;
        $thirdpartystatic->client=$obj->client;
        $thirdpartystatic->canvas=$obj->canvas;
        $thirdpartystatic->status=$obj->status;
        print $thirdpartystatic->getNomUrl(1);
		print '</td>';
		print '<td>'.$obj->zip.'</td>';
        print '<td>'.$obj->town.'</td>';
        print '<td>'.$obj->code_client.'</td>';
        print '<td>'.$obj->code_compta.'</td>';
        print '<td align="right">'.dol_print_date($db->jdate($obj->datec),'day').'</td>';
        // Checkbox
		print '<td align="center">';
		if (isset ($obj->reward) && $obj->reward>0)
		{
			$string ='<input class="flat checkforreward" type="checkbox" checked="yes" name="clients_to_rewards[]" value="'.$obj->rowid.'">';
			$oldsoc[]=$obj->rowid;
		}
		else
		{
			$string ='<input class="flat checkforreward" type="checkbox" name="clients_to_rewards[]" value="'.$obj->rowid.'">';
		}

		$allThirds[] = $obj->rowid;
		print $string;
		print '</td>';

        $parameters=array('obj' => $obj);
        $formconfirm=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook

        print "</tr>\n";
		$i++;
	}
	//print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],'',$sortfield,$sortorder,'',$num);
	print "</table>\n";
	
	/*
	 * Boutons actions
	*/
	
	$oldsoc=implode('^',$oldsoc);
	
	
	print '<div align="right">';
	print '<input type="hidden" name="action" value="create"><br>';
    print '<input type="hidden" name="token" value="'.newToken().'"><br>';
	print '<input type="hidden" name="oldsoc" value='.$oldsoc.'><br>';
	print '<input type="hidden" name="page" value='.$page.'><br>';
	print '<input type="hidden" name="allThirds" value='.serialize($allThirds).'><br>';
	print '<input type="submit" class="butAction" value="'.$langs->trans("Apply").'">';
	print '</div>';
	print '</form>';
	$db->free($result);

	$parameters=array('sql' => $sql);
	$formconfirm=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
?>