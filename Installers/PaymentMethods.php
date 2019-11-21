<?php

namespace HeidelPayment\Installers;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\PaymentInstaller;

class PaymentMethods implements InstallerInterface
{
    const PAYMENT_NAME_CREDIT_CARD                  = 'heidelCreditCard';
    const PAYMENT_NAME_IDEAL                        = 'heidelIdeal';
    const PAYMENT_NAME_EPS                          = 'heidelEps';
    const PAYMENT_NAME_SOFORT                       = 'heidelSofort';
    const PAYMENT_NAME_FLEXIPAY                     = 'heidelFlexipay';
    const PAYMENT_NAME_PAYPAL                       = 'heidelPaypal';
    const PAYMENT_NAME_GIROPAY                      = 'heidelGiropay';
    const PAYMENT_NAME_INVOICE                      = 'heidelInvoice';
    const PAYMENT_NAME_INVOICE_GUARANTEED           = 'heidelInvoiceGuaranteed';
    const PAYMENT_NAME_INVOICE_FACTORING            = 'heidelInvoiceFactoring';
    const PAYMENT_NAME_SEPA_DIRECT_DEBIT            = 'heidelSepaDirectDebit';
    const PAYMENT_NAME_SEPA_DIRECT_DEBIT_GUARANTEED = 'heidelSepaDirectDebitGuaranteed';
    const PAYMENT_NAME_PRE_PAYMENT                  = 'heidelPrepayment';
    const PAYMENT_NAME_PRZELEWY                     = 'heidelPrzelewy';

    const PROXY_ACTION_FOR_REDIRECT_PAYMENTS = 'Heidelpay/proxy';

    /**
     * Holds an array of information which represent a payment method used in Shopware.
     *
     * @see \Shopware\Models\Payment\Payment
     */
    const PAYMENT_METHODS = [
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
            'description'           => 'FlexiPay Direct (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'FlexiPay Direct Zahlungen mit Heidelpay',
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
            'description'           => 'giropay (heidelpay)',
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
        [
            'name'                  => self::PAYMENT_NAME_INVOICE_GUARANTEED,
            'description'           => 'Rechnung (gesichert, heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Rechnung (gesichert) mit Heidelpay',
            'embedIFrame'           => 'invoice_guaranteed.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE_FACTORING,
            'description'           => 'Rechnung (factoring, heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Rechnung (factoring) mit Heidelpay',
            'embedIFrame'           => 'invoice_factoring.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT,
            'description'           => 'SEPA Lastschrift (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'SEPA Lastschrift Zahlungen mit Heidelpay',
            'embedIFrame'           => 'sepa_direct_debit.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT_GUARANTEED,
            'description'           => 'SEPA Lastschrift (gesichert, heidelpay)',
            'active'                => true,
            'additionalDescription' => 'SEPA Lastschrift Zahlungen (gesichert) mit Heidelpay',
            'embedIFrame'           => 'sepa_direct_debit_guaranteed.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_PRE_PAYMENT,
            'description'           => 'Vorkasse (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Zahlung auf Vorkasse mit Heidelpay',
            'action'                => self::PROXY_ACTION_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_PRZELEWY,
            'description'           => 'Przelewy 24 (Heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Przelewy 24 Zahlungen mit Heidelpay',
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
    public function install()
    {
        $paymentInstaller = new PaymentInstaller($this->modelManager);

        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            //Prevent overwriting changes made by a customer.
            if ($this->hasPaymentMethod($paymentMethod['name'])) {
                continue;
            }

            $paymentInstaller->createOrUpdate('_HeidelPayment', $paymentMethod);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if (!$this->hasPaymentMethod($paymentMethod['name'])) {
                continue;
            }

            $paymentInstaller = new PaymentInstaller($this->modelManager);
            $paymentInstaller->createOrUpdate('_HeidelPayment', [
                'name'   => $paymentMethod['name'],
                'active' => false,
            ]);
        }
    }

    public function update(string $oldVersion, string $newVersion)
    {
        //No updates yet.This would be a good spot for adding new payment methods to the database.
    }

    private function hasPaymentMethod(string $name): bool
    {
        return $this->modelManager->getDBALQueryBuilder()->select('id')
            ->from('s_core_paymentmeans')
            ->where('name = :name')
            ->setParameter('name', $name)
            ->execute()->fetchColumn() > 0;
    }
}
