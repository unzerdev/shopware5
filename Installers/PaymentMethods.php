<?php

declare(strict_types=1);

namespace UnzerPayment\Installers;

use Shopware\Bundle\AttributeBundle\Service\DataPersister;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\PaymentInstaller;

class PaymentMethods implements InstallerInterface
{
    public const PAYMENT_PLUGIN_NAME = '_HeidelPayment';

    public const PAYMENT_NAME_ALIPAY                       = 'heidelAlipay';
    public const PAYMENT_NAME_CREDIT_CARD                  = 'heidelCreditCard';
    public const PAYMENT_NAME_EPS                          = 'heidelEps';
    public const PAYMENT_NAME_FLEXIPAY                     = 'heidelFlexipay';
    public const PAYMENT_NAME_GIROPAY                      = 'heidelGiropay';
    public const PAYMENT_NAME_HIRE_PURCHASE                = 'heidelHirePurchase';
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
        self::PAYMENT_NAME_ALIPAY        => 'HeidelpayAlipay',
        self::PAYMENT_NAME_FLEXIPAY      => 'HeidelpayFlexipayDirect',
        self::PAYMENT_NAME_GIROPAY       => 'HeidelpayGiropay',
        self::PAYMENT_NAME_HIRE_PURCHASE => 'HeidelpayHirePurchase',
        self::PAYMENT_NAME_INVOICE       => 'HeidelpayInvoice',
        self::PAYMENT_NAME_PAYPAL        => 'HeidelpayPaypal',
        self::PAYMENT_NAME_PRE_PAYMENT   => 'HeidelpayPrepayment',
        self::PAYMENT_NAME_PRZELEWY      => 'HeidelpayPrzelewy',
        self::PAYMENT_NAME_WE_CHAT       => 'HeidelpayWeChat',
        self::PAYMENT_NAME_SOFORT        => 'HeidelpaySofort',
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
            'name'                  => self::PAYMENT_NAME_ALIPAY,
            'description'           => 'Alipay (Heidelpay)',
            'additionalDescription' => 'Alipay Zahlungen mit Heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_CREDIT_CARD,
            'description'           => 'Kreditkarte (heidelpay)',
            'additionalDescription' => 'Kreditkartenzahlung mit heidelpay',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'credit_card.tpl',
            ],
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_EPS,
            'description'           => 'EPS (heidelpay)',
            'additionalDescription' => 'EPS mit heidelpay',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'eps.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_FLEXIPAY,
            'description'           => 'FlexiPay® Direct (heidelpay)',
            'additionalDescription' => 'FlexiPay Direct Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_GIROPAY,
            'description'           => 'giropay (heidelpay)',
            'additionalDescription' => 'giropay Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_HIRE_PURCHASE,
            'description'           => 'FlexiPay® Instalment (heidelpay)',
            'additionalDescription' => 'FlexiPay® Rate mit Heidelpay',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'hire_purchase.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_IDEAL,
            'description'           => 'iDEAL (heidelpay)',
            'additionalDescription' => 'iDEAL mit heidelpay',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'ideal.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE,
            'description'           => 'Rechnung (heidelpay)',
            'additionalDescription' => 'Rechnung mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE_FACTORING,
            'description'           => 'FlexiPay® Rechnung (factoring, heidelpay)',
            'additionalDescription' => 'FlexiPay® Rechnung (factoring) mit heidelpay',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'invoice_factoring.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_INVOICE_GUARANTEED,
            'description'           => 'FlexiPay® Rechnung (gesichert, heidelpay)',
            'additionalDescription' => 'FlexiPay® Rechnung (gesichert) mit heidelpay',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'invoice_guaranteed.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_PAYPAL,
            'description'           => 'PayPal (heidelpay)',
            'additionalDescription' => 'PayPal mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'paypal.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_PRE_PAYMENT,
            'description'           => 'Vorkasse (heidelpay)',
            'additionalDescription' => 'Zahlung auf Vorkasse mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_PRZELEWY,
            'description'           => 'Przelewy 24 (heidelpay)',
            'additionalDescription' => 'Przelewy 24 Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT,
            'description'           => 'SEPA Lastschrift (heidelpay)',
            'additionalDescription' => 'SEPA Lastschrift Zahlungen mit heidelpay',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'sepa_direct_debit.tpl',
            ],
            'action' => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_SEPA_DIRECT_DEBIT_GUARANTEED,
            'description'           => 'FlexiPay® Lastschrift (gesichert, heidelpay)',
            'additionalDescription' => 'FlexiPay® Lastschrift Zahlungen (gesichert) mit heidelpay',
            'embedIFrame'           => '',
            'attribute'             => [
                Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME => 'sepa_direct_debit_guaranteed.tpl',
            ],
        ],
        [
            'name'                  => self::PAYMENT_NAME_SOFORT,
            'description'           => 'Sofort (heidelpay)',
            'additionalDescription' => 'SOFORT Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
        [
            'name'                  => self::PAYMENT_NAME_WE_CHAT,
            'description'           => 'WeChat (heidelpay)',
            'additionalDescription' => 'WeChat Zahlungen mit heidelpay',
            'action'                => self::PROXY_FOR_REDIRECT_PAYMENTS,
        ],
    ];

    /** @var ModelManager */
    private $modelManager;

    /** @var DataPersister */
    private $dataPersister;

    /** @var PaymentInstaller */
    private $paymentInstaller;

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
                    'name'        => $paymentMethod['name'],
                    'embedIFrame' => '',
                ]);
            } else {
                $crudPaymentMethod = $this->paymentInstaller->createOrUpdate(self::PAYMENT_PLUGIN_NAME, $paymentMethod);
            }

            if (!empty($crudPaymentMethod) && array_key_exists('attribute', $paymentMethod)) {
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
