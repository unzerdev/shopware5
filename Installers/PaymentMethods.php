<?php

declare(strict_types=1);

namespace HeidelPayment\Installers;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\PaymentInstaller;

class PaymentMethods implements InstallerInterface
{
    public const PAYMENT_NAME_ALIPAY                       = 'heidelAlipay';
    public const PAYMENT_NAME_CREDIT_CARD                  = 'heidelCreditCard';
    public const PAYMENT_NAME_EPS                          = 'heidelEps';
    public const PAYMENT_NAME_FLEXIPAY                     = 'heidelFlexipay';
    public const PAYMENT_NAME_GIROPAY                      = 'heidelGiropay';
    public const PAYMENT_NAME_IDEAL                        = 'heidelIdeal';
    public const PAYMENT_NAME_INVOICE                      = 'heidelInvoice';
    public const PAYMENT_NAME_INVOICE_FACTORING            = 'heidelInvoiceFactoring';
    public const PAYMENT_NAME_INVOICE_GUARANTEED           = 'heidelInvoiceGuaranteed';
    public const PAYMENT_NAME_PAYPAL                       = 'heidelPaypal';
    public const PAYMENT_NAME_PRE_PAYMENT                  = 'heidelPrepayment';
    public const PAYMENT_NAME_PRZELEWY                     = 'heidelPrzelewy';
    public const PAYMENT_NAME_SEPA_DIRECT_DEBIT            = 'heidelSepaDirectDebit';
    public const PAYMENT_NAME_SEPA_DIRECT_DEBIT_GUARANTEED = 'heidelSepaDirectDebitGuaranteed';
    public const PAYMENT_NAME_SOFORT                       = 'heidelSofort';
    public const PAYMENT_NAME_WE_CHAT                      = 'heidelWeChat';

    /**
     * Stores a list of all redirect payment methods which should be handled in this controller.
     */
    public const REDIRECT_CONTROLLER_MAPPING = [
        self::PAYMENT_NAME_ALIPAY      => 'HeidelpayAlipay',
        self::PAYMENT_NAME_FLEXIPAY    => 'HeidelpayFlexipay',
        self::PAYMENT_NAME_GIROPAY     => 'HeidelpayGiropay',
        self::PAYMENT_NAME_INVOICE     => 'HeidelpayInvoice',
        self::PAYMENT_NAME_PAYPAL      => 'HeidelpayPaypal',
        self::PAYMENT_NAME_PRE_PAYMENT => 'HeidelpayPrepayment',
        self::PAYMENT_NAME_PRZELEWY    => 'HeidelpayPrzelewy',
        self::PAYMENT_NAME_WE_CHAT     => 'HeidelpayWeChat',
        self::PAYMENT_NAME_SOFORT      => 'HeidelpaySofort',
    ];

    public const RECURRING_CONTROLLER_MAPPING = [
        self::PAYMENT_NAME_CREDIT_CARD       => 'HeidelpayCreditCard',
        self::PAYMENT_NAME_PAYPAL            => self::REDIRECT_CONTROLLER_MAPPING[self::PAYMENT_NAME_PAYPAL],
        self::PAYMENT_NAME_SEPA_DIRECT_DEBIT => 'HeidelpaySepaDirectDebit',
    ];

    public const IS_B2B_ALLOWED = [
        self::PAYMENT_NAME_INVOICE_FACTORING,
        self::PAYMENT_NAME_INVOICE_GUARANTEED,
        self::PAYMENT_NAME_SEPA_DIRECT_DEBIT_GUARANTEED,
    ];

    private const PROXY_FOR_REDIRECT_PAYMENTS = 'HeidelpayProxy';

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
            'additionalDescription' => 'Kreditkartenzahlung mit heidelpay',
            'embedIFrame'           => 'credit_card.tpl',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_IDEAL,
            'description'           => 'iDEAL (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'iDEAL mit heidelpay',
            'embedIFrame'           => 'ideal.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_SOFORT,
            'description'           => 'Sofort (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'SOFORT Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_FLEXIPAY,
            'description'           => 'FlexiPay® Direct (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'FlexiPay Direct Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_EPS,
            'description'           => 'EPS (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'EPS mit heidelpay',
            'embedIFrame'           => 'eps.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_PAYPAL,
            'description'           => 'PayPal (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'PayPal mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_GIROPAY,
            'description'           => 'giropay (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'giropay Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE,
            'description'           => 'Rechnung (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Rechnung mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE_GUARANTEED,
            'description'           => 'FlexiPay® Rechnung (gesichert, heidelpay)',
            'active'                => true,
            'additionalDescription' => 'FlexiPay® Rechnung (gesichert) mit heidelpay',
            'embedIFrame'           => 'invoice_guaranteed.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE_FACTORING,
            'description'           => 'FlexiPay® Rechnung (factoring, heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Rechnung (factoring) mit heidelpay',
            'embedIFrame'           => 'invoice_factoring.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT,
            'description'           => 'SEPA Lastschrift (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'SEPA Lastschrift Zahlungen mit heidelpay',
            'embedIFrame'           => 'sepa_direct_debit.tpl',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT_GUARANTEED,
            'description'           => 'FlexiPay® Lastschrift (gesichert, heidelpay)',
            'active'                => true,
            'additionalDescription' => 'FlexiPay® Lastschrift Zahlungen (gesichert) mit heidelpay',
            'embedIFrame'           => 'sepa_direct_debit_guaranteed.tpl',
        ],
        [
            'name'                  => self::PAYMENT_NAME_PRE_PAYMENT,
            'description'           => 'Vorkasse (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Zahlung auf Vorkasse mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_PRZELEWY,
            'description'           => 'Przelewy 24 (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Przelewy 24 Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_WE_CHAT,
            'description'           => 'WeChat (heidelpay)',
            'active'                => true,
            'additionalDescription' => 'WeChat Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_ALIPAY,
            'description'           => 'Alipay (Heidelpay)',
            'active'                => true,
            'additionalDescription' => 'Alipay Zahlungen mit Heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
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
            //Prevent overwriting changes made by a customer.
            if ($this->hasPaymentMethod($paymentMethod['name'])) {
                //Set the active flag anyway, otherwise all payment methods remain inactive when reinstalling the plugin.
                $paymentInstaller->createOrUpdate('_HeidelPayment', [
                    'name'   => $paymentMethod['name'],
                    'active' => true,
                ]);
            }

            $paymentInstaller->createOrUpdate('_HeidelPayment', $paymentMethod);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void
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

    public function update(string $oldVersion, string $newVersion): void
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
