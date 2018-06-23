<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Sorting
 */
class Amasty_Sorting_Model_Observer
{
    public function onCoreBlockAbstractToHtmlBefore($observer)
    {
        $block = $observer->getBlock();
        $searchBlocks = array();
        $searchBlocks[] = Mage::getConfig()->getBlockClassName('catalogsearch/result');
        $searchBlocks[] = Mage::getConfig()->getBlockClassName('catalogsearch/advanced_result');
        $searchBlocks[] = Mage::getConfig()->getBlockClassName('searchindex/results'); // compatibility with the `Mirasvit: Search Index` extension
        if (in_array(get_class($block), $searchBlocks)) {
            $block->getChild('search_result_list')
                ->setDefaultDirection('desc')
                ->setSortBy(Mage::getStoreConfig('amsorting/general/default_search'));
        }

        return $this;
    }

    public function settingsChanged($observer)
    {
        if ('amsorting' == $observer->getObject()->getSection()) {
            $settings = $observer->getObject()->getData();
            if ($settings['groups']['general']['fields']['use_index']
                && !Mage::getStoreConfig('amsorting/general/use_index')) {
                $indexer = Mage::getSingleton('index/indexer')
                    ->getProcessByCode('amsorting_summary');
                if ($indexer) {
                    $indexer->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
                }
            }
        }
    }
}
