<?php

namespace HeidelPayment\Installers;

use Shopware\Bundle\AttributeBundle\Service\ConfigurationStruct;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Model\ModelManager;

class Attributes implements InstallerInterface
{
    const ATTRIBUTES = [
        's_order_attributes' => [
            [
                'columnName' => 'heidelpay_shipping_date',
                'type'       => TypeMapping::TYPE_DATETIME,
                'fieldData'  => [
                    'label'            => 'Versandmitteilung an Heidelpay',
                    'supportText'      => 'Gibt an wann die Versandbenachrichtigung an Heidelpay übertragen wurde.',
                    'displayInBackend' => true,
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

    public function install()
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

    public function uninstall()
    {
        foreach (self::ATTRIBUTES as $tableName => $attributes) {
            foreach ($attributes as $attribute) {
                if (null !== $this->crudService->get($tableName, $attribute['columnName'])) {
                    $this->crudService->delete($tableName, $attribute['columnName']);
                }
            }
        }

        $this->modelManager->generateAttributeModels(
            array_keys(self::ATTRIBUTES)
        );
    }

    public function update(string $oldVersion, string $newVersion)
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
