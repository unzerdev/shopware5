<?php

declare(strict_types=1);

namespace UnzerPayment\Installers;

use Shopware\Bundle\AttributeBundle\Service\DataPersister;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\PaymentInstaller;

class PaymentMethods implements InstallerInterface
{
    public const PAYMENT_PLUGIN_NAME = '_UnzerPayment';

    public const PAYMENT_NAME_ALIPAY                        = 'unzerPaymentAlipay';
    public const PAYMENT_NAME_CREDIT_CARD                   = 'unzerPaymentCreditCard';
    public const PAYMENT_NAME_EPS                           = 'unzerPaymentEps';
    public const PAYMENT_NAME_DIRECT                        = 'unzerPaymentDirect';
    public const PAYMENT_NAME_PAYLATER_DIRECT_DEBIT_SECURED = 'unzerPaylaterDirectDebitSecured';
    public const PAYMENT_NAME_GIROPAY                       = 'unzerPaymentGiropay';
    public const PAYMENT_NAME_PAYLATER_INSTALLMENT          = 'unzerPaymentPaylaterInstallment';
    public const PAYMENT_NAME_INSTALLMENT_SECURED           = 'unzerPaymentInstallmentSecured';
    public const PAYMENT_NAME_IDEAL                         = 'unzerPaymentIdeal';
    public const PAYMENT_NAME_INVOICE                       = 'unzerPaymentInvoice';
    public const PAYMENT_NAME_INVOICE_SECURED               = 'unzerPaymentInvoiceSecured';
    public const PAYMENT_NAME_PAYLATER_INVOICE              = 'unzerPaymentPaylaterInvoice';
    public const PAYMENT_NAME_PAYPAL                        = 'unzerPaymentPaypal';
    public const PAYMENT_NAME_PRE_PAYMENT                   = 'unzerPaymentPrepayment';
    public const PAYMENT_NAME_PRZELEWY                      = 'unzerPaymentPrzelewy';
    public const PAYMENT_NAME_SEPA_DIRECT_DEBIT             = 'unzerPaymentSepaDirectDebit';
    public const PAYMENT_NAME_SEPA_DIRECT_DEBIT_SECURED     = 'unzerPaymentSepaDirectDebitSecured';
    public const PAYMENT_NAME_SOFORT                        = 'unzerPaymentSofort';
    public const PAYMENT_NAME_WE_CHAT                       = 'unzerPaymentWeChat';
    public const PAYMENT_NAME_BANCONTACT                    = 'unzerPaymentBancontact';
    public const PAYMENT_NAME_APPLE_PAY                     = 'unzerPaymentApplePay';

    /**
     * Stores a list of all redirect payment methods which should be handled in this controller.
     */
    public const REDIRECT_CONTROLLER_MAPPING = [
        self::PAYMENT_NAME_ALIPAY                        => 'UnzerPaymentAlipay',
        self::PAYMENT_NAME_DIRECT                        => 'UnzerPaymentDirect',
        self::PAYMENT_NAME_PAYLATER_DIRECT_DEBIT_SECURED => 'UnzerPaylaterDirectDebitSecured',
        self::PAYMENT_NAME_GIROPAY                       => 'UnzerPaymentGiropay',
        self::PAYMENT_NAME_PAYLATER_INSTALLMENT          => 'UnzerPaymentPaylaterInstallment',
        self::PAYMENT_NAME_INSTALLMENT_SECURED           => 'UnzerPaymentInstallmentSecured',
        self::PAYMENT_NAME_INVOICE                       => 'UnzerPaymentInvoice',
        self::PAYMENT_NAME_PAYLATER_INVOICE              => 'UnzerPaymentPaylaterInvoice',
        self::PAYMENT_NAME_PAYPAL                        => 'UnzerPaymentPaypal',
        self::PAYMENT_NAME_PRE_PAYMENT                   => 'UnzerPaymentPrepayment',
        self::PAYMENT_NAME_PRZELEWY                      => 'UnzerPaymentPrzelewy',
        self::PAYMENT_NAME_WE_CHAT                       => 'UnzerPaymentWeChat',
        self::PAYMENT_NAME_SOFORT                        => 'UnzerPaymentSofort',
        self::PAYMENT_NAME_BANCONTACT                    => 'UnzerPaymentBancontact',
        self::PAYMENT_NAME_APPLE_PAY                     => 'UnzerPaymentApplePay',
    ];

    public const RECURRING_CONTROLLER_MAPPING = [
        self::PAYMENT_NAME_CREDIT_CARD       => 'UnzerPaymentCreditCard',
        self::PAYMENT_NAME_PAYPAL            => self::REDIRECT_CONTROLLER_MAPPING[self::PAYMENT_NAME_PAYPAL],
        self::PAYMENT_NAME_SEPA_DIRECT_DEBIT => 'UnzerPaymentSepaDirectDebit',
    ];

    public const IS_B2B_ALLOWED = [
        self::PAYMENT_NAME_INVOICE_SECURED,
        self::PAYMENT_NAME_PAYLATER_INVOICE,
        self::PAYMENT_NAME_SEPA_DIRECT_DEBIT_SECURED,
    ];

    private const PROXY_FOR_REDIRECT_PAYMENTS = 'unzerPaymentProxy';

