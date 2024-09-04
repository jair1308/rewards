<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
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
 * 		\file       modRewards.class.php
 * 		\defgroup   Rewards     Module Labels
 *      \brief      File of construction class of label print
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 		\class      modRewards
 *      \brief      Description and activation class for module Rewards
 */
class modRewards extends DolibarrModules
{
    /**
     *    Constructor. Define names, constants, directories, boxes, permissions
     * @param      DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Id for modul.
        $this->numero = 400008;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'rewards';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        $this->family = 'financial';
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = 'Rewards customers';
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = '12.0.0';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 2;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'barcode';

        $this->editor_name = '<b>2byte.es</b>';
        $this->editor_web = 'www.2byte.es';

        // Defined if the directory /mymodule/includes/triggers/ contains triggers or not
        $this->module_parts = array('models' => 1, 'triggers' => 1, 'css' => array('rewards/css/rewards.css'));

        // Data directories to create when module is enabled.
        $this->dirs = array();
        $r = 0;

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array('rewards.php@rewards');

        // Dependencies
        $this->depends = array(
            'modBanque',
            'modFacture'
        );    // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();                // List of modules id to disable if this one is disabled
        $this->phpmin = array(5, 6);                    // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(7, 0);    // Minimum version of Dolibarr required by module
        $this->langfiles = array('rewards@rewards');

        // Constants
        $this->const = array();

        // Array to add new pages in new tabs
        $this->tabs = array(
            'thirdparty:+rewards:Rewards:rewards@rewards:$user->rights->rewards->lire:/rewards/fiche.php?socid=__ID__',
            'invoice:+rewards:Rewards:rewards@rewards:$user->rights->rewards->lire:/rewards/invoice.php?facid=__ID__'
        );
        // Boxes
        // Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();            // List of boxes
        $r = 0;

        // Permissions
        $this->rights = array();        // Permission array used by this module
        $r = 0;
        $this->rights_class = 'rewards';
        $r++;
        $this->rights[$r][0] = 4000811;
        $this->rights[$r][1] = 'Lire les rewards';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';

        $r++;
        $this->rights[$r][0] = 4000812;
        $this->rights[$r][1] = 'Creer les rewards';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'creer';


        // Main menu entries
        $this->menus = array();            // List of menus to add
        $r = 0;

        //Menu left into financial
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=commercial',
            'type' => 'left',
            'titre' => 'Rewards',
            'mainmenu' => 'commercial',
            'leftmenu' => '1',
            'url' => '/rewards/rewards.php',
            'langs' => 'rewards@rewards',
            'position' => 100,
            'enabled' => '$conf->rewards->enabled',
            'perms' => '$user->rights->rewards->creer',
            'target' => '',
            'user' => 0
        );

    }

    /**
     * Function called when module is enabled.
     * The init function adds tabs, constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options Options when enabling module ('', 'newboxdefonly', 'noboxes')
     *                          'noboxes' = Do not insert boxes
     *                          'newboxdefonly' = For boxes, insert def of boxes only and not boxes activation
     * @return int                1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $sql = array();
        $result = $this->load_tables();

		/* if(version_compare(DOL_VERSION, 10.0) >= 0){
			$replaced = DOL_DOCUMENT_ROOT ."/core/modules/facture/doc/pdf_crabe.modules.php";
			$origin = dol_buildpath('/rewards/core_10/pdf_crabe.modules.php');

			if (dol_copy($origin, $replaced) == -1) {

				$msg = 'dol_copy failed Permission denied to overwrite target file';
				setEventMessages($msg, null, 'warnings');
				return false;

			} elseif (dol_copy($origin, $replaced) == -2) {

				$msg = 'dol_copy failed Permission denied to write into target directory';
				setEventMessages($msg, null, 'warnings');
				return false;

			} elseif (dol_copy($origin, $replaced) == -3) {

				$msg = 'dol_copy failed to copy';
				setEventMessages($msg, null, 'warnings');
				return false;

			}
		} */

        return $this->_init($sql);
    }

    /**
     * Function called when module is disabled.
     * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
     * Data directories are not deleted
     *
     * @param      string $options Options when enabling module ('', 'noboxes')
     * @return     int                    1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql);
    }


    /**
     *        Create tables, keys and data required by module
     *        Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     *        and create data commands must be stored in directory /mymodule/sql/
     *        This function is called by this->init
     *
     * @return        int        <=0 if KO, >0 if OK
     */
    public function load_tables()
    {
        return $this->_load_tables('/rewards/sql/');
    }
}
