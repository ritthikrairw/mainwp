<?php
/**
 * MainWP UI helper.
 *
 * Extension UI helper.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Html_UI_Helper
 *
 * Extension UI helper.
 */
class MainWP_AUM_Html_UI_Helper {

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_DB_Base.
	 */
	private static $instance = null;

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $model_name  model name.
	 */
	private static $model_name = null;

	/**
	 * Create public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_AUM_DB
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Creates form.
	 *
	 * @param string $model_name Model name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function create( $model_name, $options = array() ) {

		self::$model_name = $model_name;

		$action = '';
		if ( ! empty( $options['action'] ) ) {
			$action = $options['action'];
		}

		$html = '<form action="' . $action . '" method="post">';
		return $html;
	}

	/**
	 * Creates input field.
	 *
	 * @param string $field_name Field name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function input( $field_name, $options = array() ) {
		$defaults = array(
			'id'    => self::input_id( $field_name, $options ),
			'name'  => self::input_name( $field_name, $options ),
			'type'  => 'text',
			'label' => null,
			'value' => null,
		);
		$options  = array_merge( $defaults, $options );
		$method   = $options['type'] . '_input';

		$html = '';
		if ( method_exists( $this, $method ) ) {
			$html = self::$method( $field_name, $options );
		}

		return $html;
	}

	/**
	 * Creates text input field.
	 *
	 * @param string $field_name Field name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function text_input( $field_name, $options = array() ) {
		$defaults        = array(
			'id'   => $this->input_id( $field_name ),
			'name' => $this->input_name( $field_name ),
			'type' => 'text',
		);
		$options         = array_merge( $defaults, $options );
		$attributes_html = self::attributes_html( $options, 'input' );
		$html            = '<input' . $attributes_html . ' />';
		return $html;
	}

	/**
	 * Creates textarea input field.
	 *
	 * @param string $field_name Field name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function textarea_input( $field_name, $options = array() ) {
		$defaults        = array(
			'id'   => $this->input_id( $field_name ),
			'name' => $this->input_name( $field_name ),
		);
		$options         = array_merge( $defaults, $options );
		$attributes_html = self::attributes_html( $options, 'textarea' );
		$html            = '<textarea' . $attributes_html . '>' . esc_textarea( $options['value'] ) . '</textarea>';
		return $html;
	}

	/**
	 * Creates radio input field.
	 *
	 * @param string $field_name Field name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function radio_input( $field_name, $options = array() ) {
		$options['type'] = 'radio';
		return $this->checkbox_input( $field_name, $options );
	}

	/**
	 * Creates checkbox input field.
	 *
	 * @param string $field_name Field name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function checkbox_input( $field_name, $options = array() ) {
		$defaults = array(
			'id'                => $this->input_id( $field_name ),
			'name'              => $this->input_name( $field_name ),
			'type'              => 'checkbox',
			'checked'           => false,
			'value'             => '1',
			'with_hidden_input' => true,
		);

		$options = array_merge( $defaults, $options );
		if ( ! $options['checked'] ) {
			unset( $options['checked'] );
		} else {
			$options['checked'] = 'checked';
		}
		$attributes_html = self::attributes_html( $options, 'input' );

		$html = '';
		if ( $options['with_hidden_input'] ) {
			// Included to allow for a workaround to the issue of unchecked checkbox fields not being sent by clients.
			$html .= '<input type="hidden" name="' . esc_attr( $options['name'] ) . '" value="0" />';
		}
		$html .= '<input' . $attributes_html . ' />';
		if ( isset( $options['label'] ) && ! empty( $options['label'] ) ) {
			$html .= '<label for="' . $options['id'] . '">' . $options['label'] . '</label>';
		}
		return $html;
	}

	/**
	 * Creates hidden input field.
	 *
	 * @param string $field_name Field name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function hidden_input( $field_name, $options = array() ) {
		$defaults        = array(
			'id'   => $this->input_id( $field_name ),
			'name' => $this->input_name( $field_name ),
			'type' => 'hidden',
		);
		$options         = array_merge( $defaults, $options );
		$attributes_html = self::attributes_html( $options, 'input' );
		$html            = '<input' . $attributes_html . ' />';
		return $html;
	}

	/**
	 * Creates select input field.
	 *
	 * @param string $field_name Field name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function select_input( $field_name, $options = array() ) {
		$html = $this->select_tag( $field_name, $options );
		return $html;
	}

	/**
	 * Creates select tag field.
	 *
	 * @param string $field_name Field name.
	 * @param array  $options    Options.
	 *
	 * @return string HTLM string.
	 */
	public function select_tag( $field_name, $options = array() ) {
		$defaults           = array(
			'empty' => false,
			'value' => null,
			'id'    => $this->input_id( $field_name ),
			'name'  => $this->input_name( $field_name ),
		);
		$options            = array_merge( $defaults, $options );
		$options['options'] = empty( $options['options'] ) ? array() : $options['options'];

		$attributes_html = self::attributes_html( $options, 'select' );
		$html            = '<select' . $attributes_html . '>';
		if ( $options['empty'] ) {
			$empty_name = is_string( $options['empty'] ) ? $options['empty'] : '';
			$html      .= '<option value="">' . $empty_name . '</option>';
		}
		foreach ( $options['options'] as $key => $value ) {
			if ( is_object( $value ) ) {
				$key   = $value->__id;
				$value = $value->__name;
			}
			$selected_attribute = $options['value'] == $key ? ' selected="selected"' : '';
			$html              .= '<option value="' . esc_attr( $key ) . '"' . $selected_attribute . '>' . $value . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	/**
	 * Creates button input field.
	 *
	 * @param string $text    Text.
	 * @param array  $options Options.
	 *
	 * @return string HTLM string.
	 */
	public function button( $text, $options = array() ) {
		$defaults        = array(
			'id'    => $this->input_id( $text ),
			'type'  => 'button',
			'class' => 'button',
		);
		$options         = array_merge( $defaults, $options );
		$attributes_html = self::attributes_html( $options, 'input' );
		$html            = '<button' . $attributes_html . '>' . $text . '</button>';
		return $html;
	}

	/**
	 * Returns input ID.
	 *
	 * @param string $field_name Field name.
	 *
	 * @return string Field ID.
	 */
	private function input_id( $field_name ) {
		return self::$model_name . self::camelize( $field_name );
	}

	/**
	 * Returns input name.
	 *
	 * @param string $field_name Field name.
	 * @param string $options Options name.
	 *
	 * @return string Field name.
	 */
	private function input_name( $field_name, $options = array() ) {
		$name_suffix = '';
		if ( isset( $options['name_suffix'] ) ) {
			$name_suffix = $options['name_suffix'];
		}

		$name = 'data[' . self::$model_name . '][' . self::underscore( $field_name ) . ']';
		if ( $name_suffix ) {
			$name .= $name_suffix;
		}
		return $name;
	}

	/**
	 * Undescores text string.
	 *
	 * @param string $string Text string.
	 *
	 * @return string $string Text string.
	 */
	public static function tableize( $string ) {
		$string = self::underscore( $string );
		return $string;
	}

	/**
	 * Uppercases the first character of each word in a string.
	 *
	 * @param string $string Text string.
	 *
	 * @return string $string Text string.
	 */
	public static function titleize( $string ) {
		$string = preg_replace( '/[A-Z]/', ' $0', $string );
		$string = trim( str_replace( '_', ' ', $string ) );
		$string = ucwords( $string );
		return $string;
	}

	/**
	 * Uppercases the first character of each word in a string.
	 *
	 * @param string $string Text string.
	 *
	 * @return string $string Text string.
	 */
	public static function camelize( $string ) {
		$string = str_replace( '_', ' ', $string );
		$string = str_replace( '-', ' ', $string );
		$string = ucwords( $string );
		$string = str_replace( ' ', '', $string );
		return $string;
	}

	/**
	 * Undescores text string.
	 *
	 * @param string $string Text string.
	 *
	 * @return string $string Text string.
	 */
	public static function underscore( $string ) {
		$string = preg_replace( '/[A-Z]/', ' $0', $string );
		$string = trim( strtolower( $string ) );
		$string = str_replace( ' ', '_', $string );
		return $string;
	}

	/**
	 * Returns HTML attributes.
	 *
	 * @param array            $attributes                    Attributes.
	 * @param array            $valid_attributes_array_or_tag Attributes.
	 *
	 * @param array Attributes.
	 */
	public static function attributes_html( $attributes, $valid_attributes_array_or_tag ) {

		$event_attributes = array(
			'standard' => array(
				'onclick',
				'ondblclick',
				'onkeydown',
				'onkeypress',
				'onkeyup',
				'onmousedown',
				'onmousemove',
				'onmouseout',
				'onmouseover',
				'onmouseup',
			),
			'form'     => array(
				'onblur',
				'onchange',
				'onfocus',
				'onreset',
				'onselect',
				'onsubmit',
			),
		);

		$valid_attributes_by_tag = array(
			'a'        => array(
				'accesskey',
				'charset',
				'class',
				'dir',
				'coords',
				'href',
				'hreflang',
				'id',
				'lang',
				'name',
				'rel',
				'rev',
				'shape',
				'style',
				'tabindex',
				'target',
				'title',
				'xml:lang',
			),
			'input'    => array(
				'accept',
				'access_key',
				'align',
				'alt',
				'autocomplete',
				'checked',
				'class',
				'dir',
				'disabled',
				'id',
				'lang',
				'maxlength',
				'name',
				'placeholder',
				'readonly',
				'required',
				'size',
				'src',
				'style',
				'tabindex',
				'title',
				'type',
				'value',
				'xml:lang',
				$event_attributes['form'],
			),
			'textarea' => array(
				'access_key',
				'class',
				'cols',
				'dir',
				'disabled',
				'id',
				'lang',
				'maxlength',
				'name',
				'placeholder',
				'readonly',
				'rows',
				'style',
				'tabindex',
				'title',
				'xml:lang',
				$event_attributes['form'],
			),
			'select'   => array(
				'class',
				'dir',
				'disabled',
				'id',
				'lang',
				'multiple',
				'name',
				'size',
				'style',
				'tabindex',
				'title',
				'xml:lang',
				$event_attributes['form'],
			),
		);

		foreach ( $valid_attributes_by_tag as $key => $valid_attributes ) {
			$valid_attributes = array_merge( $event_attributes['standard'], $valid_attributes );
			$valid_attributes = self::array_flatten( $valid_attributes );

			$valid_attributes_by_tag[ $key ] = $valid_attributes;
		}

		$valid_attributes = is_array( $valid_attributes_array_or_tag ) ? $valid_attributes_array_or_tag : $valid_attributes_by_tag[ $valid_attributes_array_or_tag ];

		$attributes = array_intersect_key( $attributes, array_flip( $valid_attributes ) );

		$attributes_html = '';
		foreach ( $attributes as $key => $value ) {
			$attributes_html .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}
		return $attributes_html;
	}

	/**
	 * Merges arrays.
	 *
	 * @param array $array Array.
	 *
	 * @return array $array Merged Array.
	 */
	private static function array_flatten( $array ) {
		if ( ! is_array( $array ) ) {
			return $array;
		}
		foreach ( $array as $key => $value ) {
			$array[ $key ] = (array) $value;
		}
		return call_user_func_array( 'array_merge', $array );
	}

}
