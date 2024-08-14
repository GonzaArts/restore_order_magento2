<?php
namespace Bluenty\RestoreOrder\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\RedirectFactory;

class Restore extends Action implements HttpGetActionInterface
{
    protected $orderRepository;
    protected $invoiceService;
    protected $transaction;
    protected $registry;
    protected $resultRedirectFactory;

    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        Registry $registry,
        RedirectFactory $resultRedirectFactory
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->registry = $registry;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('id');
        $order = $this->orderRepository->get($orderId);

        if ($order->getId()) {
            $this->registry->register('isSecureArea', true);

            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                  ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);

            foreach ($order->getAllItems() as $item) {
                $item->setQtyCanceled(0);
                $item->setQtyInvoiced(0);
                $item->setQtyShipped(0);
                $item->setQtyRefunded(0);
                $item->setQtyReturned(0);
                $item->setStatus('processing');
                $item->save();
            }

            $order->save();

            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                if (!$invoice->getTotalQty()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Cannot create an invoice without products.'));
                }
                $invoice->register();
                $invoice->pay();
                $invoice->save();

                $transactionSave = $this->transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();

                $this->messageManager->addSuccessMessage(__('Order has been restored and invoice created.'));
            } else {
                $this->messageManager->addErrorMessage(__('Cannot create an invoice for this order.'));
            }

            $this->registry->unregister('isSecureArea');
        } else {
            $this->messageManager->addErrorMessage(__('Order not found.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluenty_RestoreOrder::restore_order');
    }
}
