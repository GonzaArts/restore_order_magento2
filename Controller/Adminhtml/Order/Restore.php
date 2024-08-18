<?php
namespace Bluenty\RestoreOrder\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Controller\Result\RedirectFactory;
use Psr\Log\LoggerInterface;

class Restore extends Action
{
    protected $orderRepository;
    protected $invoiceService;
    protected $transaction;
    protected $resultRedirectFactory;
    protected $logger;

    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        RedirectFactory $resultRedirectFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id'); 
        
        // Log to check if the order_id is being received correctly
        $this->logger->info('Restore Order - Received order_id: ' . $orderId);

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Order ID is missing.'));
            return $this->resultRedirectFactory->create()->setPath('sales/order/index');
        }

        try {
            $order = $this->orderRepository->get($orderId);
            if (!$order->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order not found.'));
            }

            // Order restoration logic
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

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not restore the order: ' . $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('sales/order/view', ['order_id' => $orderId]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluenty_RestoreOrder::restore_order');
    }
}
