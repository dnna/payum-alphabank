<?php
namespace Dnna\Payum\AlphaBank\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Request\Refund;
use Payum\Core\Exception\RequestNotSupportedException;

use Dnna\Payum\AlphaBank\Request\Api\RequestRefund;

class RefundAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Refund $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($model['status'] === 'REFUNDED') {
            return;
        }
        if ($model['status'] !== 'CAPTURED') {
            throw new \DomainException('Cannot refund a non-captured transaction');
        }

        $requestRefund = new RequestRefund($request->getToken());
        $requestRefund->setModel($model);
        $this->gateway->execute($requestRefund);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Refund &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
