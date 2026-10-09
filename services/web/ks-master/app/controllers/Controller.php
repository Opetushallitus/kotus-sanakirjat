<?php
if (!constant("IN_KS")) die("General error, exiting.");

/**
 * app/controllers/Controller.php
 *
 * Copyright: 	Institute for the Languages of Finland
 * Author: 		Teemu Ikonen <teemu.ikonen@kotus.fi>, 2013
*/

abstract class Controller {

	// Class level variable to store the current action.
	protected $_action;
	
	// Class level variable to store the model to be used.
	protected $_model;
	
	// Class level variable to store the view to be used.
	protected $_view;
	

	// Class constructor.
	// Will be executed when a class is initialized.
	public function __construct($action) {

		// Execute the requested action if a child class has implemented it.
		// If not, throws an exception and shows a error page.
		if (method_exists($this, $action)) {
			
			// Execute a given function in a child class.
			$this->$action();
			
		} else {
			
			// Throw a SysException and stop execution.
			throw new SysException(_("Requested action is not currently implemented."));
			
		}

		// Save the requested action.
		$this->_action = $action;

	}
	
	
	public function set($name, $value, $strip = true) {

		// Check wheter a class level variable is an object.
		if (is_object($this->_view)) {
			
			// If so, apply setter into a view class and return it.
			return $this->_view->set($name, $value, $strip);

		} else {
			
			// Throws a SysException and stop execution.
			throw new SysException(_("Cannot use set(), view must be initialized first."));
			
		}
		
	}
	
	
	public function get($name, $decode = false) {
		
		// Check wheter a class level variable is an object.
		if (is_object($this->_view)) {
			
			// Return variable from a view class.
			return $this->_view->get($name, $decode);
			
		} else {
			
			return false;
			
		}
		
	}


	public function getModel() {
		
		// Return a model instance.
		return $this->_model;
		
	}


	public function setModel($model) {

		// Check wheter if there's initialized model already.
		if (is_object($this->_model)) {
			
			// If so, destroy an existing model object before creating a new one.
			$this->_model = null;
			
		}

		// Check wheter is a given class found from a structure.
		if (class_exists($model)) {
		
			// If so, create a new instance of the model.
			$this->_model = new $model;
			
		} else {
		
			// No model class found, throws an exception.
			throw new SysException(_("Requested model class could not found."));
			
		}
		
		// Return a model instance.
		return $this->_model;

	}


	public function getView() {
		
		// Return a view instance.
		return $this->_view;
		
	}


	public function setView($view) {

		// Check wheter if there's initialized view already.
		if (is_object($this->_view)) {
			
			// If so, destroy an existing view object before creating a new one.
			$this->_view = null;
			
		}

		// Check wheter is a given class found from a structure.
		if (class_exists($view)) {
			
			// If so, create a new instance of the view.
			$this->_view = new $view;
			
		} else {
			
			// No view class found, throws an exception.
			throw new SysException(_("Requested view class could not found."));
			
		}

		// Return a view instance.
		return $this->_view;

	}

}


?>
