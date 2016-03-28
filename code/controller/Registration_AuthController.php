<?php
namespace NoLdman\Registration\Controller;

class Registration_AuthController extends Controller {

	public static function authorizedMember() {
		$encodedMail = filter_input(INPUT_GET, 'm'); // Email
		$encodedPass = filter_input(INPUT_GET, 'p'); // password
		$encodedDate = filter_input(INPUT_GET, 'd'); // Date

		if (empty($encodedMail) || empty($encodedPass) || empty($encodedDate))
			return FALSE;

		$date = urldecode(base64_decode($encodedDate));
		$mail = urldecode(base64_decode($encodedMail));

		$member = Member::get()->filter('Email', $mail)->First();
		if ($member) {
			$reencodedPassFromDb = hash('sha512', trim($member->Password) . $date);
			if ($encodedPass == $reencodedPassFromDb) {
				return $member;
			}
		}

		return NULL;
	}

}
