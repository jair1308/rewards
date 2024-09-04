<?php
/*  Copyright (C) 2012-2013 Juanjo Menent	    <jmenent@2byte.es>
 *	Copyright (C) 2013 		Ferran Marcet	    <fmarcet@2byte.es>
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
 */

/**
 *   \file       rewards/core/triggers/interface_modRewards_Rewards.class.php
 *   \ingroup    rewards
 *   \brief      Trigger file for rewards module  
 */


/**
 *    \class      InterfaceRewards
 *    \brief      Class of triggered functions for rewards module      
 */


dol_include_once('/rewards/class/rewards.class.php');

class InterfaceRewards
{
    public $db;
    
    /**
     *   \brief      Constructeur.
     *   \param      db   Access database Handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));					
        $this->family = 'Rewards';
        $this->description = 'Triggers of this module add actions in Rewards site according to setup made in rewards setup.';
        $this->version = 'dolibarr';            
    }
    
    /**
     *   Renvoie nom du lot de triggers
     *   @return     string      Nom du lot de triggers
     */
	public function getName()
    {
        return $this->name;
    }
    
    /**
     *   Renvoie descriptif du lot de triggers
     *   @return     string  Descriptif du lot de triggers
     */
	public function getDesc()
    {
        return $this->description;
    }

