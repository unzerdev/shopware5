{extends file="parent:backend/_base/layout.tpl"}

{block name="content/main"}
    <h1>{s name="headline" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}</h1>
    {if $shops|@count > 0 }
        {foreach $shops as $shop}
            <a {if $shop->getId() == $shopId}class="active" {/if}href="{url controller="ApplePayCertificateManager" action="index" shopId=$shop->getId()}">{$shop->getName()}</a>
        {/foreach}
    {/if}
    <div>
        {if $paymentProcessingCertificateUpdated}
            <div>
                <p>{s name="saveCertificateSuccesful" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}</p>
            </div>
        {/if}
        <form
            method="post"
            enctype="multipart/form-data"
            action="{url controller="ApplePayCertificateManager" action="updateCertificates" __csrf_token=$csrfToken}"#
        >
            {if not $isDefaultShop}
                <label for="inheritCertificates">
                    {s name="inheritCertificatesLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                </label>
                <input type="checkbox" name="inheritCertificates">
            {/if}
            <label for="paymentProcessingCertificate">
                {s name="paymentProcessingCertificateLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
            </label>
            <input type="file" name="paymentProcessingCertificate">
            <label for="paymentProcessingCertificateKey">
                {s name="paymentProcessingCertificateKeyLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
            </label>
            <input type="file" name="paymentProcessingCertificateKey">
            <label for="merchantCertificate">
                {s name="merchantCertificateLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
            </label>
            <input type="file" name="merchantCertificate">
            <label for="merchantCertificateKey">
                {s name="merchantCertificateKeyLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
            </label>
            <input type="file" name="merchantCertificateKey">
            <input type="submit" value="{s name="saveCertificate" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}">
        </form>
    </div>
{/block}
