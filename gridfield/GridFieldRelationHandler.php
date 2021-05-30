<?php

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

abstract class GridFieldRelationHandler implements GridField_ColumnProvider, GridField_HTMLProvider, GridField_ActionProvider {
	protected $targetFragment;
	protected $useToggle;

	protected $columnTitle = 'Linked';
	protected $buttonTitles = array(
		'SAVE_RELATION' => 'Save Linked',
		'CANCELSAVE_RELATION' => 'Cancel',
		'TOGGLE_RELATION' => 'Choose Existing...',
	);

	public function __construct($useToggle = true, $targetFragment = 'before') {
		$this->targetFragment = $targetFragment;
		$this->useToggle = $useToggle;
	}

	public function setUseToggle($useToggle) {
		$this->useToggle = (bool)$useToggle;
		return $this;
	}

	public function setColumnTitle($columnTitle) {
		$this->columnTitle = $columnTitle;
		return $this;
	}

	public function getColumnTitle() {
		return $this->columnTitle;
	}

	public function setButtonTitle($name, $title) {
		$this->buttonTitles[$name] = $title;
		return $this;
	}

	public function getButtonTitle($name) {
		if(isset($this->buttonTitles[$name])) {
			$value = $this->buttonTitles[$name];
			$key = sprintf('GridFieldRelationHandler.%s-%s', $name, str_replace(' ', '', $value));
			return _t($key, $value);
		} else {
			return _t('GridFieldRelationHandler.' . $name);
		}
	}

	protected function getState($gridField) {
		static $state = null;
		if(!$state) {
			$state = $gridField->State->GridFieldRelationHandler;
			$this->setupState($state);
		}
		return $state;
	}

	protected function setupState($state) {
		if(!isset($state->RelationVal)) {
			$state->RelationVal = 0;
			$state->FirstTime = 1;
		} else {
			$state->FirstTime = 0;
		}
		if(!isset($state->ShowingRelation)) {
			$state->ShowingRelation = 0;
		}
	}

	public function augmentColumns($gridField, &$columns) {
		$state = $this->getState($gridField);
		if($state->ShowingRelation || !$this->useToggle) {
			if(!in_array('RelationSetter', $columns)) {
				array_unshift($columns, 'RelationSetter');
			}
			if($this->useToggle && ($key = array_search('Actions', $columns)) !== false) {
				unset($columns[$key]);
			}
		}
	}

	public function getColumnsHandled($gridField) {
		return array('RelationSetter');
	}

	public function getColumnMetadata($gridField, $columnName) {
		if($columnName == 'RelationSetter') {
			return array(
				'title' => $this->columnTitle
			);
		}
		return array();
	}

	public function getColumnAttributes($gridField, $record, $columnName) {
		return array('class' => 'col-noedit');
	}

	protected function getFields($gridField) {
		$state = $this->getState($gridField);
		if(!$this->useToggle) {
			$fields = array(
                GridField_FormAction::create(
					$gridField,
					'relationhandler-saverel',
					$this->getButtonTitle('SAVE_RELATION'),
					'saveGridRelation',
					null
				)->addExtraClass('relationhandler-saverel btn btn-primary font-icon-save')
			);
		} elseif($state->ShowingRelation) {
			$fields = array(
                GridField_FormAction::create(
					$gridField,
					'relationhandler-cancelrel',
					$this->getButtonTitle('CANCELSAVE_RELATION'),
					'cancelGridRelation',
					null
				)->addExtraClass('relationhandler-cancelrel btn btn-outline-secondary'),
                GridField_FormAction::create(
					$gridField,
					'relationhandler-saverel',
					$this->getButtonTitle('SAVE_RELATION'),
					'saveGridRelation',
					null
				)->addExtraClass('relationhandler-saverel btn btn-primary font-icon-save')
			);
		} else {
			$fields = array(
                GridField_FormAction::create(
					$gridField,
					'relationhandler-togglerel',
					$this->getButtonTitle('TOGGLE_RELATION'),
					'toggleGridRelation',
					null
				)->addExtraClass('relationhandler-togglerel btn btn-outline-secondary font-icon-link')
			);
		}
		return ArrayList::create($fields);
	}

	public function getHTMLFragments($gridField) {
		Requirements::javascript('simonwelsh/gridfieldrelationhandler: javascript/GridFieldRelationHandler.js');
		$saveRelation = 
		$data = ArrayData::create(array(
			'Fields' => $this->getFields($gridField)
		));
		return array(
			$this->targetFragment => $data->renderWith('GridFieldRelationHandlerButtons')
		);
	}

	public function getActions($gridField) {
		return array('saveGridRelation', 'cancelGridRelation', 'toggleGridRelation');
	}

	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if(in_array($actionName, array_map('strtolower', $this->getActions($gridField)))) {
			return $this->$actionName($gridField, $arguments, $data);
		}
	}

	protected function toggleGridRelation(GridField $gridField, $arguments, $data) {
		$state = $this->getState($gridField);
		$state->ShowingRelation = true;
	}

	protected function cancelGridRelation(GridField $gridField, $arguments, $data) {
		$state = $this->getState($gridField);
		$state->ShowingRelation = false;
		$state->FirstTime = true;
	}

	protected function saveGridRelation(GridField $gridField, $arguments, $data) {
		$state = $this->getState($gridField);
		$state->ShowingRelation = false;
	}
}
