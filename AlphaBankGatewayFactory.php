<?php
namespace Dnna\Payum\AlphaBank;

use Dnna\Payum\AlphaBank\Action\AuthorizeAction;
use Dnna\Payum\AlphaBank\Action\CancelAction;
use Dnna\Payum\AlphaBank\Action\ConvertPaymentAction;
use Dnna\Payum\AlphaBank\Action\CaptureAction;
use Dnna\Payum\AlphaBank\Action\RefundAction;
use Dnna\Payum\AlphaBank\Action\StatusAction;
use Dnna\Payum\AlphaBank\Action\Api\CreateChargeAction;
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

            'payum.template.create_charge' => '@DnnaPayumAlphaBank/Action/create_charge.html.twig',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.create_charge' => function (ArrayObject $config) {
                if($config['sandbox'] == true) {
                    $actionUrl = 'https://alpha.test.modirum.com/vpos/shophandlermpi';
                } else {
                    throw new \Exception('Production URL not known yet');
                }
                return new CreateChargeAction($config['payum.template.create_charge'], $actionUrl, $config['mid'], $config['sharedSecretKey']);
            },
            'payum.action.convert_payment' => function (ArrayObject $config) {
                return new ConvertPaymentAction($config['sandbox'], $config['useMasterPass'], $config['cssUrl']);
            },
            'payum.action.status' => new StatusAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'mid' => '',
                'sharedSecretKey' => '',
                'useMasterPass' => false,
                'cssUrl' => '',
                'sandbox' => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['useMasterPass'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }

        $config['payum.paths'] = array_replace([
            'DnnaPayumAlphaBank' => __DIR__.'/Resources/views',
        ], $config['payum.paths'] ?: []);
    }
}
