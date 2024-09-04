<?php
/* Copyright (C) 2013	Juanjo Menent  <jmenent@2byte.es>
 * Copyright (C) 2014	Ferran Marcet  <fmarcet@2byte.es>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       rewards/lib/rewards.lib.php
 *		\brief      Rewards functions
 * 		\ingroup	Rewards
 *
 */

/**
 *	Show a form to select payment conditions
 *
 *  @param	int		$page        	Page
 *  @param  string	$selected    	Id condition pre-selectionne
 *  @param  string	$htmlname    	Name of select html field
 *	@param	int		$addempty		Ajoute entree vide
 *  @return	void
*/
function form_conditions_rewards($page, $selected=0, $htmlname='rewards')
{
	global $langs, $db;
	$form = new Form($db);
        
	if ($htmlname !== 'none')
	{
		print '<form method="post" action="'.$page.'">';
		print '<input type="hidden" name="action" value="setconditions">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';
		print $form->selectyesno($htmlname,$selected);
		print '</td>';
		print '<td align="left"><input type="submit" class="button" value="'.$langs->trans('Modify').'"></td>';
		print '</tr></table></form>';
	}
	else
	{
		print yn($selected);
	}
}

function rewardsadmin_prepare_head()
{
	global $langs;
	$langs->load('reports@reports');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/rewards/admin/rewards.php',1);
	$head[$h][1] = $langs->trans('RewardsSetup');
	$head[$h][2] = 'configuration';
	$h++;
	//$h++;

	return $head;
}