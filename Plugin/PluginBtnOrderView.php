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
        $order = $subject->getOrder();
        $formKey = $this->_formKey->getFormKey();

        if ($order->getState() === \Magento\Sales\Model\Order::STATE_CANCELED) {
            // URL to restore order in case of payment failure
            $restorePaymentFailureUrl = $this->_backendUrl->getUrl('restoreorder/order/restore', [
                'order_id' => $order->getId(),
                'form_key' => $formKey
            ]);

            // URL to restore canceled order
            $restoreCanceledUrl = $this->_backendUrl->getUrl('restoreorder/order/restorecanceled', [
                'order_id' => $order->getId(),
                'form_key' => $formKey
            ]);

            $this->logger->info('Restore Payment Failure URL generated: ' . $restorePaymentFailureUrl);
            $this->logger->info('Restore Canceled Order URL generated: ' . $restoreCanceledUrl);

            // Add button with drop-down menu
            $subject->addButton(
                'restore_order',
                [
                    'label' => __('Restore Order'),
                    'class' => 'restore primary',
                    'onclick' => '',
                    'class_name' => \Magento\Backend\Block\Widget\Button\SplitButton::class,
                    'options' => [
                        'restore_payment_failure' => [
                            'label' => __('Restore Order (Payment Failure)'),
                            'onclick' => "setLocation('{$restorePaymentFailureUrl}')",
                        ],
                        'restore_canceled_order' => [
                            'label' => __('Restore Canceled Order'),
                            'onclick' => "setLocation('{$restoreCanceledUrl}')",
                        ],
                    ],
                ]
            );
        }
    }
}
