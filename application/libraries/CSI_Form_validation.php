<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @author norlan
 * @version 1.0.0
 * @copyright (c) 2020, Channel Solutions Inc. * 
 * Track No.    DateTime    Author      Description
 * ------------------------------------------------------------------------
 * CFV-0001     09-08-2020  Jezzy       added email format validation
 * CFV-0002     09-12-2020  Jezzy       added min date validation
 */
class CSI_Form_validation extends CI_Form_validation {
public function special_ka($string){
    return preg_match("/[`@!%$&^*()]+/", $string) ;
    
}
    /**
     * At least 1 uppercase letter
     *
     * @param	string $str
     * @return	bool
     */
    public function atleast_one_uppercase($str) {
        return (bool) preg_match('/^(?=.*[A-Z])/', $str);
    }

    /**
     * At least 1 lowercase letter
     *
     * @param	string $str
     * @return	bool
     */
    public function atleast_one_lowercase($str) {
        return (bool) preg_match('/^(?=.*[a-z])/', $str);
    }

    /**
     * At least 1 number
     *
     * @param	string $str
     * @return	bool
     */
    public function atleast_one_number($str) {
        return (bool) preg_match('/^(?=.*\d)/', $str);
    }

    /**
     * At least 1 non-alphanumeric
     *
     * @param	string $str
     * @return	bool
     */
    public function atleast_one_non_alphanumeric($str) {
        return (bool) preg_match('/^(?=.*\W)/', $str);
    }

    /**
     * alpha_numeric_special
     * 
     * 
     * @param type $str
     * @return bool
     */
    public function alpha_numeric_special($str): bool {
        return (bool) preg_match('/^[a-zA-Z0-9\s!@#$%^&*()_+{}|\\[\\]\\\\:";\'<>?,.\\/~`\\-=]+$/', $str);
    }

    /**
     * alpha_numeric_special without spaces
     * 
     * @param type $str
     * @return bool
     */
    public function alpha_numeric_special_no_spaces($str): bool {
        return (bool) preg_match('/^[a-zA-Z0-9!@#$%^&*()_+{}|\\[\\]\\\\:";\'<>?,.\\/~`\\-=]+$/', $str);
    }
    
    /**
     * alpha_numeric_special with spaces
     * 
     * @param type $str
     * @return bool
     */
    public function alpha_numeric_spaces($str): bool {
        return (bool) preg_match('/^[a-zA-Z0-9 ]*$/', $str);
    }
    
    
      /**
     * alpha_numeric_uscore
     * 
     * @param type $str
     * @return bool
     */
     public function alpha_numeric_uscore($str): bool {
        return (bool) preg_match('/^[a-zA-Z0-9_][^\s]*$/', $str);
    }
    
    

    /**
     * Not match one field to another
     * 
     * @param string $str       string to compare against
     * @param string $field
     * @return bool
     */
    public function not_matches($str, $field) {
        return isset($this->_field_data[$field], $this->_field_data[$field]['postdata']) ? ($str !== $this->_field_data[$field]['postdata']) : FALSE;
    }

    /**
     * File validation rules
     * 
     * @param string $field
     * @param string $config upload configuration rules
     * @return boolean
     */
    public function valid_file_upload($field, $config) {
        if (empty($field)) {
            return TRUE;
        }
        $CI = & get_instance();
        $CI->load->library('upload', json_decode($config, TRUE));
        if (!$CI->upload->do_upload($field)) {
            $this->set_message('valid_file_upload', str_replace('{error}', $CI->upload->display_errors('', ''), lang('form_validation_valid_file_upload')));
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Toggle fields rules
     * 
     * @param string $str
     * @param string $param configuration rules
     * @return boolean
     */
    public function toggle_field_rules($str, $param) {
        $params = json_decode($param, TRUE);
        if ($str === $params['toggle']) {
            foreach ($params['fields'] as $field) {
                $this->_field_data[$field]['rules'] = '';
            }
        }
        return TRUE;
    }

    /**
     * valid email
     *
     * @param	string $str
     * @return	bool
     */
    public function valid_email($str) {
        return (bool) preg_match('/^[\w\-.]+@([\w\-]+\.)+[\w-]{2,4}$/', $str);
    }

    /**
     * Date format validation
     * 
     * @param string $date
     * @return bool
     */
    public function valid_date(string $date): bool {
        if (empty($date)) {
            return TRUE;
        }
        $d1 = DateTime::createFromFormat(DATE_FORMAT, $date);
        if ($d1 === FALSE) {
            return FALSE;
        }
        if ($date === $d1->format(DATE_FORMAT)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Minimum date validation
     * 
     * @param string $date
     * @param string (NULL) $interval
     * @return bool
     */
    public function min_date(string $date, string $interval = NULL): bool {
        if (empty($date)) {
            return TRUE;
        }
        $d1  = DateTime::createFromFormat(DATE_FORMAT, $date);
        $d1->setTime(0, 0, 0, 0);
        $now = new DateTime('now');
        $now->setTime(0, 0, 0, 0);
        if (!empty($interval)) {
            $now->add(new DateInterval($interval));
        }
        if ($d1 < $now) {
            return FALSE;
        }
        return TRUE;
    }
    
    protected function _prepare_rules($rules)
	{
		$new_rules = array();
		$callbacks = array();

		foreach ($rules as &$rule)
		{
			// Let 'required' always be the first (non-callback) rule
			if ($rule === 'required')
			{
				array_unshift($new_rules, 'required');
			}
			// 'isset' is a kind of a weird alias for 'required' ...
			elseif ($rule === 'isset' && (empty($new_rules) OR $new_rules[0] !== 'required'))
			{
				array_unshift($new_rules, 'isset');
			}
			// The old/classic 'callback_'-prefixed rules
			elseif (is_string($rule) && strncmp('callback_', $rule, 9) === 0)
			{
				$callbacks[] = $rule;
			}
			// Proper callables
			elseif (is_callable($rule))
			{
				$callbacks[] = $rule;
			}
			// "Named" callables; i.e. array('name' => $callable)
			elseif (is_array($rule) && isset($rule[0], $rule[1]) && is_callable($rule[1]))
			{
				$callbacks[] = $rule;
			}
			// Everything else goes at the end of the queue
			else
			{
				$new_rules[] = $rule;
			}
		}

		return array_merge($new_rules, $callbacks);
	}


}
