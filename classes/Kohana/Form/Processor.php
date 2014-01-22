<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @author: Vad Skakov <vad.skakov@gmail.com>
 */
class Kohana_Form_Processor
{
	protected $request;
	/** @var Kohana_Form_Field[] */
	protected $fields;
	protected $initalValues;
	protected $validationMessages;
	protected $errors;

	protected $fieldDefaults;
	protected $requestMethod;
	protected $action;
	protected $enctype;

	protected $onError;
	protected $onSuccess;

	/**
	 * @param null|array|ORM   $initalValues
	 * @param null|bool|string $validationMessages
	 * @param array            $fields
	 *
	 * @return Form_Processor
	 */
	static function factory($initalValues = NULL, $validationMessages = TRUE, array $fields = NULL)
	{
		return new Form_Processor($initalValues, $validationMessages, $fields);
	}

	/**
	 * @param null  $initalValues
	 * @param bool  $validationMessages
	 * @param array $fields
	 */
	function __construct($initalValues = NULL, $validationMessages = TRUE, array $fields = NULL)
	{
		$this->request = Request::current();
		$this->requestMethod = Request::POST;
		$this->initalValues = class_exists('ORM') && $initalValues instanceof ORM
			? $initalValues->as_array()
			: $initalValues;
		$this->validationMessages = $validationMessages === TRUE ? 'validation' : $validationMessages;

		$this->fields = array();
		$this->errors = array();

		if (is_array($fields)) $this->setFields($fields);
	}

	/**
	 * @param $method
	 *
	 * @return Form_Processor
	 */
	public function setMethod($method)
	{
		$this->requestMethod = $method;

		return $this;
	}

	/**
	 * @param bool $lowercase
	 *
	 * @return string
	 */
	public function method($lowercase = TRUE)
	{
		return $lowercase ? strtolower($this->requestMethod) : $this->requestMethod;
	}

