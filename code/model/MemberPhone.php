<?php

class MemberPhone extends DataObject {

	private static $db = array(
			'Number' => "Varchar(255)",
			'Type' => "Varchar"
	);

	private static $has_one = array(
		'Member' => "Member"	
	);
}
