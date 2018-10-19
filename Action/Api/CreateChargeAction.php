<?php

namespace Dnna\Payum\AlphaBank\Action\Api;

use Dnna\Payum\AlphaBank\Request\Api\CreateCharge;
use Dnna\Payum\AlphaBank\Util\DigestCalculator;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Security\SensitiveValue;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\RenderTemplate;

class CreateChargeAction extends BaseApiAwareAction
{
    protected $templateName;
    protected $alphaBankActionUrl;
    protected $mid;
    protected $sharedSecretKey;

    public function __construct($templateName, $alphaBankActionUrl, $mid, $sharedSecretKey)
    {
        $this->templateName = $templateName;
        $this->alphaBankActionUrl = $alphaBankActionUrl;
        $this->mid = $mid;
        $this->sharedSecretKey = $sharedSecretKey;
        parent::__construct();
    }

    public function execute($request): void
    {
        /** @var $request CreateCharge */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($model['status']) {
            throw new \LogicException('The status has already been set.');
        }
        if (!$request->getToken()) {
            throw new \LogicException('Request token is null');
        }

        if (isset($model['sharedSecretKey'])) {
            if ($model['sharedSecretKey'] instanceof SensitiveValue) {
                $digestCalculator = new DigestCalculator($model['sharedSecretKey']->peek());
            } else {
                $digestCalculator = new DigestCalculator($model['sharedSecretKey']);
            }
        } elseif ($this->sharedSecretKey != null) {
            $digestCalculator = new DigestCalculator($this->sharedSecretKey);
        } else {
            throw new \LogicException('sharedSecretKey must be specified in the payment model or the factory');
        }

        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        if ($getHttpRequest->method == 'POST') {
            $newModel = array();
            $newModel['status'] = $getHttpRequest->request['status'];
            if (isset($getHttpRequest->request['paymentTotal'])) {
                $newModel['paymentTotal'] = $getHttpRequest->request['paymentTotal'];
            }
            if (isset($getHttpRequest->request['message'])) {
                $newModel['message'] = $getHttpRequest->request['message'];
            }
            if (isset($getHttpRequest->request['riskScore'])) {
                $newModel['riskScore'] = $getHttpRequest->request['riskScore'];
            }
            if (isset($getHttpRequest->request['payMethod'])) {
                $newModel['payMethod'] = $getHttpRequest->request['payMethod'];
            }
            if (isset($getHttpRequest->request['txId'])) {
                $newModel['txId'] = $getHttpRequest->request['txId'];
            }
            if (isset($getHttpRequest->request['paymentRef'])) {
                $newModel['paymentRef'] = $getHttpRequest->request['paymentRef'];
            }
            if ($digestCalculator->verifyDigest($getHttpRequest->request, $getHttpRequest->request['digest'])) {
                foreach ($newModel as $k => $v) {
                    $model[$k] = $v;
                } // Merge the new attributes into the model
            } else {
                throw new \LogicException('Could not verify digest');
            }

            return;
        }

        if ($model['retries']) {
            $retries = 'R' . $model['retries'];
            $model['retries'] += 1;
        } else {
            $retries = '';
            $model['retries'] = 1;
        }

        $mappedModel = new ArrayObject();
        if (isset($model['mid'])) {
            $mappedModel['mid'] = $model['mid'];
        } elseif ($this->mid != null) {
            $mappedModel['mid'] = $this->mid;
        } else {
            throw new \LogicException('mid must be specified in the payment model or the factory');
        }
        foreach ($model as $k => $v) {
            if (!isset($mappedModel[$k])) {
                $mappedModel[$k] = $v;
            }
        }
        $mappedModel['confirmUrl'] = $request->getToken()->getTargetUrl();
        $mappedModel['cancelUrl'] = $request->getToken()->getTargetUrl();
        $mappedModel['var2'] = $mappedModel['orderid'];

        if (isset($model['custom3'])) {
            $mappedModel['var3'] = $model['custom3'];
            unset($mappedModel['custom3']);
        }

        if (isset($model['custom4'])) {
            $mappedModel['var4'] = $model['custom4'];
            unset($mappedModel['custom4']);
        }

        if (isset($model['custom5'])) {
            $mappedModel['var5'] = $model['custom5'];
            unset($mappedModel['custom5']);
        }


        $mappedModel['orderid'] = md5($mappedModel['orderid'] . 'H' . $request->getToken()->getHash() . $retries);

        if (isset($mappedModel['sharedSecretKey'])) {
            unset($mappedModel['sharedSecretKey']);
        }
        unset($mappedModel['retries']);

        $mappedModel['digest'] = $digestCalculator->calculateDigest($mappedModel);

        $model['hashedOrderid'] = $mappedModel['orderid'];

        $this->gateway->execute(
            $renderTemplate = new RenderTemplate(
                $this->templateName,
                array(
                    'model' => $mappedModel,
                    'actionUrl' => $this->alphaBankActionUrl,
                )
            )
        );
        throw new HttpResponse($renderTemplate->getResult());
    }

    public function supports($request): bool
    {
        return
            $request instanceof CreateCharge &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
