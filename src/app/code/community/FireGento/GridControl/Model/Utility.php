<?php

class FireGento_GridControl_Model_Utility
{
    public function getDropdownAttributeLabelOptionArray($attribute)
    {
        $map = array();

        if (is_array($attribute)) {
            $attribute = reset($attribute);
        }

        if ($attribute) {
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attribute);
            $options = $attribute->getSource()->getAllOptions(false);

            foreach ($options as $option) {
                $map[$option['value']] = $option['label'];
            }
        }

        return $map;
    }
}
