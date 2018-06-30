<?php
namespace Dnna\Payum\AlphaBank;

use Dnna\Payum\AlphaBank\Action\AuthorizeAction;
use Dnna\Payum\AlphaBank\Action\CancelAction;
use Dnna\Payum\AlphaBank\Action\ConvertPaymentAction;
use Dnna\Payum\AlphaBank\Action\CaptureAction;
use Dnna\Payum\AlphaBank\Action\NotifyAction;
use Dnna\Payum\AlphaBank\Action\RefundAction;
use Dnna\Payum\AlphaBank\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class AlphaBankGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'alphabank',
            'payum.factory_title' => 'Alpha Bank',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'sandbox' => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
