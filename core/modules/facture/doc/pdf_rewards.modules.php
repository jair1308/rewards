<?php
/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2017       Ferran Marcet       <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       htdocs/core/modules/facture/doc/pdf_rewards.modules.php
 *	\ingroup    facture
 *	\brief      File of class to generate customers invoices from rewards model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/doc/pdf_crabe.modules.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
dol_include_once('/rewards/class/rewards.class.php');


/**
 *	Class to manage PDF invoice template Crabe
 */
class pdf_rewards extends pdf_crabe
{


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs;

		$langs->load("rewards@rewards");

		parent::__construct($db);

		$this->name = "rewards";
		$this->description = $langs->trans('PDFRewardsDescription');
	}

    public function _tableau_rewards(&$pdf, $object, $objRewards, $posy, $outputlangs)
	{
		global $conf,$mysoc;

		$outputlangs->load('rewards@rewards');
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		$tab4_top = $posy+2;
		$tab4_hl = 4;
		$pdf->SetFont('','', $default_font_size - 1);
		
		// Tableau total
		$col1x = 120; $col2x = 170;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);
		
		$useborder=0;
		$index = 0;
		
		// Points for this invoice
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY($col1x, $tab4_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab4_hl, $outputlangs->transnoentities("TotalPointsInvoice"), 0, 'L', 1);
		$pdf->SetXY($col2x, $tab4_top + 0);
		if ($object->type==2)
		{
			$points=$objRewards->getInvoicePoints($object->id,1);
		}
		else
		{
			$points=$objRewards->getInvoicePoints($object->id);
		}
		
		$pdf->MultiCell($largcol2, $tab4_hl, price($points), 0, 'R', 1);
		
		// Total Points
		$pdf->SetFillColor(248,248,248);
		$index++;
		$pdf->SetXY($col1x, $tab4_top + $tab4_hl * $index);
		$pdf->SetTextColor(0,0,60);
		$pdf->SetFillColor(224,224,224);
		$pdf->MultiCell($col2x-$col1x, $tab4_hl, $outputlangs->transnoentities("TotalDispoPoints").' '.dol_print_date(dol_now(),"day",false,$outputlangs), $useborder, 'L', 1);
		$pdf->SetXY($col2x, $tab4_top + $tab4_hl * $index);
		$pdf->MultiCell($largcol2, $tab4_hl, price($objRewards->getCustomerPoints($object->socid)), $useborder, 'R', 1);
		
		// Fin
		$pdf->SetFont('','', $default_font_size - 1);
		$pdf->SetTextColor(0,0,0);
		
		$index++;
		return ($tab4_top + ($tab4_hl * $index));
	}


	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
    public function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc;

        $sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('','', $default_font_size - 1);

		// Tableau total
		$col1x = 120; $col2x = 170;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

		$useborder=0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
		$pdf->SetXY($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($object->total_ht + (! empty($object->remise)?$object->remise:0)), 0, $outputlangs), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248,248,248);

		$this->atleastoneratenotnull=0;
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			$tvaisnull=((! empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_ISNULL) && $tvaisnull)
			{
				// Nothing to do
			}
			else
			{
				//Local tax 1 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}

								$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					}
	      		//}
				//Local tax 2 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;



								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);

							}
						}
					}
				//}
				// VAT
				foreach($this->tva as $tvakey => $tvaval)
				{
					if ($tvakey > 0)    // On affiche pas taux 0
					{
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl='';
						if (preg_match('/\*/',$tvakey))
						{
							$tvakey=str_replace('*','',$tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat =$outputlangs->transnoentities("TotalVAT").' ';
						$totalvat.=vatrate($tvakey,1).$tvacompl;
						$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
					}
				}

				//Local tax 1 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;

								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					}
	      		//}
				//Local tax 2 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
						    // retrieve global local tax
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';

								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					//}
				}

				// Revenue stamp
				if (price2num($object->revenuestamp) != 0)
				{
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp"), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp), $useborder, 'R', 1);
				}

				// Total TTC
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(0,0,60);
				$pdf->SetFillColor(224,224,224);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->total_ttc, 0, $outputlangs), $useborder, 'R', 1);
			}
		}

		$pdf->SetTextColor(0,0,0);

		$creditnoteamount=$object->getSumCreditNotesUsed();
		$depositsamount=$object->getSumDepositsUsed();
		//print "x".$creditnoteamount."-".$depositsamount;exit;
		$resteapayer = price2num($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if ($object->paye) $resteapayer=0;

		if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0)
		{
			// Already paid + Deposits
			$index++;
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs), 0, 'R', 0);

			// Credit note
			if ($creditnoteamount)
			{
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs), 0, 'R', 0);
			}

			// Escompte
			if (version_compare(DOL_VERSION, 3.8) >= 0)
				$cond = ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT?true:false);
			else
				$cond = ($object->close_code == 'discount_vat'?true:false);

			if ($cond)
			{
				$index++;
				$pdf->SetFillColor(255,255,255);

				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOffered"), $useborder, 'L', 1);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs), $useborder, 'R', 1);

				$resteapayer=0;
			}

			$index++;
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFillColor(224,224,224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetTextColor(0,0,0);
		}

		$index++;
		$posy = ($tab2_top + ($tab2_hl * $index));

		//Rewards
		$objRewards = new Rewards($this->db);
		if ($objRewards->getCustomerReward($object->socid))
		{
			$posy=$this->_tableau_rewards($pdf, $object, $objRewards, $posy, $outputlangs);
		}
		return $posy;
	}

}

