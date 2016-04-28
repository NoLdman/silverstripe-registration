<?php

class UniqueMemberEmailField extends EmailField {

	public function validate($validator) {
		$valid = parent::validate($validator);

		$member = Member::get()
						->filter('Email', $this->value)
						->where('"ID" <> ' . Member::currentUserID())
						->count();
		if ($valid && $member) {
			$validator->validationError(
							$this->name,
							_t('Member.VALIDATIONMEMBEREXISTS', 'A member already exists with the same %s',
											array('identifier' => _t('Member.EMAIL', "Email"))), 'validation'
			);

			return false;
		}

		return $valid;
	}

}
