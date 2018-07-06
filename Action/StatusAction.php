<?php
namespace Dnna\Payum\AlphaBank\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Payum\Stripe\Constants;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());
        if ($model['error']) {
            $request->markFailed();
            return;
        }
        if (false == $model['status']) {
            $request->markNew();
            return;
        }
        if ($model['refunded']) {
            $request->markRefunded();
            return;
        }
        if ('REFUSED' == $model['status'] || 'ERROR' == $model['status']) {
            $request->markFailed();
            return;
        }
        if ('CANCELED' == $model['status']) {
            $request->markCanceled();
            return;
        }
        if ('CAPTURED' == $model['status']) {
            $request->markCaptured();
            return;
        }
        if ('AUTHORIZED' == $model['status']) {
            $request->markAuthorized();
            return;
        }
        $request->markUnknown();
    }
    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}