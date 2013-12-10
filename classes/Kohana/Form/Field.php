<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @author: Vad Skakov <vad.skakov@gmail.com>
 */
class Kohana_Form_Field
{
	const HIDDEN = 'hidden';
	const INPUT = 'input';
	const PASSWORD = 'password';
	const SELECT = 'select';
	const TEXTAREA = 'textarea';
	const FILE = 'file';
	const CHECKBOX = 'checkbox';
	const RADIO = 'radio';

	const KEY_NAME = 'name';
	const KEY_ID = 'id';
	const KEY_TYPE = 'type';
	const KEY_LABEL_TEXT = 'label_text';
	const KEY_LABEL_CLASS = 'label_class';
	const KEY_PLACEHOLDER = 'placeholder';
	const KEY_ATTR = 'attr';
	const KEY_CLASS = 'class';
	const KEY_REQUIRED = 'required';
	const KEY_INITIAL_VALUE = 'default_value';
	const KEY_SELECT_OPTIONS = 'select_options';
	const KEY_RULES = 'rules';
	const KEY_CHECKED_VALUE = 'checked_value';

	const REQUIRED_BEFORE = 1;
	const REQUIRED_AFTER = 2;

	const DEFAULT_LABEL_CLASS = 'default_label_class';
	const DEFAULT_ERROR_CLASS = 'default_error_class';
	const DEFAULT_REQUIRED_HTML = 'default_required_html';
	const DEFAULT_REQUIRED_POSITION = 'default_required_position';
	const DEFAULT_REQUIRED_DELIM = 'default_required_delim';
	const DEFAULT_CHECKED_VALUE = 'default_checked_value';
	const DEFAULT_PLACEHOLDER_TEXT = 'default_placeholder_text';

	public $default_label_class = 'control-label';
	public $default_error_class = 'error';
	public $default_required_html = '<span style="color:#cc0000">*</span>';
	public $default_required_position = self::REQUIRED_AFTER;
	public $default_required_delim = '&nbsp;';
	public $default_checked_value = '1';
	public $default_placeholder_text;

	protected $_name;
	protected $_id;
	protected $_label_text;
	protected $_label_class;
	protected $_type;
	protected $_initial_value;
	protected $_checked_value;
	protected $_unchecked_value;
	protected $_select_options;
	protected $_error;
	protected $_attr;
	protected $_class;
	protected $_placeholder;
	protected $_required;
	protected $_required_position;
	protected $_required_html;
	protected $_required_delim;
	protected $_rules;
	protected $_value;
	protected $_is_dirty;
	protected $_strict_hidden;
	protected $_prepend;
	protected $_append;
	protected $_not_nullable = FALSE;

	private $initial_value_is_set;

	/**
	 * @param            $name
	 * @param array|null $data
	 * @param array|null $defaults
	 *
	 * @throw Kohana_Exception
	 * @return Form_Field
	 */
	static function factory($name, array $data = NULL, $defaults = NULL)
	{
		return new static($name, $data, $defaults);
	}

	function __construct($name, $data = NULL, $defaults = NULL)
	{
		$this->initial_value_is_set = FALSE;
		$this->defaults_is_set = FALSE;

		if (is_array($name)) {
			$data = $name;
			$name = Kohana_Arr::get($data, self::KEY_NAME);
			if (!$name) throw new Kohana_Exception('Field :name not defined', [':name' => self::KEY_NAME]);
		}

		$this->_name = $name;
		$this->_strict_hidden = TRUE;
		$this->setDefaults($defaults);
		$this->fillData($data);
	}

