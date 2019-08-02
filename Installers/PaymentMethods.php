<?php

namespace HeidelPayment\Installers;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\PaymentInstaller;

class PaymentMethods implements InstallerInterface
{
    public const PAYMENT_NAME_CREDIT_CARD = 'heidelCreditCard';
    public const PAYMENT_NAME_IDEAL       = 'heidelIdeal';
    public const PAYMENT_NAME_EPS         = 'heidelEps';
    public const PAYMENT_NAME_SOFORT      = 'heidelSofort';
    public const PAYMENT_NAME_FLEXIPAY    = 'heidelFlexipay';
    public const PAYMENT_NAME_INVOICE     = 'heidelInvoice';
    public const PAYMENT_NAME_PAYPAL      = 'heidelPaypal';
    public const PAYMENT_NAME_GIROPAY     = 'heidelGiropay';

    private const PROXY_ACTION_FOR_REDIRECT_PAYMENTS = 'Heidelpay/proxy';

    /**
     * Holds an array of information which represent a payment method used in Shopware.
     *
     * @see \Shopware\Models\Payment\Payment
     */
    private const PAYMENT_METHODS = [
        [
            'name'                  => self::PAYMENT_NAME_CREDIT_CARD,
            'description'           => 'Kreditkarte (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Kreditkartenzahlung mit Heidelpay',
            'embedIFrame'           => 'credit_card.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_IDEAL,
            'description'           => 'iDEAL (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'iDEAL mit Heidelpay',
            'embedIFrame'           => 'ideal.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_SOFORT,
            'description'           => 'SOFORT (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'SOFORT Zahlungen mit Heidelpay',
            'action'                => self::PROXY_ACTION_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_FLEXIPAY,
            'description'           => 'Flexipay (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Flexipay Zahlungen mit Heidelpay',
            'action'                => self::PROXY_ACTION_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_EPS,
            'description'           => 'EPS (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'EPS mit Heidelpay',
            'embedIFrame'           => 'eps.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_PAYPAL,
            'description'           => 'PayPal (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'PayPal mit Heidelpay',
            'action'                => self::PROXY_ACTION_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_GIROPAY,
            'description'           => 'Giropay (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Giropay Zahlungen mit Heidelpay',
            'action'                => self::PROXY_ACTION_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE,
            'description'           => 'Rechnung (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Rechnung mit Heidelpay',
            'action'                => self::PROXY_ACTION_FOR_REDIRECT_PAYMENTS,
        ],
    ];

    /** @var ModelManager */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function install(): void
    {
        $paymentInstaller = new PaymentInstaller($this->modelManager);
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $paymentInstaller->createOrUpdate('HeidelPayment', $paymentMethod);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $paymentInstaller = new PaymentInstaller($this->modelManager);
            $paymentInstaller->createOrUpdate('HeidelPayment', [
                'name'   => $paymentMethod['name'],
                'active' => false,
            ]);
        }
    }

    public function update(string $oldVersion, string $newVersion): void
    {
        //No updates yet.This would be a good spot for adding new payment methods to the database.
    }
}
