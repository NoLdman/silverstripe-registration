<?php

class SocialProfile extends DataObject {

	private static $db = array(
			'Provider' => 'Varchar(50)',
			'Identifier' => 'Varchar(255)',
			'ProfileUrl' => 'Varchar(255)'
	);
	private static $has_one = array(
			'Member' => 'Member'
	);

}
