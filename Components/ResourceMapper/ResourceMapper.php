<?php

declare(strict_types=1);

namespace UnzerPayment\Components\ResourceMapper;

use UnzerSDK\Resources\AbstractUnzerResource;

class ResourceMapper implements ResourceMapperInterface
{
    public function mapMissingFields(AbstractUnzerResource $leadingResource, AbstractUnzerResource $fallbackResource): AbstractUnzerResource
    {
        $exposedData = array_merge($leadingResource->expose(), $fallbackResource->expose());

        foreach ($exposedData as $fieldName => $fieldValues) {
            if (is_array($fieldValues)) {
                $leadingResource = $this->handleArray($leadingResource, $fallbackResource, $fieldName, $fieldValues);

                continue;
            }

            $leadingResource = $this->setIfEmpty($leadingResource, $fallbackResource, $fieldName);
        }

        return $leadingResource;
    }

    protected function handleArray(AbstractUnzerResource $leadingResource, AbstractUnzerResource $hydratedResource, string $fieldName, array $fieldValues): AbstractUnzerResource
    {
        $setterMethod = 'set' . ucfirst($fieldName);
        $getterMethod = 'get' . ucfirst($fieldName);

        if (!method_exists($leadingResource, $setterMethod) || !method_exists($hydratedResource, $getterMethod)) {
            return $leadingResource;
        }

        $leadingValue = $leadingResource->$getterMethod();

        if (empty($leadingValue)) {
            return $this->setIfEmpty($leadingResource, $hydratedResource, $fieldName);
        }

        foreach ($fieldValues as $key => $value) {
            if (is_array($value)) {
                $this->handleArray($leadingResource, $hydratedResource, $fieldName, $value);

                continue;
            }

            $leadingValue = $this->setIfEmpty($leadingValue, $hydratedResource->$getterMethod(), $key);
        }

        $leadingResource->$setterMethod($leadingValue);

        return $leadingResource;
    }

    protected function setIfEmpty(AbstractUnzerResource $leadingResource, AbstractUnzerResource $hydratedResource, string $fieldName): AbstractUnzerResource
    {
        $setterMethod = 'set' . ucfirst($fieldName);
        $getterMethod = 'get' . ucfirst($fieldName);

        if (!empty($leadingResource->$getterMethod())) {
            return $leadingResource;
        }

        if (method_exists($leadingResource, $setterMethod) && method_exists($hydratedResource, $getterMethod)) {
            $leadingResource->$setterMethod($hydratedResource->$getterMethod());
        }

        return $leadingResource;
    }
}
