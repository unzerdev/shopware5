<?php

declare(strict_types=1);

namespace UnzerPayment\Installers;

use Shopware\Bundle\AttributeBundle\Service\DataPersister;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\PaymentInstaller;

class PaymentMethods implements InstallerInterface
{
    public const PAYMENT_PLUGIN_NAME = '_UnzerPayment';

    public const PAYMENT_NAME_ALIPAY = 'unzerPaymentAlipay';
    public const PAYMENT_NAME_CREDIT_CARD = 'unzerPaymentCreditCard';
    public const PAYMENT_NAME_EPS = 'unzerPaymentEps';
    public const PAYMENT_NAME_DIRECT = 'unzerPaymentDirect';
    public const PAYMENT_NAME_PAYLATER_DIRECT_DEBIT_SECURED = 'unzerPaylaterDirectDebitSecured';
    public const PAYMENT_NAME_GIROPAY = 'unzerPaymentGiropay';
    public const PAYMENT_NAME_PAYLATER_INSTALLMENT = 'unzerPaymentPaylaterInstallment';
    public const PAYMENT_NAME_INSTALLMENT_SECURED = 'unzerPaymentInstallmentSecured';
    public const PAYMENT_NAME_IDEAL = 'unzerPaymentIdeal';
    public const PAYMENT_NAME_INVOICE = 'unzerPaymentInvoice';
    public const PAYMENT_NAME_INVOICE_SECURED = 'unzerPaymentInvoiceSecured';
    public const PAYMENT_NAME_PAYLATER_INVOICE = 'unzerPaymentPaylaterInvoice';
    public const PAYMENT_NAME_PAYPAL = 'unzerPaymentPaypal';
    public const PAYMENT_NAME_PRE_PAYMENT = 'unzerPaymentPrepayment';
    public const PAYMENT_NAME_PRZELEWY = 'unzerPaymentPrzelewy';
    public const PAYMENT_NAME_SEPA_DIRECT_DEBIT = 'unzerPaymentSepaDirectDebit';
    public const PAYMENT_NAME_SEPA_DIRECT_DEBIT_SECURED = 'unzerPaymentSepaDirectDebitSecured';
    public const PAYMENT_NAME_SOFORT = 'unzerPaymentSofort';
    public const PAYMENT_NAME_WE_CHAT = 'unzerPaymentWeChat';
    public const PAYMENT_NAME_BANCONTACT = 'unzerPaymentBancontact';
    public const PAYMENT_NAME_APPLE_PAY = 'unzerPaymentApplePay';
    public const PAYMENT_NAME_GOOGLE_PAY = 'unzerPaymentGooglePay';
    public const PAYMENT_NAME_TWINT = 'unzerPaymentTwint';

    /**
     * Stores a list of all redirect payment methods which should be handled in this controller.
     */
    public const REDIRECT_CONTROLLER_MAPPING = [
        self::PAYMENT_NAME_ALIPAY => 'UnzerPaymentAlipay',
        self::PAYMENT_NAME_DIRECT => 'UnzerPaymentDirect',
        self::PAYMENT_NAME_PAYLATER_DIRECT_DEBIT_SECURED => 'UnzerPaylaterDirectDebitSecured',
        self::PAYMENT_NAME_GIROPAY => 'UnzerPaymentGiropay',
        self::PAYMENT_NAME_PAYLATER_INSTALLMENT => 'UnzerPaymentPaylaterInstallment',
        self::PAYMENT_NAME_INSTALLMENT_SECURED => 'UnzerPaymentInstallmentSecured',
        self::PAYMENT_NAME_INVOICE => 'UnzerPaymentInvoice',
        self::PAYMENT_NAME_PAYLATER_INVOICE => 'UnzerPaymentPaylaterInvoice',
        self::PAYMENT_NAME_PAYPAL => 'UnzerPaymentPaypal',
        self::PAYMENT_NAME_PRE_PAYMENT => 'UnzerPaymentPrepayment',
        self::PAYMENT_NAME_PRZELEWY => 'UnzerPaymentPrzelewy',
        self::PAYMENT_NAME_WE_CHAT => 'UnzerPaymentWeChat',
        self::PAYMENT_NAME_SOFORT => 'UnzerPaymentSofort',
        self::PAYMENT_NAME_BANCONTACT => 'UnzerPaymentBancontact',
        self::PAYMENT_NAME_APPLE_PAY => 'UnzerPaymentApplePay',
        self::PAYMENT_NAME_GOOGLE_PAY => 'UnzerPaymentGooglePay',
        self::PAYMENT_NAME_TWINT => 'UnzerPaymentTwint',
        self::PAYMENT_NAME_EPS => 'UnzerPaymentEps',
    ];

