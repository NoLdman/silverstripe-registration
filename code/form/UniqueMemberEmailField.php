<?php

class UniqueMemberEmailField extends EmailField {

	public function validate($validator) {
		$valid = parent::validate($validator);

		if ($valid) {
			
		}

		return $valid;
	}

}
