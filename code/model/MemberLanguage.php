<?php
namespace NoLdman\Registration\Model;

class MemberLanguage extends DataObject {

	private static $db = array(
			'ISO' => "Varchar(3)" // ISO 639-1
	);

	private static $has_one = array(
		'Member' => "Member"	
	);
}