    /**
     * Holds an array of information which represent a payment method used in Shopware.
     *
     * @see \Shopware\Models\Payment\Payment
     */
    private const PAYMENT_METHODS = [
        [
            'name'                  => self::PAYMENT_NAME_ALIPAY,
            'description'           => 'Alipay (Unzer Payment)',
            'additionalDescription' => 'Alipay Zahlungen mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_CREDIT_CARD,
            'description'           => 'Kreditkarte (Unzer Payment)',
            'additionalDescription' => 'Kreditkartenzahlung mit Unzer',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'credit_card.tpl',
            ],
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_EPS,
            'description'           => 'EPS (Unzer Payment)',
            'additionalDescription' => 'EPS mit Unzer',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'eps.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_DIRECT,
            'description'           => 'Unzer Direct',
            'additionalDescription' => 'Unzer Direct Zahlungen',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_PAYLATER_DIRECT_DEBIT_SECURED,
            'description'           => 'Lastschrift (Unzer Payment)',
            'additionalDescription' => 'Lastschrift',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME          => 'paylater_direct_debit_secured.tpl',
                Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_USAGE => true,
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_GIROPAY,
            'description'           => 'giropay (Unzer Payment)',
            'additionalDescription' => 'giropay Zahlungen mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_PAYLATER_INSTALLMENT,
            'description'           => 'Ratenkauf (Unzer Payment)',
            'additionalDescription' => 'Unzer Ratenkauf',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME          => 'paylater_installment.tpl',
                Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_USAGE => true,
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_INSTALLMENT_SECURED,
            'active'                => false,
            'description'           => 'Unzer Installment Secured (veraltet)',
            'additionalDescription' => 'Unzer Rate (veraltet)',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'installment_secured.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_IDEAL,
            'description'           => 'iDEAL (Unzer Payment)',
            'additionalDescription' => 'iDEAL mit Unzer',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'ideal.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE,
            'active'                => false,
            'description'           => 'Rechnung (Unzer Payment, veraltet)',
            'additionalDescription' => 'Rechnung mit Unzer (veraltet)',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE_SECURED,
            'active'                => false,
            'description'           => 'Unzer Rechnung (gesichert, veraltet)',
            'additionalDescription' => 'Unzer Rechnung (gesichert, veraltet)',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'invoice_secured.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_PAYLATER_INVOICE,
            'description'           => 'Rechnung (Unzer Payment)',
            'additionalDescription' => 'Rechnung mit Unzer',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME          => 'paylater_invoice.tpl',
                Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_USAGE => true,
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_PAYPAL,
            'description'           => 'PayPal (Unzer Payment)',
            'additionalDescription' => 'PayPal mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'paypal.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_PRE_PAYMENT,
            'description'           => 'Vorkasse (Unzer Payment)',
            'additionalDescription' => 'Zahlung auf Vorkasse mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_PRZELEWY,
            'description'           => 'Przelewy 24 (Unzer Payment)',
            'additionalDescription' => 'Przelewy 24 Zahlungen mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT,
            'description'           => 'SEPA Lastschrift (Unzer Payment)',
            'additionalDescription' => 'SEPA Lastschrift Zahlungen mit Unzer',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'sepa_direct_debit.tpl',
            ],
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT_SECURED,
            'active'                => false,
            'description'           => 'SEPA Lastschrift (gesichert, Unzer Payment, veraltet)',
            'additionalDescription' => 'SEPA Lastschrift Zahlungen (gesichert) mit Unzer Payment (veraltet)',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'sepa_direct_debit_secured.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_SOFORT,
            'description'           => 'Sofort (Unzer Payment)',
            'additionalDescription' => 'SOFORT Zahlungen mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_WE_CHAT,
            'description'           => 'WeChat (Unzer Payment)',
            'additionalDescription' => 'WeChat Zahlungen mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_BANCONTACT,
            'description'           => 'Bancontact (Unzer Payment)',
            'additionalDescription' => 'Bancontact Zahlungen mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_APPLE_PAY,
            'description'           => 'Apple Pay (Unzer Payment)',
            'additionalDescription' => 'Apple Pay Zahlungen mit Unzer',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
            'attribute'             => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'apple_pay.tpl',
            ],
        ],
    ];

    private ModelManager $modelManager;

    private DataPersister $dataPersister;

    private PaymentInstaller $paymentInstaller;

    public function __construct(ModelManager $modelManager, DataPersister $dataPersister)
    {
        $this->modelManager  = $modelManager;
        $this->dataPersister = $dataPersister;

        $this->paymentInstaller = new PaymentInstaller($this->modelManager);
    }

    /**
     * {@inheritdoc}
     */
    public function install(): void
    {
        $this->update('', '');
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

            $this->paymentInstaller->createOrUpdate(self::PAYMENT_PLUGIN_NAME, [
                'name'   => $paymentMethod['name'],
                'active' => false,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $oldVersion, string $newVersion): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if ($this->hasPaymentMethod($paymentMethod['name'])) {
                $crudPaymentMethod = $this->paymentInstaller->createOrUpdate(self::PAYMENT_PLUGIN_NAME, [
                    'name'                  => $paymentMethod['name'],
                    'description'           => $paymentMethod['description'],
                    'additionalDescription' => $paymentMethod['additionalDescription'],
                    'embedIFrame'           => '',
                    'attribute'             => $paymentMethod['attribute'] ?? '',
                    'active'                => $paymentMethod['active'] ?? true,
                ]);
            } else {
                $crudPaymentMethod = $this->paymentInstaller->createOrUpdate(self::PAYMENT_PLUGIN_NAME, $paymentMethod);
            }

            if ($crudPaymentMethod !== null && array_key_exists('attribute', $paymentMethod)) {
                $this->dataPersister->persist($paymentMethod['attribute'], 's_core_paymentmeans_attributes', $crudPaymentMethod->getId());
            }
        }
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
