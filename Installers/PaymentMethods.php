<?php

namespace HeidelPayment\Installers;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\PaymentInstaller;

class PaymentMethods implements InstallerInterface
{
    public const PAYMENT_NAME_CREDIT_CARD = 'heidelCreditCard';
    public const PAYMENT_NAME_IDEAL       = 'heidelIdeal';

    /**
     * Holds an array of information which represent a payment method used in Shopware.
     *
     * @see \Shopware\Models\Payment\Payment
     */
    /*private*/ public const PAYMENT_METHODS = [
        [
            'name'                  => self::PAYMENT_NAME_CREDIT_CARD,
            'description'           => 'Heidelpay (Kreditkarte)',
            'active'                => true,
            'additionalDescription' => 'Kreditkartenzahlung mit Heidelpay',
            'embedIFrame'           => 'credit_card.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_IDEAL,
            'description'           => 'Heidelpay (iDEAL)',
            'active'                => true,
            'additionalDescription' => 'iDEAL mit Heidelpay',
            'embedIFrame'           => 'ideal.tpl',
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
