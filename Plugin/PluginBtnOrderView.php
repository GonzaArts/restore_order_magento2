<?php

namespace Bluenty\RestoreOrder\Plugin;

use Magento\Backend\Model\Url as BackendUrl;
use Magento\Framework\Data\Form\FormKey;
use Psr\Log\LoggerInterface;

class PluginBtnOrderView
{
    protected $_backendUrl;
    protected $_formKey;
    protected $logger;

    public function __construct(
        BackendUrl $backendUrl,
        FormKey $formKey,
        LoggerInterface $logger
    ) {
        $this->_backendUrl = $backendUrl;
        $this->_formKey = $formKey;
        $this->logger = $logger;
    }

    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $subject)
    {
        if ($subject->getOrder()->getState() === \Magento\Sales\Model\Order::STATE_CANCELED) {
            $formKey = $this->_formKey->getFormKey();
            $restoreUrl = $this->_backendUrl->getUrl('restoreorder/order/restore', [
                'order_id' => $subject->getOrderId(),
                'form_key' => $formKey
            ]);

            $this->logger->info('Restore Order URL generated: ' . $restoreUrl);
            
            $subject->addButton(
                'restore_order',
                [
                    'label' => __('Restore Order'),
                    'onclick' => "setLocation('{$restoreUrl}')",
                    'class' => 'restore primary'
                ]
            );
        }
        
        return null;
    }
}
