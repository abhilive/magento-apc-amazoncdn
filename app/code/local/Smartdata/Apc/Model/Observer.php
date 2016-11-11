<?php

/*
 * The "observer" model.
 *
 */

class Smartdata_Apc_Model_Observer {

    public function clearApc() {
        return function_exists('apc_clear_cache') && apc_clear_cache() && apc_clear_cache('user') && apc_clear_cache('opcode');
    }

    public function injectHtml(Varien_Event_Observer $observer) {
        $block  = $observer->getBlock();

        if($block instanceof Mage_Adminhtml_Block_Cache_Additional) {
            $transport = $observer->getTransport();

            //For APC Cache
            $insert =
                '<tr>
                    <td class="scope-label">
                        <button onclick="setLocation(\'' . Mage::helper('adminhtml')->getUrl('adminhtml/clearapc/index') . '\')" type="button" class="scalable">
                            <span>' . Mage::helper('adminhtml')->__('Flush APC Cache') . '</span>
                        </button>
                    </td>
                    <td class="scope-label">' . Mage::helper('adminhtml')->__('APC user and system cache.') . '</td>
                </tr>';
            //For Amazon CDN
            $insert .=
                '<tr>
                    <td class="scope-label">
                        <button onclick="setLocation(\'' . Mage::helper('adminhtml')->getUrl('adminhtml/amazoncdn/clear') . '\')" type="button" class="scalable">
                            <span>' . Mage::helper('adminhtml')->__('Run Amazon CDN Invalidation') . '</span>
                        </button>
                    </td>
                    <td class="scope-label">
                        <button onclick="setLocation(\'' . Mage::helper('adminhtml')->getUrl('adminhtml/amazoncdn/checkstatus') . '\')" type="button" class="scalable">
                            <span>' . Mage::helper('adminhtml')->__('Check Status Of Last Invalidation') . '</span>
                        </button>
                    </td>
                    <td class="scope-label">' . Mage::helper('adminhtml')->__('Amazon CDN Cache for Skin/Js/Media files.') . '</td>
                </tr>';

            $dom = new DOMDocument();

            $dom->loadHTML($transport->getHtml());

            $td = $dom->createDocumentFragment();
            $td->appendXML($insert);

            $dom->getElementsByTagName('table')->item(1)->insertBefore($td, $dom->getElementsByTagName('table')->item(1)->firstChild);

            $transport->setHtml($dom->saveHTML());
        }
    }
}
