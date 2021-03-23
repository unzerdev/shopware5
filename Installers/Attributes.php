<?php

declare(strict_types=1);

namespace UnzerPayment\Installers;

use Shopware\Bundle\AttributeBundle\Service\ConfigurationStruct;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Model\ModelManager;

class Attributes implements InstallerInterface
{
    public const UNZER_PAYMENT_ATTRIBUTE_SHIPPING_DATA = 'unzer_payment_shipping_date';
    public const UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME = 'unzer_payment_payment_frame';

    private const ATTRIBUTES = [
        's_order_attributes' => [
            [
                'columnName' => self::UNZER_PAYMENT_ATTRIBUTE_SHIPPING_DATA,
                'type'       => TypeMapping::TYPE_DATETIME,
                'fieldData'  => [
                    'label'            => 'Versandmitteilung an Unzer',
                    'supportText'      => 'Gibt an wann die Versandbenachrichtigung an Unzer übertragen wurde.',
                    'displayInBackend' => true,
                    'custom'           => false,
                ],
            ],
       ],
        's_core_paymentmeans_attributes' => [
            [
                'columnName' => self::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME,
                'type'       => TypeMapping::TYPE_STRING,
                'fieldData'  => [
                    'label'            => 'Zahlungsfelder für den Checkout',
                    'supportText'      => '',
                    'displayInBackend' => false,
                    'custom'           => false,
                ],
            ],
        ],
    ];

    /** @var CrudService crudService */
    private $crudService;

    /** @var ModelManager modelManager */
    private $modelManager;

    public function __construct(CrudService $crudService, ModelManager $modelManager)
    {
        $this->crudService  = $crudService;
        $this->modelManager = $modelManager;
    }

    public function install(): void
    {
        foreach (self::ATTRIBUTES as $tableName => $attributes) {
            $attributesList = $this->crudService->getList($tableName);

            foreach ($attributes as $attribute) {
                if (!$this->attributeExists($attributesList, $attribute['columnName'])) {
                    $this->crudService->update(
                        $tableName,
                        $attribute['columnName'],
                        $attribute['type'],
                        $attribute['fieldData']
                    );
                }
            }
        }

        $this->modelManager->generateAttributeModels(
            array_keys(self::ATTRIBUTES)
        );
    }

    public function uninstall(): void
    {
        foreach (self::ATTRIBUTES as $tableName => $attributes) {
            foreach ($attributes as $attribute) {
                if ($this->crudService->get($tableName, $attribute['columnName']) !== null) {
                    $this->crudService->delete($tableName, $attribute['columnName']);
                }
            }
        }

        $this->modelManager->generateAttributeModels(
            array_keys(self::ATTRIBUTES)
        );
    }

    public function update(string $oldVersion, string $newVersion): void
    {
        //No updates yet
    }

    /**
     * @param ConfigurationStruct[] $list
     * @param string                $attributeName
     */
    private function attributeExists(array $list, $attributeName): bool
    {
        foreach ($list as $item) {
            if ($item->getColumnName() === $attributeName) {
                return true;
            }
        }

        return false;
    }
}
