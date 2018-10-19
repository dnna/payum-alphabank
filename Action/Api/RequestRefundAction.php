<?php

namespace Dnna\Payum\AlphaBank\Action\Api;

use Dnna\Payum\AlphaBank\Request\Api\CreateCharge;
use Dnna\Payum\AlphaBank\Request\Api\RequestRefund;
use Dnna\Payum\AlphaBank\Util\DigestCalculator;
use Dnna\Payum\AlphaBank\Util\XMLHandler;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Security\SensitiveValue;
use Payum\Core\Exception\RequestNotSupportedException;

class RequestRefundAction extends BaseApiAwareAction
{
    protected $mid;
    protected $sharedSecretKey;

    public function __construct($mid, $sharedSecretKey)
    {
        $this->mid = $mid;
        $this->sharedSecretKey = $sharedSecretKey;
        parent::__construct();
    }

    public function execute($request): void
    {
        /** @var $request CreateCharge */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($model['status'] === 'REFUNDED') {
            return;
        }
        if ($model['status'] !== 'CAPTURED') {
            throw new \DomainException('Cannot refund a non-captured transaction');
        }

        if (isset($model['mid'])) {
            $mid = $model['mid'];
        } elseif ($this->mid != null) {
            $mid = $this->mid;
        } else {
            throw new \LogicException('mid must be specified in the payment model or the factory');
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

        $requests = [
            'RefundRequest' => 'RefundResponse',
            'CancelRequest' => 'CancelResponse',
        ];
        foreach ($requests as $curRequestType => $curResponseType) {
            $data = [
                'VPOS' => [
                    '_attributes' => ['xmlns' => 'http://www.modirum.com/schemas'],
                    'Message' => [
                        '_attributes' => [
                            'xmlns' => 'http://www.modirum.com/schemas',
                            'messageId' => uniqid('refund', true),
                            'version' => '1.0',
                        ],
                        $curRequestType => [
                            'Authentication' => [
                                'Mid' => $mid,
                            ],
                            'OrderInfo' => [
                                'OrderId' => $model['hashedOrderid'],
                                'OrderAmount' => $model['orderAmount'],
                                'Currency' => $model['currency'],
                            ],
                        ],
                    ],
                ],
            ];

            $xmlHandler = new XMLHandler();
            $messageXml = $xmlHandler->arrayToXml($data)->Message->asXML();
            $digest = $digestCalculator->calculateDigestForString($messageXml);

            $data['VPOS']['Digest'] = $digest;

            $response = $this->api->sendXMLRequest($xmlHandler->arrayToXml($data)->asXML());
            $responseArray = json_decode(json_encode((array)simplexml_load_string($response->getBody())), true);
            if (isset($responseArray['Message'][$curResponseType]) &&
                $responseArray['Message'][$curResponseType]['TxId'] != 0 &&
                $responseArray['Message'][$curResponseType]['Status'] !== 'ERROR'
            ) {
                $model['status'] = 'REFUNDED';
            }
        }

        if ($model['status'] !== 'REFUNDED') {
            throw new \DomainException('Refund did not complete');
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof RequestRefund &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