	/**
	 * @param $url
	 *
	 * @return Form_Processor
	 */
	public function setAction($url)
	{
		$this->action = $url;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function action()
	{
		return $this->action;
	}

	/**
	 * @param $value
	 *
	 * @return Form_Processor
	 */
	public function setEncType($value)
	{
		$this->enctype = $value;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function enctype()
	{
		return $this->enctype;
	}

	/**
	 * @param null $defaults
	 *
	 * @return Form_Processor
	 */
	public function setFieldsDefaults($defaults = NULL)
	{
		$this->fieldDefaults = $defaults;

		return $this;
	}

	/**
	 * @param Form_Field $field
	 *
	 * @return Form_Processor
	 */
	public function addField(Form_Field $field)
	{
		$name = $field->getName();
		$initalValue = Arr::get($this->initalValues, $name);
		$value = $this->requestMethod === Request::GET ? $this->request->query($name) : $this->request->post($name);
		//if (is_string($value) && !strlen($value)) $value = NULL;
		if ($this->isInitial() && NULL === $value) $value = $field->isSetInitialValue() ? $field->getInitialValue()
			: $initalValue;

		$this->fields[$name] = $field
			->setDefaults($this->fieldDefaults)
			->setInitialValue($initalValue, FALSE)
			->setValue($value);
		if ($field->getType() == Form_Field::FILE) $this->setEncType('multipart/form-data');

		return $this;
	}

	/**
	 * @param array $fieldsData
	 *
	 * @return Form_Processor
	 */
	public function setFields(array $fieldsData)
	{
		foreach ($fieldsData as $field) $this->addField($field);

		return $this;
	}

	/**
	 * @param callable $closure
	 * @param array    $args
	 *
	 * @return $this
	 */
	public function onError(Closure $closure = NULL, $args = [])
	{
		$this->onError = is_callable($closure)
			? [
				'closure' => $closure,
				'args' => Helpers_Arr::merge([$this], $args),
			]
			: NULL;

		return $this;
	}

	/**
	 * @param callable $closure
	 * @param array    $args
	 *
	 * @return $this
	 */
	public function onSuccess(Closure $closure = NULL, $args = [])
	{
		$this->onSuccess = is_callable($closure)
			? [
				'closure' => $closure,
				'args' => Helpers_Arr::merge([$this], $args),
			]
			: NULL;

		return $this;
	}

	/**
	 * @param bool $forceValidation
	 *
	 * @return Form_Processor
	 */
	public function process($forceValidation = FALSE)
	{
		$this->errors = array();
		if (!$this->isInitial() || $forceValidation) {
			$rulesCommon = array();
			$rulesFILES = array();
			/** @var $field Form_Field */
			foreach ($this->fields as $key => $field) {
				$field->setError(NULL);
				if ($field->getType() == Form_Field::FILE) $rulesFILES[$key] = $field->getRules();
				else $rulesCommon[$key] = $field->getRules();
			}
			$this->_validate($this->values(), $rulesCommon);
			$this->_validate($_FILES, $rulesFILES);

			if (TRUE === $this->isValid() && isset($this->onSuccess)) {
				call_user_func_array($this->onSuccess['closure'], $this->onSuccess['args']);
			} elseif (FALSE === $this->isValid() && isset($this->onError)) {
				call_user_func_array($this->onError['closure'], $this->onError['args']);
			}
		}

		return $this;
	}

	/**
	 * @param array $values
	 * @param array $rules
	 */
	protected function _validate($values, $rules)
	{
		if (count($rules)) {
			$validation = Validation::factory($values);
			foreach ($rules as $key => $rule) $validation->rules($key, $rule);
			if (!$validation->check()) {
				foreach ($validation->errors($this->validationMessages, FALSE) as $key => $error) {
					$this->errors[$key] = $this->getField($key)->setError($error, TRUE);
				}
			}
		}
	}

	/**
	 * !!! Alwayas FALSE on non POST requests !!!
	 *
	 * @return bool
	 */
	public function isInitial()
	{
		return $this->request->method() !== $this->requestMethod;
	}

	/**
	 * @return bool|null
	 */
	public function isValid()
	{
		return $this->isInitial() ? NULL : !count($this->errors());
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function fieldExists($name)
	{
		return isset($this->fields[$name]);
	}

	/**
	 * @param string $name
	 *
	 * @throws Kohana_Exception
	 * @return Form_Field
	 */
	public function &getField($name)
	{
		if (!$this->fieldExists($name)) throw new Kohana_Exception('Field [:name] - not exists', array(':name' => $name));

		return $this->fields[$name];
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @return Form_Processor
	 */
	public function setField_IsDirty($name, $value)
	{
		if ($name instanceof Form_Field) $name = $name->getName();
		if ($this->fieldExists($name)) $this->fields[$name]->setDirty($value);

		return $this;
	}

	/**
	 * @param $name
	 * @param $text
	 *
	 * @return Form_Processor
	 */
	public function setField_Error($name, $text)
	{
		if ($name instanceof Form_Field) $name = $name->getName();
		if ($this->fieldExists($name)) $this->fields[$name]->setError($text);

		return $this;
	}

	/**
	 * @return array
	 */
	public function fields()
	{
		return $this->fields;
	}

	/**
	 * @param null $keys
	 *
	 * @return array
	 */
	public function values($keys = NULL)
	{
		$values = array();
		/** @var $field Form_Field */
		foreach ($this->fields as $key => $field) {
			if (NULL === $keys || Helpers_Arr::inArray($key, $keys)) $values[$key] = $field->getValue();
		}

		return $values;
	}

	/**
	 * @param      $key
	 * @param null $default
	 *
	 * @return null|string
	 */
	public function value($key, $default = NULL)
	{
		$value = $this->getField($key)->getValue();

		return NULL === $value && NULL !== $default ? $default : $value;
	}

	/**
	 * @return mixed
	 */
	public function errors()
	{
		$this->errors = array();
		/** @var $field Form_Field */
		foreach ($this->fields as $key => $field) {
			if ($field->isError()) $this->errors[$key] = $field->getValue();
		}

		return $this->errors;
	}


	/**
	 * @param bool $onlyKeys
	 *
	 * @return array
	 */
	public function dirtyFields($onlyKeys = FALSE)
	{
		$result = array();
		/** @var $field Form_Field */
		foreach ($this->fields as $field) {
			if ($field->isDirty()) {
				$key = $field->getName();
				if ($onlyKeys) $result[] = $key;
				else $result[$key] = $field->getValue();
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isDirty()
	{
		$dirty = 0;
		foreach ($this->fields as $field) if ($field->isDirty()) $dirty++;

		return $dirty > 0;
	}

}
