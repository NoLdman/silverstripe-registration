<?php

class RegistrationPage extends Page {

	/**
	 * Allow only anonymous users and admins to view this page
	 * 
	 * @param type $member
	 * @return boolean
	 */
	public function canView($member = null) {
		// load member if not provided
		if (!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
			$member = Member::currentUser();
		}
		// admin override
		if ($member && Permission::checkMember($member, "ADMIN")) {
			return TRUE;
		}

		return $member ? FALSE : TRUE;
	}

}

class RegistrationPage_Controller extends Page_Controller {

	static $allowed_actions = array(
			'RegistrationForm'
	);
	
	public function index() {
		$this->setField('Form', new RegistrationForm($this, 'RegistrationForm'));
		return $this->render();
	}

	public function RegistrationForm() {
		return new RegistrationForm($this, 'RegistrationForm');
	}

}
