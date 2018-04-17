<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\AdminNotification\Model\Inbox;

class Notifications extends AbstractHelper
{
    protected $_inbox;

    public function __construct(Inbox $inbox)
    {
        $this->_inbox = $inbox;
    }

    public function createAdminNotification($title, $desc, $url)
    {
        $this->_inbox->addCritical($title, $desc, $url);
        
        return $this;
    }
    
    public function getNotificationsCollection($title, $desc)
    {
        $collection = $this->_inbox->getCollection();
        
        $collection->getSelect()
            ->where('title like "%'.$title.'%" and description like "%'.$desc.'%"')
            ->where('is_read != 1')
            ->where('is_remove != 1');
            
        return $collection;
    }

    public function getLatestMissingKeyNotification()
    {
        $collection = $this->getNotificationsCollection('Trustpilot', 'installation key');

        $collection->getSelect()
            ->order('notification_id DESC')
            ->limit(1);
        
        $items = array_values($collection->getItems());
        return count($items) > 0 ? $items[0] : null;
    }

    public function createMissingKeyNotification()
    {
        $this->createAdminNotification(
            'An invitation to leave a review on Trustpilot has failed due to a missing installation key.',
            'Please enter your installation key in the Trustpilot extension configuration page to complete the integration. You can find your installation key in the Trustpilot Business > Integrations > Apps > Magento integration guide.',
            'https://support.trustpilot.com/hc/en-us/articles/203934298-How-to-Guide-Trustpilot-s-Magento-Application'
        );
    }
}
