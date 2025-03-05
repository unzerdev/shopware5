{extends file="parent:backend/_base/layout.tpl"}

{block name="content/navigation"}
    <ul class="nav nav-tabs">
        {if $shops|@count > 0 }
            {foreach $shops as $shop}
                <li {if $shop->getId() == $shopId}class="active" {/if}><a href="{url controller="ApplePayCertificateManager" action="index" shopId=$shop->getId()}">{$shop->getName()}</a></li>
            {/foreach}
        {/if}
    </ul>
{/block}

{block name="content/main"}
    <div class="page-header">
        <p>{s name="headline" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}</p>
        <p>{s name="information" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}</p>
    </div>
    <div class="row">
        <div class="col-12">

            {if $certificateCheck.merchantIdentificationValid}
                <div class="alert alert-info" role="alert">
                    {s name="infoTextExistingMerchants" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                </div>
            {/if}
            <div class="alert alert-info" role="alert">
                {s name="infoTextAllMerchants" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
            </div>

            {if $paymentProcessingCertificateUpdated}
                <div class="alert alert-success" role="alert">
                    {s name="saveCertificateSuccesful" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                </div>
            {/if}
            {if $errorMessage}
                <div class="alert alert-danger" role="alert">
                    {s name="errorOccured" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s} {$errorMessage}
                </div>
            {/if}
            <form
                    method="post"
                    enctype="multipart/form-data"
                    action="{url controller="ApplePayCertificateManager" action="updateCertificates" __csrf_token=$csrfToken}"
            >
                <input type="hidden" name="shopId" value="{$shopId}">
                {if not $isDefaultShop}
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="inheritPaymentProcessingCertificates" value="1" {if $certificateCheck.paymentProcessingInherited}checked="checked"{/if}> {s name="inheritCertificatesLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                        </label>
                    </div>
                {/if}
                {if $paymentProcessingCertificatePartialUpdate}
                    <div class="alert alert-danger" role="alert">
                        {s name="partialUpdatePaymentProcessingCertificate" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {/if}
                {if $paymentProcessingCertificateInvalid}
                    <div class="alert alert-danger" role="alert">
                        {s name="certificateInvalid" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {/if}
                <div class="form-group">
                    <label for="paymentProcessingCertificate">
                        {s name="paymentProcessingCertificateLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </label>
                    <input class="form-control" type="file" name="paymentProcessingCertificate" >
                </div>
                <div class="form-group">
                    <label for="paymentProcessingCertificateKey">
                        {s name="paymentProcessingCertificateKeyLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </label>
                    <input class="form-control" type="file" name="paymentProcessingCertificateKey">
                </div>
                {if $certificateCheck.paymentProcessingValid}
                    <div class="alert alert-success" role="alert">
                        {s name="paymentProcessingValid" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {else}
                    <div class="alert alert-danger" role="alert">
                        {s name="paymentProcessingMissing" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {/if}
                {if not $isDefaultShop}
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="inheritMerchantCertificates" value="1" {if $certificateCheck.merchantIdentificationInherited}checked="checked"{/if}>
                            {s name="inheritCertificatesLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                        </label>
                    </div>
                {/if}

                {if $merchantCertificatePartialUpdate}
                    <div class="alert alert-danger" role="alert">
                        {s name="partialUpdateMerchantCertificate" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {/if}
                {if $merchantCertificateInvalid}
                    <div class="alert alert-danger" role="alert">
                        {s name="certificateInvalid" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {/if}
                <div class="form-group">
                    <label for="merchantCertificate">
                        {s name="merchantCertificateLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </label>
                    <input class="form-control" type="file" name="merchantCertificate">
                </div>
                <div class="form-group">
                    <label for="merchantCertificateKey">
                        {s name="merchantCertificateKeyLabel" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </label>
                    <input class="form-control" type="file" name="merchantCertificateKey">
                </div>
                {if $certificateCheck.merchantIdentificationValid and not $certificateCheck.merchantIdentificationExpired}
                    <div class="alert alert-success" role="alert">
                        {s name="merchantIdentificationValid" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {elseif $certificateCheck.merchantIdentificationValid}
                    <div class="alert alert-warning" role="alert">
                        {s name="merchantIdentificationExpired" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {else}
                    <div class="alert alert-danger" role="alert">
                        {s name="merchantIdentificationMissing" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}
                    </div>
                {/if}
                <div class="form-group">
                    <input class="btn btn-default" type="submit" value="{s name="saveCertificate" namespace="backend/unzer_payment/apple_pay_certificate_manager"}{/s}">
                </div>
            </form>
        </div>
    </div>
{/block}
