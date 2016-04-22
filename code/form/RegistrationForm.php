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

		if (!$actions)
			$actions = new FieldList(
							FormAction::create('doRegister', _t('RegistrationForm.REGISTER', "Register"))
			);

		if (!$validator)
			$validator = new RequiredFields(Config::inst()->get('Registration', 'RequiredFields'));

		parent::__construct($controller, $name, $fields, $actions, $validator);
	}

	public function doRegister($data, $form) {
		// remove old notifications
		Notification::unsetMessage('redoActivationNotification');
		Notification::unsetMessage('pwForgottenNotification');

		$member = new Member();
		$form->saveInto($member);
		$member->changePassword($data['Password']['_Password']);
		$member->write();
		foreach (Config::inst()->get('Registration', 'UserGroups') as $group) {
			$member->addToGroupByCode(Convert::raw2url($group), $group);
		}

		// Send activation Email
		MemberExtension::sendActivationEmail($member);
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
