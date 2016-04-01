<?php

class RegistrationForm extends Form {

	public static $DEFAULT_GROUP = 'Patient';

	/**
	 * Create a new form, with the given fields an action buttons.
	 * 
	 * @param Controller $controller The parent controller, necessary to create the appropriate form action tag.
	 * @param String $name The method on the controller that will return this form object.
	 * @param FieldList $fields All of the fields in the form - a {@link FieldList} of {@link FormField} objects.
	 * @param FieldList $actions All of the action buttons in the form - a {@link FieldLis} of
	 *                           {@link FormAction} objects
	 * @param Validator $validator Override the default validator instance (Default: {@link RequiredFields})
	 */
	public function __construct($controller, $name, $fields = null, $actions = null, $validator = null) {
		if (!$fields)
			$fields = RegistrationFormScaffolder::create(new Member())
							->setRestrictFields(array_keys(Config::inst()->get('Registration', 'RegistrationFields')))
							->setFieldClasses(array_filter(Config::inst()->get('Registration', 'RegistrationFields')))
							->getFieldList();


		if ($successUrl = Link::getBackUrl('SuccessURL')) {
			$fields->push(new HiddenField('SuccessURL', 'SuccessURL', $successUrl));
		}

		if (!$validator)
			$validator = new RequiredFields(Config::inst()->get('Registration', 'RequiredFields'));


		if (!$actions)
			$actions = new FieldList(
							FormAction::create('doRegister', _t('RegistrationForm.REGISTER', "Register"))
			);


		parent::__construct($controller, $name, $fields, $actions, $validator);
	}

	public function doRegister($data, $form) {
		$validationErrors = !$this->validate();

		// remove old notifications
		Notification::unsetMessage('redoActivationNotification');
		Notification::unsetMessage('pwForgottenNotification');

		//Check if user already exists
		if ($existingMember = Member::get()->filter(array('Email' => Convert::raw2sql($data["Email"])))->first()) {
			//Set error message
			if ($existingMember->Active) {
				$signinPage = SigninPage::get()->first();
				$resetPasswordAction = new NotificationAction('MyMemberLoginForm::requestNewPassword');
				$resetPasswordAction->setRedirect($signinPage->Link('passwort-vergessen'));

				$pwForgottenNotification = new Notification(_t('RegistrationForm.PASSWORDFORGOTTENMESSAGE',
												"Have you forgotten your password?", '', ['Link' => $signinPage->Link()]),
								'pwForgottenNotification');
				$pwForgottenNotification->setType(Notification::TYPE_WARNING);
				$pwForgottenNotification->addAction($resetPasswordAction);
				$pwForgottenNotification->setPersistent();
			} else {
				$activateNotificationAction = new NotificationAction('MyMemberLoginForm::resendActivation');
				$activateNotificationAction->addParam('email', $existingMember->Email);

				$redoActivationNotification = new Notification(_t('RegistrationForm.ACTIVATIONMAILNOTRECEIVED',
												"Haven't you received your activation mail?"), 'redoActivationNotification');
				$redoActivationNotification->setType(Notification::TYPE_WARNING);
				$redoActivationNotification->addAction($activateNotificationAction);
				$redoActivationNotification->setPersistent();
			}

			$form->AddErrorMessage('Email',
							_t('RegistrationForm.ERROREMAILDUPLICATE', "Sorry that email address already exists."),
							'bad', false);


			//Set form data from submitted values
			Session::set("FormInfo.{$this->FormName()}.data", $data);
			return Controller::curr()->redirectBack();
		}

		// Otherwise try to create new member
		//
		// Validate fields
		if ($data['Email'] !== $data['EmailConfirmation']) {
			$form->addErrorMessage("EmailConfirmation",
							_t('RegistrationForm.ERROREMAILDIFFERENT', "The provided email addresses don't match."),
							"bad");
			$validationErrors = TRUE;
		}
		if (isset($data['Password']['_Password']) && strlen($data['Password']['_Password']) < 5) {
			$form->addErrorMessage("Password",
							_t('RegistrationForm.ERRORPASSWORDTOOSHORT',
											"The password has to be at least 6 characters long."), "bad");
			$validationErrors = TRUE;
		}

		if ($validationErrors) {
			unset($data['Password']); // make user re-enter his password
			Session::set("FormInfo.{$this->FormName()}.data", $data);
			return Controller::curr()->redirectBack();
		} else {
			// create member only if there were no validation errors
			$newMember = new Member();
			$form->saveInto($newMember);
			$newMember->TimeFormat = 'H:mm'; // overwrite so seconds are hidden for local time representation
			$newMember->changePassword($data['Password']['_Password']);
			$newMember->write();

			//Find or create the 'user' group
			if (!$userGroup = Group::get()->filter('Code', Convert::raw2url(self::$DEFAULT_GROUP))->first()) {
				$userGroup = new Group();
				$userGroup->Code = Convert::raw2url(self::$DEFAULT_GROUP);
				$userGroup->Title = self::$DEFAULT_GROUP;
				$userGroup->Write();
				$userGroup->Members()->add($newMember);
			}
			//Add member to user group
			$userGroup->Members()->add($newMember);

			// Send activation Email
			MemberExtension::sendActivationEmail($newMember);
			Session::save(); // no idea why, but in this case we have to "save" the session to store all notifications

			$successUrl = (isset($_REQUEST['SuccessURL'])) ? $_REQUEST['SuccessURL'] : NULL;
			if ($successUrl) {
				$backUrl = Link::addParameter('register', 'success', $successUrl);
				header("Location: $backUrl");
				die();
				// HACK: can't use controller->redirect() because it throws X-Frame-Options' to 'SAMEORIGIN'
				//       error in iFrames (HK)
				// return Controller::curr()->redirect($backUrl);
			} else {
				return Controller::curr()->redirect(HomePage::get()->first()->Link());
			}
		}
	}

}
