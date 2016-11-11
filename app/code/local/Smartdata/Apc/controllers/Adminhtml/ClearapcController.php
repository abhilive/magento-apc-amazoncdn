<?php
/*
** Created By : Abhishek Srivastava <abhishek.srivastava@smartdatainc.net>
** Created At : 10-June-16
** Updated At : 10-June-16
** Description : Controller for handling APC request from backend.
*/

class Smartdata_Apc_Adminhtml_ClearapcController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        if(function_exists('apc_clear_cache'))
        {
            if(
                Mage::getModel('smartdata_apc/observer')->clearApc()
            )
            {
                Mage::getSingleton('adminhtml/session')->addSuccess('APC cache flushed successfully.');
            }
            else
            {
                Mage::getSingleton('adminhtml/session')->addError('Something went wrong while flushing APC cache.');
            }
            $this->_redirect('adminhtml/cache/index');
        }
        else
        {
            Mage::getSingleton('adminhtml/session')->addNotice('APC is not installed.');
            $this->_redirect('adminhtml/cache/index');
        }
    }
    
    protected function _isAllowed()
    {
        return true;
    }
}
