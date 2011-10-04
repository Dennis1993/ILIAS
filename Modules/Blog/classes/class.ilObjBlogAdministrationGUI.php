<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once("./classes/class.ilObjectGUI.php");

/**
* Blog Administration Settings.
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id:$
*
* @ilCtrl_Calls ilObjBlogAdministrationGUI: ilPermissionGUI
* @ilCtrl_IsCalledBy ilObjBlogAdministrationGUI: ilAdministrationGUI
*
* @ingroup ModulesForum
*/
class ilObjBlogAdministrationGUI extends ilObjectGUI
{
	/**
	 * Contructor
	 *
	 * @access public
	 */
	public function __construct($a_data, $a_id, $a_call_by_reference = true, $a_prepare_output = true)
	{
		$this->type = "blga";
		parent::ilObjectGUI($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->lng->loadLanguageModule("blog");
	}

	/**
	 * Execute command
	 *
	 * @access public
	 *
	 */
	public function executeCommand()
	{
		global $rbacsystem,$ilErr,$ilAccess;

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$this->prepareOutput();

/*		if(!$ilAccess->checkAccess('read','',$this->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('no_permission'),$ilErr->WARNING);
		}
*/
		switch($next_class)
		{
			case 'ilpermissiongui':
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				if(!$cmd || $cmd == 'view')
				{
					$cmd = "editSettings";
				}

				$this->$cmd();
				break;
		}
		return true;
	}

	/**
	 * Get tabs
	 *
	 * @access public
	 *
	 */
	public function getAdminTabs()
	{
		global $rbacsystem, $ilAccess;

		if ($rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("settings",
				$this->ctrl->getLinkTarget($this, "editSettings"),
				array("editSettings", "view"));
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$this->tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass('ilpermissiongui',"perm"),
				array(),'ilpermissiongui');
		}
	}

	
	/**
	* Edit settings.
	*/
	public function editSettings($a_form = null)
	{
		global $lng;
		
		$this->tabs_gui->setTabActive('settings');	
		
		ilUtil::sendInfo($lng->txt("blog_admin_toggle_info"));
		
		if(!$a_form)
		{
			$a_form = $this->initFormSettings();
		}		
		$this->tpl->setContent($a_form->getHTML());
		return true;
	}

	/**
	* Save settings
	*/
	public function saveSettings()
	{
		global $ilCtrl, $ilSetting;
		
		$this->checkPermission("write");
		
		$form = $this->initFormSettings();
		if($form->checkInput())
		{
			$banner = (bool)$form->getInput("banner");
			
			$blga_set = new ilSetting("blga");
			$blga_set->set("banner", $banner);
			$blga_set->set("banner_width", (int)$form->getInput("width"));
			$blga_set->set("banner_height", (int)$form->getInput("height"));
			
			ilUtil::sendSuccess($this->lng->txt("settings_saved"),true);
			$ilCtrl->redirect($this, "editSettings");
		}
		
		$form->setValuesByPost();
		$this->editSettings($form);
	}

	/**
	* Save settings
	*/
	public function cancel()
	{
		global $ilCtrl;
		
		$ilCtrl->redirect($this, "view");
	}
		
	/**
	 * Init settings property form
	 *
	 * @access protected
	 */
	protected function initFormSettings()
	{
	    global $lng;
		
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt('blog_settings'));
		$form->addCommandButton('saveSettings',$this->lng->txt('save'));
		$form->addCommandButton('cancel',$this->lng->txt('cancel'));

		$banner = new ilCheckboxInputGUI($lng->txt("blog_preview_banner"), "banner");
		$banner->setInfo($lng->txt("blog_preview_banner_info"));
		$form->addItem($banner);				
		
		$width = new ilNumberInputGUI($lng->txt("blog_preview_banner_width"), "width");
		$width->setRequired(true);
		$width->setSize(4);
		$banner->addSubItem($width);
		
		$height = new ilNumberInputGUI($lng->txt("blog_preview_banner_height"), "height");
		$height->setRequired(true);
		$height->setSize(4);
		$banner->addSubItem($height);
		
		$blga_set = new ilSetting("blga");
		$banner->setChecked($blga_set->get("banner"));		
		if($blga_set->get("banner"))
		{
			$width->setValue($blga_set->get("banner_width"));
			$height->setValue($blga_set->get("banner_height"));
		}
		else
		{
			$width->setValue(880);
			$height->setValue(100);
		}

		return $form;
	}
}
?>