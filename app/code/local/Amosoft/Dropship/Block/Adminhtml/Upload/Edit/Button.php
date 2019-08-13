<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_Upload_Edit_Button extends Varien_Data_Form_Element_Abstract {

	protected $_element;
 
 public function getElementHtml()
 {
 	$enable = ($this->getDisabled()) ? 'disabled' : "";
 	$required = ($this->getRequired()) ? 'required-entry' : "";
 	
 	$html = '<button id="'.$this->getId().'" title="'.$this->getTitle().'" type="button" class="scalable save '.$required.' '.$enable.'" onclick="'.$this->getOnclick().'" '.$enable.'><span><span><span>'.$this->getValue().'</span></span></span></button>';
 	$html .= '<div id="email-list"></div>';
 	return $html;
 
 }
 }