	/**
	 * @return array
	 */
	public function allowedDefaults()
	{
		return array(
			self::DEFAULT_CHECKED_VALUE,
			self::DEFAULT_ERROR_CLASS,
			self::DEFAULT_LABEL_CLASS,
			self::DEFAULT_PLACEHOLDER_TEXT,
			self::DEFAULT_REQUIRED_DELIM,
			self::DEFAULT_REQUIRED_HTML,
			self::DEFAULT_REQUIRED_POSITION
		);
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function allowedDefault($key)
	{
		return in_array($key, $this->allowedDefaults());
	}

	/**
	 * @param null|array $defaults
	 *
	 * @return Form_Field
	 */
	public function setDefaults($defaults = NULL)
	{
		if (is_array($defaults)) {
			foreach ($defaults as $key => $value) {
				if ($this->allowedDefault($key)) $this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * @param array $data
	 *
	 * @return Form_Field
	 */
	protected function fillData(array $data = NULL)
	{
		if (NULL === $data) $data = array();
		$this->setType(Arr::get($data, self::KEY_TYPE, self::HIDDEN));
		$this->setID(Arr::get($data, self::KEY_ID));
		$this->setLabelText(Arr::get($data, self::KEY_LABEL_TEXT));
		$this->setLabelClass(Arr::get($data, self::KEY_LABEL_CLASS));
		$this->setRequired(Arr::get($data, self::KEY_REQUIRED, FALSE));
		if (array_key_exists(self::KEY_INITIAL_VALUE, $data)) $this->setInitialValue(Arr::get($data, self::KEY_INITIAL_VALUE));
		$this->setAttr(Arr::get($data, self::KEY_ATTR));
		$this->setClass(Arr::get($data, self::KEY_CLASS));
		$this->setPlaceholder(Arr::get($data, self::KEY_PLACEHOLDER));
		$this->setRules(Arr::get($data, self::KEY_RULES));
		$this->setCheckedValue(Arr::get($data, self::KEY_CHECKED_VALUE));
		$this->setSelectOptions(Arr::get($data, self::KEY_SELECT_OPTIONS));

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param null $value
	 *
	 * @throws Kohana_Exception
	 * @return Form_Field
	 */
	public function setType($value)
	{
		if (!in_array($value, [
			self::CHECKBOX,
			self::FILE,
			self::HIDDEN,
			self::INPUT,
			self::PASSWORD,
			self::SELECT,
			self::TEXTAREA,
		])
		) throw new Kohana_Exception('Unknown field type [:type] on field [:field]', [
			':type' => (NULL === $value ? 'NULL' : $value),
			':field' => $this->_name,
		]);

		if (in_array($value, [self::PASSWORD])) {
			$this->setInitialValue(NULL, TRUE);
		}

		$this->_type = $value;

		return $this;
	}

	/**
	 * @param bool        $required
	 * @param null|string $className
	 *
	 * @throws Kohana_Exception
	 * @return Form_Field
	 */
	public function setType_Input($required = FALSE, $className = NULL)
	{
		return $this
			->setType(self::INPUT)
			->setRequired($required)
			->setClass($className);
	}

	/**
	 * @param int $checked_value
	 * @param int $unchecked_value
	 *
	 * @throws Kohana_Exception
	 * @return Form_Field
	 */
	public function setType_Checkbox($checked_value = 1, $unchecked_value = 0)
	{
		return $this
			->setType(self::CHECKBOX)
			->setCheckedValue($checked_value, $unchecked_value);
	}

	/**
	 * @param bool $required
	 * @param int  $max_length
	 *
	 * @throws Kohana_Exception
	 * @return Form_Field
	 */
	public function setType_Email($required = FALSE, $max_length = 254)
	{
		return $this
			->setType(self::INPUT)
			->setRequired($required)
			->addRule_Email($max_length);
	}

	/**
	 * @param bool $required
	 * @param int  $min_length
	 * @param int  $max_length
	 *
	 * @throws Kohana_Exception
	 * @return Form_Field
	 */
	public function setType_Password($required = FALSE, $min_length = 6, $max_length = 32)
	{
		return $this
			->setType(self::PASSWORD)
			->setRequired($required)
			->addRule_Password($min_length, $max_length);
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @param null $value
	 *
	 * @return Form_Field
	 */
	public function setID($value = NULL)
	{
		$this->_id = $value;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getID()
	{
		return NULL === $this->_id ? 'field_' . $this->_name : $this->_id;
	}

	/**
	 * @param null $text
	 * @param null $class
	 *
	 * @return Form_Field
	 */
	public function setLabel($text = NULL, $class = NULL)
	{
		return $this->setLabelText($text)->setLabelClass($class);
	}

	/**
	 * @param null $value
	 *
	 * @return Form_Field
	 */
	public function setLabelText($value = NULL)
	{
		$this->_label_text = $value;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getLabelText()
	{
		return NULL === $this->_label_text ? NULL : trim($this->_label_text);
	}

	/**
	 * @param null $value
	 *
	 * @return Form_Field
	 */
	public function setLabelClass($value = NULL)
	{
		$this->_label_class = $value;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getLabelClass()
	{
		return NULL === $this->_label_class ? $this->default_label_class : trim($this->_label_class);
	}

	/**
	 * @param      $value
	 * @param null $position
	 * @param null $html
	 * @param null $delim
	 *
	 * @return Form_Field
	 */
	public function setRequired($value, $position = NULL, $html = NULL, $delim = NULL)
	{
		$this->_required = $value;
		$this->_required_position = $position;
		$this->_required_html = $html;
		$this->_required_delim = $delim;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getRequired()
	{
		return (bool) $this->_required;
	}

	/**
	 * @return int
	 */
	public function getRequiredPosition()
	{
		return NULL === $this->_required_position ? $this->default_required_position : $this->_required_position;
	}

	/**
	 * @return string
	 */
	public function getRequiredHTML()
	{
		return NULL === $this->_required_html ? $this->default_required_html : $this->_required_html;
	}

	/**
	 * @return string
	 */
	public function getRequiredDelim()
	{
		return NULL === $this->_required_delim ? $this->default_required_delim : $this->_required_delim;
	}

	/**
	 * @param null $error
	 * @param bool $returnError
	 *
	 * @return Form_Field|mixed
	 */
	public function setError($error = NULL, $returnError = FALSE)
	{
		$this->_error = $error;

		return $returnError ? $this->getError() : $this;
	}

	/**
	 * @return bool
	 */
	public function isError()
	{
		return NULL !== $this->_error && $this->_error !== FALSE;
	}

	/**
	 * @return mixed
	 */
	public function getError()
	{
		return $this->isError() ? (is_array($this->_error) ? reset($this->_error) : $this->_error) : NULL;
	}

	/**
	 * @param null $value
	 * @param bool $overwrite
	 *
	 * @return Form_Field
	 */
	public function setInitialValue($value = NULL, $overwrite = TRUE)
	{
		if (!$this->initial_value_is_set || $overwrite) {
			if ($this->_type === self::CHECKBOX && NULL !== $this->_unchecked_value && NULL === $value) $value = $this->_unchecked_value;
			elseif ($this->_type === self::FILE && (is_array($value) || (class_exists('ORM') && $value instanceof ORM))) $value = NULL;
			$this->_initial_value = NULL !== $value ? (string) $value : NULL;
		}
		$this->initial_value_is_set = TRUE;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getInitialValue()
	{
		return $this->_initial_value;
		/*
				$value = $this->_initial_value;
				$opts = is_array($this->_select_options) ? array_keys($this->_select_options) : NULL;
				return $this->_type == self::SELECT && (!$this->isSetInitialValue() || NULL === $value) && NULL !== $opts ? (string) reset($opts) : $value;
		*/
	}

	/**
	 * @return bool
	 */
	public function isSetInitialValue()
	{
		return $this->initial_value_is_set;
	}

	/**
	 * @param null $checked
	 * @param null $unchecked
	 *
	 * @return Form_Field
	 */
	public function setCheckedValue($checked = NULL, $unchecked = NULL)
	{
		if ($this->_type === self::CHECKBOX) $this->_checked_value = NULL === $checked ? $this->default_checked_value
			: (string) $checked;

		return $this->setUnCheckedValue($unchecked);
	}

	/**
	 * @param null $value
	 *
	 * @return Form_Field
	 */
	public function setUnCheckedValue($value = NULL)
	{
		if ($this->_type === self::CHECKBOX) $this->_unchecked_value = NULL !== $value ? (string) $value : NULL;

		return $this;
	}

	/**
	 * @param null $data
	 *
	 * @return Form_Field
	 */
	public function setAttr($data = NULL)
	{
		$this->_attr = $data;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAttr()
	{
		return NULL === $this->_attr || !is_array($this->_attr) ? array() : $this->_attr;
	}

	/**
	 * @param null $value
	 *
	 * @return Form_Field
	 */
	public function setClass($value = NULL)
	{
		$this->_class = $value;

		return $this;
	}

	/**
	 * @param null $addClass
	 *
	 * @return null|string
	 */
	public function getClass($addClass = NULL)
	{
		$result = NULL;
		if (!$this->isHidden()) {
			$result = array();
			if (!empty($this->_class)) $result[] = trim($this->_class);
			if ($this->isError()) $result[] = $this->default_error_class;
			if (NULL !== $addClass) $result[] = $addClass;
			$result = count($result) ? implode(' ', $result) : NULL;
		}

		return $result;
	}

	/**
	 * @param null $value
	 *
	 * @return Form_Field
	 */
	public function setPlaceholder($value = NULL)
	{
		$this->_placeholder = $value;

		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getPlaceholder()
	{
		$placeholder = NULL;
		if (in_array($this->_type, array(
			self::PASSWORD,
			self::INPUT,
			self::TEXTAREA,
		))
		) $placeholder = NULL === $this->_placeholder ? $this->default_placeholder_text : $this->_placeholder;

		return $placeholder;
	}

	/**
	 * @param null|array $data
	 *
	 * @return Form_Field
	 */
	public function setSelectOptions($data = NULL)
	{
		$this->_select_options = $data;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getSelectOptions()
	{
		return NULL === $this->_select_options ? array() : $this->_select_options;
	}

	/**
	 * @param string|null $value
	 *
	 * @throws Kohana_Exception
	 * @return Form_Field
	 */
	public function setValue($value = NULL)
	{
		//$value = NULL === $value || is_array($value) || is_object($value) ? NULL : trim($value);
		$value = is_string($value) ? trim($value) : $value;
		if ($this->isStrictHidden() && $value != $this->_initial_value) {
			throw new Kohana_Exception('Unable to redefine HIDDEN field [:key]', array(':key' => $this->_name));
		} elseif ($this->_type === self::CHECKBOX && NULL !== $this->_unchecked_value && NULL === $value) {
			$value = $this->_unchecked_value;
		} elseif ($this->_type === self::FILE) {
			if (Request::current()->method() === Request::POST) {
				$value = Arr::path($_FILES, array($this->_name, 'name'));
			}
			if (!is_string($value) || !strlen($value)) $value = NULL;
		}

		$this->_value = $value;
		$this->_is_dirty = $this->getValue() != $this->getInitialValue();

		return $this;
	}

	/**
	 * @param null $default
	 *
	 * @return mixed
	 */
	public function getValue($default = NULL)
	{
		$value = NULL === $this->_value && $this->isSetInitialValue() ? $this->getInitialValue() : $this->_value;

		return NULL === $value && NULL !== $default ? $default : $value;
	}

	/**
	 * @param null|array $data
	 *
	 * @return Form_Field
	 */
	public function setRules($data = NULL)
	{
		$this->_rules = !is_array($data) ? array() : $data;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getRules()
	{
		$rules = array();
		if ($this->getRequired()) $rules[] = array($this->_type == self::FILE ? 'Upload::not_empty' : 'not_empty');
		if (is_array($this->_rules)) {
			foreach ($this->_rules as $rule) $rules[] = $rule;
		}

		return $rules;
	}


	/**
	 * @param      $rule
	 * @param null $params
	 *
	 * @return Form_Field
	 */
	public function addRule($rule, $params = NULL)
	{
		if (NULL === $params) $params = array();
		elseif (!is_array($params)) $params = array($params);
		$params = Arr::merge(array(':value'), $params);

		if (is_string($rule)) $rule = Arr::merge(array($rule), array($params));
		if (is_array($rule)) {
			if (!is_array($this->_rules)) $this->_rules = array();
			$this->_rules[] = $rule;
		}

		return $this;
	}

	public function addRule_MinLength($value)
	{
		return $this->addRule('min_length', $value);
	}

	public function addRule_MaxLength($value)
	{
		return $this->addRule('max_length', $value);
	}

	public function addRule_Password($min_length = 6, $max_length = 32)
	{
		return $this
			->addRule_MinLength($min_length)
			->addRule_MaxLength($max_length);
	}

	public function addRule_Email($max_length = 254)
	{
		return $this
			->addRule('email')
			->addRule_MaxLength($max_length);
	}

	/**
	 * @return bool
	 */
	public function isHidden()
	{
		return $this->_type === self::HIDDEN;
	}

	/**
	 * @return bool
	 */
	public function isDirty()
	{
		return NULL !== $this->_is_dirty ? $this->_is_dirty
			: $this->_type !== self::FILE && $this->_value !== $this->_initial_value;
	}

	/**
	 * @param $value
	 *
	 * @return $this
	 */
	public function setDirty($value)
	{
		$this->_is_dirty = (bool) $value;

		return $this;
	}

	/**
	 * @param $value
	 *
	 * @return $this
	 */
	public function setStrictHidden($value)
	{
		$this->_strict_hidden = (bool) $value;

		return $this;
	}

	public function isStrictHidden()
	{
		return $this->isHidden() ? (bool) $this->_strict_hidden : FALSE;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|mixed
	 */
	public function prepend($value = NULL)
	{
		if (func_num_args() == 0) return $this->_prepend;
		$this->_prepend = $value;

		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|mixed
	 */
	public function append($value = NULL)
	{
		if (func_num_args() == 0) return $this->_append;
		$this->_append = $value;

		return $this;
	}

	/**
	 * @param null $addClass
	 *
	 * @return null|string
	 */
	public function html($addClass = NULL)
	{
		$attr = $this->getAttr();
		$class = $this->getClass($addClass);
		if (NULL !== $class) $attr['class'] = $class;
		$placeholder = $this->getPlaceholder();
		if (NULL !== $placeholder) $attr['placeholder'] = $placeholder;
		$attr = Arr::merge($attr, array(self::KEY_ID => $this->getID()));

		$html = NULL;
		switch ($this->_type) {
			case self::HIDDEN:
				$html = Form::hidden($this->_name, $this->getValue(), $attr);
				break;
			case self::PASSWORD:
				$html = Form::password($this->_name, NULL, $attr);
				break;
			case self::INPUT:
				$html = Form::input($this->_name, $this->getValue(), $attr);
				break;
			case self::TEXTAREA:
				$html = Form::textarea($this->_name, $this->getValue(), $attr);
				break;
			case self::FILE:
				$html = Form::file($this->_name, $attr);
				break;
			case self::SELECT:
				$html = Form::select($this->_name, $this->getSelectOptions(), $this->getValue(), $attr);
				break;
			case self::CHECKBOX:
				$html = Form::checkbox($this->_name, $this->_checked_value, $this->_checked_value == $this->getValue(), $attr);
				break;
		}

		return $html;
	}

	/**
	 * @param bool $asHTML
	 *
	 * @return null|string|array
	 */
	protected function _label($asHTML)
	{
		$labelText = $this->getLabelText();
		if (NULL === $labelText) return NULL;

		$required_before = ($this->getRequired() && $this->getRequiredPosition() === self::REQUIRED_BEFORE
			? $this->getRequiredHTML() . $this->getRequiredDelim() : '');
		$required_after = ($this->getRequired() && $this->getRequiredPosition() === self::REQUIRED_AFTER
			? $this->getRequiredDelim() . $this->getRequiredHTML() : '');
		$text_full = trim($required_before . $labelText . $required_after);
		$html = Form::label($this->getID(), $text_full, array('class' => $this->getLabelClass()));
		if (!$asHTML) return array(
			'text' => $labelText,
			'text_full' => $text_full,
			'html' => $html,
			'class' => $this->getLabelClass(),
			'for' => $this->getID(),
		);

		return $html;
	}

	/**
	 * @return null|string
	 */
	public function label()
	{
		return $this->_label(TRUE);
	}

	/**
	 * @return array|null
	 */
	public function labelData()
	{
		return $this->_label(FALSE);
	}

	/**
	 * @return null|string
	 */
	public function __toString()
	{
		return $this->html();
	}

	public function __call($name, $arguments)
	{
		$matches = NULL;
		if (preg_match('@^addRule_(.+)$@', $name, $matches)) {
			return $this->addRule(strtolower($matches[1]), $arguments);
		}
		throw new Kohana_Exception('Method [:method] does not exists', array(':method' => $name));
	}

	/**
	 * @param      $name
	 * @param bool $required
	 * @param null $label
	 * @param null $max_size
	 * @param null $allowed_ext
	 *
	 * @return Form_Field
	 */
	static function image($name, $required = FALSE, $label = NULL, $max_size = NULL, $allowed_ext = NULL)
	{
		if (NULL === $max_size || NULL === $allowed_ext) {
			$settingsMedia = (array) Kohana::$config->load('settings.media.images');
			if (NULL === $max_size) $max_size = Arr::get($settingsMedia, 'max_size');
			if (NULL === $allowed_ext) $allowed_ext = Arr::get($settingsMedia, 'allowed_ext');
		}

		$fieldImage = self::factory($name)
			->setType(self::FILE)
			->setLabel($label)
			->setRequired($required)
			->addRule('Form_Field::validImage')
			->addRule('Form_Field::validFileType', array($allowed_ext))
			->addRule('Form_Field::validFileSize', $max_size);

		return $fieldImage;
	}

	static function validImage($data)
	{
		if (NULL === $data || !Upload::not_empty($data)) return TRUE;

		return Upload::image($data);
	}

	static function validFileType($data, $allowed_ext = NULL)
	{
		if (NULL === $data || !Upload::not_empty($data)) return TRUE;

		return NULL === $allowed_ext || Upload::type($data, $allowed_ext);
	}

	static function validFileSize($data, $max_size = NULL)
	{
		if (NULL === $data || !Upload::not_empty($data)) return TRUE;

		return NULL === $max_size || Upload::size($data, $max_size);
	}

	/**
	 * @param bool $value
	 *
	 * @return Form_Field
	 */
	public function setIsNullable($value = TRUE)
	{
		$this->_not_nullable = !$value;

		return $this;
	}

}
