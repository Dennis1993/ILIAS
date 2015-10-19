<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Course booking GUI base class
 *
 *  @ingroup ServicesCourseBilling
 * @ilCtrl_Calls ilCourseBillingGUI: ilCourseBillingAdminGUI
 */
class ilCourseBillingGUI {
	/**
	 * Execute request command
	 * 
	 * @throws new ilException
	 * @return boolean
	 */
	public function executeCommand() {
		global $ilCtrl, $tpl;

		$next_class = $ilCtrl->getNextClass($this);
		if(!$next_class) {
			$next_class = "ilcoursebillingadmingui";
		}
		
		$tpl->getStandardTemplate();
		
		switch($next_class)	{			
			case 'ilcoursebillingadmingui':
				$ref_id = $_GET["ref_id"];
				if(!$ref_id)
				{
					throw new ilException("ilCourseBillingGUI - no ref_id");
				}
				$ilCtrl->saveParameterByClass("ilCourseBillingAdminGUI", "ref_id", $ref_id);			
				
				require_once "Modules/Course/classes/class.ilObjCourse.php";
				$course = new ilObjCourse($ref_id);
				
				$this->setCoursePageTitleAndLocator($course);
												
				require_once "Services/Billing/classes/class.ilCourseBillingAdminGUI.php";
				$gui = new ilCourseBillingAdminGUI($course);													
				$ilCtrl->forwardCommand($gui);
				break;
			
			default:				
				throw new ilException("ilCourseBillingGUI - cannot be called directly");
		}
		
		$tpl->show();
	}
	
	/**
	 * Set page title, description and locator
	 * 
	 * @param ilObjCourse $a_course
	 */
	protected function setCoursePageTitleAndLocator(ilObjCourse $a_course) {
		global $tpl, $ilLocator, $lng;
		
		// see ilObjectGUI::setTitleAndDescription()
				
		$tpl->setTitle($a_course->getPresentationTitle());
		$tpl->setDescription($a_course->getLongDescription());
		$tpl->setTitleIcon(ilUtil::getImagePath("icon_crs_b.png"),
			$lng->txt("obj_crs"));

		include_once './Services/Object/classes/class.ilObjectListGUIFactory.php';
		$lgui = ilObjectListGUIFactory::_getListGUIByType("crs");
		$lgui->initItem($a_course->getRefId(), $a_course->getId());
		$tpl->setAlertProperties($lgui->getAlertProperties());	

		// see ilObjectGUI::setLocator()

		$ilLocator->addRepositoryItems($a_course->getRefId());
		$tpl->setLocator();
	}
}
