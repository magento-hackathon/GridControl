<?php
/**
 * GridControl observer
 *
 * adminhtml_block_html_before:
 * checks if grid block id is found in gridcontrol config and, if found, pass a reference to the block to the gridcontrol processor
 *
 * eav_collection_abstract_load_before:
 * checks if current blockid is set to add joints and attributes to grid collection
 */
class FireGento_GridControl_Model_Observer
{
    /**
     * observe adminhtml_block_html_before
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function adminhtmlBlockHtmlBefore(Varien_Event_Observer $event)
    {
        $block = $event->getBlock();

        if (in_array($block->getId(), Mage::getSingleton('firegento_gridcontrol/config')->getGridList())) {
            Mage::getModel('firegento_gridcontrol/processor')->processBlock($block);
        }
    }

    /**
     * observes eav_collection_abstract_load_before to add attributes and joins to grid collection
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function eavCollectionAbstractLoadBefore(Varien_Event_Observer $event)
    {
        $columnJoinField = array();

        if (Mage::registry('firegento_gridcontrol_current_block')) {
            $blockId = Mage::registry('firegento_gridcontrol_current_block')->getId();

            /**
             * @var FireGento_GridControl_Model_Config $config
             */
            $config = Mage::getSingleton('firegento_gridcontrol/config');

            // add attributes to eav collection
            if ($event->getCollection() instanceof Mage_Eav_Model_Entity_Collection_Abstract){
                foreach ($config->getCollectionUpdates(FireGento_GridControl_Model_Config::TYPE_ADD_ATTRIBUTE, $blockId) as $entry) {
                    $event->getCollection()->addAttributeToSelect($entry);
                }
            }

            // join attributes to collection
            foreach ($config->getCollectionUpdates(FireGento_GridControl_Model_Config::TYPE_JOIN_ATTRIBUTE, $blockId) as $attribute) {
                $attribute = explode('|', $attribute);
                // 5 parameters needed for joinAttribute()
                if (count($attribute) < 5) {
                    continue;
                }
                try {
                    $event->getCollection()->joinAttribute(
                        $attribute[0],
                        $attribute[1],
                        $attribute[2],
                        (strlen($attribute[3]) ? $attribute[3] :null),
                        $attribute[4]
                    );
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            // join fields to collection
            foreach ($config->getCollectionUpdates(FireGento_GridControl_Model_Config::TYPE_JOIN_FIELD, $blockId) as $field) {
                $field = explode('|', $field);
                // 6 parameters needed for joinField()
                if (count($field) < 6) {
                    continue;
                }
                try {
                    $event->getCollection()->joinField(
                        $field[0],
                        $field[1],
                        $field[2],
                        $field[3],
                        $field[4],
                        $field[5]
                    );
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            // joins to collection
            foreach ($config->getCollectionUpdates(FireGento_GridControl_Model_Config::TYPE_JOIN, $blockId) as $field) {
                try {
                    $event->getCollection()->join(
                        $field['table'],
                        str_replace('{{table}}', '`' . $field['table'] . '`', $field['condition']),
                        $field['field']
                    );
                    $columnJoinField[$field['column']] = $field['field'];
                } catch (Exception $e) {
                    Mage::logException($);
                }
            }

            // update index from join_index (needed for joins)
            foreach (Mage::registry('firegento_gridcontrol_current_block')->getColumns() as $column) {
                if (isset($columnJoinField[$column->getId()])) {
                    $column->setIndex($columnJoinField[$column->getId()]);
                }
            }
        }
    }
}
