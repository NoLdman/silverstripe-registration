<?php

class Registration_AuthController extends Controller {

	/**
	 *
	 * @var String Email for sending activation links, if used via classic method call. <p>
	 * Can't use classic parameter due to Controller extension.</p>
	 */
	private static $allowed_actions = array(
			'index',
			'activateuser',
	);

	public function index() {
		return $this->httpError(404); // no-op
	}

	public function activateuser() {
		$encodedMail = filter_input(INPUT_GET, 'm'); // Email
		$encodedPass = filter_input(INPUT_GET, 'p'); // password
		$encodedDate = filter_input(INPUT_GET, 'd'); // Date
		$backUrl = filter_input(INPUT_GET, 'BackURL');

		if (empty($encodedMail) || empty($encodedPass) || empty($encodedDate)) {
			return $this->httpError(404); // not all params present
		}
		$date = urldecode(base64_decode($encodedDate));
		$mail = urldecode(base64_decode($encodedMail));

		if ($member = Member::get()->filter('Email', $mail)->First()) {
			$member = Member::get()->byID($member->ID);

			$reencodedPassFromDb = hash('sha512', trim($member->Password) . $date);
			if ($encodedPass == $reencodedPassFromDb) {
				$member->Active = TRUE;
				$member->write();
				$member->LogIn(TRUE);

				// clear old notification
				Notification::unsetMessage('redoActivationNotification');
				Notification::unsetMessage('pwForgottenNotification');

				new Notification(_t('AuthController.ACTIVATIONSUCCESSMESSAGE', "Activation successful!"));

				// redirect Member
				if ($backUrl) {
					return Controller::curr()->redirect($backUrl);
				} elseif (class_exists('ProfilePage') && ($profilePage = ProfilePage::get()->first())) {
					return Controller::curr()->redirect($profilePage->Link());
				} else {
					return Controller::cur()->redirect(Director::baseURL());
				}
			}
		}

		return $this->httpError(404); // member not found
	}

}
