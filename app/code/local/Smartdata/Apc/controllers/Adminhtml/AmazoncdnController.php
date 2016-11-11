<?php
/*
** Created By : Abhishek Srivastava <abhishek.srivastava@smartdatainc.net>
** Created At : 10-June-16
** Updated At : 10-June-16
** Description : Controller for handling Amazon request from backend.
*/

require_once(Mage::getBaseDir('lib') . '/AmazonSDK/vendor/autoload.php');

use Aws\S3\S3Client;
use Aws\CloudFront\CloudFrontClient;

class Smartdata_Apc_Adminhtml_AmazoncdnController extends Mage_Adminhtml_Controller_Action
{
    
    protected $api_key;
    protected $api_secret;
    protected $distribution_id;
    protected $invalidation_id;

    public function _construct () {

        $api_key = Mage::getStoreConfig('smartdata_cache/amazoncdn/api_key');
        $api_secret = Mage::getStoreConfig('smartdata_cache/amazoncdn/api_secret');
        $distribution_id = Mage::getStoreConfig('smartdata_cache/amazoncdn/distribution_id');
        $invalidation_id = Mage::getStoreConfig('smartdata_cache/amazoncdn/invalidation_id');

        if(($api_key=='') || ($api_secret=='') || ($distribution_id=='')) {
            Mage::getSingleton('adminhtml/session')->addNotice('Amazon CDN is not properly configured. Please check <a target="_blank" href="'.Mage::helper("adminhtml")->getUrl("adminhtml/amazoncdn/configure").'">this</a>.');
        } else {
            $this->api_key = $api_key;
            $this->api_secret = $api_secret;
            $this->distribution_id = $distribution_id;
            $this->invalidation_id = $invalidation_id;
        }
    }

    public function clearAction()
    {
        if(($this->api_key!='') || ($this->api_secret!='') || ($this->distribution_id!=''))
        {
            $cfClient = CloudFrontClient::factory(array(
                'region'  => 'us-east-1',
                'credentials' => array(
                    'key'    => $this->api_key,
                    'secret' => $this->api_secret,
                )
            ));

            $distributionId = $this->distribution_id;
            $epoch = date('U');
            $CallerReference = $distributionId.$epoch;
            
            try {
                $result = $cfClient->createInvalidation(array(
                    'DistributionId' => $distributionId,
                    'Paths' => array(
                        'Quantity' => 3,
                        'Items' => array('/skin/*', '/media/*', '/js/*' ),
                    ),
                    'CallerReference' => $CallerReference, //This should be unique string.
                ));

                $return = $result->toArray();
                /*Clear cache of 'core_config_data' in magento Otherwise this will not update config data values*/
                Mage::app()->getCacheInstance()->cleanType('config');
                /*End*/
                Mage::getModel('core/config')->saveConfig('smartdata_cache/amazoncdn/invalidation_id', $return['Id']);
                Mage::getSingleton('adminhtml/session')->addSuccess('Amazon CDN invalidation has been created.');
                Mage::getSingleton('adminhtml/session')->addSuccess('Invalidation Id: '.$return['Id']);
                Mage::getSingleton('adminhtml/session')->addSuccess('Status: '.$return['Status']);
                Mage::getSingleton('adminhtml/session')->addSuccess('<a target="_blank" href="https://console.aws.amazon.com/cloudfront/home?region=us-east-1#distribution-settings:'.$this->distribution_id.'">Go To AWS Console</a>');
                //$this->_redirect('adminhtml/cache/index');
            } catch (\Aws\S3\Exception\S3Exception $e) {
                // The AWS error code (e.g., )
                Mage::getSingleton('adminhtml/session')->addError('Something went wrong while flushing Amazon CDN cache. \n Error Code :'.$e->getAwsErrorCode().' \n '.$e->getMessage());
            }
        }
        else
        {
            Mage::getSingleton('adminhtml/session')->addNotice('Amazon CDN is not properly configured. Please check <a target="_blank" href="'.Mage::helper("adminhtml")->getUrl("adminhtml/amazoncdn/configure").'">this</a>.');
        }
        $this->_redirect('adminhtml/cache/index');
    }
    
    public function checkstatusAction()
    {
        try {
            /*echo 'Keys : <br />';
            echo $this->api_key.'<br />';
            echo $this->api_secret.'<br />';
            echo $this->distribution_id.'<br />';
            echo $this->invalidation_id.'<br />';*/

        if(($this->api_key!='') || ($this->api_secret!='') || ($this->distribution_id!='') || ($this->invalidation_id!=''))
        {
            try {
            $cfClient = CloudFrontClient::factory(array(
                'region'  => 'us-east-1',
                'credentials' => array(
                    'key'    => $this->api_key,
                    'secret' => $this->api_secret,
                )
            ));

            $result = $cfClient->getInvalidation(array(
                // DistributionId is required
                'DistributionId' => $this->distribution_id,
                // Id is required
                'Id' => $this->invalidation_id,
            ));

            $return = $result->toArray();
            /*echo $return['CreateTime'].'<br />';*/
            //echo 'Status:'.$return['Status'].' Create Time:'.date("l jS, F Y h:i:s A", strtotime($return['CreateTime'])).' Invalidation Id: '.$return['Id'];die;
            Mage::getSingleton('adminhtml/session')->addNotice('Invalidation Id: '.$return['Id']);
            Mage::getSingleton('adminhtml/session')->addNotice('Status: '.$return['Status']);
            Mage::getSingleton('adminhtml/session')->addNotice('Create Time: '.date("l jS, F Y h:i:s A", strtotime($return['CreateTime'])));
            Mage::getSingleton('adminhtml/session')->addNotice('<a target="_blank" href="https://console.aws.amazon.com/cloudfront/home?region=us-east-1#distribution-settings:'.$this->distribution_id.'">Go To AWS Console</a>');
            } catch (\Aws\S3\Exception\S3Exception $e) {
                // The AWS error code (e.g., )
                Mage::getSingleton('adminhtml/session')->addError('Something went wrong while flushing Amazon CDN cache. \n Error Code :'.$e->getAwsErrorCode().' \n '.$e->getMessage());
            }
            
        } else { //End - If
            Mage::getSingleton('adminhtml/session')->addError('Something went wrong while flushing Amazon CDN cache.');
        }
    } catch (Exception $ex) {
        echo $ex->getMessage();die;
    }
    $this->_redirect('adminhtml/cache/index');
    }

    protected function _isAllowed()
    {
        return true;
    }
}
