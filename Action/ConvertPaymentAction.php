<?php

namespace Dnna\Payum\AlphaBank\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentAction implements ActionInterface
{
    use GatewayAwareTrait;

    private $isSandbox;
    private $useMasterPass;
    private $lang;
    private $cssUrl;

    public function __construct(bool $isSandbox, bool $useMasterPass, string $lang, string $cssUrl)
    {
        $this->isSandbox = $isSandbox;
        $this->useMasterPass = $useMasterPass;
        $this->lang = $lang;
        $this->cssUrl = $cssUrl;
    }

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $details['version'] = 2;
        $details['lang'] = $this->lang;
        $details['orderid'] = $payment->getNumber();
        $details['orderDesc'] = $payment->getDescription();
        $details['orderAmount'] = $payment->getTotalAmount() / 100;
        $details['currency'] = $payment->getCurrencyCode();
        $details['payerEmail'] = $payment->getClientEmail();
        if ($this->isSandbox) {
            $details['orderAmount'] = 0.5; // Alpha Bank's sandbox requires <1 EUR
        }
        if ($this->useMasterPass == true) {
            $details['payMethod'] = 'auto:MasterPass';
        }
        if ($this->cssUrl != null) {
            $details['cssUrl'] = $this->cssUrl;
        }

        $request->setResult((array)$details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array';
    }
}
