<?php

/**
 *
 * @package framework
 * @subpackage forms
 *
 * @uses DBField::scaffoldFormField()
 * @uses DataObject::fieldLabels()
 */
class RegistrationFormScaffolder extends Object {

	/**
	 * @var DataObject $obj The object defining the fields to be scaffolded
	 * through its metadata like $db, $searchable_fields, etc.
	 */
	protected $obj;

	/**
	 * @var boolean $tabbed Return fields in a tabset, with all main fields in the path "Root.Main",
	 * relation fields in "Root.<relationname>" (if {@link $includeRelations} is enabled).
	 */
	protected $tabbed = false;

	/**
	 * @var array $restrictFields Numeric array of a field name whitelist.
	 * If left blank, all fields from {@link DataObject->db()} will be included.
	 *
	 * @todo Implement restrictions for has_many and many_many relations.
	 */
	protected $restrictFields;

	/**
	 * @var array $fieldClasses Optional mapping of fieldnames to subclasses of {@link FormField}.
	 * By default the scaffolder will determine the field instance by {@link DBField::scaffoldFormField()}.
	 *
	 * @todo Implement fieldClasses for has_many and many_many relations
	 */
	protected $fieldClasses;

	/**
	 * @var boolean $includeRelations Include has_one, has_many and many_many relations
	 */
	protected $includeRelations = false;

	/**
	 * @param DataObject $obj
	 * @param array $params
	 */
	public function __construct($obj) {
		$this->obj = $obj;
		$this->fieldList = new FieldList();

		parent::__construct();
	}

	/**
	 * Gets the form fields as defined through the metadata
	 * on {@link $obj} and the custom parameters passed to FormScaffolder.
	 * Depending on those parameters, the fields can be used in ajax-context,
	 * contain {@link TabSet}s etc.
	 *
	 * @return FieldList
	 */
	public function getFieldList() {
		$fields = new FieldList();

		// tabbed or untabbed
		if ($this->tabbed) {
			$fields->push(new TabSet("Root", $mainTab = new Tab("Main")));
			$mainTab->setTitle(_t('SiteTree.TABMAIN', "Main"));
		}

		// add database fields
		$availableDbFieldnames = array_keys($this->obj->db());
		$usedDbFieldnames = $this->restrictFields ? array_intersect($this->restrictFields,
										$availableDbFieldnames) : $availableDbFieldnames;
		foreach ($usedDbFieldnames as $fieldName) {
			if ($this->fieldClasses && isset($this->fieldClasses[$fieldName])) {
				$fieldClass = $this->fieldClasses[$fieldName];
				$fieldObject = new $fieldClass($fieldName);
			} else {
				$fieldObject = $this->obj->dbObject($fieldName)->scaffoldFormField(null);
			}
			$fieldObject->setTitle($this->obj->fieldLabel($fieldName));
			if ($this->tabbed) {
				$fields->addFieldToTab("Root.Main", $fieldObject);
			} else {
				$fields->push($fieldObject);
			}
		}

		// add has_one relation fields
		if ($this->obj->hasOne()) {
			foreach ($this->obj->hasOne() as $relationship => $component) {
				if ($this->restrictFields && !in_array($relationship, $this->restrictFields))
					continue;
				$fieldName = $component === 'DataObject' ? $relationship // Polymorphic has_one field is composite, so don't refer to ID subfield
								: "{$relationship}ID";
				if ($this->fieldClasses && isset($this->fieldClasses[$fieldName])) {
					$fieldClass = $this->fieldClasses[$fieldName];
					$hasOneField = new $fieldClass($fieldName);
				} else {
					$hasOneField = $this->obj->dbObject($fieldName)->scaffoldFormField(null);
				}
				if (empty($hasOneField))
					continue; // Allow fields to opt out of scaffolding
				$hasOneField->setTitle($this->obj->fieldLabel($relationship));
				if ($this->tabbed) {
					$fields->addFieldToTab("Root.Main", $hasOneField);
				} else {
					$fields->push($hasOneField);
				}
			}
		}

		// only add relational fields if an ID is present
		if ($this->obj->ID) {
			// add has_many relation fields
			if ($this->obj->hasMany() && ($this->includeRelations === true || isset($this->includeRelations['has_many']))) {

				foreach ($this->obj->hasMany() as $relationship => $component) {
					if ($this->tabbed) {
						$relationTab = $fields->findOrMakeTab(
										"Root.$relationship", $this->obj->fieldLabel($relationship)
						);
					}
					$fieldClass = (isset($this->fieldClasses[$relationship])) ? $this->fieldClasses[$relationship] : 'GridField';
					$grid = Object::create($fieldClass, $relationship, $this->obj->fieldLabel($relationship),
													$this->obj->$relationship(), GridFieldConfig_RelationEditor::create()
					);
					if ($this->tabbed) {
						$fields->addFieldToTab("Root.$relationship", $grid);
					} else {
						$fields->push($grid);
					}
				}
			}

			if ($this->obj->manyMany() && ($this->includeRelations === true || isset($this->includeRelations['many_many']))) {

				foreach ($this->obj->manyMany() as $relationship => $component) {
					if ($this->tabbed) {
						$relationTab = $fields->findOrMakeTab(
										"Root.$relationship", $this->obj->fieldLabel($relationship)
						);
					}

					$fieldClass = (isset($this->fieldClasses[$relationship])) ? $this->fieldClasses[$relationship] : 'GridField';

					$grid = Object::create($fieldClass, $relationship, $this->obj->fieldLabel($relationship),
													$this->obj->$relationship(), GridFieldConfig_RelationEditor::create()
					);
					if ($this->tabbed) {
						$fields->addFieldToTab("Root.$relationship", $grid);
					} else {
						$fields->push($grid);
					}
				}
			}
		}

		return $fields;
	}

	public function getObj() {
		return $this->obj;
	}

	public function getTabbed() {
		return $this->tabbed;
	}

	public function getRestrictFields() {
		return $this->restrictFields;
	}

	public function getFieldClasses() {
		return $this->fieldClasses;
	}

	public function getIncludeRelations() {
		return $this->includeRelations;
	}

	public function setObj(DataObject $obj) {
		$this->obj = $obj;
	}

	public function setTabbed($tabbed) {
		$this->tabbed = $tabbed;
		return $this;
	}

	public function setRestrictFields($restrictFields) {
		$this->restrictFields = $restrictFields;
		return $this;
	}

	public function setFieldClasses($fieldClasses) {
		$this->fieldClasses = $fieldClasses;
		return $this;
	}

	public function setIncludeRelations($includeRelations) {
		$this->includeRelations = $includeRelations;
		return $this;
	}

}