    /**
     *   Renvoie version du lot de triggers
     *   @return     string  Version du lot de triggers
     */
	public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version === 'experimental') return $langs->trans('Experimental');
        elseif ($this->version === 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans('Unknown');
    }
    
    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions run_trigger are triggered if file is inside directory includes/triggers
     *
     *      Following properties must be filled:
     *      $object->actiontypecode (translation action code: AC_OTH, ...)
     *      $object->actionmsg (note, long text)
     *      $object->actionmsg2 (label, short text)
     *      $object->sendtoid (id of contact)
     *      $object->socid
     *      Optionnal:
     *      $object->facid
     *      $object->propalrowid
     *      $object->orderrowid
     *
     *      @param      action      Event code (COMPANY_CREATE, PROPAL_VALIDATE, ...)
     *      @param      object      Object action is done on
     *      @param      user        Object user
     *      @param      langs       Object langs
     *      @param      conf        Object conf
     *      @return     int         <0 if KO, 0 if no action are done, >0 if OK
     */
	public function run_trigger($action,$object,$user,$langs,$conf)
    {
    	$errors=0;
		// Actions
		
    	/*
		if (($action == 'BILL_VALIDATE') && $object->type==0 && $conf->global->REWARDS_APPLY==0)
		{	
			$points= abs($object->total_ttc / $conf->global->REWARDS_RATIO);
			$objRewards = new Rewards($this->db);
			
			$facwithpoints= $objRewards->getInvoicePoints($object->id);
			if($facwithpoints<=0)
			{
				$res= $objRewards->create($object, $points, 'increase');
			}
		}
		
		if (($action == 'BILL_VALIDATE') && $object->type==2 && $conf->global->REWARDS_CANCEL==0)
		{
			$points= abs($object->total_ttc / $conf->global->REWARDS_RATIO);
			
			$objRewards = new Rewards($this->db);
			
			$facwithpoints= $objRewards->getInvoicePoints($object->fk_facture_source);
			
			if($facwithpoints>0)
			{
				$res= $objRewards->create($object, $points, 'decrease');
			}
		}
		*/
    	    	
		if ($action == 'PAYMENT_CUSTOMER_CREATE')
		{
			$flag_esta = 0;
			if ($object->paiementid==100) return 0; // if payment id is points we don't apply more points
			foreach($object->amounts as $key => $val)
			{
				if($flag_esta == 0)
					if(!$this->testRewards($key)) 
						return 0;
				$flag_esta = 1;
				if ($val == 0)
					continue;
				else{

                    $fk_source ="";

                    $objInvoice=new Facture($this->db);
                    $objInvoice->fetch($key);
                    $objInvoice->fetch_thirdparty();

                    if($objInvoice->fk_facture_source){

                        $objRewards = new Rewards($this->db);
                        $fk_source = $objRewards->getInvoicePoints($objInvoice->fk_facture_source);
                    }

					if($val>=$conf->global->REWARDS_MINPAY || $fk_source)
					{
						if (! empty($conf->pos->enabled)) // Is pos enabled?
						{
							if(! $conf->global->REWARDS_POS) // Pos with not Rewards?
							{
								$sql = 'SELECT count (fk_facture) as items';
								$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_facture';
								$sql.= ' WHERE fk_facture = '.$key;
								 
								$result = $this->db->query($sql);
								if ($result)
								{
									$objp = $this->db->fetch_object($result);
									$items = $objp->items;
									if ($items)
									{
										$errors++;
									}
									else $errors=$this->setPoints($key,$val);
								}
								else 
								{
									$errors= $this->setPoints($key,$val);
								}
								
							}
							else
							{
								$errors= $this->setPoints($key,$val);
							}
					
						}
						else {
							$errors = $this->setPoints($key, $val);
						}

						$outputlangs = $langs;
						if ($conf->global->MAIN_MULTILANGS)	$newlang = $objInvoice->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}
						$objInvoice->generateDocument($objInvoice->modelpdf, $outputlangs);
					}
				}
			}
    	}
    	else if ($action == 'COMPANY_CREATE'){
    		if($object->client == 1 && $conf->global->REWARDS_ADD_CUSTOMER){
    			$objRewards = new Rewards($this->db);
    			$objRewards->setCustomerReward("yes", $object->id);
    		}
    		
    	}
    	else if ($action == 'PAYMENT_CUSTOMER_DELETE'){
			$flag_esta = 0;
			if ($object->type_code=='PNT') return 0; // if payment id is points we don't apply more points
			$sql = 'SELECT fk_facture, amount FROM '.MAIN_DB_PREFIX.'paiement_facture WHERE fk_paiement = '.$object->id;
			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					if ($flag_esta == 0) {
						if (!$this->testRewards($obj->fk_facture)) {
							return 0;
						}
					}
					$flag_esta = 1;
					if ($obj->amount == 0) {
						continue;
					} else {

						$fk_source = "";

						$objInvoice = new Facture($this->db);
						$objInvoice->fetch($obj->fk_facture);
						$objInvoice->fetch_thirdparty();

						if ($objInvoice->fk_facture_source) {

							$objRewards = new Rewards($this->db);
							$fk_source = $objRewards->getInvoicePoints($objInvoice->fk_facture_source);
						}

						if ($obj->amount >= $conf->global->REWARDS_MINPAY || $fk_source) {

							$errors = $this->setPoints($obj->fk_facture, $obj->amount,1);


							$outputlangs = $langs;
							if ($conf->global->MAIN_MULTILANGS) {
								$newlang = $objInvoice->thirdparty->default_lang;
							}
							if (!empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
							}
							$objInvoice->generateDocument($objInvoice->modelpdf, $outputlangs);
						}
					}
				}
			}
		}
    	return 0;
	}

	/**
	 *
	 * @param $facid
	 * @param $amount
	 * @param int $isdelete
	 * @return int        <0 if KO >0 if OK
	 */
	public function setPoints ($facid,$amount,$isdelete=0)
    {
    	global $conf;
		$res = null;
    	
		$objInvoice=new Facture($this->db);
		$objInvoice->fetch($facid);
		
		if ($objInvoice->type==0)// && $conf->global->REWARDS_APPLY==1)
		{
			$points= abs($amount / $conf->global->REWARDS_RATIO);
			$objRewards = new Rewards($this->db);
			$res= $objRewards->create($objInvoice, $points, ($isdelete==0?'increase':'decrease'));
		}
		
		elseif ($objInvoice->type==2)// && $conf->global->REWARDS_CANCEL==1)
		{
			$points= abs($amount / $conf->global->REWARDS_RATIO);
			$objRewards = new Rewards($this->db);
			
			$facwithpoints= $objRewards->getInvoicePoints($objInvoice->fk_facture_source);
				
			if($facwithpoints>0)
			{
                if($facwithpoints < $points){
                    $points = $facwithpoints;
                }
				$res= $objRewards->create($objInvoice, $points, ($isdelete==0?'decrease':'increase'));
			}
		}	

		return $res;
    }
    
    /**
     *
     * @param 	int 	facid	Invoice id
     * @return int		<0 if KO >0 if OK
     */
	public function testRewards ($facid)
    {
    	global $conf;
    	 
    	$objInvoice=new Facture($this->db);
    	$objInvoice->fetch($facid);
    	
    	$objRewards = new Rewards($this->db);
		if (!$objRewards->getCustomerReward($objInvoice->socid)) {
			return 0;
		} else {
			return 1;
		}
    }

}