    public const RECURRING_CONTROLLER_MAPPING = [
        self::PAYMENT_NAME_CREDIT_CARD => 'UnzerPaymentCreditCard',
        self::PAYMENT_NAME_PAYPAL => self::REDIRECT_CONTROLLER_MAPPING[self::PAYMENT_NAME_PAYPAL],
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
            'name' => self::PAYMENT_NAME_ALIPAY,
            'description' => 'Alipay',
            'additionalDescription' => 'Alipay mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_CREDIT_CARD,
            'description' => 'Kreditkarte',
            'additionalDescription' => 'Kreditkarte mit Unzer',
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'credit_card.tpl',
            ],
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_EPS,
            'description' => 'EPS',
            'additionalDescription' => 'EPS mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_DIRECT,
            'description' => '(Veraltet) Banküberweisung',
            'additionalDescription' => '(Veraltet) Banküberweisung mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_PAYLATER_DIRECT_DEBIT_SECURED,
            'description' => 'Lastschrift',
            'additionalDescription' => 'Lastschrift mit Unzer',
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'paylater_direct_debit_secured.tpl',
                Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_USAGE => true,
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_PAYLATER_INSTALLMENT,
            'description' => 'Ratenkauf',
            'additionalDescription' => 'Ratenkauf mit Unzer',
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'paylater_installment.tpl',
                Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_USAGE => true,
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_INSTALLMENT_SECURED,
            'active' => false,
            'description' => '(Veraltet) Ratenkauf',
            'additionalDescription' => '(Veraltet) Ratenkauf mit Unzer',
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'installment_secured.tpl',
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_IDEAL,
            'description' => 'iDEAL',
            'additionalDescription' => 'iDEAL mit Unzer',
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'ideal.tpl',
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_INVOICE,
            'active' => false,
            'description' => '(Veraltet) Rechnungskauf',
            'additionalDescription' => '(Veraltet) Rechnungskauf mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_INVOICE_SECURED,
            'active' => false,
            'description' => '(Veraltet) Rechnungskauf Gesichert',
            'additionalDescription' => '(Veraltet) Rechnungskauf Gesichert mit Unzer',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'invoice_secured.tpl',
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_PAYLATER_INVOICE,
            'description' => 'Rechnungskauf',
            'additionalDescription' => 'Rechnungskauf mit Unzer',
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'paylater_invoice.tpl',
                Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_USAGE => true,
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_PAYPAL,
            'description' => 'PayPal',
            'additionalDescription' => 'PayPal mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'paypal.tpl',
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_PRE_PAYMENT,
            'description' => 'Vorkasse',
            'additionalDescription' => 'Vorkasse mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_PRZELEWY,
            'description' => 'Przelewy 24',
            'additionalDescription' => 'Przelewy 24 mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT,
            'description' => 'SEPA Lastschrift',
            'additionalDescription' => 'SEPA Lastschrift mit Unzer',
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'sepa_direct_debit.tpl',
            ],
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT_SECURED,
            'active' => false,
            'description' => '(Veraltet) SEPA Lastschrift Gesichert',
            'additionalDescription' => '(Veraltet) SEPA Lastschrift Gesichert mit Unzer',
            'embedIFrame' => '',
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'sepa_direct_debit_secured.tpl',
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_SOFORT,
            'description' => 'Sofort',
            'additionalDescription' => 'Sofort mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_WE_CHAT,
            'description' => 'WeChat Pay',
            'additionalDescription' => 'WeChat Pay mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_BANCONTACT,
            'description' => 'Bancontact',
            'additionalDescription' => 'Bancontact mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name' => self::PAYMENT_NAME_APPLE_PAY,
            'description' => 'Apple Pay',
            'additionalDescription' => 'Apple Pay mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'apple_pay.tpl',
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_GOOGLE_PAY,
            'description' => 'Google Pay',
            'additionalDescription' => 'Google Pay mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
            'attribute' => [
                Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME => 'google_pay.tpl',
            ],
        ],
        [
            'name' => self::PAYMENT_NAME_TWINT,
            'description' => 'TWINT',
            'additionalDescription' => 'TWINT mit Unzer',
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
    ];

    private ModelManager $modelManager;

    private DataPersister $dataPersister;

    private PaymentInstaller $paymentInstaller;

    public function __construct(ModelManager $modelManager, DataPersister $dataPersister)
    {
        $this->modelManager = $modelManager;
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
                'name' => $paymentMethod['name'],
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
                    'name' => $paymentMethod['name'],
                    'description' => $paymentMethod['description'],
                    'additionalDescription' => $paymentMethod['additionalDescription'],
                    'embedIFrame' => '',
                    'attribute' => $paymentMethod['attribute'] ?? '',
                    'action' => $paymentMethod['action'] ?? '',
                    'active' => $paymentMethod['active'] ?? true,
                ]);
            } else {
                $crudPaymentMethod = $this->paymentInstaller->createOrUpdate(self::PAYMENT_PLUGIN_NAME, $paymentMethod);
            }

            if ($crudPaymentMethod !== null && array_key_exists('attribute', $paymentMethod)) {
                $this->dataPersister->persist($paymentMethod['attribute'], 's_core_paymentmeans_attributes', $crudPaymentMethod->getId());
            }
        }
        $this->deprecateGiropay();

    }

    public function deprecateGiropay(): void
    {
        $this->modelManager->getDBALQueryBuilder()->update('s_core_paymentmeans')
            ->set('active', 0)
            ->set('description', "'Giropay (Unzer Payment, veraltet)'")
            ->where('name = :name')
            ->setParameter('name', self::PAYMENT_NAME_GIROPAY)
            ->execute();
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
