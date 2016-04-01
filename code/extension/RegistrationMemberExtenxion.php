<?php

class RegistrationMemberExtenxion extends DataExtension {

	private static $db = array(
			'Company' => 'Varchar(100)', //Business, Organisation, Group, Institution.
			'Address' => 'Varchar(255)', //Number + type of thoroughfare/street. P.O. box
			'AddressLine2' => 'Varchar(255)', //Premises, Apartment, Building. Suite, Unit, Floor, Level, Side, Wing.
			'PostalCode' => 'Varchar(20)', //code: ZipCode, PostCode (could cross above levels within a country)
			'City' => 'Varchar(100)', //level3: Dependent Locality, City, Suburb, County, District
			'Region' => "Varchar(255)", //level2: Locality, Administrative Area, State, Province, Region, Territory, Island
			'CountryCode' => "Varchar(3)",
			'Birthdate' => "Date",
			'Activated' => 'Boolean',
	);
	private static $has_one = array(
			'ProfileImage' => "Image",
			'CoverImage' => "Image"
	);
	private static $has_many = array(
			'SocialProfiles' => 'SocialProfile',
			'Languages' => "MemberLanguage",
			'Phones' => "MemberPhone",
	);
	private static $defaults = array(
			'Active' => 0
	);

	public function updateCMSFields(FieldList $fields) {
		$fields->insertAfter(new CountryDropdownField('CountryCode'), 'ZIP');
	}

	public function AddressShort() {
		return trim("{$this->owner->Address}, {$this->owner->PostalCode} {$this->Country()}", ', ');
	}

	public function Country() {
		return Zend_Locale::getTranslation($this->owner->CountryCode, "country", i18n::get_locale());
	}

	public function Age() {
		if ($this->owner->Birthdate) {
			$from = new DateTime($this->owner->Birthdate);
			$to = new DateTime('today');
			return $from->diff($to)->y;
		}
	}

	public function IdEncryptUrlSafe() {
		return urlencode(base64_encode($this->owner->ID) . md5(Member::currentUser()->Salt));
	}

	public static function IdDecryptUrlSafe($memberIdEncrypted) {
		$stringWithoutSalt = preg_replace('/' . preg_quote(md5(Member::currentUser()->Salt), '/') . '$/',
						'', urldecode($memberIdEncrypted));
		return base64_decode($stringWithoutSalt);
	}

}
